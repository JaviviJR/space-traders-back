# Market

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**symbol** | **string** | The symbol of the market. The symbol is the same as the waypoint where the market is located. |
**exports** | [**\App\SpaceTraders\OpenApi\Generated\Model\TradeGood[]**](TradeGood.md) | The list of goods that are exported from this market. |
**imports** | [**\App\SpaceTraders\OpenApi\Generated\Model\TradeGood[]**](TradeGood.md) | The list of goods that are sought as imports in this market. |
**exchange** | [**\App\SpaceTraders\OpenApi\Generated\Model\TradeGood[]**](TradeGood.md) | The list of goods that are bought and sold between agents at this market. |
**transactions** | [**\App\SpaceTraders\OpenApi\Generated\Model\MarketTransaction[]**](MarketTransaction.md) | The list of recent transactions at this market. Visible only when a ship is present at the market. | [optional]
**tradeGoods** | [**\App\SpaceTraders\OpenApi\Generated\Model\MarketTradeGood[]**](MarketTradeGood.md) | The list of goods that are traded at this market. Visible only when a ship is present at the market. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
