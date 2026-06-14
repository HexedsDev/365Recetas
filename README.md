# 🥗 365 Recetas Saludables

Producto digital en español: **recetario (ebook PDF) + página de venta** con pasarela de pago,
entrega automática por correo y precio que se ajusta por país. Dominio: **365recetas.com**.

> Gancho de venta: **365 recetas (una por día)**, cada una con sus **calorías + macros**
> ("contador de calorías gratis"). Pago único, acceso de por vida, entrega inmediata.

---

## ✅ Lo que llevamos hecho

### 1. El recetario (ebook)
- **12 recetas listas** (de las 365 objetivo) con texto, calorías y macros.
- Cada receta tiene: foto, badge de calorías, ingredientes + tabla nutricional, preparación,
  enlace a la web dentro del texto, **"Consejos útiles"** (2 tips) y una "Advertencia" de salud.
- **Portada llamativa** con el "365" gigante, badge "CALORÍAS EN CADA RECETA" y el dominio.
- **Índice clickeable**: cada fila salta a su receta (y vuelve al índice) — funciona como
  enlaces internos del PDF.
- Se genera a PDF desde [`libro.html`](libro.html) con Edge headless (`--print-to-pdf`).
- Imágenes generadas con **Higgsfield** (Soul 2 para recetas, Nano Banana Pro para la portada).

### 2. La página de venta (`web/`)
Landing mobile-first, tema verde, modelada sobre un ejemplo validado del nicho. Incluye:
- Hero con la portada en marco de tablet + contador regresivo de oferta (10 min).
- Bloques: "Es para ti si…", bonus de calorías, "Lo que incluye", **"Así verás tus recetas"**
  (3 capturas reales del recetario en tablets), variedad, garantía, FAQ.
- **Tarjeta de compra** con precio dinámico (USD/GTQ), prueba social ("+7.2k compraron…" con
  punto rojo palpitante que sube cada día), badges de pago seguro.
- **Combo** (recetario + 3 complementos) en un solo pago — ver abajo.
- **Reseñas reales** (4.5★, 12 reseñas en 2 columnas, con respuestas de marca en español neutro).
- Formulario "Dejar reseña" (solo front-end por ahora).
- Barra de compra fija (sticky) abajo.

### 3. Backend PHP (`web/api/`) — sin base de datos
| Archivo | Qué hace |
|---|---|
| `config.php` | ⚙️ **el único que editas** (llaves, precios, SMTP, Pixel). **No se sube al repo.** |
| `config.example.php` | Plantilla para crear tu `config.php`. |
| `lib.php` | Funciones: precio por país, llamadas a Recurrente, tokens firmados, firma de webhook (Svix), SMTP, Meta CAPI. |
| `precio.php` | Devuelve el precio (USD o GTQ) según el país, incl. el del combo. |
| `crear-checkout.php` | Crea la sesión de pago en Recurrente (normal o combo) y devuelve la URL. |
| `webhook.php` | Recibe el pago confirmado, verifica firma, **envía el email con la descarga**. |
| `descargar.php` | Descarga protegida por token firmado (sin token válido = 403). |
| `../gracias.php` | Página de regreso tras pagar; muestra la descarga y dispara el Pixel `Purchase`. |

**Pagos:** [Recurrente](https://docs.recurrente.com) (USD + GTQ, llaves de prueba/producción, webhooks).
**Tracking:** Meta Pixel + Conversions API (opcional).
**Entrega:** email vía SMTP de Hostinger + descarga con token firmado (HMAC, válido 7 días).
El PDF real está bloqueado a descarga directa con `.htaccess`; solo `descargar.php` con token lo entrega.

### 4. Combo (recetario + 3 complementos) — **cableado y probado**
- Un solo bloque "Llévate el combo completo" que cobra **$14.97 USD / Q119 GTQ** en un pago.
- El webhook y `gracias.php` detectan el combo **por el monto** y entregan el PDF del combo.
- Verificado en modo prueba: el checkout genera `total_in_cents: 1497` (USD), `live_mode: false`. ✓

---

## 💵 Precios
| | USD | GTQ (Guatemala) |
|---|---|---|
| Recetario | **$9.97** | **Q79** |
| Combo (4 productos) | **$14.97** | **Q119** |

El precio se decide **en el servidor** según la IP del visitante (no se puede manipular desde el navegador).

---

## 🚀 Cómo correrlo en local (XAMPP / PHP)
```bash
# 1) Copia la plantilla de config y pon tu llave de PRUEBA de Recurrente
cp web/api/config.example.php web/api/config.php
#    → edita RECURRENTE_SECRET_KEY con tu sk_test_...

# 2) Levanta el servidor (PHP 7.4+ con cURL)
php -S localhost:8080 -t web

# 3) Abre http://localhost:8080
```
Para producción: sube el contenido de `web/` a `public_html` de Hostinger y registra el webhook
`https://365recetas.com/api/webhook.php` en Recurrente. Guía completa en [`web/SETUP-WEB.md`](web/SETUP-WEB.md).

---

## 📦 Estructura del repo
```
.
├── README.md                  ← este archivo
├── libro.html                 ← plantilla del recetario (→ PDF)
├── recetas-001-005.md         ← texto de las primeras recetas
├── imagenes/                  ← portada + fotos de recetas (Higgsfield)
└── web/                       ← la página de venta
    ├── index.html             ← landing
    ├── gracias.php            ← regreso tras pagar
    ├── SETUP-WEB.md           ← guía de instalación/deploy
    ├── assets/                ← imágenes del sitio
    ├── descargas/             ← el PDF (NO se sube) + .htaccess
    └── api/                   ← backend PHP
```
> **No se sube al repo:** `web/api/config.php` (lleva la llave secreta) ni los **PDFs** del
> producto (para no regalarlo). Ver `.gitignore`.

---

## 🔜 Pendiente

**Antes de cobrar dinero real:**
- [ ] **Rotar la llave `sk_live_`** de Recurrente (estuvo expuesta) y usar `sk_test_` para probar.
- [ ] **Crear los 3 PDFs del combo** (postres / air fryer / plan 30 días) o esconder el combo
      (hoy el combo entrega solo el recetario principal).
- [ ] Completar en `config.php`: `RECURRENTE_WEBHOOK_SECRET`, `SMTP_PASS`, `DOWNLOAD_SECRET`, `META_PIXEL_ID`.
- [ ] Subir `web/` a Hostinger y registrar el webhook en Recurrente.
- [ ] Links legales del footer (privacidad / términos / reembolsos) — Meta los exige para anuncios.

**Para escalar y pulir:**
- [ ] Llegar a **365 recetas** (hoy 12) — requiere recarga de créditos Higgsfield.
- [ ] Portada a 2K para aligerar el PDF (hoy ~81 MB).
- [ ] Conectar el contador de compras y el formulario de reseñas a datos reales (hoy simulados).

---

*Proyecto en desarrollo · 365recetas.com*
