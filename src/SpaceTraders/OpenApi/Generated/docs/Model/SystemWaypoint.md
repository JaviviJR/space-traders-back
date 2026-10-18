# SystemWaypoint

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**symbol** | **string** | The symbol of the waypoint. |
**type** | [**\App\SpaceTraders\OpenApi\Generated\Model\WaypointType**](WaypointType.md) |  |
**x** | **int** | Relative position of the waypoint on the system&#39;s x axis. This is not an absolute position in the universe. |
**y** | **int** | Relative position of the waypoint on the system&#39;s y axis. This is not an absolute position in the universe. |
**orbitals** | [**\App\SpaceTraders\OpenApi\Generated\Model\WaypointOrbital[]**](WaypointOrbital.md) | Waypoints that orbit this waypoint. |
**orbits** | **string** | The symbol of the parent waypoint, if this waypoint is in orbit around another waypoint. Otherwise this value is undefined. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
