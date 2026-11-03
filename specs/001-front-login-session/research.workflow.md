# Research (workflow): Sesión del back lista para el login del front

**Feature**: `specs/001-front-login-session` | **Date**: 2026-11-03

Versión de [research.md](research.md) que aplica AGENTS.md y `doc/workflow.md`: incluye las decisiones cerradas en la spec, cita la documentación oficial (o el fichero de `vendor/` como inferencia desde el código) y dice dónde más se registra cada decisión.

## Decisiones cerradas en la spec (no se reabren)

### S1. Credenciales en el CORS del back con origen explícito

- **Decision**: el back autoriza credenciales en CORS y devuelve el origen concreto permitido, nunca `*`.
- **Rationale**: el front se sirve desde otro origen y necesita que el navegador guarde y reenvíe la cookie; la spec (Assumptions) lo fija.
- **Alternatives considered**: proxy de desarrollo con mismo origen (descartado en la spec).
- **Dónde se registra**: `doc/architecture.md` → "CORS" (FR-010).

### S2. «recordarme» como campo booleano opcional, sin guardar el modo

- **Decision**: `rememberMe` en el body del login, booleano, falso por defecto; el modo se deduce de `expiresAt`.
- **Rationale**: protege equipos compartidos y hace la persistencia explícita; sin columna nueva ni migración.
- **Alternatives considered**: no ofrecer la opción; el `remember_me` nativo de Symfony (ambos descartados en la spec).
- **Dónde se registra**: `doc/authentication.md` → "Tokens" (FR-010) y la spec OpenAPI del login (FR-009).

### S3. Token de acceso de 15 minutos configurado en Lexik

- **Decision**: `token_ttl: 900`.
- **Rationale**: `doc/authentication.md` ya promete 15 minutos; limita la ventana de un token robado, ya que el refresh token no rota.
- **Alternatives considered**: dejar 1 hora y corregir la doc (descartado en la spec).
- **Dónde se registra**: ya está en `doc/authentication.md` → "Tokens"; el valor vive solo en `config/packages/lexik_jwt_authentication.yaml` (AGENTS.md: no copiar valores de config a la doc).

## Decisiones del plan

### D1. Dónde se lee y se valida `rememberMe`

- **Decision**: listener de `CheckPassportEvent` (`#[AsEventListener]`) en el dispatcher del firewall `login` (`security.event_dispatcher.login`), con prioridad por encima de 2048, en `src/Security/EventListener/RememberMeChoiceListener.php`. Lee el payload con `RequestStack` y, si `rememberMe` está presente y no es `bool`, lanza `BadRequestHttpException`. `LoginSuccessHandler` lee el valor ya validado con `$request->getPayload()->getBoolean(<constante del listener>)`; el nombre del campo vive en esa única constante.
- **Rationale**:
  - FR-007 pide el mismo trato que un email o contraseña que no son texto. Según `vendor/symfony/security-http/Authenticator/JsonLoginAuthenticator.php` (inferencia desde el código), `json_login` los rechaza con `BadRequestHttpException` en `authenticate()`, antes de comprobar credenciales. El listener reproduce ese momento.
  - La doc de Symfony ([Security → Security Events](https://symfony.com/doc/current/security.html#security-events)) documenta `CheckPassportEvent` y un dispatcher por firewall. `bin/console debug:event-dispatcher` sobre `security.event_dispatcher.login` muestra que los listeners del framework van de 2048 (`UserProviderListener`) a 0 (`CheckCredentialsListener`), así que por encima de 2048 corre antes de cargar el usuario y de calcular el hash.
  - Según `vendor/symfony/security-http/Authentication/AuthenticatorManager.php` (inferencia desde el código), el `try` del paso de autenticación solo captura `AuthenticationException`; una `BadRequestHttpException` sale a `kernel.exception` y el `HttpExceptionErrorMapper` existente responde 400 `http_error` (doc/errors.md).
  - `CheckPassportEvent` no lleva la `Request`; se usa `RequestStack` (servicio documentado) en lugar de `RememberMeBadge::$parameters`, que pertenece a la función `remember_me` nativa descartada en S2.
- **Alternatives considered**:
  - Validar en `LoginSuccessHandler`: un solo fichero, pero valida después de la contraseña; con credenciales incorrectas o cuenta sin verificar ganaría 401/403 sobre el 400, y rechazaría una petición ya autenticada.
  - Autenticador propio en lugar de `json_login`: reimplementa decodificación, validación de email/contraseña y badges que el autenticador integrado ya da.
  - Leer `RememberMeBadge::$parameters`: evita volver a decodificar el JSON, pero acopla a la función nativa descartada.
- **Dónde se registra**: docblock de `RememberMeChoiceListener` (por qué `CheckPassportEvent`, ese dispatcher y esa prioridad: quitarlo invitaría a "simplificar" moviéndolo al success handler, que rompe FR-007). Sin `doc/*.md`: es un solo caso.

### D2. Alcance de `allow_credentials` en el CORS

- **Decision**: entrada `'^/auth/': { allow_credentials: true }` en `nelmio_cors.paths`, antes de `'^/'`.
- **Rationale**:
  - La doc de NelmioCorsBundle presenta `paths` como opciones por ruta sobre `defaults`; no opina sobre el ámbito de `allow_credentials` (decisión del proyecto).
  - Según `vendor/nelmio/cors-bundle/Options/ConfigProvider.php` (inferencia desde el código), las opciones de la ruta se combinan con `array_merge` sobre `defaults` y gana la primera regex que coincide; según `vendor/nelmio/cors-bundle/EventListener/CorsListener.php`, `Access-Control-Allow-Credentials` se pone en preflight y en la respuesta real, y `Access-Control-Allow-Origin` lleva el origen de la petición.
  - El ámbito coincide con el `Path=/auth` de la cookie, así que no se desalinean.
- **Alternatives considered**:
  - En `defaults`: una línea, pero autoriza credenciales también en `/api/*`, que usa Bearer y nunca recibe la cookie. Ganaría si el front necesitara cookies en `/api/*`.
  - `^/auth/(login|refresh|logout)$`: literal a FR-001, pero duplica la lista de `security.yaml`. Ganaría si algún endpoint de `/auth/` no debiera recibir credenciales.
- **Dónde se registra**: `#` en `config/packages/nelmio_cors.yaml` (el orden importa y el ámbito sigue al `Path` de la cookie); `doc/architecture.md` → "CORS" (FR-010).

### D3. Cómo llega el modo al servicio y a la cookie

- **Decision**: `RefreshTokenService::issue(User $user, bool $rememberMe)` elige `+30 days` o `+24 hours`; `RefreshTokenCookieFactory::create(string $plaintext, ?\DateTimeImmutable $expiresAt)` emite cookie de sesión con `null`; `LoginSuccessHandler` pasa `$rememberMe ? $pair->refreshTokenExpiresAt : null`.
- **Rationale**:
  - Ni la doc de Symfony ni la de PHP opinan sobre `bool` frente a enum: decisión del proyecto, resuelta por la regla de AGENTS.md de la estructura más pequeña y el principio III de la constitución.
  - La doc de HttpFoundation ([Cookies](https://symfony.com/doc/current/components/http_foundation.html#setting-cookies)) describe `expire: 0` como cookie que dura hasta que se cierra el navegador; `Cookie` no emite `Expires` ni `Max-Age` en ese caso.
  - Cada capa tiene una sola regla (duración en el servicio, tipo de cookie en el handler) y el servicio sigue sin saber nada de HTTP (doc/errors.md → "Controllers don't translate exceptions").
- **Alternatives considered**:
  - Enum `SessionLifetime`: el modo tiene nombre y una sola regla, pero son más tipos para dos valores definitivos. Ganaría con un tercer modo o si el modo hiciera falta en más sitios.
  - Que el servicio calcule la caducidad de la cookie: igual de pequeño, pero mete transporte HTTP en el servicio.
- **Dónde se registra**: `doc/authentication.md` → "Tokens" (duraciones por modo y cookie de sesión, FR-010). Sin docblock: la firma `?\DateTimeImmutable` y el nombre del parámetro bastan.

### D4. Cómo se prueban las caducidades

- **Decision**: `RefreshTokenService` recibe `Psr\Clock\ClockInterface` y usa `$this->clock->now()` en `issue()` y `verify()`; los tests usan `ClockSensitiveTrait::mockTime()`. `symfony/clock` se añade con `composer require symfony/clock` (hoy es transitivo; AGENTS.md → "Adding features").
- **Rationale**:
  - La doc del componente Clock ([Clock](https://symfony.com/doc/current/components/clock.html)) recomienda inyectar `ClockInterface` para hacer testeables los servicios y documenta `ClockSensitiveTrait`/`mockTime()` para `KernelTestCase` y `WebTestCase`.
  - Permite el test de extremo a extremo de US2-3/SC-002 (login → +24h1s → refresh rechazado) y fechas exactas sin márgenes.
  - Según `vendor/lexik/jwt-authentication-bundle/Services/JWSProvider/LcobucciJWSProvider.php` (inferencia desde el código), Lexik crea su propio `NativeClock`; por eso FR-008 se prueba con `exp - iat === 900` y no con hora simulada.
- **Alternatives considered**:
  - Márgenes de tolerancia y un `RefreshToken` ya caducado creado a mano: no toca el servicio, pero no prueba la secuencia login → renovación, requiere una factoría Foundry nueva y puede ser intermitente. Ganaría si el servicio no pudiera tocarse.
- **Dónde se registra**: en ningún sitio más; inyectar el reloj es el uso documentado del componente y no hay trampa local que explicar.

## Dependencias e integraciones

- **NelmioCorsBundle**: responde el preflight en su listener de `kernel.request`, antes del firewall, así que `access_control` no bloquea `OPTIONS` (se cubre en `AuthCorsTest`).
- **NelmioApiDocBundle**: el body del login es un `OA\JsonContent` inline (sin DTO), así que no necesita alias en `models.names` (doc/openapi.md → "Schema names"); el 400 de transporte no se documenta (doc/openapi.md → "Error codes").
- **Doctrine**: sin cambio de esquema ni migración; las sesiones ya emitidas conservan sus 30 días (spec, Assumptions).
