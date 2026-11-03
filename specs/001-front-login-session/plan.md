# Implementation Plan: Sesión del back lista para el login del front

**Branch**: `login` | **Date**: 2026-11-03 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/001-front-login-session/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

El front (SPA en otro origen) necesita que el navegador guarde y reenvíe la cookie del refresh token, poder elegir una sesión no persistente («recordarme») y un token de acceso de 15 minutos. Enfoque técnico (detalle en [research.workflow.md](research.workflow.md)):

- **CORS**: `allow_credentials: true` solo en una entrada `'^/auth/'` de NelmioCors, antes de `'^/'`; el origen concreto ya se devuelve porque `allow_origin` es una regex.
- **`rememberMe`**: un listener de `CheckPassportEvent` en el dispatcher del firewall `login`, con prioridad por encima de 2048, valida que sea booleano o ausente antes de cargar el usuario y comprobar la contraseña; si no, `BadRequestHttpException` → 400 `http_error` por el `HttpExceptionErrorMapper` existente.
- **Modo de sesión**: `RefreshTokenService::issue(User, bool $rememberMe)` fija `expiresAt` a 30 días o 24 horas; `RefreshTokenCookieFactory::create(string, ?\DateTimeImmutable)` emite cookie de sesión con `null`; `LoginSuccessHandler` decide qué fecha pasar.
- **Tiempo**: `RefreshTokenService` usa `Psr\Clock\ClockInterface` (`symfony/clock`) para poder simular la hora en los tests.
- **Token de acceso**: `token_ttl: 900` en la config de Lexik.
- **Contrato**: `rememberMe` en el `OA\RequestBody` del login y descripción de la cookie en cada modo; `doc/authentication.md` y `doc/architecture.md` actualizados en el mismo cambio (FR-010).

## Technical Context

**Language/Version**: PHP 8.4+ (contenedor `php`), Symfony 8.1.9

**Primary Dependencies**: SecurityBundle (`json_login`), LexikJWTAuthenticationBundle 3.2, NelmioCorsBundle 2.6, NelmioApiDocBundle 5.13, Doctrine ORM, `symfony/clock` (hoy transitivo; se requiere explícitamente con `composer require symfony/clock`)

**Storage**: PostgreSQL vía Doctrine; tabla `refresh_tokens` sin cambios de esquema (sin migración)

**Testing**: PHPUnit con `WebTestCase` + zenstruck/browser para HTTP, `KernelTestCase` para servicios, Foundry para fixtures, `ClockSensitiveTrait::mockTime()` para la hora

**Target Platform**: servidor Linux (contenedores Docker de `docker/compose.yml`)

**Project Type**: web-service (JSON API, BFF del front Vue)

**Performance Goals**: N/A (sin cambios de carga; el listener evita además calcular el hash de contraseña con un body mal formado)

**Constraints**: refresh token sin rotación; la renovación no vuelve a fijar la cookie; sesiones ya emitidas conservan sus 30 días; mismo site front/back en desarrollo (SameSite=Strict se mantiene)

**Scale/Scope**: 3 endpoints existentes (`/auth/login`, `/auth/refresh`, `/auth/logout`), 1 clase nueva, ~6 ficheros de `src/`/`config/` modificados

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Gate | Estado | Cómo se cumple |
|------|--------|----------------|
| I. Idiomatic Symfony, thin controllers | PASS | Sin código CORS propio (NelmioCors); `symfony/clock` vía `composer require` (Flex); listener con `#[AsEventListener]`; `ClockInterface` autowired; controladores sin cambios de lógica. |
| II. The BFF contract is kept | PASS | Login, refresh y logout son back-only (doc/architecture.md). El 400 sale por `HttpExceptionErrorMapper` como `http_error` con el envelope; sin código de error nuevo. OpenAPI: `rememberMe` en el body inline del login, el 400 de transporte no se documenta (doc/openapi.md → "Error codes"); sin DTO nuevo, así que sin alias en `models.names`. |
| III. The smallest structure that satisfies SOLID | PASS | Una clase nueva (el listener), justificada frente a validar en el success handler (rompería el orden de FR-007). Modo como `bool`, sin enum (decisión 3). Sin interfaces nuevas. |
| IV. Tested the way a caller would use it | PASS | Ver "Testing": cada cambio con su test HTTP o de servicio. |
| V. Quality checked by tools | PASS | Cierre con PHPUnit, PHPStan nivel 9 y php-cs-fixer, sin ignores nuevos. |

**Re-check post-design (Phase 1)**: PASS. El diseño de [data-model.md](data-model.md) y [contracts/](contracts/) no añade entidades, columnas, interfaces ni códigos de error.

## Project Structure

### Documentation (this feature)

```text
specs/001-front-login-session/
├── plan.md                 # This file (/speckit-plan command output)
├── research.md             # Phase 0 output, formato spec-kit (inglés)
├── research.workflow.md    # Phase 0 output, reglas del proyecto (español)
├── data-model.md           # Phase 1 output
├── quickstart.md           # Phase 1 output
├── contracts/
│   └── auth-session.md     # Phase 1 output: login, refresh, logout y CORS
└── tasks.md                # Phase 2 output (/speckit-tasks command - NOT created by /speckit-plan)
```

### Source Code (repository root)

```text
config/packages/
├── lexik_jwt_authentication.yaml        # token_ttl: 900
└── nelmio_cors.yaml                     # entrada '^/auth/' con allow_credentials antes de '^/'

src/
├── Controller/Auth/LoginController.php  # OpenAPI: rememberMe + cookie por modo
├── Security/
│   ├── EventListener/
│   │   └── RememberMeChoiceListener.php # NUEVO: valida rememberMe en CheckPassportEvent (firewall login)
│   ├── Handler/LoginSuccessHandler.php  # lee rememberMe, pasa fecha o null a la cookie
│   ├── RefreshTokenCookieFactory.php    # create(string, ?DateTimeImmutable): null = cookie de sesión
│   └── RefreshTokenService.php          # issue(User, bool), ClockInterface en issue()/verify()

doc/
├── authentication.md                    # duraciones por modo, rememberMe, cookie de sesión
└── architecture.md                      # CORS con credenciales solo en /auth/

tests/
├── Controller/Auth/
│   ├── AuthCorsTest.php                 # NUEVO
│   ├── LoginControllerTest.php
│   ├── LogoutControllerTest.php
│   └── RefreshControllerTest.php
├── OpenApi/LoginRequestSchemaTest.php   # NUEVO
└── Security/RefreshTokenServiceTest.php
```

**Structure Decision**: proyecto único Symfony. El listener va en `src/Security/EventListener/` junto al resto de piezas de seguridad (`Checker/`, `Handler/`), no en `src/EventListener/`, que agrupa las entradas de errores.

## Testing

| Change | Test (path) | Type | Why this type |
|--------|-------------|------|---------------|
| `token_ttl: 900` (FR-008) | `tests/Controller/Auth/LoginControllerTest.php`, `tests/Controller/Auth/RefreshControllerTest.php` | WebTestCase | el JWT se emite en login y refresh; se decodifica la respuesta y se comprueba `exp - iat === 900` |
| `RememberMeChoiceListener` (FR-003, FR-007) | `tests/Controller/Auth/LoginControllerTest.php` | WebTestCase | solo existe dentro del firewall; texto, número y `null` explícito → 400 `http_error`, sin `Set-Cookie` ni fila en BD; también con contraseña incorrecta (precedencia sobre 401) |
| `LoginSuccessHandler` + `RefreshTokenCookieFactory::create(?expiresAt)` (FR-004, FR-005) | `tests/Controller/Auth/LoginControllerTest.php` | WebTestCase | la forma de la cookie solo se ve en el `Set-Cookie` de la respuesta: con `expires` en modo `rememberMe`, sin `expires` ni `Max-Age` sin el campo y con `false` |
| `RefreshTokenService::issue(User, bool)` + `ClockInterface` | `tests/Security/RefreshTokenServiceTest.php` | KernelTestCase | llamada al servicio desde el contenedor con `mockTime()`: `expiresAt` exacto a `now + 24h` / `now + 30d` |
| Caducidad aplicada en la renovación (FR-005, FR-006, SC-002, SC-003) | `tests/Controller/Auth/RefreshControllerTest.php` | WebTestCase + `ClockSensitiveTrait` | login → avanzar la hora → `POST /auth/refresh`: por defecto rechazado y cookie borrada a las 24h+1s; `rememberMe` válido a 24h+1s y rechazado a 30d+1s; renovar no fija cookie ni cambia `expiresAt` |
| Logout en ambos modos (FR-011) | `tests/Controller/Auth/LogoutControllerTest.php` | WebTestCase | sesión iniciada sin `rememberMe` se revoca y la cookie se borra igual que hoy |
| CORS con credenciales en `/auth/` (FR-001, FR-002, SC-006) | `tests/Controller/Auth/AuthCorsTest.php` | WebTestCase | las cabeceras CORS solo existen en la respuesta: preflight y llamadas reales a login/refresh/logout desde origen permitido → `Allow-Origin` = ese origen y `Allow-Credentials: true`; origen no permitido → ninguna; `/api/*` → sin `Allow-Credentials` |
| OpenAPI del login (FR-009) | `tests/OpenApi/LoginRequestSchemaTest.php` | KernelTestCase (con `DumpsOpenApiSpec`) | el contrato es la spec generada: `rememberMe` booleano, no requerido, `default: false` |

**Session close checks**: `php bin/phpunit`, `composer phpstan` (nivel 9, sin ignores nuevos) y `vendor/bin/php-cs-fixer fix`, todos limpios.

## Complexity Tracking

Sin violaciones de la constitución que justificar.
