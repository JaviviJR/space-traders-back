# Quickstart: validar la sesión del back para el login del front

## Requisitos previos

- Contenedores levantados (`./dev up` desde el host).
- `symfony/clock` requerido explícitamente (lo ejecuta el usuario, ver [research.workflow.md](research.workflow.md) → D4):

  ```bash
  composer require symfony/clock
  ```

  Doc: https://symfony.com/doc/current/components/clock.html

- Una cuenta verificada en la BD de desarrollo (registro + confirmación de email, ver `doc/authentication.md`).

## Validación automática

Dentro del contenedor php:

```bash
php bin/phpunit tests/Controller/Auth tests/Security/RefreshTokenServiceTest.php tests/OpenApi
composer phpstan
vendor/bin/php-cs-fixer fix
```

Resultado esperado: todo en verde, PHPStan sin errores. Qué cubre cada test: tabla "Testing" de [plan.md](plan.md).

## Escenarios manuales

Base: `http://localhost:<puerto del back>`, origen del front `http://localhost:5173`. Contrato completo en [contracts/auth-session.md](contracts/auth-session.md).

1. **Preflight con credenciales (US1-4)**: `curl -i -X OPTIONS -H 'Origin: http://localhost:5173' -H 'Access-Control-Request-Method: POST' <base>/auth/login` → `Access-Control-Allow-Origin: http://localhost:5173` y `Access-Control-Allow-Credentials: true`.
2. **Origen no permitido (US1-5)**: mismo comando con `Origin: http://evil.example` → sin `Access-Control-Allow-Origin`. `Access-Control-Allow-Credentials: true` puede salir igualmente en el preflight (NelmioCors la pone antes de comprobar el origen), pero no tiene efecto: sin `Allow-Origin` el navegador rechaza la respuesta.
3. **Login persistente (US2-1)**: `POST /auth/login` con `"rememberMe": true` y `Origin` permitido → 200, `Set-Cookie` con `expires` a 30 días, cabeceras CORS con credenciales.
4. **Login de sesión (US2-2)**: sin `rememberMe` → `Set-Cookie` sin `expires` ni `Max-Age`.
5. **`rememberMe` mal formado (US2-5)**: `"rememberMe": "yes"`, incluso con contraseña incorrecta → 400 `http_error`, sin `Set-Cookie`.
6. **Token de 15 min (US3-1)**: decodificar el `accessToken` (payload base64) → `exp - iat = 900`.
7. **Renovación y cierre (US1-2, US1-3)**: `POST /auth/refresh` y `POST /auth/logout` con la cookie del paso 3 y `Origin` permitido → 200 / 204, con cabeceras CORS con credenciales; tras el logout, refresh → 401 `invalid_refresh_token`.
8. **Contrato (US3-2)**: `bin/console nelmio:apidoc:dump --format=json` → en `/auth/login`, `rememberMe` booleano, no requerido, `default: false`, y la descripción del 200 explica ambos modos de cookie.

La caducidad a 24h/30d (US2-3, SC-002, SC-003) solo se valida en los tests automáticos, con la hora simulada.
