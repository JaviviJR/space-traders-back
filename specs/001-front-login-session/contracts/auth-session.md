# Contrato HTTP: login, renovación, cierre de sesión y CORS

Endpoints back-only (doc/architecture.md). Envelope y errores según doc/errors.md. Lo que la spec OpenAPI generada documenta se marca **[OpenAPI]**; lo demás es comportamiento en tiempo de ejecución.

## `POST /auth/login`

**Request** (`Content-Type: application/json`) **[OpenAPI]**

```json
{ "email": "ana@example.com", "password": "secreto123", "rememberMe": true }
```

- `rememberMe`: booleano, opcional, `default: false`, no en `required`.

**Responses**

| Status | Body | Cookie | Doc |
|--------|------|--------|-----|
| 200 | `{"data": {"accessToken": "<JWT, exp = iat + 900>"}}` | `rememberMe: true` → `refresh_token=…; expires=<login + 30d>; path=/auth; secure; httponly; samesite=strict`. Ausente o `false` → igual pero sin `expires` ni `Max-Age` (cookie de sesión; el servidor la rechaza a las 24h del login) | **[OpenAPI]** la descripción del 200 explica ambos modos |
| 400 | `{"error": {"code": "http_error", "message": "…"}}` | ninguna | no documentado (error de transporte): `rememberMe` presente y no booleano, con cualquier credencial |
| 401 | `invalid_credentials` | ninguna | **[OpenAPI]** sin cambios |
| 403 | `email_not_verified` | ninguna | **[OpenAPI]** sin cambios |

Precedencia: el 400 de `rememberMe` gana sobre 401/403 (se valida antes de cargar el usuario), igual que un email o contraseña que no son texto.

## `POST /auth/refresh`

Sin cambios de forma. Envía la cookie; no la vuelve a fijar.

| Status | Cuándo |
|--------|--------|
| 200 | sesión válida: `{"data": {"accessToken": "<JWT, exp = iat + 900>"}}`; `expiresAt` de la sesión no cambia |
| 401 `invalid_refresh_token` + cookie borrada | sin cookie, desconocida, revocada o caducada (24h desde el login sin `rememberMe`, 30d con él) |

## `POST /auth/logout`

Sin cambios: 204 idempotente, revoca solo la sesión de la cookie presentada y la borra, en cualquier modo.

## CORS en `/auth/*`

Con `Origin` permitido (regex de `CORS_ALLOW_ORIGIN`):

| Petición | Cabeceras de respuesta |
|----------|------------------------|
| Preflight `OPTIONS` a `/auth/login`, `/auth/refresh`, `/auth/logout` | `Access-Control-Allow-Origin: <ese origen>`, `Access-Control-Allow-Credentials: true`, métodos y cabeceras de `defaults` |
| Petición real a esos endpoints | `Access-Control-Allow-Origin: <ese origen>`, `Access-Control-Allow-Credentials: true` |

- Nunca `Access-Control-Allow-Origin: *`.
- Origen no permitido: ni `Access-Control-Allow-Origin` ni `Access-Control-Allow-Credentials`.
- Fuera de `/auth/` (p. ej. `/api/*`): `Access-Control-Allow-Origin` como hoy, sin `Access-Control-Allow-Credentials`.
