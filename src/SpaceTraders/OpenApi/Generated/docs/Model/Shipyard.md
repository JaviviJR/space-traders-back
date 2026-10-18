# Shipyard

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**symbol** | **string** | The symbol of the shipyard. The symbol is the same as the waypoint where the shipyard is located. |
**shipTypes** | [**\App\SpaceTraders\OpenApi\Generated\Model\ShipyardShipTypesInner[]**](ShipyardShipTypesInner.md) | The list of ship types available for purchase at this shipyard. |
**transactions** | [**\App\SpaceTraders\OpenApi\Generated\Model\ShipyardTransaction[]**](ShipyardTransaction.md) | The list of recent transactions at this shipyard. | [optional]
**ships** | [**\App\SpaceTraders\OpenApi\Generated\Model\ShipyardShip[]**](ShipyardShip.md) | The ships that are currently available for purchase at the shipyard. | [optional]
**modificationsFee** | **int** | The fee to modify a ship at this shipyard. This includes installing or removing modules and mounts on a ship. In the case of mounts, the fee is a flat rate per mount. In the case of modules, the fee is per slot the module occupies. |

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
