# Alternative: model describer that turns ResponseData inheritance into allOf

Alternative to the class-level `#[OA\Schema(allOf: [...])]` recorded in
"Back-only endpoints" in [../openapi.md](../openapi.md), which documents a
`ResponseData` subclass as `allOf` its parent but still repeats the
inherited properties inside that `allOf`. Kept because it may be revisited
if subclasses multiply or the front's codegen trips on the duplicated
properties.

**How it would work:** a class in `src/OpenApi/ModelDescriber/` implementing
Nelmio's `ModelDescriberInterface` and `ModelRegistryAwareInterface`, tagged
`nelmio_api_doc.model_describer` with a priority above the built-in object
describer (default priority) and below the enum (100) and self-describing
(1000) ones. It supports any class implementing `App\Http\ResponseData`
whose parent also implements it. In `describe()`:

1. Delegate to Nelmio's object describer for the full, flat schema. Nelmio
   uses exactly one model describer per model ("Model describers are
   **not** chained", [model_describer](https://symfony.com/bundles/NelmioApiDocBundle/current/customization/model_describer.html)),
   so this one has to produce the whole schema; delegating keeps type,
   attribute and `#[Ignore]` handling in Nelmio instead of copying it.
2. Register the parent as a `Model` to get its `$ref` (resolved through its
   `models.names` alias, e.g. `ApiError`).
3. Describe the parent into a throwaway schema to learn its serialized
   property names (same naming and `#[Ignore]` rules as the spec, rather
   than raw reflection).
4. Drop those properties from `properties` and `required`, and reshape the
   schema into `allOf: [{$ref: parent}, {type: object, properties: own,
   required: own}]`.

Open/closed: a new subclass with extra fields comes out as `allOf` with no
edit anywhere, unlike the attribute, which every subclass must remember.

What makes it harder than the current decision:

- One more class plus a spec test, for a single subclass today.
- It depends on the built-in object describer's service id
  (`nelmio_api_doc.model_describers.object`), which Nelmio's docs don't
  name (only found in its `config/services.php`), and on delegating to it
  from inside another describer. A spec test asserting the reshaped
  `allOf` would catch a Nelmio upgrade breaking it.
- Check with `debug:container --tag=nelmio_api_doc.model_describer` that
  the class ends up tagged once (autoconfiguration might tag it too).

Also discarded: implementing Nelmio's `SelfDescribingModelInterface` on the
subclass. It produces a clean `allOf`, but the whole schema, the parent's
`$ref` string included (the static `describe()` gets no model registry),
is hand-written and drifts silently when the class changes.

Switching means: remove the class-level `#[OA\Schema]` from each subclass
(and its docblock note), add the describer, point the spec test at the
reshaped `allOf` (own properties only), and update the paragraph in
openapi.md.
