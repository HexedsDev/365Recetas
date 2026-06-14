<?php
/* Devuelve la moneda y el precio según el país del visitante (para mostrar). */
require_once __DIR__ . '/lib.php';

$p = precio_para_pais();
$c = precio_combo_para_pais();
json_out([
  'currency' => $p['currency'],
  'symbol'   => $p['symbol'],
  'now'      => $p['now'],
  'old'      => $p['old'],
  'total'    => $p['total'],
  'combo'    => [
    'currency' => $c['currency'],
    'symbol'   => $c['symbol'],
    'now'      => $c['now'],
    'old'      => $c['old'],
  ],
]);
