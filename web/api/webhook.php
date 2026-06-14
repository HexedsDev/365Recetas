<?php
/* Recibe el webhook de Recurrente, confirma el pago y entrega el PDF por email. */
require_once __DIR__ . '/lib.php';

$raw = file_get_contents('php://input');

if (!svix_verify($raw, RECURRENTE_WEBHOOK_SECRET)) {
  http_response_code(400); echo 'firma invalida'; exit;
}

$ev = json_decode($raw, true);
if (!is_array($ev)) { http_response_code(400); echo 'json invalido'; exit; }

$type    = $ev['event_type'] ?? '';
$status  = $ev['status'] ?? '';
$eventId = $ev['id'] ?? ('ev_' . md5($raw));

$pagado = in_array($type, ['intent.succeeded', 'intent.paid'], true)
       || in_array($status, ['succeeded', 'paid'], true);

if ($pagado) {
  // Idempotencia: no procesar dos veces el mismo evento (Recurrente reintenta)
  $proc = __DIR__ . '/.processed';
  $seen = is_file($proc) ? file($proc, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
  if (!in_array($eventId, $seen, true)) {
    $email    = $ev['customer']['email'] ?? '';
    $cents    = (int) ($ev['amount_in_cents'] ?? 0);
    $amount   = $cents / 100;
    $currency = $ev['currency'] ?? 'USD';
    $pdf      = es_compra_combo($cents) ? COMBO_PDF_FILE : PDF_FILE;
    if ($email) {
      $link = rtrim(SITE_URL, '/') . '/api/descargar.php?token=' . make_download_token($email, $pdf);
      send_email_smtp($email, 'Tu recetario · 365 Recetas Saludables 🥗', email_entrega_html($link));
      meta_capi_purchase($email, $amount, $currency, $eventId);
    }
    file_put_contents($proc, $eventId . "\n", FILE_APPEND | LOCK_EX);
  }
}

http_response_code(200);
echo 'ok';
