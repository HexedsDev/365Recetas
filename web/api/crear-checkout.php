<?php
/* Crea una sesión de pago en Recurrente y devuelve la checkout_url. */
require_once __DIR__ . '/lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['error' => 'método no permitido'], 405);

if (strpos(RECURRENTE_SECRET_KEY, 'PEGA_') === 0) {
  json_out(['error' => 'Falta configurar la llave de Recurrente en config.php'], 500);
}

$in      = json_decode(file_get_contents('php://input'), true);
$esCombo = is_array($in) && !empty($in['combo']);

$p      = $esCombo ? precio_combo_para_pais() : precio_para_pais();
$nombre = $esCombo ? PRODUCTO_COMBO_NOMBRE     : PRODUCTO_NOMBRE;

$body = [
  'items' => [[
    'name'            => $nombre,
    'amount_in_cents' => $p['amount_in_cents'],
    'currency'        => $p['currency'],
    'quantity'        => 1,
  ]],
  'success_url' => rtrim(SITE_URL, '/') . '/gracias.php',
  'cancel_url'  => rtrim(SITE_URL, '/') . '/index.html#comprar',
];

$r = recurrente_req('POST', '/checkouts', $body);

if (($r['code'] === 201 || $r['code'] === 200) && !empty($r['json']['checkout_url'])) {
  json_out([
    'checkout_url' => $r['json']['checkout_url'],
    'id'           => $r['json']['id'] ?? null,
  ]);
}

json_out(['error' => 'No se pudo crear el checkout', 'detalle' => $r['json'] ?? $r['raw']], 500);
