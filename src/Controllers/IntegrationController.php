<?php

namespace EDGVI10\Controllers;

/* 
    TODO:
    - Implement retry logic with exponential backoff
    - Add support for OAuth2 authentication
    - Add support for custom cURL options
    - Content type handling based on request data
        - Add basic support for application/json content type: done
        - Add basic support for application/xml content type
        - Add basic support for multipart/form-data requests
    - Improve error handling and logging
    - Add support for async requests using curl_multi or other libraries
*/

class IntegrationController
{
    public $baseURL;
    public $headers = [];
    public $authentication = null;

    public $requestMethod = null;
    public $requestEndpoint = null;
    public $responseEndpoint = null;
    public $requestParams = null;
    public $requestData = null;

    public $requestHeaders = null;

    public $lastError = null;

    public $responseStatusCode = null;
    public $responseBody = null;
    public $responseHeaders = null;

    public $timeout = 30;
    public $verifySSL = true;
    public $userAgent = null;

    public $useJson = false;
    public $debug = false;
    public $logFile = null;
    public $enableLogging = false;
    public $logs = [];

    public function __construct($config = [])
    {
        if (isset($config["baseURL"])) $this->setBaseURL($config["baseURL"]);
        if (isset($config["headers"])) $this->setHeaders($config["headers"]);

        if (isset($config["authentication"])):
            if (!isset($config["authentication"]["type"]) || !isset($config["authentication"]["credentials"]))
                throw new \InvalidArgumentException("Authentication configuration requires \"type\" and \"credentials\".");

            $this->setAuthentication($config["authentication"]["type"], $config["authentication"]["credentials"]);
        endif;

        if (isset($config["timeout"])) $this->timeout = $config["timeout"];

        if (isset($config["verifySSL"])) $this->verifySSL = $config["verifySSL"];
        if (isset($config["userAgent"])) $this->userAgent = $config["userAgent"];
        if (isset($config["useJson"])):
            $this->useJson = $config["useJson"];
            $this->addHeader("Accept", "application/json");
            $this->addHeader("Content-Type", "application/json");
        endif;
    }

    public function log($message, $data = null)
    {
        if ($this->enableLogging)
            $this->logs[] = [
                "timestamp" => date("Y-m-d H:i:s"),
                "message" => $message,
                "data" => $data
            ];

        return $this;
    }

    public function setAuthentication($type, $auth)
    {
        $this->authentication = [
            "type" => $type,
            "credentials" => $auth
        ];

        return $this;
    }

    public function setRequest($method, $endpoint, $params = null, $data = null, $headers = null)
    {
        $this->requestMethod = strtoupper($method);
        $this->requestEndpoint = $endpoint;
        if ($params !== null) $this->setParams($params);
        if ($data !== null) $this->setData($data);
        if ($headers !== null) $this->setHeaders($headers);

        return $this;
    }

    public function setBaseURL($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL))
            throw new \InvalidArgumentException("Invalid URL: $url");

        $this->baseURL = rtrim($url, "/");
    }

    public function setMethod($method)
    {
        $this->requestMethod = strtoupper($method);

        return $this;
    }

    public function setEndpoint($endpoint)
    {
        $this->requestEndpoint = $this->baseURL . "/" . ltrim($endpoint, "/");

        return $this;
    }

    public function setHeaders($values)
    {
        $this->headers = $values;

        return $this;
    }

    public function addHeader($key, $value = null)
    {
        if (!is_array($this->headers)) $this->headers = [];
        if (strpos($key, ":") !== false) list($key, $value) = explode(":", $key, 2);
        $this->headers[] = "$key: $value";

        return $this;
    }

    public function rmHeader($key, $conditionalValue = null)
    {
        if (!is_array($this->headers)) return $this;

        foreach ($this->headers as $index => $header) :
            list($headerKey, $headerValue) = explode(":", $header, 2);
            if (
                strtolower(trim($headerKey)) === strtolower(trim($key)) &&
                ($conditionalValue === null || trim($headerValue) === trim($conditionalValue))
            )
                unset($this->headers[$index]);
        endforeach;

        return $this;
    }

    public function setParams($params = [])
    {
        $this->requestParams = [];
        foreach ($params as $key => $value) :
            if (is_array($value)) $params[$key] = implode(",", $value);

            $this->requestParams[$key] = $value;
        endforeach;

        return $this;
    }

    public function setData($data = [])
    {
        $this->requestData = $data;

        return $this;
    }

    public function addField($key, $value)
    {
        if (!is_array($this->requestData)) $this->requestData = [];
        $this->requestData[$key] = $value;

        return $this;
    }

    public function addFile($key, $filePath)
    {
        if ($this->useJson)
            throw new \InvalidArgumentException("File upload is not supported with JSON. Set useJson to false.");

        if (!is_array($this->requestData)) $this->requestData = [];
        if (!file_exists($filePath))
            throw new \InvalidArgumentException("File not found: $filePath");


        if (function_exists("curl_file_create"))
            $this->requestData[$key] = curl_file_create($filePath);
        else
            $this->requestData[$key] = "@" . realpath($filePath);

        return $this;
    }

    public function setUseJson($useJson = true)
    {
        $this->useJson = $useJson;

        if ($useJson) :
            $this->rmHeader("Content-Type");
            $this->rmHeader("Accept");

            $this->addHeader("Content-Type", "application/json");
            $this->addHeader("Accept", "application/json");
        else:
            $this->rmHeader("Content-Type", "application/json");
            $this->rmHeader("Accept", "application/json");
        endif;

        return $this;
    }

    public function send()
    {
        $this->clearResponse();
        $this->clearLastError();

        $endpoint_url = $this->requestEndpoint;
        $this->responseEndpoint = $endpoint_url;

        if ($this->requestParams && in_array($this->requestMethod, ["GET", "DELETE"]))
            $endpoint_url .= "?" . http_build_query($this->requestParams);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint_url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->requestMethod);

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifySSL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->verifySSL ? 2 : 0);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);

        if ($this->userAgent)
            curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);

        $headers = array_merge($this->headers, $this->requestHeaders ?? []);

        if ($this->authentication) :
            switch (strtolower($this->authentication["type"])):
                case "bearer":
                    $headers[] = "Authorization: Bearer " . $this->authentication["credentials"];
                    break;
                case "basic":
                    if (is_array($this->authentication["credentials"])) :
                        curl_setopt(
                            $ch,
                            CURLOPT_USERPWD,
                            $this->authentication["credentials"]["username"] . ":" .
                                $this->authentication["credentials"]["password"]
                        );
                    endif;
                    break;
                case "api_key":
                    if (is_array($this->authentication["credentials"])) :
                        $headers[] = $this->authentication["credentials"]["header"] . ": " .
                            $this->authentication["credentials"]["key"];
                    endif;
                    break;
            endswitch;
        endif;

        if (in_array($this->requestMethod, ["POST", "PUT", "PATCH"]) && $this->requestData):
            if ($this->useJson) :
                if (!array_key_exists("content-type", array_change_key_case(array_flip($headers), CASE_LOWER)))
                    $headers[] = "Content-Type: application/json";

                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->requestData));
            else :
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->requestData));
            endif;
        endif;

        if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $responseHeaders = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$responseHeaders) {
            $len = strlen($header);
            $header = explode(":", $header, 2);
            if (count($header) < 2) return $len;

            $responseHeaders[strtolower(trim($header[0]))] = trim($header[1]);
            return $len;
        });

        $response = curl_exec($ch);

        if ($response === false) :
            $this->lastError = curl_error($ch);
            return false;
        endif;

        $this->responseStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->responseHeaders = $responseHeaders;
        $this->responseBody = ($this->useJson) ? json_decode($response, true) : $response;

        $this->clearRequest();

        return $this->responseStatusCode >= 200 && $this->responseStatusCode < 300;
    }

    public function sendAsync($callbackSuccess, $callbackError)
    {
        if ($this->send() && $this->responseStatusCode >= 200 && $this->responseStatusCode < 400)
            return call_user_func($callbackSuccess, [
                "status" => $this->responseStatusCode,
                "headers" => $this->responseHeaders,
                "body" => $this->responseBody
            ]);
        elseif ($this->responseStatusCode >= 400 || $this->lastError)
            return call_user_func($callbackError, [
                "status" => $this->responseStatusCode,
                "headers" => $this->responseHeaders,
                "error" => $this->lastError,
                "body" => $this->responseBody
            ]);

        return null;
    }

    public function sendWithRetry($maxRetries = 3, $delaySeconds = 1)
    {
        $attempts = 0;
        while ($attempts < $maxRetries) :
            if ($this->send())
                return true;

            $attempts++;
            if ($attempts < $maxRetries)
                sleep($delaySeconds);
        endwhile;

        return false;
    }

    public function jsonResponse()
    {
        if ($this->useJson)
            return $this->responseBody;
        else if ($this->responseBody)
            return json_decode($this->responseBody, true);

        return null;
    }

    public function clearRequest()
    {
        $this->requestMethod = null;
        $this->requestEndpoint = null;
        $this->requestParams = null;
        $this->requestData = null;
        $this->requestHeaders = null;

        return $this;
    }

    public function clearResponse()
    {
        $this->responseStatusCode = null;
        $this->responseBody = null;
        $this->responseHeaders = null;
        $this->lastError = null;

        return $this;
    }

    public function clearLogs()
    {
        $this->logs = [];
        return $this;
    }

    public function clearLastError()
    {
        $this->lastError = null;
        return $this;
    }

    public function clear()
    {
        $this->clearRequest();
        $this->clearResponse();
        $this->clearLastError();

        return $this;
    }

    // HTTP Verb Shortcuts
    public function get($endpoint, $params = [])
    {
        $this->setParams($params);
        $this->setMethod("GET");
        $this->setEndpoint($endpoint);

        return $this->send();
    }

    public function post($endpoint, $data = [], $params = [])
    {
        $this->clear();

        $this->setParams($params);
        $this->setData($data);
        $this->setMethod("POST");
        $this->setEndpoint($endpoint);

        return $this->send();
    }

    public function put($endpoint, $data = [], $params = [])
    {
        $this->clear();

        $this->setParams($params);
        $this->setData($data);
        $this->setMethod("PUT");
        $this->setEndpoint($endpoint);

        return $this->send();
    }

    public function patch($endpoint, $data = [], $params = [])
    {
        $this->clear();

        $this->setParams($params);
        $this->setData($data);
        $this->setMethod("PATCH");
        $this->setEndpoint($endpoint);

        return $this->send();
    }

    public function delete($endpoint, $params = [])
    {
        $this->setParams($params);
        $this->setMethod("DELETE");
        $this->setEndpoint($endpoint);

        return $this->send();
    }
}
