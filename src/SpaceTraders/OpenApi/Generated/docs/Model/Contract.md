# Contract

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **string** | ID of the contract. |
**factionSymbol** | **string** | The symbol of the faction that this contract is for. |
**type** | **string** | Type of contract. |
**terms** | [**\App\SpaceTraders\OpenApi\Generated\Model\ContractTerms**](ContractTerms.md) |  |
**accepted** | **bool** | Whether the contract has been accepted by the agent | [default to false]
**fulfilled** | **bool** | Whether the contract has been fulfilled | [default to false]
**expiration** | **\DateTime** | Deprecated in favor of deadlineToAccept |
**deadlineToAccept** | **\DateTime** | The time at which the contract is no longer available to be accepted | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
