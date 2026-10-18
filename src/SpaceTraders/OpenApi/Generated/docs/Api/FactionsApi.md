# App\SpaceTraders\OpenApi\Generated\FactionsApi

All URIs are relative to https://api.spacetraders.io/v2, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**getFaction()**](FactionsApi.md#getFaction) | **GET** /factions/{factionSymbol} | Get Faction |
| [**getFactions()**](FactionsApi.md#getFactions) | **GET** /factions | List Factions |


## `getFaction()`

```php
getFaction($factionSymbol): \App\SpaceTraders\OpenApi\Generated\Model\GetFaction200Response
```

Get Faction

View the details of a faction.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (JWT) authorization: AccountToken
$config = App\SpaceTraders\OpenApi\Generated\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');

// Configure Bearer (JWT) authorization: AgentToken
$config = App\SpaceTraders\OpenApi\Generated\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new App\SpaceTraders\OpenApi\Generated\Api\FactionsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$factionSymbol = COSMIC; // string | The faction symbol

try {
    $result = $apiInstance->getFaction($factionSymbol);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FactionsApi->getFaction: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **factionSymbol** | **string**| The faction symbol | |

### Return type

[**\App\SpaceTraders\OpenApi\Generated\Model\GetFaction200Response**](../Model/GetFaction200Response.md)

### Authorization

[AccountToken](../../README.md#AccountToken), [AgentToken](../../README.md#AgentToken)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getFactions()`

```php
getFactions($page, $limit): \App\SpaceTraders\OpenApi\Generated\Model\GetFactions200Response
```

List Factions

Return a paginated list of all the factions in the game.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (JWT) authorization: AgentToken
$config = App\SpaceTraders\OpenApi\Generated\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new App\SpaceTraders\OpenApi\Generated\Api\FactionsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$page = 1; // int | What entry offset to request
$limit = 10; // int | How many entries to return per page

try {
    $result = $apiInstance->getFactions($page, $limit);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FactionsApi->getFactions: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **page** | **int**| What entry offset to request | [optional] [default to 1] |
| **limit** | **int**| How many entries to return per page | [optional] [default to 10] |

### Return type

[**\App\SpaceTraders\OpenApi\Generated\Model\GetFactions200Response**](../Model/GetFactions200Response.md)

### Authorization

[AgentToken](../../README.md#AgentToken)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
