# Integration Controller

Uma biblioteca PHP flexível para realizar requisições HTTP e integrar com APIs REST de forma simples e eficiente.

## 📋 Requisitos

- PHP >= 7.4
- Extensão cURL habilitada

## 📦 Instalação

### Via Composer

```bash
composer require edgvi10/integration-controller
```

### Instalação Manual

1. Clone o repositório:

```bash
git clone https://github.com/edgvi10/PkgIntegrationController.git
```

2. Inclua o autoloader do Composer no seu projeto:

```php
require_once 'vendor/autoload.php';
```

## 🚀 Uso Básico

### Inicialização

```php
use EDGVI10\Controllers\IntegrationController;

// Inicialização simples
$client = new IntegrationController();

// Inicialização com configurações
$client = new IntegrationController([
    'baseURL' => 'https://api.example.com',
    'headers' => ['Accept' => 'application/json'],
    'timeout' => 30,
    'verifySSL' => true,
    'userAgent' => 'MyApp/1.0'
]);
```

## 📖 Exemplos de Uso

### 1. Requisição GET Simples

```php
$client = new IntegrationController([
    'baseURL' => 'https://api.example.com'
]);

// Fazer uma requisição GET
if ($client->get('/users', ['page' => 1, 'limit' => 10])) {
    $data = $client->jsonResponse();
    print_r($data);
} else {
    echo "Erro: " . $client->lastError;
}
```

### 2. Requisição POST com Dados JSON

```php
$client = new IntegrationController([
    'baseURL' => 'https://api.example.com',
    'headers' => ['Content-Type: application/json']
]);

$userData = [
    'name' => 'João Silva',
    'email' => 'joao@example.com',
    'age' => 30
];

if ($client->post('/users', $userData)) {
    echo "Usuário criado com sucesso!";
    echo "Status: " . $client->responseStatusCode;
} else {
    echo "Erro: " . $client->lastError;
}
```

### 3. Autenticação Bearer Token

```php
$client = new IntegrationController([
    'baseURL' => 'https://api.example.com'
]);

$client->setAuthentication('bearer', 'seu_token_aqui');

if ($client->get('/protected-resource')) {
    $data = $client->jsonResponse();
    print_r($data);
}
```

### 4. Autenticação Basic

```php
$client = new IntegrationController([
    'baseURL' => 'https://api.example.com'
]);

$client->setAuthentication('basic', [
    'username' => 'usuario',
    'password' => 'senha'
]);

$client->get('/secure-endpoint');
```

### 5. Autenticação API Key

```php
$client = new IntegrationController([
    'baseURL' => 'https://api.example.com'
]);

$client->setAuthentication('api_key', [
    'header' => 'X-API-Key',
    'key' => 'sua_api_key_aqui'
]);

$client->get('/data');
```

### 6. Requisição PUT

```php
$client = new IntegrationController([
    'baseURL' => 'https://api.example.com'
]);

$updateData = [
    'name' => 'João Silva Atualizado',
    'email' => 'joao.novo@example.com'
];

if ($client->put('/users/123', $updateData)) {
    echo "Usuário atualizado com sucesso!";
}
```

### 7. Requisição PATCH

```php
$client = new IntegrationController([
    'baseURL' => 'https://api.example.com'
]);

$partialUpdate = [
    'status' => 'active'
];

$client->patch('/users/123', $partialUpdate);
```

### 8. Requisição DELETE

```php
$client = new IntegrationController([
    'baseURL' => 'https://api.example.com'
]);

if ($client->delete('/users/123')) {
    echo "Usuário excluído com sucesso!";
}
```

### 9. Configuração Avançada de Requisição

```php
$client = new IntegrationController([
    'baseURL' => 'https://api.example.com'
]);

$client->setMethod('POST')
       ->setEndpoint('/users')
       ->setHeaders(['Content-Type: application/json'])
       ->setData(['name' => 'Maria', 'email' => 'maria@example.com'])
       ->send();

if ($client->responseStatusCode === 201) {
    echo "Recurso criado!";
}
```

### 10. Adicionando Campos Dinamicamente

```php
$client = new IntegrationController([
    'baseURL' => 'https://api.example.com'
]);

$client->setMethod('POST')
       ->setEndpoint('/products')
       ->useJson(false)
       ->addHeader('Content-Type', 'multipart/form-data')
       ->addField('name', 'Produto A')
       ->addField('price', 99.90)
       ->addField('category', 'Eletrônicos')
       ->addFile('image', fopen('/path/to/image.jpg', 'r'))
       ->addFile('image2', fopen('/path/to/image2.jpg', 'r'))
       ->send();
```

### 11. Requisição com Retry (Tentativas Automáticas)

```php
$client = new IntegrationController([
    'baseURL' => 'https://api.example.com'
]);

$client->setMethod('GET')
       ->setEndpoint('/unstable-endpoint');

// Tenta até 5 vezes com 2 segundos de intervalo
if ($client->sendWithRetry(5, 2)) {
    echo "Requisição bem-sucedida após tentativas!";
} else {
    echo "Falhou após todas as tentativas";
}
```

### 12. Requisição Assíncrona

```php
$client = new IntegrationController([
    'baseURL' => 'https://api.example.com'
]);

$client->setMethod('GET')
       ->setEndpoint('/data');

$client->sendAsync(
    function($body, $statusCode, $headers) {
        echo "Sucesso! Status: $statusCode";
        print_r(json_decode($body, true));
    },
    function($error) {
        echo "Erro: $error";
    }
);
```

### 13. Logging Habilitado

```php
$client = new IntegrationController([
    'baseURL' => 'https://api.example.com'
]);

$client->enableLogging = true;

$client->get('/users');

// Visualizar logs
foreach ($client->logs as $log) {
    echo "[{$log['timestamp']}] {$log['message']}\n";
    print_r($log['data']);
}

// Limpar logs
$client->clearLogs();
```

### 14. Trabalhando com Respostas

```php
$client = new IntegrationController([
    'baseURL' => 'https://api.example.com'
]);

if ($client->get('/users/123')) {
    // Status code
    echo "Status: " . $client->responseStatusCode . "\n";
    
    // Body bruto
    echo "Body: " . $client->responseBody . "\n";
    
    // JSON decodificado
    $data = $client->jsonResponse();
    print_r($data);
    
    // Headers
    print_r($client->responseHeaders);
    
    // Endpoint usado
    echo "Endpoint: " . $client->responseEndpoint;
}
```

### 15. Múltiplas Requisições com Mesma Instância

```php
$client = new IntegrationController([
    'baseURL' => 'https://api.example.com'
]);

// Primeira requisição
$client->get('/users');
$users = $client->jsonResponse();

// Limpar estado antes da próxima requisição
$client->clear();

// Segunda requisição
$client->get('/products');
$products = $client->jsonResponse();
```

### 16. Configurando SSL e Timeout

```php
$client = new IntegrationController([
    'baseURL' => 'https://api.example.com',
    'timeout' => 60,        // 60 segundos
    'verifySSL' => false,   // Desabilitar verificação SSL (não recomendado em produção)
    'userAgent' => 'MyBot/2.0'
]);

$client->get('/slow-endpoint');
```

### 17. Enviando Array de Valores em Parâmetros

```php
$client = new IntegrationController([
    'baseURL' => 'https://api.example.com'
]);

// Arrays são automaticamente convertidos em strings separadas por vírgula
$client->get('/search', [
    'categories' => ['tech', 'science', 'sports'],
    'status' => ['active', 'pending']
]);
// Resultado: ?categories=tech,science,sports&status=active,pending
```

### 18. Usando os atalhos para Verbos HTTP

```php
$client = new IntegrationController([
    'baseURL' => 'https://api.example.com',
]);

$client->setAuthentication('bearer', 'seu_token_aqui');
$client->setUseJson(true);

// Criando um item com POST enviando dados e parâmetros adicionais
$create = $client->post('/items', ['name' => 'Item 1'], ['verbose' => true]);
$createdItem = $client->jsonResponse();

$list = $client->get('/items', ['page' => 1, 'limit' => 20]);
$items = $client->jsonResponse();

$update = $client->put('/items/1', ['name' => 'Item 1 Updated'], ['notify' => 'yes']);
$updatedItem = $client->jsonResponse();

$partialUpdate = $client->patch('/items/1', ['status' => 'active']);
$patchedItem = $client->jsonResponse();

$delete = $client->delete('/items/1', ['hard_delete' => 'true']);
$deleteSuccess = $client->responseStatusCode === 204;

```

## 🔧 Métodos Disponíveis

### Métodos de Configuração

- `setBaseURL($url)` - Define a URL base
- `setUseJson($bool)` - Define se usa JSON automaticamente
- `setAuthentication($type, $auth)` - Configura autenticação
- `setMethod($method)` - Define o método HTTP
- `setEndpoint($endpoint)` - Define o endpoint
- `setHeaders($values)` - Define headers personalizados
- `addHeader($key, $value)` - Adiciona um header personalizado
- `rmHeader($key, $conditionalValue = null)` - Remove um header personalizado, opcionalmente filtrando pelo valor
- `setParams($params)` - Define parâmetros de query string
- `setData($data)` - Define dados do body
- `addField($key, $value)` - Adiciona um campo ao body
- `addFile($key, $fileResource)` - Adiciona um arquivo ao body

### Métodos de Requisição

- `send()` - Envia a requisição configurada
- `sendAsync($callbackSuccess, $callbackError)` - Envia requisição assíncrona
- `sendWithRetry($maxRetries, $delaySeconds)` - Envia com tentativas automáticas
- `get($endpoint, $params)` - Requisição GET
- `post($endpoint, $data, $params)` - Requisição POST
- `put($endpoint, $data, $params)` - Requisição PUT
- `patch($endpoint, $data, $params)` - Requisição PATCH
- `delete($endpoint, $params)` - Requisição DELETE

### Métodos de Resposta

- `jsonResponse()` - Retorna resposta decodificada como JSON
- `responseStatusCode` - Código de status HTTP
- `responseBody` - Body da resposta
- `responseHeaders` - Headers da resposta

### Métodos de Limpeza

- `clear()` - Limpa tudo
- `clearRequest()` - Limpa dados da requisição
- `clearResponse()` - Limpa dados da resposta
- `clearLogs()` - Limpa logs
- `clearLastError()` - Limpa último erro

### Métodos de Log

- `log($message, $data)` - Registra uma mensagem de log

## ⚙️ Propriedades Configuráveis

| Propriedade | Tipo | Padrão | Descrição |
|------------|------|--------|-----------|
| `baseURL` | string | null | URL base da API |
| `headers` | array | [] | Headers padrão |
| `authentication` | array | null | Configurações de autenticação |
| `timeout` | int | 30 | Timeout em segundos |
| `verifySSL` | bool | true | Verificar certificado SSL |
| `userAgent` | string | null | User Agent personalizado |
| `useJson` | bool | false | Usar JSON automaticamente |
| `debug` | bool | false | Modo debug |
| `enableLogging` | bool | false | Habilitar logging |
| `logs` | array | [] | Array de logs |

## 🔐 Tipos de Autenticação Suportados

1. **Bearer Token**: Para APIs que usam tokens JWT ou OAuth
2. **Basic Auth**: Para autenticação básica HTTP
3. **API Key**: Para APIs que usam chaves em headers personalizados

## 🐛 Tratamento de Erros

```php
$client = new IntegrationController([
    'baseURL' => 'https://api.example.com'
]);

if (!$client->get('/endpoint')) {
    // Capturar erro
    echo "Erro: " . $client->lastError;
    echo "Status Code: " . $client->responseStatusCode;
}
```

## ✅ Pendencias

- [ ] Implementar lógica de retry com backoff exponencial
- [ ] Adicionar suporte para autenticação OAuth2
- [ ] Adicionar suporte para opções personalizadas do cURL
- [ ] Tratamento de tipos de conteúdo baseado nos dados da requisição
  - [x] Adicionar suporte básico para content type application/json
  - [ ] Adicionar suporte básico para content type application/xml
  - [ ] Adicionar suporte básico para requisições multipart/form-data
- [ ] Melhorar tratamento de erros e logging
- [ ] Adicionar suporte para requisições assíncronas usando curl_multi ou outras bibliotecas

## 📝 Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## 👤 Autor

**Eduardo Vieira**

- Email: <edgvi10@gmail.com>
- GitHub: [@edgvi10](https://github.com/edgvi10)
- LinkedIn: [Eduardo Vieira](https://www.linkedin.com/in/edgvi10/)

## 🤝 Contribuindo

Contribuições, issues e solicitações de recursos são bem-vindas!

## ⭐ Suporte

Se este projeto foi útil para você, considere dar uma estrela no GitHub!
