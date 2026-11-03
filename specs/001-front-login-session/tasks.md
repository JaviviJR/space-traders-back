---

description: "Lista de tareas de la feature 001-front-login-session"
---

# Tasks: Sesión del back lista para el login del front

**Input**: Design documents from `/specs/001-front-login-session/`

**Prerequisites**: plan.md, spec.md, research.md, research.workflow.md, data-model.md, contracts/auth-session.md, quickstart.md

**Tests**: REQUIRED by the constitution (principle IV) and listed in plan.md → Testing. TDD is not imposed: each test task goes in the same phase as the code it covers.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)
- Rutas relativas a la raíz del repo (proyecto Symfony único: `src/`, `config/`, `tests/`, `doc/`)

Reglas que aplican a todas las tareas (no se repiten en cada una):

- Decisiones cerradas en [research.workflow.md](research.workflow.md) (S1–S3, D1–D4): no se reabren.
- Cualquier escritura de fichero necesita confirmación del usuario (doc/workflow.md → "Confirmations"); las tareas sobre `doc/*.md` se confirman **aparte** de la implementación (doc/workflow.md → "Proposals").
- Los `composer require` los ejecuta el usuario; Claude da el comando y verifica después leyendo `composer.json` y `symfony.lock`.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and basic structure

Sin tareas: el proyecto, Lexik, NelmioCors y NelmioApiDoc ya están instalados. La única instalación (`symfony/clock`) solo la necesita US2 y va en su fase (T006).

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

Sin tareas: no hay entidades, columnas ni migraciones nuevas (data-model.md) y ninguna pieza es compartida por las tres historias.

**Checkpoint**: Foundation ready - user story implementation can now begin

---

## Phase 3: User Story 1 - El front mantiene la sesión desde su propio origen (Priority: P1) 🎯 MVP

**Goal**: el navegador acepta la cookie del login desde un origen permitido distinto al del back y la reenvía en renovación y cierre (FR-001, FR-002, SC-001, SC-006).

**Independent Test**: desde un `Origin` permitido, preflight y peticiones reales a `/auth/login`, `/auth/refresh` y `/auth/logout` devuelven `Access-Control-Allow-Origin` = ese origen y `Access-Control-Allow-Credentials: true`; un origen no permitido no recibe ninguna de las dos; `/api/*` no recibe `Allow-Credentials`.

### Implementation for User Story 1

- [X] T001 [US1] En `config/packages/nelmio_cors.yaml`, añadir bajo `paths` la entrada `'^/auth/': { allow_credentials: true }` **antes** de `'^/': null` (Nelmio usa la primera regex que coincide y combina sus opciones sobre `defaults` con `array_merge`, así que origen, métodos y cabeceras siguen saliendo de `defaults`). Añadir un comentario `#` de una línea junto a la entrada: el orden importa y el ámbito sigue al `Path=/auth` de la cookie del refresh token (research.workflow.md → D2). No tocar `defaults` ni `CORS_ALLOW_ORIGIN`.

### Tests for User Story 1

- [X] T002 [US1] Crear `tests/Controller/Auth/AuthCorsTest.php` (`WebTestCase` + zenstruck/browser, `#[ResetDatabase]`, usuario verificado con `UserFactory`; seguir doc/testing.md). Casos, con un origen permitido por la regex de `CORS_ALLOW_ORIGIN` (p. ej. `http://localhost:5173`):
  - preflight `OPTIONS` con `Access-Control-Request-Method: POST` a `/auth/login`, `/auth/refresh` y `/auth/logout` (data provider) → `Access-Control-Allow-Origin: http://localhost:5173` y `Access-Control-Allow-Credentials: true`; el preflight no lo bloquea el firewall;
  - `POST /auth/login` real con credenciales correctas → 200 con ambas cabeceras y `Set-Cookie` de `refresh_token`;
  - `POST /auth/refresh` y `POST /auth/logout` reales enviando esa cookie → 200 / 204 con ambas cabeceras;
  - origen no permitido (p. ej. `http://evil.example`) en preflight y petición real → ni `Access-Control-Allow-Origin` ni `Access-Control-Allow-Credentials`;
  - nunca `Access-Control-Allow-Origin: *` (aserción explícita en el caso permitido);
  - una ruta fuera de `/auth/` (preflight a una ruta `/api/...`, ver `bin/console debug:router`) con origen permitido → `Access-Control-Allow-Origin` presente y **sin** `Access-Control-Allow-Credentials`.

### Documentation for User Story 1

- [X] T003 [US1] Actualizar `doc/architecture.md` → sección "CORS" (FR-010): el CORS admite credenciales solo bajo `/auth/` y devuelve el origen concreto que hace la petición, nunca un comodín; el ámbito sigue al `Path` de la cookie del refresh token; apuntar a `config/packages/nelmio_cors.yaml` sin copiar valores literales de config (AGENTS.md → "Where documentation lives"). Reescribir el texto existente si contradice esto, no añadir una nota. Confirmación separada de T001.

**Checkpoint**: US1 funcional y probada por sí sola (`php bin/phpunit tests/Controller/Auth/AuthCorsTest.php`).

---

## Phase 4: User Story 2 - La persona elige si mantener la sesión («recordarme») (Priority: P2)

**Goal**: `rememberMe` opcional en el login: `true` → sesión de 30 días con cookie persistente; ausente o `false` → cookie de sesión y servidor que la rechaza a las 24h; valor no booleano → 400 `http_error`; la caducidad no cambia con el uso (FR-003 a FR-007, FR-011, SC-002, SC-003).

**Independent Test**: login con y sin `rememberMe` y comprobar el `Set-Cookie` y el `expiresAt` de la sesión; con la hora simulada, renovar pasadas 24h en modo por defecto (rechazo y cookie borrada) y en modo `rememberMe` (aceptada hasta los 30 días); `rememberMe` no booleano → 400 sin cookie ni sesión.

### Validación de `rememberMe` (FR-007)

- [X] T004 [US2] Crear `src/Security/EventListener/RememberMeChoiceListener.php` (`final class`, no `readonly`): listener de `Symfony\Component\Security\Http\Event\CheckPassportEvent` registrado con `#[AsEventListener]` en el dispatcher del firewall `login` (`dispatcher: 'security.event_dispatcher.login'`) con prioridad **mayor que 2048** (comprobar con `bin/console debug:event-dispatcher --dispatcher=security.event_dispatcher.login` que corre antes de `UserProviderListener`, 2048). Expone `public const FIELD = 'rememberMe'` (único sitio donde vive el nombre del campo). Recibe `RequestStack` por constructor, lee el body de la petición principal con `getPayload()` y, si la clave `rememberMe` está presente y su valor no es `bool` (texto, número o `null` explícito), lanza `Symfony\Component\HttpKernel\Exception\BadRequestHttpException` (el `HttpExceptionErrorMapper` existente la convierte en 400 `http_error`; no añadir mapper ni código de error). Campo ausente → no hace nada. Docblock de clase (research.workflow.md → D1): por qué `CheckPassportEvent`, ese dispatcher y esa prioridad (reproduce el momento en que `json_login` rechaza un email o contraseña que no son texto, antes de cargar el usuario y calcular el hash; moverlo al success handler haría ganar 401/403 sobre el 400 y rompería FR-007), y por qué `RequestStack` y no `RememberMeBadge` (la función `remember_me` nativa está descartada).
- [X] T005 [US2] En `tests/Controller/Auth/LoginControllerTest.php`, añadir casos HTTP para `rememberMe` mal formado (data provider con `"yes"`, `1` y `null` explícito): con credenciales correctas → 400, `error.code` = `http_error` con el envelope de doc/errors.md, sin `Set-Cookie` y sin fila nueva en `refresh_tokens`; con contraseña incorrecta → 400 (no 401); con cuenta sin verificar → 400 (no 403). Añadir también que `rememberMe: true` y `rememberMe: false` con credenciales correctas siguen dando 200 (el listener no rechaza booleanos).

**Checkpoint intermedio**: la validación funciona y los tests existentes siguen verdes; el modo aún no cambia la sesión.

### Modo de sesión (FR-003 a FR-006)

- [X] T006 [US2] Pedir al usuario que ejecute `composer require symfony/clock` en el contenedor php (hoy es dependencia transitiva; AGENTS.md → "Adding features"; doc: https://symfony.com/doc/current/components/clock.html). Esperar el resultado y verificar que `symfony/clock` aparece en `require` de `composer.json` y en `symfony.lock`.
- [X] T007 [US2] Modificar `src/Security/RefreshTokenService.php`: inyectar `Psr\Clock\ClockInterface` por constructor; cambiar `issue(User $user)` a `issue(User $user, bool $rememberMe): RefreshTokenPair` (sin valor por defecto), con `expiresAt` = `$this->clock->now()->modify('+30 days')` si `$rememberMe` y `+24 hours` si no (sustituir la constante `TOKEN_TTL` por dos constantes, una por modo); `verify()` usa `$this->clock->now()` en lugar de `new \DateTimeImmutable()`. El modo no se guarda aparte (data-model.md: "Modo: no se guarda; se deduce de `expiresAt - createdAt`"). Sin cambios en `RefreshToken`, su repositorio ni el esquema.
- [X] T008 [P] [US2] Modificar `src/Security/RefreshTokenCookieFactory.php`: `create(string $plaintext, ?\DateTimeImmutable $expiresAt): Cookie`; con `null` no llama a `withExpires()` (cookie de sesión: ni `Expires` ni `Max-Age`); con fecha, igual que hoy. `Path=/auth`, `Secure`, `HttpOnly`, `SameSite=Strict` sin cambios en ambos casos; `clear()` sin cambios (data-model.md → "Cookie `refresh_token`").
- [X] T009 [US2] Modificar `src/Security/Handler/LoginSuccessHandler.php`: leer `$rememberMe = $request->getPayload()->getBoolean(RememberMeChoiceListener::FIELD)` (ya validado por T004; ausente → `false`), llamar a `issue($user, $rememberMe)` y pasar a la cookie `$rememberMe ? $pair->refreshTokenExpiresAt : null` (depende de T004, T007, T008).
- [X] T010 [US2] En `tests/Security/RefreshTokenServiceTest.php` (`KernelTestCase`): usar `Symfony\Component\Clock\Test\ClockSensitiveTrait` y `mockTime()`; nuevos casos: `issue($user, true)` → `expiresAt` exactamente `now + 30 days`; `issue($user, false)` → exactamente `now + 24 hours`; `verify()` acepta a `24h - 1s` y rechaza a `24h + 1s` en modo por defecto (avanzando el reloj simulado). Actualizar la llamada existente `issue($user)` de `testRevokingAllTokensForAUserInvalidatesThem` a la nueva firma.
- [X] T011 [US2] En `tests/Controller/Auth/LoginControllerTest.php` (`WebTestCase`, con `ClockSensitiveTrait::mockTime()` para fechas exactas): `rememberMe: true` → `Set-Cookie` de `refresh_token` con `expires` = login + 30 días, `path=/auth`, `secure`, `httponly`, `samesite=strict`, y la fila de `refresh_tokens` con ese `expiresAt`; sin `rememberMe` y con `rememberMe: false` → `Set-Cookie` sin `expires` ni `Max-Age` y fila con `expiresAt` = login + 24 horas; login nuevo con una cookie previa presente → sesión nueva con el modo pedido y la anterior conserva su `expiresAt`. Ajustar los asserts de cookie existentes que den por hecho `expires` en el login por defecto.
- [X] T012 [US2] En `tests/Controller/Auth/RefreshControllerTest.php` (`WebTestCase` + `ClockSensitiveTrait`), secuencia login → avanzar reloj → `POST /auth/refresh`: modo por defecto a `24h + 1s` → 401 `invalid_refresh_token` y cookie borrada (`Max-Age=0`); modo por defecto a `24h - 1s` → 200; `rememberMe` a `24h + 1s` → 200 y a `30d + 1s` → 401 con cookie borrada; varias renovaciones no fijan `Set-Cookie` y no cambian el `expiresAt` de la fila (FR-006).
- [X] T013 [P] [US2] En `tests/Controller/Auth/LogoutControllerTest.php` (`WebTestCase`): una sesión iniciada sin `rememberMe` se revoca y la cookie se borra igual que hoy, y una con `rememberMe: true` también; los casos idempotentes existentes (sin cookie, cookie inválida) siguen pasando (FR-011).

### Documentation for User Story 2

- [X] T014 [US2] Actualizar `doc/authentication.md` → "Tokens" y "Refresh token (non-rotating)" (FR-010): campo opcional `rememberMe` del login (booleano, falso por defecto); duración por modo (30 días desde el login con cookie persistente; sin él, cookie de sesión y el servidor la rechaza a las 24h del login); la caducidad se fija en el login y no se amplía con el uso; el modo no se guarda, lo da `expiresAt`; un `rememberMe` no booleano es 400 `http_error` como un email o contraseña mal formados. Reescribir el texto actual que diga 30 días fijos, sin añadir nota; sin copiar valores de config. Confirmación separada de T004–T013.

**Checkpoint**: US1 y US2 funcionan y se prueban por separado.

---

## Phase 5: User Story 3 - Token de acceso corto y contrato documentado para el front (Priority: P3)

**Goal**: el JWT caduca a los 15 minutos y la spec OpenAPI del login documenta `rememberMe` y la cookie en ambos modos (FR-008, FR-009, SC-004, SC-005).

**Independent Test**: decodificar el `accessToken` de login y refresh → `exp - iat === 900`; la spec generada muestra `rememberMe` booleano, opcional, `default: false`, y la descripción del 200 explica ambos modos.

### Implementation for User Story 3

- [ ] T015 [US3] En `config/packages/lexik_jwt_authentication.yaml`, añadir `token_ttl: 900` (decisión cerrada S3; comprobar la clave con `bin/console config:dump-reference lexik_jwt_authentication`).
- [ ] T016 [P] [US3] En `src/Controller/Auth/LoginController.php`, en el `OA\RequestBody` inline: añadir `new OA\Property(property: 'rememberMe', type: 'boolean', default: false, description: …)` sin incluirla en `required` (que sigue siendo `['email', 'password']`); reescribir la `description` del `DataResponse` 200 para explicar ambos modos: con `rememberMe: true`, cookie persistente que caduca a los 30 días del login; sin él o con `false`, cookie de sesión (sin `Expires`) que el servidor deja de aceptar a las 24 horas del login. No documentar el 400 `http_error` (doc/openapi.md → "Error codes"; spec FR-009). Sin DTO nuevo ni alias en `models.names`.

### Tests for User Story 3

- [ ] T017 [US3] En `tests/Controller/Auth/LoginControllerTest.php`: decodificar el payload del `accessToken` (segmento base64url) de un login correcto y comprobar `exp - iat === 900` (sin hora simulada: Lexik usa su propio `NativeClock`, research.workflow.md → D4).
- [ ] T018 [P] [US3] En `tests/Controller/Auth/RefreshControllerTest.php`: el `accessToken` de un `POST /auth/refresh` correcto cumple `exp - iat === 900`.
- [ ] T019 [P] [US3] Crear `tests/OpenApi/LoginRequestSchemaTest.php` (`KernelTestCase` con el trait `tests/Support/DumpsOpenApiSpec.php`, como `tests/OpenApi/AuthenticationErrorResponseTest.php`): en el request body de la operación `login`, `rememberMe` es `type: boolean`, `default: false` y no está en `required`; `email` y `password` siguen en `required`; la descripción del 200 menciona ambos modos de cookie.

**Checkpoint**: las tres historias funcionan y se prueban por separado.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [ ] T020 Comprobar que `doc/authentication.md` y `doc/architecture.md` cubren todo FR-010 / US3-3 (duraciones de ambos modos, token de acceso de 15 minutos —ya prometido en "Tokens", research.workflow.md → S3—, CORS con credenciales solo para orígenes permitidos); si falta algo, proponer el cambio con confirmación separada.
- [ ] T021 Ejecutar la validación de [quickstart.md](quickstart.md): `php bin/phpunit` (suite completa), `composer phpstan` (nivel 9, sin ignores nuevos) y `vendor/bin/php-cs-fixer fix` (listar los ficheros que toque fuera del alcance, sin revertirlos); después, los escenarios manuales 1–8 con `curl` contra el servidor de desarrollo si está accesible desde el contenedor php, o indicar al usuario que los ejecute desde el host.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)** y **Foundational (Phase 2)**: sin tareas.
- **User Stories (Phase 3+)**: no dependen entre sí a nivel de código; se hacen en orden de prioridad (P1 → P2 → P3).
- **Polish (Phase 6)**: depende de las tres historias.

### User Story Dependencies

- **US1 (P1)**: sin dependencias. Solo config y un test nuevo.
- **US2 (P2)**: sin dependencias de código con US1 (se prueba contra el back sin `Origin`); en el front solo tiene efecto con US1 hecha.
- **US3 (P3)**: sin dependencias de código. T016 documenta el comportamiento que implementa US2, así que tiene sentido después de US2; T017/T018 tocan los mismos ficheros de test que US2 (T011, T012), por eso van después.

### Within Each User Story

- US1: T001 → T002 → T003.
- US2: T004 → T005 (validación); T006 (instalación) → T007; T008 en paralelo con T007; T009 depende de T004, T007 y T008; T010–T013 después de T009 (T010 solo necesita T007); T014 al final.
- US3: T015 → T017, T018; T016 → T019.

### Parallel Opportunities

- T008 [P] con T007 (ficheros distintos, sin dependencia).
- T013 [P] con T010–T012 (otro fichero de test).
- T016 [P] con T015; T018 [P] y T019 [P] con T017.
- Los tests de US1 y US2 que tocan `LoginControllerTest.php` (T005, T011, T017) no son paralelos entre sí: mismo fichero.

---

## Parallel Example: User Story 2

```bash
# Tras T007 (servicio), en paralelo:
Task: "Cookie de sesión con null en src/Security/RefreshTokenCookieFactory.php"   # T008
# Tras T009 (handler), en paralelo:
Task: "Logout en ambos modos en tests/Controller/Auth/LogoutControllerTest.php"  # T013
Task: "Caducidad con reloj simulado en tests/Controller/Auth/RefreshControllerTest.php"  # T012
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1 y 2: sin tareas.
2. Phase 3: US1 (CORS con credenciales en `/auth/`).
3. **STOP and VALIDATE**: `AuthCorsTest` verde; el front ya puede guardar y reenviar la cookie (con sesiones de 30 días, como hoy).

### Incremental Delivery

1. US1 → el front puede iniciar, renovar y cerrar sesión desde su origen (MVP).
2. US2 → «recordarme» y límite de 24h en el modo por defecto.
3. US3 → token de 15 minutos y contrato OpenAPI para escribir la spec del front.
4. Polish → checks completos y quickstart.

### Sessions

| Session | Tasks | Close | Command |
|---|---|---|---|
| S1 | T001–T003 (US1: CORS con credenciales) | php-cs-fixer, PHPStan, PHPUnit | `/feature-session specs/001-front-login-session S1` |
| S2 | T004–T005 (US2: validación de `rememberMe`) | php-cs-fixer, PHPStan, PHPUnit | `/feature-session specs/001-front-login-session S2` |
| S3 | T006–T014 (US2: modo de sesión, reloj, cookie y doc) | php-cs-fixer, PHPStan, PHPUnit | `/feature-session specs/001-front-login-session S3` |
| S4 | T015–T021 (US3: token de 15 min y OpenAPI; Polish) | php-cs-fixer, PHPStan, PHPUnit, quickstart.md | `/feature-session specs/001-front-login-session S4` |

S2 y S3 parten US2 en dos: la validación (T004–T005) deja el proyecto verde por sí sola sin tocar el servicio, y el cambio de firma de `issue()` (T007) obliga a cambiar el handler y sus tests en la misma sesión (S3). Polish se une a S4 por ser de dos tareas.

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Mark each finished task `[X]` here; Claude commits each session once the user has reviewed it and says it's all OK
- Stop at any checkpoint to validate story independently
- Avoid: vague tasks, same file conflicts, cross-story dependencies that break independence
