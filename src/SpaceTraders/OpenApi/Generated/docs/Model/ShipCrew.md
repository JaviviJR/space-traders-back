# ShipCrew

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**current** | **int** | The current number of crew members on the ship. |
**required** | **int** | The minimum number of crew members required to maintain the ship. |
**capacity** | **int** | The maximum number of crew members the ship can support. |
**rotation** | **string** | The rotation of crew shifts. A stricter shift improves the ship&#39;s performance. A more relaxed shift improves the crew&#39;s morale. | [default to 'STRICT']
**morale** | **int** | A rough measure of the crew&#39;s morale. A higher morale means the crew is happier and more productive. A lower morale means the ship is more prone to accidents. |
**wages** | **int** | The amount of credits per crew member paid per hour. Wages are paid when a ship docks at a civilized waypoint. |

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
