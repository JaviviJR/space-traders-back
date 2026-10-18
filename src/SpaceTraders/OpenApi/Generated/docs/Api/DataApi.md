# App\SpaceTraders\OpenApi\Generated\DataApi

All URIs are relative to https://api.spacetraders.io/v2, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**getSupplyChain()**](DataApi.md#getSupplyChain) | **GET** /market/supply-chain | Get Supply Chain |


## `getSupplyChain()`

```php
getSupplyChain(): \App\SpaceTraders\OpenApi\Generated\Model\GetSupplyChain200Response
```

Get Supply Chain

Describes which import and exports map to each other.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (JWT) authorization: AgentToken
$config = App\SpaceTraders\OpenApi\Generated\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new App\SpaceTraders\OpenApi\Generated\Api\DataApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->getSupplyChain();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling DataApi->getSupplyChain: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\App\SpaceTraders\OpenApi\Generated\Model\GetSupplyChain200Response**](../Model/GetSupplyChain200Response.md)

### Authorization

[AgentToken](../../README.md#AgentToken)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
