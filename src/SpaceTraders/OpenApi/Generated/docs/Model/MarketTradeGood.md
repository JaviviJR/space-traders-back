# MarketTradeGood

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**symbol** | [**\App\SpaceTraders\OpenApi\Generated\Model\TradeSymbol**](TradeSymbol.md) |  |
**type** | **string** | The type of trade good (export, import, or exchange). |
**tradeVolume** | **int** | This is the maximum number of units that can be purchased or sold at this market in a single trade for this good. Trade volume also gives an indication of price volatility. A market with a low trade volume will have large price swings, while high trade volume will be more resilient to price changes. |
**supply** | [**\App\SpaceTraders\OpenApi\Generated\Model\SupplyLevel**](SupplyLevel.md) |  |
**activity** | [**\App\SpaceTraders\OpenApi\Generated\Model\ActivityLevel**](ActivityLevel.md) |  | [optional]
**purchasePrice** | **int** | The price at which this good can be purchased from the market. |
**sellPrice** | **int** | The price at which this good can be sold to the market. |

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
