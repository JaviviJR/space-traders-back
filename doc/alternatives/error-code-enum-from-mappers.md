# Alternative: enum generated from the mappers

Alternative to the decision recorded in "Error codes" in
[../openapi.md](../openapi.md) (`ErrorResponseData::$code` typed with the
central `App\Http\ApiErrorCode` enum). Kept because it may be revisited if the
central enum becomes a bottleneck (many domains adding codes, merge conflicts
on `ApiErrorCode`).

**How it would work:** a Nelmio `DescriberInterface` (tagged
`nelmio_api_doc.describer.default`, like `ProxyDescriber`) receives the
`app.exception_error_mapper` tagged iterator, collects every mapper's code
and creates the `ApiErrorCode` component (`type: string`, `enum: [...]`)
itself. `ErrorResponseData::$code` goes back to `string` and points at that
component with a static `#[OA\Property(ref: '#/components/schemas/ApiErrorCode')]`,
inherited by subclasses. Adding a mapper then needs no edit to any central
file.

Why a describer that *creates* the component rather than one that *patches*
`ApiError`: describers run before Nelmio registers model schemas, then the
swagger-php processor pipeline runs (`ApiDocGenerator::generate()` —
inferred from `vendor/` source, not documented), so `ApiError` doesn't exist
yet when a describer runs. A custom swagger-php processor (tag
`nelmio_api_doc.swagger.processor`, also only found in `vendor/`, not in
Nelmio's docs) runs late enough to patch it, but relies on undocumented
machinery.

How each mapper would expose its code, from weakest to preferred:

- A public `CODE` constant read by reflection: nothing enforces it exists.
- A method on `ExceptionErrorMapperInterface`: forces every mapper, but the
  listener (the interface's real consumer) never calls it — an interface
  segregation smell.
- A small separate interface (e.g. `describedCodes(): list<string>`), the
  same shape as `AugmentInterface::describedAugmentation()` for hooks: a
  doc-time counterpart kept apart from the runtime interface, filtered with
  `instanceof`. A list allows a mapper with several codes.

What makes it harder than the current decision:

- Codes emitted outside mappers (the factory's unmapped fallback) need
  their own source, or they silently vanish from the spec. This is the drift the enum rules out by type.
- A mapper that forgets the small interface also silently vanishes; it
  needs a test asserting every `app.exception_error_mapper` service
  implements it.
- The spec test compares the dumped `ApiErrorCode` enum against the codes
  collected from the registered mappers plus the non-mapper sources.

Switching means: remove `App\Http\ApiErrorCode` and its `models.names` alias,
type `$code` back as `string` with the `ref`, add the describer and the
small interface, and replace the enum cases used in mappers and the
factory's fallback with each one's own code. `#[ErrorResponse]`'s
`code:` would become a plain string, no longer checked by the type system —
only by the spec test's "exactly one known case" assertion.
