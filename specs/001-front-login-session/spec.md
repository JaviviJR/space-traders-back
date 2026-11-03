# Feature Specification: Sesión del back lista para el login del front

**Feature Branch**: `login`

**Created**: 2026-11-03

**Status**: Draft

**Input**: User description: "Preparar la sesión del back para que el front (SPA Vue, servido desde un origen distinto al back) pueda implementar el login. Hoy el back emite el refresh token en una cookie httpOnly, pero el front no puede usarla y no existe forma de elegir una sesión no persistente. (Descripción completa: necesidades 1–4, decisiones cerradas y fuera de alcance, recogidas abajo.)"

## Clarifications

### Session 2026-11-03

- Q: ¿Con qué error se rechaza un «recordarme» no booleano en el login? → A: 400 `http_error` (body mal formado), igual que un email o contraseña que no son texto en ese endpoint; no 422 `validation_failed`, que es el de los endpoints que validan su body como DTO.
- Q: ¿Cómo se llama el campo «recordarme» en el body del login? → A: `rememberMe`, en camelCase como `accessToken`.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - El front mantiene la sesión desde su propio origen (Priority: P1)

Una persona inicia sesión en el front, que se sirve desde un origen distinto al del back. El navegador guarda la cookie de sesión que devuelve el login y la envía en las llamadas posteriores de renovación y de cierre de sesión, de modo que el front puede renovar el token de acceso sin pedir de nuevo la contraseña y cerrar la sesión de verdad.

**Why this priority**: sin esto el front no puede implementar ningún login útil: la cookie se descarta y cada token de acceso caducado obliga a volver a introducir credenciales. Es el bloqueo actual.

**Independent Test**: desde un origen permitido distinto al del back, hacer login, después renovar y después cerrar sesión, comprobando que la respuesta del login es aceptada por el navegador con credenciales y que la renovación y el cierre reciben la cookie.

**Acceptance Scenarios**:

1. **Given** una cuenta verificada y una petición desde un origen permitido, **When** el front envía el login con credenciales correctas, **Then** la respuesta autoriza explícitamente el uso de credenciales para ese origen concreto (nunca para «cualquier origen») y fija la cookie del refresh token.
2. **Given** una sesión iniciada desde un origen permitido, **When** el front llama a la renovación con la cookie, **Then** recibe un nuevo token de acceso y la respuesta autoriza credenciales para ese origen.
3. **Given** una sesión iniciada desde un origen permitido, **When** el front llama al cierre de sesión con la cookie, **Then** la sesión queda revocada, la cookie se borra y la respuesta autoriza credenciales para ese origen.
4. **Given** una petición previa (preflight) de un origen permitido hacia login, renovación o cierre, **When** el back la responde, **Then** indica que se admiten credenciales para ese origen.
5. **Given** una petición desde un origen no permitido, **When** llama a cualquiera de los tres endpoints, **Then** la respuesta no autoriza ni ese origen ni el uso de credenciales.

---

### User Story 2 - La persona elige si mantener la sesión («recordarme») (Priority: P2)

Al iniciar sesión, la persona decide si quiere que la sesión sobreviva al cierre del navegador. Si lo marca, la sesión dura 30 días desde el login. Si no lo marca (o el cliente no envía nada), la sesión termina al cerrar el navegador y, en cualquier caso, el servidor deja de aceptarla a las 24 horas del login. Ninguna sesión se alarga por usarla.

**Why this priority**: protege a quien usa un equipo compartido y hace que la persistencia sea una elección explícita. Depende de que la cookie ya funcione entre orígenes (P1) para tener efecto en el front, aunque se puede probar por sí sola contra el back.

**Independent Test**: hacer login con y sin la opción y comprobar la caducidad de la cookie devuelta y la que el servidor aplica a la sesión, incluyendo una renovación pasadas las 24 horas en el modo por defecto.

**Acceptance Scenarios**:

1. **Given** credenciales correctas, **When** el login se envía con «recordarme» activado, **Then** la cookie es persistente con caducidad a los 30 días del login, y el servidor acepta renovaciones hasta ese momento.
2. **Given** credenciales correctas, **When** el login se envía sin el campo «recordarme» o con él desactivado, **Then** la cookie es de sesión (sin fecha de caducidad, el navegador la descarta al cerrarse) y el servidor deja de aceptar esa sesión a las 24 horas del login.
3. **Given** una sesión sin «recordarme» iniciada hace más de 24 horas cuya cookie el navegador ha conservado (p. ej. al restaurar la sesión del navegador), **When** el front llama a la renovación, **Then** se rechaza igual que cualquier sesión caducada y se borra la cookie.
4. **Given** una sesión de cualquier modo, **When** se renueva el token de acceso una o varias veces, **Then** la caducidad de la sesión no cambia: sigue contando desde el login, y la cookie no pasa de un modo al otro.
5. **Given** un login cuyo campo «recordarme» trae un valor que no es booleano, **When** se envía, **Then** se rechaza como body mal formado (400 con código `http_error` y el formato de error del back, igual que un email o contraseña que no son texto; no 422 `validation_failed`) y no se inicia sesión ni se fija cookie.

---

### User Story 3 - Token de acceso corto y contrato documentado para el front (Priority: P3)

El token de acceso caduca a los 15 minutos, como ya promete la documentación del back. El contrato que consume el front (el nuevo campo opcional del login y el comportamiento de la cookie en ambos modos) queda documentado en la especificación de la API y en la documentación del back, para que la spec del front se escriba contra él.

**Why this priority**: reduce la ventana de uso de un token de acceso robado (el refresh token no rota, así que es el único límite) y desbloquea la spec del front, pero no impide probar P1 y P2.

**Independent Test**: hacer login y comprobar que el token de acceso emitido caduca 15 minutos después de su emisión; consultar la especificación de la API y ver el campo opcional del login y la descripción de la cookie en ambos modos.

**Acceptance Scenarios**:

1. **Given** un login o una renovación correctos, **When** se emite un token de acceso, **Then** su caducidad es 15 minutos después de su emisión.
2. **Given** la especificación de la API publicada por el back, **When** el equipo del front consulta el login, **Then** ve el campo «recordarme» (`rememberMe`) como booleano opcional con valor por defecto falso, y la descripción de la cookie en cada modo (persistente 30 días o de sesión con límite de 24 horas en el servidor).
3. **Given** la documentación del back, **When** alguien consulta la autenticación y el CORS, **Then** encuentra las duraciones de ambos modos, el token de acceso de 15 minutos y que el CORS admite credenciales solo para orígenes permitidos.

---

### Edge Cases

- Renovación con una cookie de modo por defecto pasadas las 24 horas: rechazo de sesión inválida (como cualquier refresh caducado), con borrado de la cookie.
- Cierre de sesión en cualquiera de los dos modos: revoca solo esa sesión y borra la cookie; sigue siendo idempotente sin cookie o con una cookie inválida.
- Login nuevo con una cookie previa presente: se emite una sesión nueva con el modo pedido en ese login; la sesión anterior no cambia de modo ni de duración.
- «recordarme» con valor no booleano (texto, número, nulo explícito): 400 `http_error` por body mal formado, como un email o contraseña que no son texto (no 422 `validation_failed`), sin sesión ni cookie. Un campo ausente equivale a falso.
- Credenciales incorrectas o cuenta sin verificar con «recordarme» activado: mismas respuestas que hoy (401 / 403); la opción no altera esos errores.
- Petición desde un origen no permitido: sin cabeceras que autoricen el origen ni credenciales.
- Reseteo del universo de SpaceTraders: revoca todas las sesiones del usuario, de ambos modos, como hoy.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El back MUST permitir peticiones con credenciales (cookies) desde los orígenes del front permitidos hacia login, renovación y cierre de sesión, incluidas sus peticiones previas (preflight).
- **FR-002**: El back MUST autorizar credenciales solo para el origen concreto que hace la petición cuando está entre los permitidos, y nunca para «cualquier origen».
- **FR-003**: El login MUST aceptar un campo booleano opcional «recordarme», llamado `rememberMe` en el body, con valor por defecto falso cuando no se envía.
- **FR-004**: Con «recordarme» activado, el back MUST emitir una cookie persistente y una sesión que el servidor acepta durante 30 días desde el login.
- **FR-005**: Con «recordarme» desactivado o ausente, el back MUST emitir una cookie de sesión (sin fecha de caducidad) y una sesión que el servidor deja de aceptar a las 24 horas del login.
- **FR-006**: La caducidad de una sesión MUST fijarse en el login y MUST NOT ampliarse con su uso (renovaciones del token de acceso). El modo no se guarda aparte: lo determina la caducidad propia de la sesión.
- **FR-007**: El back MUST rechazar un login cuyo «recordarme» no sea booleano como body mal formado: 400 con código `http_error` y el formato de error del back, el mismo trato que da a un email o contraseña que no son texto en ese endpoint (no 422 `validation_failed`, reservado a los endpoints que validan su body como DTO), sin iniciar sesión ni fijar cookie.
- **FR-008**: Los tokens de acceso MUST caducar 15 minutos después de emitirse.
- **FR-009**: La especificación de la API del login MUST documentar el campo `rememberMe` (opcional, booleano, falso por defecto), el error 400 `http_error` por valor inválido y el comportamiento de la cookie en cada modo.
- **FR-010**: La documentación del back (autenticación y CORS) MUST reflejar las duraciones de ambos modos, el token de acceso de 15 minutos y la admisión de credenciales en CORS, en el mismo cambio que los aplica.
- **FR-011**: El comportamiento existente MUST mantenerse: refresh token sin rotación, cierre de sesión idempotente que revoca solo la sesión presentada, mismas respuestas 401/403 del login y revocación de todas las sesiones al resetear el universo.

### Key Entities

- **Sesión (refresh token)**: representa un login concreto de un usuario. Atributos relevantes: usuario, caducidad (30 días o 24 horas desde el login según el modo elegido), estado revocado. El modo no se guarda: se deduce de la caducidad fijada al emitirla.
- **Cookie del refresh token**: transporte de la sesión en el navegador. En modo «recordarme» es persistente con la misma caducidad que la sesión; en modo por defecto es de sesión, sin caducidad propia.
- **Token de acceso**: credencial de corta duración (15 minutos) que el front usa en las llamadas autenticadas y renueva con la sesión.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100 % de los logins correctos hechos desde un origen permitido dejan una cookie que el navegador envía en la siguiente renovación y en el cierre de sesión.
- **SC-002**: Ninguna sesión iniciada sin «recordarme» se acepta más de 24 horas después del login, ni siquiera si el navegador conserva la cookie.
- **SC-003**: Ninguna sesión iniciada con «recordarme» se acepta más de 30 días después del login, por mucho que se use.
- **SC-004**: Ningún token de acceso emitido es válido más de 15 minutos después de su emisión.
- **SC-005**: El equipo del front puede escribir su spec de login solo con la especificación de la API y la documentación del back, sin preguntar al back por el comportamiento de la cookie ni por el campo «recordarme».
- **SC-006**: Las peticiones desde orígenes no permitidos nunca reciben autorización de credenciales.

## Assumptions

- Front y back comparten site (en desarrollo, localhost con puertos distintos), así que la política de envío estricta de la cookie no impide enviarla. El caso de sites distintos en producción queda fuera de alcance.
- Los orígenes permitidos siguen definidos como hoy: cualquier puerto de localhost/127.0.0.1 en desarrollo. El back devuelve el origen concreto que hace la petición, nunca un comodín. Restringirlos por entorno es el TODO de producción, fuera de alcance.
- Los navegadores tratan `http://localhost` como contexto seguro, así que la cookie marcada como segura se guarda y se envía en desarrollo.
- La renovación no vuelve a fijar la cookie (comportamiento actual), así que una cookie de sesión no se convierte en persistente al renovar.
- Las sesiones ya emitidas antes del cambio conservan su caducidad de 30 días; no se migran.
- Fuera de alcance: rotación del refresh token, configuración de producción (dominios reales, orígenes por entorno, cookies entre sites distintos) y cualquier cambio en el front.
- Decisiones cerradas que no se reabren (sus alternativas descartadas irán a `research.md` en `/speckit-plan`): credenciales en el CORS del back con origen explícito (descartado: proxy de desarrollo con mismo origen); «recordarme» como campo booleano opcional del login, falso por defecto, sin guardar el modo (descartados: no ofrecer la opción, y el «remember me» nativo del framework); token de acceso de 15 minutos configurado en la librería de JWT (descartado: dejar 1 hora y corregir la doc).
