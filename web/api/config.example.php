<?php
/* =========================================================================
   365recetas.com — CONFIGURACIÓN (PLANTILLA)
   1) Copia este archivo como  config.php
   2) Pon aquí TUS datos reales.
   config.php NO se sube al repo (está en .gitignore) porque lleva la llave secreta.
   ========================================================================= */

/* ===== RECURRENTE ===== */
// Llave secreta de API (Configuración → Llaves API en app.recurrente.com).
// Empieza con la de PRUEBA (no mueve dinero real). Producción luego.
const RECURRENTE_SECRET_KEY = 'PEGA_TU_LLAVE_SECRETA_AQUI';   // ej: sk_test_xxx o sk_live_xxx
const RECURRENTE_API        = 'https://app.recurrente.com/api';
// Signing secret del webhook (Configuración → Desarrolladores y API → Webhooks).
// Se muestra al crear el endpoint. Empieza con "whsec_".
const RECURRENTE_WEBHOOK_SECRET = 'PEGA_TU_SIGNING_SECRET_AQUI';

/* ===== PRECIOS =====
   USD por defecto; GTQ (Q) si el visitante es de Guatemala. */
const PRECIO_USD     = 9.97;
const PRECIO_USD_OLD = 19.97;
const VALOR_USD      = 29.97;   // "valor total" tachado

const PRECIO_GTQ     = 79;      // ≈ 9.97 x 8
const PRECIO_GTQ_OLD = 158;
const VALOR_GTQ      = 239;

/* ===== PRODUCTO ===== */
const PRODUCTO_NOMBRE = '365 Recetas Saludables';
const PDF_FILE        = '365-Recetas-Saludables.pdf';   // está en web/descargas/

/* ===== COMBO (recetario + 3 complementos) =====
   El visitante puede llevarse el paquete completo por un solo precio. */
const PRECIO_COMBO_USD     = 14.97;
const PRECIO_COMBO_USD_OLD = 34.88;   // valor de los 4 por separado (tachado)
const PRECIO_COMBO_GTQ     = 119;     // ≈ 14.97 x 8
const PRECIO_COMBO_GTQ_OLD = 279;
const PRODUCTO_COMBO_NOMBRE = '365 Recetas + 3 Complementos (Combo)';
// PDF que se entrega al comprar el COMBO. Mientras creas los 3 complementos,
// déjalo igual al principal; cuando tengas el combo armado, pon aquí su archivo.
const COMBO_PDF_FILE = '365-Recetas-Saludables.pdf';

/* ===== URL DEL SITIO =====
   En producción cámbiala por tu dominio con https (sin barra final). */
const SITE_URL = 'https://365recetas.com';   // local de prueba: http://localhost:8080

/* ===== DESCARGA PROTEGIDA ===== */
// Cadena larga y aleatoria para firmar los enlaces de descarga. ¡Cámbiala!
const DOWNLOAD_SECRET = 'CAMBIA_ESTO_por_una_cadena_larga_y_aleatoria_3f9x';
const DOWNLOAD_TTL    = 604800;   // validez del enlace en segundos (7 días)

/* ===== META (tracking) ===== */
const META_PIXEL_ID   = '';   // tu Pixel ID (también pégalo en index.html y gracias.php)
const META_CAPI_TOKEN = '';   // token de Conversions API (opcional, recomendado)

/* ===== EMAIL (SMTP de Hostinger) ===== */
const SMTP_HOST      = 'smtp.hostinger.com';
const SMTP_PORT      = 465;                  // 465 = SSL
const SMTP_USER      = 'hola@365recetas.com';
const SMTP_PASS      = 'TU_CONTRASEÑA_DE_CORREO';
const MAIL_FROM      = 'hola@365recetas.com';
const MAIL_FROM_NAME = '365 Recetas Saludables';

/* ===== GEO ===== */
const PAIS_GTQ = 'GT';   // país que recibe precio en quetzales
