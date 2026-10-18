# App\SpaceTraders\OpenApi\Generated\ContractsApi

All URIs are relative to https://api.spacetraders.io/v2, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**acceptContract()**](ContractsApi.md#acceptContract) | **POST** /my/contracts/{contractId}/accept | Accept Contract |
| [**deliverContract()**](ContractsApi.md#deliverContract) | **POST** /my/contracts/{contractId}/deliver | Deliver Cargo to Contract |
| [**fulfillContract()**](ContractsApi.md#fulfillContract) | **POST** /my/contracts/{contractId}/fulfill | Fulfill Contract |
| [**getContract()**](ContractsApi.md#getContract) | **GET** /my/contracts/{contractId} | Get Contract |
| [**getContracts()**](ContractsApi.md#getContracts) | **GET** /my/contracts | List Contracts |


## `acceptContract()`

```php
acceptContract($contractId): \App\SpaceTraders\OpenApi\Generated\Model\AcceptContract200Response
```

Accept Contract

Accept a contract by ID.   You can only accept contracts that were offered to you, were not accepted yet, and whose deadlines has not passed yet.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (JWT) authorization: AgentToken
$config = App\SpaceTraders\OpenApi\Generated\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new App\SpaceTraders\OpenApi\Generated\Api\ContractsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$contractId = 'contractId_example'; // string | The contract ID to accept.

try {
    $result = $apiInstance->acceptContract($contractId);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ContractsApi->acceptContract: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **contractId** | **string**| The contract ID to accept. | |

### Return type

[**\App\SpaceTraders\OpenApi\Generated\Model\AcceptContract200Response**](../Model/AcceptContract200Response.md)

### Authorization

[AgentToken](../../README.md#AgentToken)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `deliverContract()`

```php
deliverContract($contractId, $deliverContractRequest): \App\SpaceTraders\OpenApi\Generated\Model\DeliverContract200Response
```

Deliver Cargo to Contract

Deliver cargo to a contract.  In order to use this API, a ship must be at the delivery location (denoted in the delivery terms as `destinationSymbol` of a contract) and must have a number of units of a good required by this contract in its cargo.  Cargo that was delivered will be removed from the ship's cargo.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (JWT) authorization: AgentToken
$config = App\SpaceTraders\OpenApi\Generated\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new App\SpaceTraders\OpenApi\Generated\Api\ContractsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$contractId = 'contractId_example'; // string | The ID of the contract.
$deliverContractRequest = new \App\SpaceTraders\OpenApi\Generated\Model\DeliverContractRequest(); // \App\SpaceTraders\OpenApi\Generated\Model\DeliverContractRequest | 

try {
    $result = $apiInstance->deliverContract($contractId, $deliverContractRequest);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ContractsApi->deliverContract: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **contractId** | **string**| The ID of the contract. | |
| **deliverContractRequest** | [**\App\SpaceTraders\OpenApi\Generated\Model\DeliverContractRequest**](../Model/DeliverContractRequest.md)|  | [optional] |

### Return type

[**\App\SpaceTraders\OpenApi\Generated\Model\DeliverContract200Response**](../Model/DeliverContract200Response.md)

### Authorization

[AgentToken](../../README.md#AgentToken)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `fulfillContract()`

```php
fulfillContract($contractId): \App\SpaceTraders\OpenApi\Generated\Model\FulfillContract200Response
```

Fulfill Contract

Fulfill a contract. Can only be used on contracts that have all of their delivery terms fulfilled.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (JWT) authorization: AgentToken
$config = App\SpaceTraders\OpenApi\Generated\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new App\SpaceTraders\OpenApi\Generated\Api\ContractsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$contractId = 'contractId_example'; // string | The ID of the contract to fulfill.

try {
    $result = $apiInstance->fulfillContract($contractId);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ContractsApi->fulfillContract: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **contractId** | **string**| The ID of the contract to fulfill. | |

### Return type

[**\App\SpaceTraders\OpenApi\Generated\Model\FulfillContract200Response**](../Model/FulfillContract200Response.md)

### Authorization

[AgentToken](../../README.md#AgentToken)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getContract()`

```php
getContract($contractId): \App\SpaceTraders\OpenApi\Generated\Model\GetContract200Response
```

Get Contract

Get the details of a contract by ID.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (JWT) authorization: AgentToken
$config = App\SpaceTraders\OpenApi\Generated\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new App\SpaceTraders\OpenApi\Generated\Api\ContractsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$contractId = 'contractId_example'; // string | The contract ID

try {
    $result = $apiInstance->getContract($contractId);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ContractsApi->getContract: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **contractId** | **string**| The contract ID | |

### Return type

[**\App\SpaceTraders\OpenApi\Generated\Model\GetContract200Response**](../Model/GetContract200Response.md)

### Authorization

[AgentToken](../../README.md#AgentToken)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `getContracts()`

```php
getContracts($page, $limit): \App\SpaceTraders\OpenApi\Generated\Model\GetContracts200Response
```

List Contracts

Return a paginated list of all your contracts.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (JWT) authorization: AgentToken
$config = App\SpaceTraders\OpenApi\Generated\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new App\SpaceTraders\OpenApi\Generated\Api\ContractsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$page = 1; // int | What entry offset to request
$limit = 10; // int | How many entries to return per page

try {
    $result = $apiInstance->getContracts($page, $limit);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling ContractsApi->getContracts: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **page** | **int**| What entry offset to request | [optional] [default to 1] |
| **limit** | **int**| How many entries to return per page | [optional] [default to 10] |

### Return type

[**\App\SpaceTraders\OpenApi\Generated\Model\GetContracts200Response**](../Model/GetContracts200Response.md)

### Authorization

[AgentToken](../../README.md#AgentToken)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
