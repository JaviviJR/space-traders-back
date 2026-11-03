// Genera auth/flujo-autenticacion.pdf: `npm run build --prefix doc/project`.
// Las fuentes estándar del PDF (Helvetica, Courier) usan WinAnsi: nada de
// flechas Unicode ni símbolos fuera de Latin-1 en los textos.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import PDFDocument from 'pdfkit';

const OUT = path.join(path.dirname(fileURLToPath(import.meta.url)), '..', 'auth', 'flujo-autenticacion.pdf');

const C = {
    ink: '#1d2430',
    muted: '#5b6577',
    primary: '#1f4e79',
    accent: '#2e75b6',
    fill: '#eaf1fb',
    line: '#9aa6b8',
    note: '#fff6d6',
    noteLine: '#d9b84a',
    ok: '#2e7d4f',
    danger: '#b23b3b',
    code: '#7a2e8a',
};

const doc = new PDFDocument({
    size: 'A4',
    margins: { top: 56, bottom: 60, left: 56, right: 56 },
    bufferPages: true,
    info: { Title: 'Flujo de autenticación', Author: 'Space Trader Back' },
});
doc.pipe(fs.createWriteStream(OUT));

const L = doc.page.margins.left;
const W = doc.page.width - L - doc.page.margins.right;
const BOTTOM = () => doc.page.height - doc.page.margins.bottom;

function ensureSpace(h) {
    if (doc.y + h > BOTTOM()) {
        doc.addPage();
    }
}

// ---------- texto ----------

// `**negrita**`, `*cursiva*` y `` `código` `` dentro de un párrafo.
function segments(text) {
    return text
        .split(/(\*\*[^*]+\*\*|\*[^*]+\*|`[^`]+`)/)
        .filter(Boolean)
        .map((s) => {
            if (s.startsWith('**')) return { t: s.slice(2, -2), font: 'Helvetica-Bold', color: C.ink };
            if (s.startsWith('*')) return { t: s.slice(1, -1), font: 'Helvetica-Oblique', color: C.ink };
            if (s.startsWith('`')) return { t: s.slice(1, -1), font: 'Courier', color: C.code };
            return { t: s, font: 'Helvetica', color: C.ink };
        });
}

function richHeight(text, width, size = 10.5) {
    return doc.font('Helvetica').fontSize(size).heightOfString(text.replace(/[*`]/g, ''), { width, lineGap: 2 });
}

function rich(text, x, width, size = 10.5) {
    const segs = segments(text);
    ensureSpace(richHeight(text, width, size));
    doc.fontSize(size);
    segs.forEach((s, i) => {
        doc.font(s.font).fillColor(s.color);
        const opts = { width, continued: i < segs.length - 1, lineGap: 2 };
        if (i === 0) doc.text(s.t, x, doc.y, opts);
        else doc.text(s.t, opts);
    });
    doc.x = L;
}

function p(text) {
    rich(text, L, W);
    doc.moveDown(0.5);
}

function bullets(items) {
    for (const item of items) {
        // la viñeta y su texto van juntos a la página siguiente
        ensureSpace(richHeight(item, W - 16));
        const y = doc.y;
        doc.font('Helvetica').fontSize(10.5).fillColor(C.accent).text('•', L + 4, y, { lineBreak: false });
        doc.y = y;
        rich(item, L + 16, W - 16);
        doc.moveDown(0.25);
    }
    doc.moveDown(0.3);
}

function h1(text) {
    ensureSpace(60);
    doc.moveDown(0.6);
    doc.font('Helvetica-Bold').fontSize(16).fillColor(C.primary).text(text, L, doc.y, { width: W });
    const y = doc.y + 2;
    doc.moveTo(L, y).lineTo(L + W, y).lineWidth(1).strokeColor(C.accent).stroke();
    doc.y = y + 8;
}

// keep: alto del bloque que sigue, para no dejar el título solo al pie de página.
function h2(text, keep = 0) {
    ensureSpace(70 + keep);
    doc.moveDown(0.4);
    doc.font('Helvetica-Bold').fontSize(12.5).fillColor(C.accent).text(text, L, doc.y, { width: W });
    doc.moveDown(0.3);
}

function caption(text) {
    doc.font('Helvetica-Oblique').fontSize(8.5).fillColor(C.muted).text(text, L, doc.y, { width: W, align: 'center' });
    doc.moveDown(0.8);
}

// ---------- tablas ----------

function cellFont(text, header) {
    if (header) return { font: 'Helvetica-Bold', t: text };
    if (/^`[^`]+`$/.test(text)) return { font: 'Courier', t: text.slice(1, -1) };
    return { font: 'Helvetica', t: text };
}

function table(cols, rows) {
    const size = 9;
    const pad = 5;
    const widths = cols.map((c) => c.w * W);
    const drawRow = (cells, header) => {
        doc.fontSize(size);
        const parsed = cells.map((c) => cellFont(c, header));
        const h = Math.max(
            ...parsed.map((c, i) => doc.font(c.font).heightOfString(c.t, { width: widths[i] - 2 * pad }))
        ) + 2 * pad;
        if (!header && doc.y + h > BOTTOM()) {
            doc.addPage();
            drawRow(cols.map((c) => c.label), true);
        }
        ensureSpace(h);
        const y = doc.y;
        let x = L;
        parsed.forEach((c, i) => {
            doc.rect(x, y, widths[i], h).fillAndStroke(header ? C.primary : C.fill, '#ffffff');
            doc.font(c.font).fillColor(header ? '#ffffff' : C.ink)
                .text(c.t, x + pad, y + pad, { width: widths[i] - 2 * pad });
            x += widths[i];
        });
        doc.y = y + h;
    };
    drawRow(cols.map((c) => c.label), true);
    rows.forEach((r) => drawRow(r, false));
    doc.x = L;
    doc.moveDown(0.8);
}

// ---------- primitivas de diagramas ----------

function arrowHead(x, y, dir, color) {
    const s = 5;
    doc.polygon([x, y], [x - dir * s * 1.6, y - s], [x - dir * s * 1.6, y + s]).fill(color);
}

function box(x, y, w, h, title, { fill = C.fill, stroke = C.primary, size = 9 } = {}) {
    doc.roundedRect(x, y, w, h, 4).lineWidth(1).fillAndStroke(fill, stroke);
    doc.font('Helvetica-Bold').fontSize(size).fillColor(C.primary);
    const th = doc.heightOfString(title, { width: w - 8, align: 'center' });
    doc.text(title, x + 4, y + (h - th) / 2, { width: w - 8, align: 'center' });
}

// Diagrama de secuencia. steps:
//   { from, to, label, reply? }   mensaje (reply = línea discontinua)
//   { self, label }               acción interna de un participante
//   { section }                   separador de alternativa
//   { note, over: [i] | [i, j] }  nota sobre uno o varios participantes
function sequence(parts, steps, { measure = false } = {}) {
    const n = parts.length;
    const xs = parts.map((_, i) => L + (W * (i + 0.5)) / n);
    const boxW = Math.min(W / n - 16, 150);
    const boxH = 30;
    const lbl = 8;

    const labelWidth = (s) => {
        if (s.self !== undefined) {
            const right = s.self < n - 1 ? xs[s.self + 1] - 8 : L + W;
            return right - (xs[s.self] + 36);
        }
        return Math.abs(xs[s.to] - xs[s.from]) - 14;
    };
    const noteBox = (s) => {
        const a = Math.min(...s.over);
        const b = Math.max(...s.over);
        const x0 = Math.max(L, xs[a] - W / n / 2 + 6);
        const x1 = Math.min(L + W, xs[b] + W / n / 2 - 6);
        return { x0, w: x1 - x0 };
    };
    const heightOf = (s) => {
        doc.font('Helvetica').fontSize(lbl);
        if (s.section) return 26;
        if (s.note) return doc.heightOfString(s.note, { width: noteBox(s).w - 12 }) + 22;
        const th = doc.heightOfString(s.label, { width: labelWidth(s), align: s.self !== undefined ? 'left' : 'center' });
        return s.self !== undefined ? Math.max(th, 16) + 16 : th + 16;
    };

    const total = boxH + 14 + steps.reduce((a, s) => a + heightOf(s), 0) + 10;
    if (measure) return total;
    ensureSpace(total);
    const top = doc.y;
    const end = top + total;

    // líneas de vida
    xs.forEach((x) => {
        doc.moveTo(x, top + boxH).lineTo(x, end).lineWidth(0.8).dash(3, { space: 3 }).strokeColor(C.line).stroke();
        doc.undash();
    });
    parts.forEach((name, i) => box(xs[i] - boxW / 2, top, boxW, boxH, name));

    let y = top + boxH + 14;
    for (const s of steps) {
        const h = heightOf(s);
        doc.font('Helvetica').fontSize(lbl);
        if (s.section) {
            const ly = y + 13;
            doc.moveTo(L, ly).lineTo(L + W, ly).lineWidth(0.8).dash(4, { space: 3 }).strokeColor(C.accent).stroke();
            doc.undash();
            doc.font('Helvetica-Bold').fontSize(lbl);
            const tw = doc.widthOfString(s.section) + 12;
            doc.rect(L, ly - 7, tw, 14).fill(C.accent);
            doc.fillColor('#ffffff').text(s.section, L + 6, ly - 4, { lineBreak: false });
        } else if (s.note) {
            const { x0, w } = noteBox(s);
            doc.rect(x0, y + 4, w, h - 10).lineWidth(0.8).fillAndStroke(C.note, C.noteLine);
            doc.fillColor(C.ink).font('Helvetica').fontSize(lbl).text(s.note, x0 + 6, y + 10, { width: w - 12 });
        } else if (s.self !== undefined) {
            const x = xs[s.self];
            const lw = labelWidth(s);
            const th = doc.heightOfString(s.label, { width: lw });
            const ly = y + 4;
            const loopH = Math.max(th, 16);
            doc.moveTo(x, ly).lineTo(x + 28, ly).lineTo(x + 28, ly + loopH).lineTo(x + 4, ly + loopH)
                .lineWidth(1).strokeColor(C.primary).stroke();
            arrowHead(x + 1, ly + loopH, -1, C.primary);
            doc.fillColor(C.ink).text(s.label, x + 36, ly, { width: lw });
        } else {
            const x1 = xs[s.from];
            const x2 = xs[s.to];
            const dir = x2 > x1 ? 1 : -1;
            const lw = labelWidth(s);
            const th = doc.heightOfString(s.label, { width: lw, align: 'center' });
            const color = s.reply ? C.ok : C.primary;
            const color2 = s.error ? C.danger : color;
            doc.fillColor(s.error ? C.danger : C.ink)
                .text(s.label, Math.min(x1, x2) + 7, y + 3, { width: lw, align: 'center' });
            const ay = y + th + 7;
            doc.moveTo(x1, ay).lineTo(x2 - dir * 6, ay).lineWidth(1.1);
            if (s.reply) doc.dash(4, { space: 3 });
            doc.strokeColor(color2).stroke();
            doc.undash();
            arrowHead(x2, ay, dir, color2);
        }
        y += h;
    }
    doc.y = end + 6;
    doc.x = L;
}

// Título + diagrama de secuencia, sin separarlos entre páginas.
function diagram(title, parts, steps) {
    h2(title, sequence(parts, steps, { measure: true }));
    sequence(parts, steps);
}

// Vista general: navegador, back y SpaceTraders, con qué guarda cada uno.
function overview() {
    const cols = [
        { w: 150, title: 'Navegador\n(front Vue, origen A)', items: [
            'Memoria JS: access token (JWT, 15 min)',
            'Cookie httpOnly: refresh token (Path=/auth)',
        ] },
        { w: 175, title: 'Back (BFF Symfony, origen B)', items: [
            'Verifica la firma del JWT con la clave pública',
            'BD: hash SHA-256 de cada refresh token y su caducidad',
            'BD: token del agente SpaceTraders, cifrado',
        ] },
        { w: 106, title: 'API de SpaceTraders', items: [
            'Recibe solo el token del agente, nunca el del usuario',
        ] },
    ];
    doc.font('Helvetica').fontSize(8.5);
    const h = 48 + Math.max(...cols.map((c) => c.items.reduce(
        (a, it) => a + doc.heightOfString(it, { width: c.w - 22 }) + 12, 0,
    )));
    ensureSpace(h + 40);
    const top = doc.y;
    const gap = (W - cols.reduce((a, c) => a + c.w, 0)) / 2;
    let x = L;
    const centers = [];
    cols.forEach((c) => {
        doc.roundedRect(x, top, c.w, h, 6).lineWidth(1).fillAndStroke(C.fill, C.primary);
        doc.font('Helvetica-Bold').fontSize(9.5).fillColor(C.primary)
            .text(c.title, x + 6, top + 8, { width: c.w - 12, align: 'center' });
        let iy = top + 44;
        c.items.forEach((it) => {
            doc.font('Helvetica').fontSize(8.5).fillColor(C.ink);
            const ih = doc.heightOfString(it, { width: c.w - 22 });
            doc.roundedRect(x + 6, iy - 3, c.w - 12, ih + 6, 3).fill('#ffffff');
            doc.fillColor(C.ink).text(it, x + 11, iy, { width: c.w - 22 });
            iy += ih + 12;
        });
        centers.push([x, x + c.w]);
        x += c.w + gap;
    });
    [[0, 1, '1'], [1, 2, '2']].forEach(([a, b, n]) => {
        const ay = top + h / 2;
        const x1 = centers[a][1] + 2;
        const x2 = centers[b][0] - 2;
        doc.moveTo(x1, ay).lineTo(x2 - 6, ay).lineWidth(1.4).strokeColor(C.accent).stroke();
        arrowHead(x2, ay, 1, C.accent);
        doc.circle((x1 + x2) / 2, ay - 12, 7).fill(C.accent);
        doc.font('Helvetica-Bold').fontSize(8).fillColor('#ffffff')
            .text(n, (x1 + x2) / 2 - 7, ay - 15.5, { width: 14, align: 'center' });
    });
    doc.y = top + h + 8;
    doc.x = L;
    bullets([
        '**(1)** HTTPS entre orígenes distintos. CORS admite credenciales solo para orígenes permitidos, así que la cookie del refresh token viaja en `/auth/login`, `/auth/refresh` y `/auth/logout`.',
        '**(2)** El back llama a SpaceTraders con el token del agente, que nunca sale del back.',
    ]);
}

// Línea temporal de los dos modos de sesión frente al access token.
function timeline() {
    const labelW = 108;
    const tx = L + labelW;
    const tw = W - labelW - 10;
    const rowH = 46;
    const h = 30 + rowH * 3 + 24;
    ensureSpace(h + 20);
    const top = doc.y;

    const login = tx;
    doc.moveTo(login, top + 14).lineTo(login, top + h - 18).lineWidth(1.2).strokeColor(C.primary).stroke();
    doc.font('Helvetica-Bold').fontSize(8.5).fillColor(C.primary).text('Login', login - 20, top, { width: 40, align: 'center' });

    const rowLabel = (i, text) => {
        doc.font('Helvetica-Bold').fontSize(9).fillColor(C.ink)
            .text(text, L, top + 30 + i * rowH + 8, { width: labelW - 10 });
    };
    const endMark = (x, y, text, color) => {
        doc.moveTo(x, y - 6).lineTo(x, y + 26).lineWidth(1.6).strokeColor(color).stroke();
        doc.font('Helvetica-Bold').fontSize(7.5).fillColor(color).text(text, x - 110, y + 28, { width: 116, align: 'right' });
    };

    // fila 0: access tokens encadenados
    rowLabel(0, 'Access token');
    const n = 8;
    const segW = tw / n;
    for (let i = 0; i < n; i++) {
        const y0 = top + 30 + 6;
        doc.rect(tx + i * segW + 1, y0, segW - 2, 18).fill(i % 2 ? '#c7dbf3' : '#a9c8ec');
        doc.font('Helvetica').fontSize(7.5).fillColor(C.ink)
            .text("15 min", tx + i * segW + 1, y0 + 5, { width: segW - 2, align: 'center' });
    }
    doc.font('Helvetica-Oblique').fontSize(7.5).fillColor(C.muted)
        .text('cada uno se pide con /auth/refresh mientras la sesión siga siendo válida', tx + 4, top + 30 + 28, { width: tw - 4 });

    // fila 1: sin recordarme
    rowLabel(1, 'Sin «recordarme»\n(por defecto)');
    let y = top + 30 + rowH + 6;
    const cookieEnd = tx + tw * 0.62;
    doc.rect(tx, y, tw, 9).fill('#cfe5d6');
    doc.font('Helvetica').fontSize(7.5).fillColor(C.ink).text('servidor: sesión válida 24 h desde el login', tx + 4, y + 1, { lineBreak: false });
    doc.rect(tx, y + 11, cookieEnd - tx, 9).fill('#e9d7a5');
    doc.moveTo(cookieEnd, y + 15.5).lineTo(tx + tw, y + 15.5).lineWidth(1).dash(3, { space: 3 }).strokeColor('#c8a640').stroke();
    doc.undash();
    doc.fillColor(C.ink).text('cookie de sesión: se borra al cerrar el navegador', tx + 4, y + 12, { lineBreak: false });
    endMark(tx + tw, y - 2, 'caduca a las 24 h', C.danger);

    // fila 2: con recordarme
    rowLabel(2, 'Con «recordarme»');
    y = top + 30 + rowH * 2 + 6;
    doc.rect(tx, y, tw, 9).fill('#cfe5d6');
    doc.font('Helvetica').fontSize(7.5).fillColor(C.ink).text('servidor: sesión válida 30 días desde el login', tx + 4, y + 1, { lineBreak: false });
    doc.rect(tx, y + 11, tw, 9).fill('#e9d7a5');
    doc.fillColor(C.ink).text('cookie persistente: Expires = login + 30 días', tx + 4, y + 12, { lineBreak: false });
    endMark(tx + tw, y - 2, 'caduca a los 30 días', C.danger);

    doc.y = top + h;
    doc.x = L;
    caption('Escala ilustrativa: cada fila tiene su propia escala. Ninguna duración se alarga con el uso; todas cuentan desde el login.');
}

// ===================== contenido =====================

doc.font('Helvetica-Bold').fontSize(26).fillColor(C.primary).text('Flujo de autenticación', L, 70, { width: W });
doc.font('Helvetica').fontSize(12).fillColor(C.muted)
    .text('Space Trader Back · guía para desarrolladores de front y back', { width: W });
doc.moveDown(0.3);
doc.fontSize(9).text('Fuente de verdad: doc/authentication.md y doc/architecture.md. Este PDF es un resumen visual.', { width: W });
doc.moveDown(1);

h1('1. Visión general');
p('El back es un **Backend for Frontend (BFF)**: el front (SPA Vue) solo habla con este back y nunca con SpaceTraders. La persona usuaria se autentica contra el back con email y contraseña. Cada agente de SpaceTraders vinculado tiene su propio token, que el back guarda cifrado y el front nunca ve.');
p('La sesión se apoya en dos tokens con papeles distintos: un **access token** de vida corta, que acompaña cada petición, y un **refresh token** de vida larga, que solo sirve para conseguir access tokens nuevos.');
overview();

h1('2. Conceptos');
h2('2.1 Access token');
bullets([
    '**Qué es:** un JWT (JSON Web Token) firmado por el back con RS256: clave privada para firmar, clave pública para verificar.',
    '**Para qué sirve:** demuestra quién eres en cada petición protegida. El front lo envía en la cabecera `Authorization: Bearer <token>`.',
    '**Dónde vive:** solo en la memoria del front, nunca en `localStorage` ni en cookies. Si la pestaña se recarga se pierde, y se recupera con el refresh token.',
    '**En el servidor:** no se guarda. El back solo comprueba la firma y la caducidad (es *stateless*). Por eso un access token emitido no se puede revocar: vale hasta que caduca.',
    '**Duración:** 15 minutos. Es corta a propósito: como no se puede revocar y el refresh token no rota, es el único límite que tiene un access token robado.',
]);

h2('2.2 Refresh token');
bullets([
    '**Qué es:** una cadena aleatoria opaca de 256 bits, sin información dentro.',
    '**Para qué sirve:** sirve únicamente para pedir un access token nuevo sin volver a introducir la contraseña. Representa **una sesión**: un login en un dispositivo.',
    '**Dónde vive en el cliente:** en una cookie `httpOnly; Secure; SameSite=Strict; Path=/auth`.',
    '`httpOnly`: JavaScript no puede leerla, lo que la protege de XSS. `Secure`: solo viaja por HTTPS (los navegadores tratan `localhost` como seguro). `SameSite=Strict`: no se envía en peticiones iniciadas desde otros sites, lo que la protege de CSRF. `Path=/auth`: solo llega a login, refresh y logout.',
    '**Dónde vive en el servidor:** en base de datos se guarda solo su hash SHA-256, nunca el valor en claro. Una filtración de la BD no permite suplantar sesiones.',
    '**Duración:** 24 horas o 30 días desde el login, según el modo elegido (apartado 2.4). Nunca se alarga con el uso.',
    '**Sin rotación:** cada refresh valida el mismo token y no emite uno nuevo. Es una simplificación aceptada: un refresh token robado vale hasta que caduca o se cierra esa sesión.',
]);

h2('2.3 ¿Por qué dos tokens?');
table(
    [
        { label: '', w: 0.22 },
        { label: 'Access token', w: 0.39 },
        { label: 'Refresh token', w: 0.39 },
    ],
    [
        ['Se usa en', 'Cada petición a la API', 'Solo /auth/login, /auth/refresh y /auth/logout'],
        ['Duración', 'Corta: 15 min', 'Larga: 24 h o 30 días'],
        ['Revocable', 'No, caduca solo', 'Sí, está en la BD'],
        ['Accesible desde JS', 'Sí (memoria)', 'No (cookie httpOnly)'],
        ['Formato', 'JWT firmado, autocontenido', 'Cadena opaca; el servidor guarda su hash'],
    ],
);
p('El access token se usa constantemente y no se puede revocar, así que dura poco. El refresh token viaja pocas veces, está mejor protegido y se puede revocar, así que puede durar mucho.');

h2('2.4 Modos de sesión: «recordarme»');
p('El login acepta un campo opcional `rememberMe` (booleano, `false` si no se envía). Persistir la sesión es siempre una elección explícita: los clientes que no envían nada no dejan sesiones de 30 días abiertas.');
table(
    [
        { label: 'Modo', w: 0.22 },
        { label: 'Cookie en el navegador', w: 0.3 },
        { label: 'Validez en el servidor', w: 0.24 },
        { label: 'Pensado para', w: 0.24 },
    ],
    [
        ['rememberMe: true', 'Persistente, con Expires = login + 30 días. Sobrevive al cierre del navegador.', '30 días desde el login', 'Equipo personal'],
        ['rememberMe: false o ausente (por defecto)', 'De sesión, sin Expires: el navegador la descarta al cerrarse.', '24 horas desde el login', 'Equipo compartido o uso puntual'],
    ],
);
bullets([
    '**¿Por qué el límite de 24 h si la cookie ya es de sesión?** Los navegadores pueden restaurar las cookies de sesión al reabrir pestañas, así que la cookie podría vivir días. El servidor pone un tope que no depende del navegador.',
    '**El modo no se guarda aparte:** la caducidad se fija al emitir el refresh token y queda en la BD. `/auth/refresh` no vuelve a enviar la cookie, así que una cookie de sesión nunca se convierte en persistente.',
    'Un `rememberMe` que no sea booleano (por ejemplo `"true"` como texto, o `1`) se rechaza con `400 http_error`, igual que un `email` o `password` que no sean texto, y no se inicia sesión.',
]);

h2('2.5 CORS con credenciales');
p('El front y el back se sirven desde **orígenes distintos** (en desarrollo, localhost con puertos distintos). Para que el navegador guarde la cookie que devuelve `/auth/login` y la envíe en `/auth/refresh` y `/auth/logout`, hacen falta dos cosas:');
bullets([
    '**En el front:** las llamadas a `/auth/*` se hacen con credenciales (`fetch(..., { credentials: \'include\' })`, o `withCredentials: true` en axios).',
    '**En el back:** responde con `Access-Control-Allow-Credentials: true` y con `Access-Control-Allow-Origin` igual al origen concreto que hace la petición, nunca `*`, y solo si está entre los orígenes permitidos (variable `CORS_ALLOW_ORIGIN`).',
    'Como front y back comparten site (mismo host, distinto puerto), `SameSite=Strict` no impide el envío de la cookie. Si en producción viven en sites distintos, habrá que revisarlo (pendiente en doc/architecture.md).',
]);

h1('3. Flujos');
diagram('3.1 Registro y verificación de email', ['Navegador (front)', 'Back', 'Buzón de email'], [
    { from: 0, to: 1, label: 'POST /auth/register  {email, password}' },
    { self: 1, label: 'Crea el User con isVerified = false' },
    { from: 1, to: 0, label: '201 Created', reply: true },
    { from: 1, to: 2, label: 'Email con enlace a {FRONT_URL}/confirm-email con 4 parámetros: id, expires, token, signature' },
    { note: 'La persona abre el enlace: aterriza en una página del front, no del back.', over: [0, 2] },
    { from: 0, to: 1, label: 'POST /auth/confirm-email?<los 4 parámetros, sin tocar>' },
    { from: 1, to: 0, label: '200 (verificado)  |  400 invalid_email_confirmation', reply: true },
]);
bullets([
    'Un email ya registrado devuelve `409 email_already_registered`.',
    'El 400 de confirmación tiene el mismo cuerpo falte un parámetro, sobre uno, esté alterado o caducado: el back no revela qué ha fallado.',
    '`POST /auth/resend-confirmation` reenvía el email y responde `202`.',
]);

diagram('3.2 Login', ['Navegador (front)', 'Back', 'Base de datos'], [
    { from: 0, to: 1, label: 'OPTIONS /auth/login (preflight)  Origin: http://localhost:5173' },
    { from: 1, to: 0, label: '204  Allow-Origin: <ese origen>  Allow-Credentials: true', reply: true },
    { from: 0, to: 1, label: 'POST /auth/login  {email, password, rememberMe}  credentials: include' },
    { from: 1, to: 2, label: 'Busca el usuario por email' },
    { self: 1, label: 'Verifica la contraseña y que el email esté confirmado' },
    { self: 1, label: 'Genera el refresh token (256 bits) y firma el access token (15 min)' },
    { from: 1, to: 2, label: 'Guarda hash SHA-256 + caducidad (24 h o 30 días)' },
    { section: 'rememberMe = true' },
    { from: 1, to: 0, label: '200 {data: {accessToken}}  Set-Cookie: refresh_token=...; Expires=+30 días', reply: true },
    { section: 'rememberMe = false o ausente' },
    { from: 1, to: 0, label: '200 {data: {accessToken}}  Set-Cookie: refresh_token=... (sin Expires)', reply: true },
    { note: 'El front guarda el accessToken en memoria. La cookie la gestiona el navegador: JS no la ve.', over: [0] },
]);
p('**Errores del login:**');
bullets([
    '`401 invalid_credentials`: contraseña incorrecta o email inexistente, con el mismo cuerpo en ambos casos para no revelar qué emails existen. Es un error del formulario: el front **no** debe intentar un refresh.',
    '`403 email_not_verified`: las credenciales son correctas pero el email no está confirmado. Solo se devuelve con la contraseña correcta.',
    '`400 http_error`: body mal formado; por ejemplo, `rememberMe` no es booleano o `email` no es texto.',
    'Cada login crea una sesión nueva e independiente: una persona puede tener varias a la vez (móvil, portátil...).',
]);

diagram('3.3 Peticiones autenticadas y renovación', ['Navegador (front)', 'Back', 'Base de datos'], [
    { from: 0, to: 1, label: 'GET /api/...  Authorization: Bearer <access token caducado>' },
    { from: 1, to: 0, label: '401 authentication_required  (WWW-Authenticate: Bearer)', reply: true, error: true },
    { from: 0, to: 1, label: 'POST /auth/refresh  (cookie automática, credentials: include)' },
    { from: 1, to: 2, label: 'Busca el refresh token por su hash' },
    { section: 'Válido: existe, no revocado, no caducado' },
    { from: 1, to: 0, label: '200 {data: {accessToken}}  (la cookie no cambia)', reply: true },
    { from: 0, to: 1, label: 'Repite GET /api/... con el access token nuevo' },
    { section: 'No existe, revocado o caducado' },
    { from: 1, to: 0, label: '401 invalid_refresh_token  + Set-Cookie que borra la cookie', reply: true, error: true },
    { note: 'El front manda a la persona al login.', over: [0] },
]);
bullets([
    '**Regla del front:** ante un `401 authentication_required`, intenta `/auth/refresh` **una sola vez**. Si funciona, repite la petición original; si falla, al login.',
    'El back responde `authentication_required` igual si el JWT falta, está mal formado, ha caducado o es de un usuario que ya no existe.',
    'Al recargar la pestaña el access token se pierde: el front llama a `/auth/refresh` al arrancar para recuperar la sesión, si la cookie sigue viva.',
]);

diagram('3.4 Logout', ['Navegador (front)', 'Back', 'Base de datos'], [
    { from: 0, to: 1, label: 'POST /auth/logout  (cookie, credentials: include)' },
    { from: 1, to: 2, label: 'Si la cookie corresponde a una sesión: la marca revocada' },
    { from: 1, to: 0, label: '204 No Content  + Set-Cookie que borra la cookie', reply: true },
    { note: 'El front borra el access token de su memoria. Ojo: ese token sigue siendo válido hasta que caduque (como mucho 15 min), porque no se puede revocar.', over: [0, 1] },
]);
bullets([
    'Revoca **solo esa sesión**; las de otros dispositivos siguen activas.',
    'No necesita access token: la cookie basta para identificar la sesión.',
    'Es idempotente: sin cookie o con una cookie desconocida responde igualmente `204`.',
]);

h2('3.5 Reset del universo de SpaceTraders');
p('SpaceTraders reinicia su universo periódicamente y los tokens de agente dejan de valer. Cuando el back lo detecta, revoca **todas** las sesiones activas del usuario afectado, de los dos modos, y el siguiente `/auth/refresh` de cada dispositivo devuelve `401 invalid_refresh_token`.');

h2('3.6 Línea temporal de una sesión', 230);
timeline();

h1('4. Referencia rápida');
h2('4.1 Endpoints de autenticación');
table(
    [
        { label: 'Endpoint', w: 0.3 },
        { label: 'Requiere', w: 0.17 },
        { label: 'Éxito', w: 0.2 },
        { label: 'Errores', w: 0.33 },
    ],
    [
        ['POST /auth/register', 'Nada', '201', '409 email_already_registered; 422 validation_failed'],
        ['POST /auth/confirm-email', 'Nada', '200', '400 invalid_email_confirmation'],
        ['POST /auth/resend-confirmation', 'Nada', '202', '422 validation_failed'],
        ['POST /auth/login', 'Nada', '200 + Set-Cookie', '401 invalid_credentials; 403 email_not_verified; 400 http_error'],
        ['POST /auth/refresh', 'Cookie', '200', '401 invalid_refresh_token (borra la cookie)'],
        ['POST /auth/logout', 'Cookie (opcional)', '204 + borra la cookie', 'Ninguno (idempotente)'],
        ['Resto de /api/...', 'Bearer JWT', 'Según endpoint', '401 authentication_required'],
    ],
);
p('Todos los errores usan el envelope común del back (ver doc/errors.md).');

h2('4.2 Firewall (Symfony Security)');
bullets([
    '`login` (`^/auth/login$`): *stateless*, con `json_login`. Un *user checker* exige el email verificado y unos handlers propios construyen la respuesta de éxito y de fallo.',
    '`main`: *stateless*, con el autenticador JWT de LexikJWTAuthenticationBundle.',
    'Rutas públicas: `/auth/register`, `/auth/login`, `/auth/refresh`, `/auth/logout`, `/auth/confirm-email`, `/auth/resend-confirmation` y `/api/doc`. El resto exige `ROLE_USER`.',
]);

h2('4.3 Dónde mirar en el código');
bullets([
    '`config/packages/security.yaml`: firewalls y reglas de acceso.',
    '`config/packages/lexik_jwt_authentication.yaml`: claves y duración del access token.',
    '`config/packages/nelmio_cors.yaml`: CORS y credenciales.',
    '`src/Security/`: emisión y verificación del refresh token, la cookie y los handlers del login.',
    '`src/Controller/Auth/`: endpoints de registro, confirmación, login, refresh y logout.',
]);

// numeración de páginas
const range = doc.bufferedPageRange();
for (let i = range.start; i < range.start + range.count; i++) {
    doc.switchToPage(i);
    const bottom = doc.page.margins.bottom;
    doc.page.margins.bottom = 0;
    doc.font('Helvetica').fontSize(8).fillColor(C.muted)
        .text(`Flujo de autenticación · ${i + 1} / ${range.count}`, L, doc.page.height - 36, { width: W, align: 'center', lineBreak: false });
    doc.page.margins.bottom = bottom;
}

doc.end();
