<?php
/* =========================================================================
   365recetas.com — FUNCIONES COMPARTIDAS (no necesitas editar esto)
   ========================================================================= */
require_once __DIR__ . '/config.php';

/* ---------- Respuesta JSON ---------- */
function json_out($data, $code = 200) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data);
  exit;
}

/* ---------- IP y país del visitante ---------- */
function client_ip() {
  foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $h) {
    if (!empty($_SERVER[$h])) {
      $ip = trim(explode(',', $_SERVER[$h])[0]);
      if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
    }
  }
  return null;
}

function geo_country() {
  // 1) Header de proxy/CDN (Cloudflare lo da gratis si lo activas en Hostinger)
  foreach (['HTTP_CF_IPCOUNTRY','HTTP_X_COUNTRY'] as $h) {
    if (!empty($_SERVER[$h]) && strlen($_SERVER[$h]) === 2) return strtoupper($_SERVER[$h]);
  }
  // 2) API gratis por IP
  $ip = client_ip();
  if ($ip && !preg_match('/^(127\.|10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/', $ip)) {
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    $r = @file_get_contents("http://ip-api.com/json/{$ip}?fields=countryCode", false, $ctx);
    if ($r) { $j = json_decode($r, true); if (!empty($j['countryCode'])) return strtoupper($j['countryCode']); }
  }
  return null;
}

/* ---------- Precio según país (USD por defecto, GTQ si Guatemala) ---------- */
function precio_para_pais() {
  if (geo_country() === PAIS_GTQ) {
    return [
      'currency' => 'GTQ', 'symbol' => 'Q',
      'now' => (string) PRECIO_GTQ, 'old' => (string) PRECIO_GTQ_OLD, 'total' => (string) VALOR_GTQ,
      'amount_in_cents' => (int) round(PRECIO_GTQ * 100),
    ];
  }
  return [
    'currency' => 'USD', 'symbol' => '$',
    'now' => number_format(PRECIO_USD, 2, '.', ''),
    'old' => number_format(PRECIO_USD_OLD, 2, '.', ''),
    'total' => number_format(VALOR_USD, 2, '.', ''),
    'amount_in_cents' => (int) round(PRECIO_USD * 100),
  ];
}

/* ---------- Precio del COMBO según país ---------- */
function precio_combo_para_pais() {
  if (geo_country() === PAIS_GTQ) {
    return [
      'currency' => 'GTQ', 'symbol' => 'Q',
      'now' => (string) PRECIO_COMBO_GTQ, 'old' => (string) PRECIO_COMBO_GTQ_OLD,
      'amount_in_cents' => (int) round(PRECIO_COMBO_GTQ * 100),
    ];
  }
  return [
    'currency' => 'USD', 'symbol' => '$',
    'now' => number_format(PRECIO_COMBO_USD, 2, '.', ''),
    'old' => number_format(PRECIO_COMBO_USD_OLD, 2, '.', ''),
    'amount_in_cents' => (int) round(PRECIO_COMBO_USD * 100),
  ];
}

/* ¿El monto pagado corresponde al combo? (decide qué PDF entregar) */
function es_compra_combo($amount_in_cents) {
  $combo = [(int) round(PRECIO_COMBO_USD * 100), (int) round(PRECIO_COMBO_GTQ * 100)];
  return in_array((int) $amount_in_cents, $combo, true);
}

/* ---------- Llamadas a la API de Recurrente ---------- */
function recurrente_req($method, $path, $body = null) {
  $ch = curl_init(RECURRENTE_API . $path);
  $headers = ['X-SECRET-KEY: ' . RECURRENTE_SECRET_KEY, 'Content-Type: application/json', 'Accept: application/json'];
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_HTTPHEADER => $headers, CURLOPT_TIMEOUT => 30,
  ]);
  if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
  $res  = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err  = curl_error($ch);
  curl_close($ch);
  return ['code' => $code, 'json' => json_decode($res, true), 'raw' => $res, 'err' => $err];
}

/* ---------- Token de descarga firmado (sin base de datos) ---------- */
function b64url($s)     { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }
function b64url_dec($s) { return base64_decode(strtr($s, '-_', '+/')); }

function make_download_token($email, $file = PDF_FILE) {
  $payload = $email . '|' . (time() + DOWNLOAD_TTL) . '|' . $file;
  return b64url($payload) . '.' . hash_hmac('sha256', $payload, DOWNLOAD_SECRET);
}
function verify_download_token($token) {
  $parts = explode('.', $token, 2);
  if (count($parts) !== 2) return false;
  $payload = b64url_dec($parts[0]);
  if (!hash_equals(hash_hmac('sha256', $payload, DOWNLOAD_SECRET), $parts[1])) return false;
  $f = explode('|', $payload);
  if (count($f) < 2 || (int) $f[1] < time()) return false;
  // [email, archivo]  (archivo opcional para tokens antiguos)
  return ['email' => $f[0], 'file' => basename($f[2] ?? PDF_FILE)];
}

/* ---------- Verificación de firma del webhook (estilo Svix) ---------- */
function svix_verify($payload, $secret) {
  if (empty($secret) || strpos($secret, 'PEGA_') === 0) return true; // sin secreto: no verifica (¡configúralo!)
  $id  = $_SERVER['HTTP_SVIX_ID']        ?? ($_SERVER['HTTP_WEBHOOK_ID']        ?? '');
  $ts  = $_SERVER['HTTP_SVIX_TIMESTAMP'] ?? ($_SERVER['HTTP_WEBHOOK_TIMESTAMP'] ?? '');
  $sh  = $_SERVER['HTTP_SVIX_SIGNATURE'] ?? ($_SERVER['HTTP_WEBHOOK_SIGNATURE'] ?? '');
  if (!$id || !$ts || !$sh) return false;
  if (abs(time() - (int) $ts) > 300) return false;
  $key      = base64_decode(substr($secret, strpos($secret, '_') + 1));
  $expected = base64_encode(hash_hmac('sha256', "{$id}.{$ts}.{$payload}", $key, true));
  foreach (explode(' ', $sh) as $part) {
    $p = explode(',', $part, 2);
    $sig = count($p) === 2 ? $p[1] : $p[0];
    if (hash_equals($expected, $sig)) return true;
  }
  return false;
}

/* ---------- Meta Conversions API (opcional) ---------- */
function meta_capi_purchase($email, $value, $currency, $eventId) {
  if (empty(META_PIXEL_ID) || empty(META_CAPI_TOKEN)) return;
  $url = 'https://graph.facebook.com/v19.0/' . META_PIXEL_ID . '/events?access_token=' . urlencode(META_CAPI_TOKEN);
  $data = ['data' => [[
    'event_name' => 'Purchase', 'event_time' => time(), 'event_id' => $eventId, 'action_source' => 'website',
    'user_data' => ['em' => [hash('sha256', strtolower(trim($email)))]],
    'custom_data' => ['currency' => $currency, 'value' => $value],
  ]]];
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($data), CURLOPT_TIMEOUT => 15,
  ]);
  curl_exec($ch); curl_close($ch);
}

/* ---------- Email de entrega (HTML) ---------- */
function email_entrega_html($link) {
  return '<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;color:#2b2b2b;">'
    . '<h2 style="color:#22341f;">¡Gracias por tu compra! 🥗</h2>'
    . '<p>Tu recetario <b>365 Recetas Saludables</b> (con el contador de calorías en cada receta) está listo.</p>'
    . '<p style="text-align:center;margin:26px 0;"><a href="' . htmlspecialchars($link) . '" '
    . 'style="background:#d9622b;color:#fff;text-decoration:none;padding:15px 28px;border-radius:9px;font-weight:bold;display:inline-block;">⬇ Descargar mi recetario</a></p>'
    . '<p style="font-size:13px;color:#666;">El enlace es personal y válido por 7 días. ¿Algún problema? Responde a este correo y te ayudamos.</p>'
    . '<p style="font-size:12px;color:#999;margin-top:24px;">365recetas.com</p></div>';
}

/* ---------- Envío SMTP (sin librerías externas) ---------- */
function smtp_b($s) { return '=?UTF-8?B?' . base64_encode($s) . '?='; }
function send_email_smtp($to, $subject, $html) {
  if (strpos(SMTP_PASS, 'TU_CONTRA') === 0) return [false, 'SMTP sin configurar'];
  $transport = (SMTP_PORT == 465 ? 'ssl://' : 'tcp://') . SMTP_HOST;
  $fp = @stream_socket_client($transport . ':' . SMTP_PORT, $errno, $errstr, 20);
  if (!$fp) return [false, "conexión: $errstr"];
  $read = function () use ($fp) {
    $data = '';
    while ($line = fgets($fp, 515)) { $data .= $line; if (isset($line[3]) && $line[3] === ' ') break; }
    return $data;
  };
  $cmd = function ($c) use ($fp, $read) { fwrite($fp, $c . "\r\n"); return $read(); };
  $read();
  $cmd('EHLO 365recetas.com');
  if (SMTP_PORT != 465) {
    $cmd('STARTTLS');
    stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    $cmd('EHLO 365recetas.com');
  }
  $cmd('AUTH LOGIN');
  $cmd(base64_encode(SMTP_USER));
  $r = $cmd(base64_encode(SMTP_PASS));
  if (strpos($r, '235') === false) { fclose($fp); return [false, "auth: $r"]; }
  $cmd('MAIL FROM:<' . MAIL_FROM . '>');
  $cmd('RCPT TO:<' . $to . '>');
  $cmd('DATA');
  $headers = 'From: ' . smtp_b(MAIL_FROM_NAME) . ' <' . MAIL_FROM . ">\r\n"
    . 'To: <' . $to . ">\r\n"
    . 'Subject: ' . smtp_b($subject) . "\r\n"
    . "MIME-Version: 1.0\r\n"
    . "Content-Type: text/html; charset=UTF-8\r\n";
  $r = $cmd($headers . "\r\n" . $html . "\r\n.");
  $cmd('QUIT');
  fclose($fp);
  return [strpos($r, '250') !== false, $r];
}
