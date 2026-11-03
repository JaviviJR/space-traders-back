# Data Model: Sesión del back lista para el login del front

Sin entidades, columnas ni migraciones nuevas. Cambia el significado de un campo existente y la forma de la cookie.

## Sesión (`App\Entity\RefreshToken`, tabla `refresh_tokens`)

| Campo | Tipo | Cambio |
|-------|------|--------|
| `user` | `User` (ManyToOne) | — |
| `tokenHash` | `string(64)`, único | — |
| `expiresAt` | `DateTimeImmutable` | Se fija en el login según el modo: `now + 30 days` con `rememberMe: true`, `now + 24 hours` si no. No cambia nunca después (FR-006). |
| `revokedAt` | `?DateTimeImmutable` | — |
| `createdAt` | `?DateTimeImmutable` (Gedmo) | — |

- **Modo**: no se guarda; se deduce de `expiresAt - createdAt` (FR-006, spec Assumptions).
- **Validez**: `isValid($now)` sin cambios (`revokedAt === null && expiresAt > $now`); `$now` pasa a venir de `ClockInterface`.
- **Sesiones existentes**: conservan sus 30 días; no se migran.

### Transiciones de estado

```text
login (rememberMe: true)   → activa, expiresAt = login + 30d
login (rememberMe ausente/false) → activa, expiresAt = login + 24h
activa --refresh--> activa (sin cambios en expiresAt; no se re-emite la cookie)
activa --logout de esa cookie--> revocada
activa --reset del universo--> revocada (todas las del usuario, ambos modos)
activa --now ≥ expiresAt--> caducada (refresh → 401 invalid_refresh_token + cookie borrada)
```

## Cookie `refresh_token`

| Atributo | `rememberMe: true` | `rememberMe` ausente o `false` |
|----------|--------------------|-------------------------------|
| Valor | token opaco (256 bits) | igual |
| `Path` | `/auth` | igual |
| `Secure`, `HttpOnly`, `SameSite=Strict` | sí | igual |
| `Expires` / `Max-Age` | `Expires` = `expiresAt` de la sesión | ninguno (cookie de sesión) |

El borrado (refresh rechazado, logout) no cambia: `refresh_token=deleted`, `Max-Age=0`.

## Token de acceso (JWT, no persistido)

- Caducidad: `exp = iat + 900` (15 minutos), en login y en refresh.

## Entrada del login

| Campo | Tipo | Requerido | Regla |
|-------|------|-----------|-------|
| `email` | string | sí | sin cambios (`json_login`) |
| `password` | string | sí | sin cambios (`json_login`) |
| `rememberMe` | boolean | no, por defecto `false` | presente y no booleano (texto, número, `null` explícito) → 400 `http_error`, antes de comprobar credenciales, sin sesión ni cookie |
