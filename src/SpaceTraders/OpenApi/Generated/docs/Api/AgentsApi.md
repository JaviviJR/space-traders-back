# App\SpaceTraders\OpenApi\Generated\AgentsApi

All URIs are relative to https://api.spacetraders.io/v2, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**getAgent()**](AgentsApi.md#getAgent) | **GET** /agents/{agentSymbol} | Get Public Agent |
| [**getAgents()**](AgentsApi.md#getAgents) | **GET** /agents | List Agents |
| [**getMyAgent()**](AgentsApi.md#getMyAgent) | **GET** /my/agent | Get Agent |


## `getAgent()`

```php
getAgent($agentSymbol): \App\SpaceTraders\OpenApi\Generated\Model\GetMyAgent200Response
```

Get Public Agent

Fetch agent details.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (JWT) authorization: AgentToken
$config = App\SpaceTraders\OpenApi\Generated\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new App\SpaceTraders\OpenApi\Generated\Api\AgentsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$agentSymbol = 'FEBA66'; // string | The agent symbol

try {
    $result = $apiInstance->getAgent($agentSymbol);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AgentsApi->getAgent: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **agentSymbol** | **string**| The agent symbol | [default to &#39;FEBA66&#39;] |

### Return type

[**\App\SpaceTraders\OpenApi\Generated\Model\GetMyAgent200Response**](../Model/GetMyAgent200Response.md)

### Authorization

[AgentToken](../../README.md#AgentToken)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getAgents()`

```php
getAgents($page, $limit): \App\SpaceTraders\OpenApi\Generated\Model\GetAgents200Response
```

List Agents

Fetch agents details.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (JWT) authorization: AgentToken
$config = App\SpaceTraders\OpenApi\Generated\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new App\SpaceTraders\OpenApi\Generated\Api\AgentsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$page = 1; // int | What entry offset to request
$limit = 10; // int | How many entries to return per page

try {
    $result = $apiInstance->getAgents($page, $limit);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AgentsApi->getAgents: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **page** | **int**| What entry offset to request | [optional] [default to 1] |
| **limit** | **int**| How many entries to return per page | [optional] [default to 10] |

### Return type

[**\App\SpaceTraders\OpenApi\Generated\Model\GetAgents200Response**](../Model/GetAgents200Response.md)

### Authorization

[AgentToken](../../README.md#AgentToken)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getMyAgent()`

```php
getMyAgent(): \App\SpaceTraders\OpenApi\Generated\Model\GetMyAgent200Response
```

Get Agent

Fetch your agent's details.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (JWT) authorization: AgentToken
$config = App\SpaceTraders\OpenApi\Generated\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new App\SpaceTraders\OpenApi\Generated\Api\AgentsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->getMyAgent();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AgentsApi->getMyAgent: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\App\SpaceTraders\OpenApi\Generated\Model\GetMyAgent200Response**](../Model/GetMyAgent200Response.md)

### Authorization

[AgentToken](../../README.md#AgentToken)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
