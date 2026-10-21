# Errors

A single `kernel.exception` listener (`ExceptionListener`) converts every
uncaught exception into the same envelope as any other response —
`{"error": {"code", "message"}}` — via `EnvelopeResponseFactory::error()`,
instead of Symfony's default error shape. `code` is a stable,
machine-readable identifier for the front to branch on; `message` is
human-readable and safe to expose.

Deciding the response for a given exception is delegated to
`ExceptionErrorMapperInterface` (`supports()` + `map()`): one class per kind
of exception, tagged `app.exception_error_mapper` and injected into the
listener as a priority-ordered `#[AutowireIterator]`, trying each
`supports()` in order and using the first match — the same shape Symfony
itself uses for Normalizers. `map()` returns a `MappedError`: the HTTP
status plus the public body as an `ErrorResponseData`, which the listener
hands to the envelope as-is.

Anything unmapped → `500`, `internal_error`, a generic message (the real
exception is never exposed). `500` is reserved for the back's own internal
failures; anything else that should reach the front gets a mapper.

## Adding a mapper

1. Create a class in `src/EventListener/ErrorMapper/` implementing
   `ExceptionErrorMapperInterface`. The interface carries
   `#[AutoconfigureTag]`, so autoconfiguration registers it; no service
   definition needed.
2. `supports()` matches the exception (usually an `instanceof`); `map()`
   returns its status, a stable `code` and the public message. The code is
   a case of `App\Http\ErrorCode` (backing value: a `snake_case` slug): add
   a case there for a new code, never a string literal — the enum is what
   the generated spec lists for the front (see "Error codes" in
   [openapi.md](openapi.md)). The same applies to any error body built
   outside a mapper (e.g. a security failure handler).
3. Choose the message deliberately: a fixed text by default. Reuse
   `getMessage()` only when the exception's message is written to be shown
   to the front (as `HttpExceptionInterface` messages are); a domain
   exception's message is not a public contract.
4. If it must run ahead of a more generic mapper that would also match, raise
   its priority with `#[AsTaggedItem(priority: ...)]`; with none, it runs
   after every prioritised one.
5. If the error needs fields beyond `code`/`message` (e.g. per-field detail
   the front binds to a form), put them in a `readonly` subclass of
   `ErrorResponseData` in `src/Http/` and return that as the body — not a
   nullable field on `ErrorResponseData`, which every other error would then
   carry. The rest of the pipeline is unchanged: the subclass is still an
   `ErrorResponseData`. Document it on the affected endpoints with
   `#[ErrorResponse(..., code: ErrorCode::TheCase, model: TheSubclass::class)]`
   (see [openapi.md](openapi.md)). Every `#[ErrorResponse]` names its
   `code:`, with or without a subclass. The exception is the validation
   error of `#[MapRequestPayload]` / `#[MapQueryString]`: it's inferred
   from the attribute and never annotated by hand (see "Validation errors
   are inferred" in [openapi.md](openapi.md)).
6. If the error response needs headers (e.g. a `Set-Cookie` clearing a
   rejected credential, or headers an `HttpExceptionInterface` carries), return
   them in `MappedError::$headers`; the listener passes them to the envelope.
   A mapper may inject whatever HTTP-layer service it needs to build them.
7. Add a unit test in `tests/EventListener/ErrorMapper/` (supports, doesn't
   support another exception, maps to the expected status/code/message), and
   assert the `code` in the functional test of the endpoint that triggers it.
   No spec test is needed for a new case: `tests/OpenApi/ErrorCodeSchemaTest`
   already checks that every `ErrorCode` case reaches the dumped spec.

Anything specific to one mapper (why that status, why that priority, what it
deliberately doesn't match) is documented in the mapper's own docblock, not
here.

## Localized validation messages

Only the per-field messages in a validation error's `violations` follow the
request's `Accept-Language` header. Everything else — the envelope's `code`
and `message` (`Validation failed.` included), every other mapper's
message, and relayed SpaceTraders errors — stays in English.

This relies on the framework, not on code in the mapper:
`framework.set_locale_from_accept_language` sets the request locale, and the
Validator translates constraint messages with the `validators` catalogs
shipped by `symfony/validator` (`config/packages/translation.yaml`).

- Enabled locales are `en` and `es` (`framework.enabled_locales`). A missing
  header keeps `default_locale` (`en`); an unsupported one gets the *first*
  enabled locale, so `en` must stay first. A regional variant (`es-AR`)
  matches its language (`es`).
- The response carries `Vary: Accept-Language` (added by the framework to
  every response once the option is on). No `Content-Language`: most of the
  body, including the envelope's `message`, isn't in the request locale.
- A custom constraint message (`#[Assert\...(message: ...)]`) must be a
  translation key with entries in `translations/validators.{en,es}.*`;
  plain English text there is never translated.
- Type mismatches reported by `#[MapRequestPayload]` (`This value should be
  of type ...`) are translated too; templates with no entry in the catalogs
  stay in English.

## Controllers don't translate exceptions

Controllers and services let domain exceptions propagate to the listener;
they don't `catch` them to rethrow an `HttpException`. Services stay
HTTP-agnostic, and every error response is decided in one place.

The one exception: when the same exception needs a different response
depending on the endpoint (e.g. "user not found" is a `404` on a lookup but
must be a generic `401` on login, to avoid revealing which emails exist), a
global mapper can't express it. Prefer having the service throw distinct
exceptions; only if that isn't reasonable, translate in that specific
controller.

## TODO

- Known leak of localized messages, accepted for now: once a translator
  exists, LexikJWTAuthenticationBundle translates its own authentication
  failure message with the `security` domain. Its own keys (`Invalid JWT
  Token`, `Expired JWT Token`) have no catalog entry and stay in English, but
  a valid JWT for a deleted user returns Symfony's `Username could not be
  found.` translated (e.g. Spanish with `Accept-Language: es`). Revisit if it
  causes problems — e.g. a listener on Lexik's `JWT_INVALID`/`JWT_EXPIRED`
  events that resets the untranslated message.
- `error.code`'s type is inconsistent across the two response kinds: a
  string slug for back-only errors (`ErrorResponseData`), a number from
  SpaceTraders' own catalog for relayed proxy errors (see
  https://spacetraders.io/api-guide/response-errors). Not decided yet: how
  (or whether) to unify the type — e.g. casting SpaceTraders' numeric `code`
  to string on the way out — without losing the pass-through optimization
  for responses that don't need it.
- Related, also not decided: whether the HTTP status code the front
  receives for a relayed SpaceTraders error should keep being SpaceTraders'
  own status unchanged for every case (today's guarantee, see "SpaceTraders
  endpoints" in [spacetraders-proxy.md](spacetraders-proxy.md)), or be
  decided per error. Changing this would mean revisiting that existing
  guarantee, not just adding to it.
