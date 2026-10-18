# OpenAPIClient-php

SpaceTraders is an open-universe game and learning platform that offers a set of HTTP endpoints to control a fleet of ships and explore a multiplayer universe.

The API is documented using [OpenAPI](https://github.com/SpaceTradersAPI/api-docs). You can send your first request right here in your browser to check the status of the game server.

```json http
{
  \"method\": \"GET\",
  \"url\": \"https://api.spacetraders.io/v2\",
}
```

Unlike a traditional game, SpaceTraders does not have a first-party client or app to play the game. Instead, you can use the API to build your own client, write a script to automate your ships, or try an app built by the community.

We have a [Discord channel](https://discord.com/invite/jh6zurdWk5) where you can share your projects, ask questions, and get help from other players.





## Installation & Usage

### Requirements

PHP 8.1 and later.

### Composer

To install the bindings via [Composer](https://getcomposer.org/), add the following to `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/GIT_USER_ID/GIT_REPO_ID.git"
    }
  ],
  "require": {
    "GIT_USER_ID/GIT_REPO_ID": "*@dev"
  }
}
```

Then run `composer install`

### Manual Installation

Download the files and include `autoload.php`:

```php
<?php
require_once('/path/to/OpenAPIClient-php/vendor/autoload.php');
```

## Getting Started

Please follow the [installation procedure](#installation--usage) and then run the following:

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

## API Endpoints

All URIs are relative to *https://api.spacetraders.io/v2*

Class | Method | HTTP request | Description
------------ | ------------- | ------------- | -------------
*AgentsApi* | [**getAgent**](docs/Api/AgentsApi.md#getagent) | **GET** /agents/{agentSymbol} | Get Public Agent
*AgentsApi* | [**getAgents**](docs/Api/AgentsApi.md#getagents) | **GET** /agents | List Agents
*AgentsApi* | [**getMyAgent**](docs/Api/AgentsApi.md#getmyagent) | **GET** /my/agent | Get Agent
*ContractsApi* | [**acceptContract**](docs/Api/ContractsApi.md#acceptcontract) | **POST** /my/contracts/{contractId}/accept | Accept Contract
*ContractsApi* | [**deliverContract**](docs/Api/ContractsApi.md#delivercontract) | **POST** /my/contracts/{contractId}/deliver | Deliver Cargo to Contract
*ContractsApi* | [**fulfillContract**](docs/Api/ContractsApi.md#fulfillcontract) | **POST** /my/contracts/{contractId}/fulfill | Fulfill Contract
*ContractsApi* | [**getContract**](docs/Api/ContractsApi.md#getcontract) | **GET** /my/contracts/{contractId} | Get Contract
*ContractsApi* | [**getContracts**](docs/Api/ContractsApi.md#getcontracts) | **GET** /my/contracts | List Contracts
*DataApi* | [**getSupplyChain**](docs/Api/DataApi.md#getsupplychain) | **GET** /market/supply-chain | Get Supply Chain
*FactionsApi* | [**getFaction**](docs/Api/FactionsApi.md#getfaction) | **GET** /factions/{factionSymbol} | Get Faction
*FactionsApi* | [**getFactions**](docs/Api/FactionsApi.md#getfactions) | **GET** /factions | List Factions
*FleetApi* | [**createChart**](docs/Api/FleetApi.md#createchart) | **POST** /my/ships/{shipSymbol}/chart | Create Chart
*FleetApi* | [**createShipShipScan**](docs/Api/FleetApi.md#createshipshipscan) | **POST** /my/ships/{shipSymbol}/scan/ships | Scan Ships
*FleetApi* | [**createShipSystemScan**](docs/Api/FleetApi.md#createshipsystemscan) | **POST** /my/ships/{shipSymbol}/scan/systems | Scan Systems
*FleetApi* | [**createShipWaypointScan**](docs/Api/FleetApi.md#createshipwaypointscan) | **POST** /my/ships/{shipSymbol}/scan/waypoints | Scan Waypoints
*FleetApi* | [**createSurvey**](docs/Api/FleetApi.md#createsurvey) | **POST** /my/ships/{shipSymbol}/survey | Create Survey
*FleetApi* | [**dockShip**](docs/Api/FleetApi.md#dockship) | **POST** /my/ships/{shipSymbol}/dock | Dock Ship
*FleetApi* | [**extractResources**](docs/Api/FleetApi.md#extractresources) | **POST** /my/ships/{shipSymbol}/extract | Extract Resources
*FleetApi* | [**extractResourcesWithSurvey**](docs/Api/FleetApi.md#extractresourceswithsurvey) | **POST** /my/ships/{shipSymbol}/extract/survey | Extract Resources with Survey
*FleetApi* | [**getMounts**](docs/Api/FleetApi.md#getmounts) | **GET** /my/ships/{shipSymbol}/mounts | Get Mounts
*FleetApi* | [**getMyShip**](docs/Api/FleetApi.md#getmyship) | **GET** /my/ships/{shipSymbol} | Get Ship
*FleetApi* | [**getMyShipCargo**](docs/Api/FleetApi.md#getmyshipcargo) | **GET** /my/ships/{shipSymbol}/cargo | Get Ship Cargo
*FleetApi* | [**getMyShips**](docs/Api/FleetApi.md#getmyships) | **GET** /my/ships | List Ships
*FleetApi* | [**getRepairShip**](docs/Api/FleetApi.md#getrepairship) | **GET** /my/ships/{shipSymbol}/repair | Get Repair Ship
*FleetApi* | [**getScrapShip**](docs/Api/FleetApi.md#getscrapship) | **GET** /my/ships/{shipSymbol}/scrap | Get Scrap Ship
*FleetApi* | [**getShipCooldown**](docs/Api/FleetApi.md#getshipcooldown) | **GET** /my/ships/{shipSymbol}/cooldown | Get Ship Cooldown
*FleetApi* | [**getShipModules**](docs/Api/FleetApi.md#getshipmodules) | **GET** /my/ships/{shipSymbol}/modules | Get Ship Modules
*FleetApi* | [**getShipNav**](docs/Api/FleetApi.md#getshipnav) | **GET** /my/ships/{shipSymbol}/nav | Get Ship Nav
*FleetApi* | [**installMount**](docs/Api/FleetApi.md#installmount) | **POST** /my/ships/{shipSymbol}/mounts/install | Install Mount
*FleetApi* | [**installShipModule**](docs/Api/FleetApi.md#installshipmodule) | **POST** /my/ships/{shipSymbol}/modules/install | Install Ship Module
*FleetApi* | [**jettison**](docs/Api/FleetApi.md#jettison) | **POST** /my/ships/{shipSymbol}/jettison | Jettison Cargo
*FleetApi* | [**jumpShip**](docs/Api/FleetApi.md#jumpship) | **POST** /my/ships/{shipSymbol}/jump | Jump Ship
*FleetApi* | [**navigateShip**](docs/Api/FleetApi.md#navigateship) | **POST** /my/ships/{shipSymbol}/navigate | Navigate Ship
*FleetApi* | [**negotiateContract**](docs/Api/FleetApi.md#negotiatecontract) | **POST** /my/ships/{shipSymbol}/negotiate/contract | Negotiate Contract
*FleetApi* | [**orbitShip**](docs/Api/FleetApi.md#orbitship) | **POST** /my/ships/{shipSymbol}/orbit | Orbit Ship
*FleetApi* | [**patchShipNav**](docs/Api/FleetApi.md#patchshipnav) | **PATCH** /my/ships/{shipSymbol}/nav | Patch Ship Nav
*FleetApi* | [**purchaseCargo**](docs/Api/FleetApi.md#purchasecargo) | **POST** /my/ships/{shipSymbol}/purchase | Purchase Cargo
*FleetApi* | [**purchaseShip**](docs/Api/FleetApi.md#purchaseship) | **POST** /my/ships | Purchase Ship
*FleetApi* | [**refuelShip**](docs/Api/FleetApi.md#refuelship) | **POST** /my/ships/{shipSymbol}/refuel | Refuel Ship
*FleetApi* | [**removeMount**](docs/Api/FleetApi.md#removemount) | **POST** /my/ships/{shipSymbol}/mounts/remove | Remove Mount
*FleetApi* | [**removeShipModule**](docs/Api/FleetApi.md#removeshipmodule) | **POST** /my/ships/{shipSymbol}/modules/remove | Remove Ship Module
*FleetApi* | [**repairShip**](docs/Api/FleetApi.md#repairship) | **POST** /my/ships/{shipSymbol}/repair | Repair Ship
*FleetApi* | [**scrapShip**](docs/Api/FleetApi.md#scrapship) | **POST** /my/ships/{shipSymbol}/scrap | Scrap Ship
*FleetApi* | [**sellCargo**](docs/Api/FleetApi.md#sellcargo) | **POST** /my/ships/{shipSymbol}/sell | Sell Cargo
*FleetApi* | [**shipRefine**](docs/Api/FleetApi.md#shiprefine) | **POST** /my/ships/{shipSymbol}/refine | Ship Refine
*FleetApi* | [**siphonResources**](docs/Api/FleetApi.md#siphonresources) | **POST** /my/ships/{shipSymbol}/siphon | Siphon Resources
*FleetApi* | [**transferCargo**](docs/Api/FleetApi.md#transfercargo) | **POST** /my/ships/{shipSymbol}/transfer | Transfer Cargo
*FleetApi* | [**warpShip**](docs/Api/FleetApi.md#warpship) | **POST** /my/ships/{shipSymbol}/warp | Warp Ship
*GlobalApi* | [**getStatus**](docs/Api/GlobalApi.md#getstatus) | **GET** / | Get Status
*GlobalApi* | [**register**](docs/Api/GlobalApi.md#register) | **POST** /register | Register New Agent
*SystemsApi* | [**getConstruction**](docs/Api/SystemsApi.md#getconstruction) | **GET** /systems/{systemSymbol}/waypoints/{waypointSymbol}/construction | Get Construction Site
*SystemsApi* | [**getJumpGate**](docs/Api/SystemsApi.md#getjumpgate) | **GET** /systems/{systemSymbol}/waypoints/{waypointSymbol}/jump-gate | Get Jump Gate
*SystemsApi* | [**getMarket**](docs/Api/SystemsApi.md#getmarket) | **GET** /systems/{systemSymbol}/waypoints/{waypointSymbol}/market | Get Market
*SystemsApi* | [**getShipyard**](docs/Api/SystemsApi.md#getshipyard) | **GET** /systems/{systemSymbol}/waypoints/{waypointSymbol}/shipyard | Get Shipyard
*SystemsApi* | [**getSystem**](docs/Api/SystemsApi.md#getsystem) | **GET** /systems/{systemSymbol} | Get System
*SystemsApi* | [**getSystemWaypoints**](docs/Api/SystemsApi.md#getsystemwaypoints) | **GET** /systems/{systemSymbol}/waypoints | List Waypoints in System
*SystemsApi* | [**getSystems**](docs/Api/SystemsApi.md#getsystems) | **GET** /systems | List Systems
*SystemsApi* | [**getWaypoint**](docs/Api/SystemsApi.md#getwaypoint) | **GET** /systems/{systemSymbol}/waypoints/{waypointSymbol} | Get Waypoint
*SystemsApi* | [**supplyConstruction**](docs/Api/SystemsApi.md#supplyconstruction) | **POST** /systems/{systemSymbol}/waypoints/{waypointSymbol}/construction/supply | Supply Construction Site

## Models

- [AcceptContract200Response](docs/Model/AcceptContract200Response.md)
- [AcceptContract200ResponseData](docs/Model/AcceptContract200ResponseData.md)
- [ActivityLevel](docs/Model/ActivityLevel.md)
- [Agent](docs/Model/Agent.md)
- [Chart](docs/Model/Chart.md)
- [Construction](docs/Model/Construction.md)
- [ConstructionMaterial](docs/Model/ConstructionMaterial.md)
- [Contract](docs/Model/Contract.md)
- [ContractDeliverGood](docs/Model/ContractDeliverGood.md)
- [ContractPayment](docs/Model/ContractPayment.md)
- [ContractTerms](docs/Model/ContractTerms.md)
- [Cooldown](docs/Model/Cooldown.md)
- [CreateChart201Response](docs/Model/CreateChart201Response.md)
- [CreateChart201ResponseData](docs/Model/CreateChart201ResponseData.md)
- [CreateShipShipScan201Response](docs/Model/CreateShipShipScan201Response.md)
- [CreateShipShipScan201ResponseData](docs/Model/CreateShipShipScan201ResponseData.md)
- [CreateShipSystemScan201Response](docs/Model/CreateShipSystemScan201Response.md)
- [CreateShipSystemScan201ResponseData](docs/Model/CreateShipSystemScan201ResponseData.md)
- [CreateShipWaypointScan201Response](docs/Model/CreateShipWaypointScan201Response.md)
- [CreateShipWaypointScan201ResponseData](docs/Model/CreateShipWaypointScan201ResponseData.md)
- [CreateSurvey201Response](docs/Model/CreateSurvey201Response.md)
- [CreateSurvey201ResponseData](docs/Model/CreateSurvey201ResponseData.md)
- [DeliverContract200Response](docs/Model/DeliverContract200Response.md)
- [DeliverContract200ResponseData](docs/Model/DeliverContract200ResponseData.md)
- [DeliverContractRequest](docs/Model/DeliverContractRequest.md)
- [DockShip200Response](docs/Model/DockShip200Response.md)
- [ExtractResources201Response](docs/Model/ExtractResources201Response.md)
- [ExtractResources201ResponseData](docs/Model/ExtractResources201ResponseData.md)
- [ExtractResourcesRequest](docs/Model/ExtractResourcesRequest.md)
- [ExtractResourcesWithSurvey201Response](docs/Model/ExtractResourcesWithSurvey201Response.md)
- [ExtractResourcesWithSurvey201ResponseData](docs/Model/ExtractResourcesWithSurvey201ResponseData.md)
- [Extraction](docs/Model/Extraction.md)
- [ExtractionYield](docs/Model/ExtractionYield.md)
- [Faction](docs/Model/Faction.md)
- [FactionSymbol](docs/Model/FactionSymbol.md)
- [FactionTrait](docs/Model/FactionTrait.md)
- [FactionTraitSymbol](docs/Model/FactionTraitSymbol.md)
- [FulfillContract200Response](docs/Model/FulfillContract200Response.md)
- [GetAgents200Response](docs/Model/GetAgents200Response.md)
- [GetConstruction200Response](docs/Model/GetConstruction200Response.md)
- [GetContract200Response](docs/Model/GetContract200Response.md)
- [GetContracts200Response](docs/Model/GetContracts200Response.md)
- [GetFaction200Response](docs/Model/GetFaction200Response.md)
- [GetFactions200Response](docs/Model/GetFactions200Response.md)
- [GetJumpGate200Response](docs/Model/GetJumpGate200Response.md)
- [GetMarket200Response](docs/Model/GetMarket200Response.md)
- [GetMounts200Response](docs/Model/GetMounts200Response.md)
- [GetMyAgent200Response](docs/Model/GetMyAgent200Response.md)
- [GetMyShip200Response](docs/Model/GetMyShip200Response.md)
- [GetMyShipCargo200Response](docs/Model/GetMyShipCargo200Response.md)
- [GetMyShips200Response](docs/Model/GetMyShips200Response.md)
- [GetRepairShip200Response](docs/Model/GetRepairShip200Response.md)
- [GetRepairShip200ResponseData](docs/Model/GetRepairShip200ResponseData.md)
- [GetScrapShip200Response](docs/Model/GetScrapShip200Response.md)
- [GetScrapShip200ResponseData](docs/Model/GetScrapShip200ResponseData.md)
- [GetShipCooldown200Response](docs/Model/GetShipCooldown200Response.md)
- [GetShipModules200Response](docs/Model/GetShipModules200Response.md)
- [GetShipNav200Response](docs/Model/GetShipNav200Response.md)
- [GetShipyard200Response](docs/Model/GetShipyard200Response.md)
- [GetStatus200Response](docs/Model/GetStatus200Response.md)
- [GetStatus200ResponseAnnouncementsInner](docs/Model/GetStatus200ResponseAnnouncementsInner.md)
- [GetStatus200ResponseLeaderboards](docs/Model/GetStatus200ResponseLeaderboards.md)
- [GetStatus200ResponseLeaderboardsMostCreditsInner](docs/Model/GetStatus200ResponseLeaderboardsMostCreditsInner.md)
- [GetStatus200ResponseLeaderboardsMostSubmittedChartsInner](docs/Model/GetStatus200ResponseLeaderboardsMostSubmittedChartsInner.md)
- [GetStatus200ResponseLinksInner](docs/Model/GetStatus200ResponseLinksInner.md)
- [GetStatus200ResponseServerResets](docs/Model/GetStatus200ResponseServerResets.md)
- [GetStatus200ResponseStats](docs/Model/GetStatus200ResponseStats.md)
- [GetSupplyChain200Response](docs/Model/GetSupplyChain200Response.md)
- [GetSupplyChain200ResponseData](docs/Model/GetSupplyChain200ResponseData.md)
- [GetSystem200Response](docs/Model/GetSystem200Response.md)
- [GetSystemWaypoints200Response](docs/Model/GetSystemWaypoints200Response.md)
- [GetSystemWaypointsTraitsParameter](docs/Model/GetSystemWaypointsTraitsParameter.md)
- [GetSystems200Response](docs/Model/GetSystems200Response.md)
- [GetWaypoint200Response](docs/Model/GetWaypoint200Response.md)
- [InstallMount201Response](docs/Model/InstallMount201Response.md)
- [InstallMount201ResponseData](docs/Model/InstallMount201ResponseData.md)
- [InstallMountRequest](docs/Model/InstallMountRequest.md)
- [InstallShipModule201Response](docs/Model/InstallShipModule201Response.md)
- [InstallShipModule201ResponseData](docs/Model/InstallShipModule201ResponseData.md)
- [InstallShipModule201ResponseDataTransaction](docs/Model/InstallShipModule201ResponseDataTransaction.md)
- [InstallShipModuleRequest](docs/Model/InstallShipModuleRequest.md)
- [Jettison200Response](docs/Model/Jettison200Response.md)
- [Jettison200ResponseData](docs/Model/Jettison200ResponseData.md)
- [JettisonRequest](docs/Model/JettisonRequest.md)
- [JumpGate](docs/Model/JumpGate.md)
- [JumpShip200Response](docs/Model/JumpShip200Response.md)
- [JumpShip200ResponseData](docs/Model/JumpShip200ResponseData.md)
- [JumpShipRequest](docs/Model/JumpShipRequest.md)
- [Market](docs/Model/Market.md)
- [MarketTradeGood](docs/Model/MarketTradeGood.md)
- [MarketTransaction](docs/Model/MarketTransaction.md)
- [Meta](docs/Model/Meta.md)
- [NavigateShip200Response](docs/Model/NavigateShip200Response.md)
- [NavigateShip200ResponseData](docs/Model/NavigateShip200ResponseData.md)
- [NavigateShipRequest](docs/Model/NavigateShipRequest.md)
- [NegotiateContract200Response](docs/Model/NegotiateContract200Response.md)
- [NegotiateContract200ResponseData](docs/Model/NegotiateContract200ResponseData.md)
- [OrbitShip200Response](docs/Model/OrbitShip200Response.md)
- [OrbitShip200ResponseData](docs/Model/OrbitShip200ResponseData.md)
- [PatchShipNav200Response](docs/Model/PatchShipNav200Response.md)
- [PatchShipNav200ResponseData](docs/Model/PatchShipNav200ResponseData.md)
- [PatchShipNavRequest](docs/Model/PatchShipNavRequest.md)
- [PurchaseCargo201Response](docs/Model/PurchaseCargo201Response.md)
- [PurchaseCargoRequest](docs/Model/PurchaseCargoRequest.md)
- [PurchaseShip201Response](docs/Model/PurchaseShip201Response.md)
- [PurchaseShip201ResponseData](docs/Model/PurchaseShip201ResponseData.md)
- [PurchaseShipRequest](docs/Model/PurchaseShipRequest.md)
- [RefuelShip200Response](docs/Model/RefuelShip200Response.md)
- [RefuelShip200ResponseData](docs/Model/RefuelShip200ResponseData.md)
- [RefuelShipRequest](docs/Model/RefuelShipRequest.md)
- [Register201Response](docs/Model/Register201Response.md)
- [Register201ResponseData](docs/Model/Register201ResponseData.md)
- [RegisterRequest](docs/Model/RegisterRequest.md)
- [RemoveModule201Response](docs/Model/RemoveModule201Response.md)
- [RemoveMount201Response](docs/Model/RemoveMount201Response.md)
- [RemoveMount201ResponseData](docs/Model/RemoveMount201ResponseData.md)
- [RemoveMountRequest](docs/Model/RemoveMountRequest.md)
- [RemoveShipModuleRequest](docs/Model/RemoveShipModuleRequest.md)
- [RepairShip200Response](docs/Model/RepairShip200Response.md)
- [RepairShip200ResponseData](docs/Model/RepairShip200ResponseData.md)
- [RepairTransaction](docs/Model/RepairTransaction.md)
- [ScannedShip](docs/Model/ScannedShip.md)
- [ScannedShipEngine](docs/Model/ScannedShipEngine.md)
- [ScannedShipFrame](docs/Model/ScannedShipFrame.md)
- [ScannedShipMountsInner](docs/Model/ScannedShipMountsInner.md)
- [ScannedShipReactor](docs/Model/ScannedShipReactor.md)
- [ScannedSystem](docs/Model/ScannedSystem.md)
- [ScannedWaypoint](docs/Model/ScannedWaypoint.md)
- [ScrapShip200Response](docs/Model/ScrapShip200Response.md)
- [ScrapShip200ResponseData](docs/Model/ScrapShip200ResponseData.md)
- [ScrapTransaction](docs/Model/ScrapTransaction.md)
- [SellCargo201Response](docs/Model/SellCargo201Response.md)
- [SellCargo201ResponseData](docs/Model/SellCargo201ResponseData.md)
- [SellCargoRequest](docs/Model/SellCargoRequest.md)
- [Ship](docs/Model/Ship.md)
- [ShipCargo](docs/Model/ShipCargo.md)
- [ShipCargoItem](docs/Model/ShipCargoItem.md)
- [ShipConditionEvent](docs/Model/ShipConditionEvent.md)
- [ShipCrew](docs/Model/ShipCrew.md)
- [ShipEngine](docs/Model/ShipEngine.md)
- [ShipFrame](docs/Model/ShipFrame.md)
- [ShipFuel](docs/Model/ShipFuel.md)
- [ShipFuelConsumed](docs/Model/ShipFuelConsumed.md)
- [ShipModificationTransaction](docs/Model/ShipModificationTransaction.md)
- [ShipModule](docs/Model/ShipModule.md)
- [ShipMount](docs/Model/ShipMount.md)
- [ShipNav](docs/Model/ShipNav.md)
- [ShipNavFlightMode](docs/Model/ShipNavFlightMode.md)
- [ShipNavRoute](docs/Model/ShipNavRoute.md)
- [ShipNavRouteWaypoint](docs/Model/ShipNavRouteWaypoint.md)
- [ShipNavStatus](docs/Model/ShipNavStatus.md)
- [ShipReactor](docs/Model/ShipReactor.md)
- [ShipRefine201Response](docs/Model/ShipRefine201Response.md)
- [ShipRefine201ResponseData](docs/Model/ShipRefine201ResponseData.md)
- [ShipRefine201ResponseDataProducedInner](docs/Model/ShipRefine201ResponseDataProducedInner.md)
- [ShipRefineRequest](docs/Model/ShipRefineRequest.md)
- [ShipRegistration](docs/Model/ShipRegistration.md)
- [ShipRequirements](docs/Model/ShipRequirements.md)
- [ShipRole](docs/Model/ShipRole.md)
- [ShipType](docs/Model/ShipType.md)
- [Shipyard](docs/Model/Shipyard.md)
- [ShipyardShip](docs/Model/ShipyardShip.md)
- [ShipyardShipCrew](docs/Model/ShipyardShipCrew.md)
- [ShipyardShipTypesInner](docs/Model/ShipyardShipTypesInner.md)
- [ShipyardTransaction](docs/Model/ShipyardTransaction.md)
- [Siphon](docs/Model/Siphon.md)
- [SiphonResources201Response](docs/Model/SiphonResources201Response.md)
- [SiphonResources201ResponseData](docs/Model/SiphonResources201ResponseData.md)
- [SiphonYield](docs/Model/SiphonYield.md)
- [SupplyConstruction201Response](docs/Model/SupplyConstruction201Response.md)
- [SupplyConstruction201ResponseData](docs/Model/SupplyConstruction201ResponseData.md)
- [SupplyConstructionRequest](docs/Model/SupplyConstructionRequest.md)
- [SupplyLevel](docs/Model/SupplyLevel.md)
- [Survey](docs/Model/Survey.md)
- [SurveyDeposit](docs/Model/SurveyDeposit.md)
- [System](docs/Model/System.md)
- [SystemFaction](docs/Model/SystemFaction.md)
- [SystemType](docs/Model/SystemType.md)
- [SystemWaypoint](docs/Model/SystemWaypoint.md)
- [TradeGood](docs/Model/TradeGood.md)
- [TradeSymbol](docs/Model/TradeSymbol.md)
- [TransferCargo200Response](docs/Model/TransferCargo200Response.md)
- [TransferCargoRequest](docs/Model/TransferCargoRequest.md)
- [WarpShip200Response](docs/Model/WarpShip200Response.md)
- [WarpShip200ResponseData](docs/Model/WarpShip200ResponseData.md)
- [Waypoint](docs/Model/Waypoint.md)
- [WaypointFaction](docs/Model/WaypointFaction.md)
- [WaypointModifier](docs/Model/WaypointModifier.md)
- [WaypointModifierSymbol](docs/Model/WaypointModifierSymbol.md)
- [WaypointOrbital](docs/Model/WaypointOrbital.md)
- [WaypointTrait](docs/Model/WaypointTrait.md)
- [WaypointTraitSymbol](docs/Model/WaypointTraitSymbol.md)
- [WaypointType](docs/Model/WaypointType.md)

## Authorization

### AgentToken

- **Type**: Bearer authentication (JWT)


### AccountToken

- **Type**: Bearer authentication (JWT)

## Tests

To run the tests, use:

```bash
composer install
vendor/bin/phpunit
```

## Author

joel@spacetraders.io

## About this package

This PHP package is automatically generated by the [OpenAPI Generator](https://openapi-generator.tech) project:

- API version: `2.3.0`
    - Generator version: `7.27.0-SNAPSHOT`
- Build package: `org.openapitools.codegen.languages.PhpNextgenClientCodegen`
