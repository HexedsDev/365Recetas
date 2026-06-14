# 365recetas.com — Guía de instalación

Sitio estático + backend PHP (pasarela Recurrente, entrega por email, Pixel de Meta,
precio USD/GTQ automático). Pensado para **Hostinger** (y pruebas locales con **XAMPP**).

## 1. Estructura
```
web/
├── index.html            ← landing (página de venta)
├── gracias.php           ← página de regreso después de pagar
├── assets/               ← imágenes (portada, recetas, tablets, upsell)
├── descargas/
│   ├── 365-Recetas-Saludables.pdf   ← el producto (protegido)
│   └── .htaccess         ← bloquea la descarga directa
└── api/
    ├── config.php        ← ⚙️ EDITA SOLO ESTE
    ├── lib.php           ← funciones (no editar)
    ├── precio.php        ← devuelve USD o GTQ según país
    ├── crear-checkout.php← crea el pago en Recurrente
    ├── webhook.php       ← confirma el pago y manda el email
    └── descargar.php     ← descarga protegida por token
```

## 2. Lo único que editas: `api/config.php`
1. **`RECURRENTE_SECRET_KEY`** → tu llave secreta (empieza con la de **prueba** `sk_test_…`).
   Está en Recurrente → **Configuración → Llaves API**.
2. **`SITE_URL`** → tu dominio con https (ej. `https://365recetas.com`). Para XAMPP usa `http://localhost/web`.
3. **`DOWNLOAD_SECRET`** → cambia el texto por una cadena larga y aleatoria.
4. **SMTP** (`SMTP_USER`, `SMTP_PASS`, `MAIL_FROM`) → una cuenta de correo de tu dominio creada en Hostinger.
5. **`META_PIXEL_ID`** → tu Pixel (y opcional `META_CAPI_TOKEN`).
6. `RECURRENTE_WEBHOOK_SECRET` → lo obtienes en el paso 4.

> Los precios ($9.97 / Q79) ya están en `config.php`; cámbialos ahí si quieres.

## 3. Pega tu Pixel de Meta
- En `index.html`: busca `var META_PIXEL_ID = "";` y pon tu ID.
- En `config.php`: `META_PIXEL_ID` (lo usa `gracias.php` y la Conversions API).

## 4. Webhook en Recurrente (entrega automática)
1. Recurrente → **Configuración → Desarrolladores y API → Webhooks** → **añadir URL**:
   `https://365recetas.com/api/webhook.php`
2. Copia el **signing secret** (`whsec_…`) que te muestra y pégalo en
   `config.php` → `RECURRENTE_WEBHOOK_SECRET`.

## 5. Subir a Hostinger
- Sube **todo el contenido de `web/`** a `public_html` (por hPanel → Administrador de archivos, o FTP).
- Verifica que `descargas/.htaccess` se haya subido (los archivos que empiezan con punto a veces se ocultan).
- Confirma que tu PHP sea 7.4+ y que **cURL** esté activo (lo está por defecto en Hostinger).

## 6. Probar (modo prueba, sin dinero real)
1. Con la llave `sk_test_…` puesta, abre tu sitio y dale **Comprar**.
2. Completa el pago de prueba en Recurrente.
3. Debe **regresar a `gracias.php`** y llegar el **email con el enlace de descarga**.
4. El enlace `api/descargar.php?token=…` baja el PDF; sin token válido da 403.

## 7. Pasar a producción
- Cambia `RECURRENTE_SECRET_KEY` por la llave **live** (`sk_live_…`).
- Repite el webhook con la URL de producción si cambia y actualiza el `whsec_`.
- Verifica que `SITE_URL` sea el dominio real con https.

## Notas
- **Precio por país:** se detecta por IP (header de Cloudflare si lo activas, o `ip-api.com` gratis).
  El precio se decide **en el servidor**, no en el navegador (no se puede manipular).
- **Seguridad:** la llave secreta vive solo en `config.php` (PHP, no se expone). El PDF no es
  descargable directo (lo bloquea `.htaccess`); solo `descargar.php` con token de pago lo entrega.
- **Combo (recetario + 3 complementos):** la sección "Llévate el combo completo" SÍ está cableada.
  Cobra `PRECIO_COMBO_USD` ($14.97) / `PRECIO_COMBO_GTQ` (Q119) en un solo pago. El webhook y
  `gracias.php` detectan el combo **por el monto** y entregan `COMBO_PDF_FILE`.
  ⚠️ **Pendiente real:** los 3 complementos (postres / air fryer / plan 30 días) todavía NO existen
  como PDF. Mientras tanto `COMBO_PDF_FILE` apunta al recetario principal, así que un comprador del
  combo recibiría solo el recetario. Antes de vender el combo en serio: crea esos 3 PDFs (o un único
  PDF combinado), súbelo a `web/descargas/` y pon su nombre en `COMBO_PDF_FILE` (config.php).
