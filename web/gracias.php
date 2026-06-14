<?php
require_once __DIR__ . '/api/lib.php';

$cid = $_GET['checkout_id'] ?? ($_GET['id'] ?? ($_GET['cid'] ?? ''));
$paid = false; $download = ''; $value = 0; $currency = 'USD';

if ($cid && strpos(RECURRENTE_SECRET_KEY, 'PEGA_') !== 0) {
  $r  = recurrente_req('GET', '/checkouts/' . urlencode($cid));
  $co = $r['json'] ?? [];
  if (($co['status'] ?? '') === 'paid') {
    $paid     = true;
    $currency = $co['currency'] ?? 'USD';
    $cents    = (int) ($co['amount_in_cents'] ?? 0);
    $value    = $cents / 100;
    $email    = $co['customer']['email'] ?? ($co['customer_email'] ?? '');
    $pdf      = es_compra_combo($cents) ? COMBO_PDF_FILE : PDF_FILE;
    if ($email) $download = 'api/descargar.php?token=' . make_download_token($email, $pdf);
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>¡Gracias por tu compra! · 365 Recetas Saludables</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:'Segoe UI',Helvetica,Arial,sans-serif;background:#3c5a39;color:#eef2ea;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}
  .card{background:#f6f4ee;color:#2b2b2b;border-radius:18px;max-width:480px;width:100%;padding:32px 26px;text-align:center;box-shadow:0 16px 40px rgba(0,0,0,.35);}
  .check{width:78px;height:78px;border-radius:50%;background:#5aa64f;color:#fff;font-size:40px;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;}
  h1{font-family:Georgia,serif;color:#22341f;font-size:26px;margin-bottom:10px;}
  p{color:#555;font-size:15px;margin-bottom:10px;line-height:1.5;}
  .btn{display:inline-block;background:#d9622b;color:#fff;text-decoration:none;font-weight:800;padding:16px 28px;border-radius:11px;margin:16px 0 8px;font-size:17px;}
  .mail{background:#eaf4e5;color:#22341f;border-radius:10px;padding:14px;font-size:14px;margin-top:14px;}
  .back{display:inline-block;margin-top:18px;color:#5aa64f;text-decoration:none;font-size:13px;font-weight:700;}
</style>
</head>
<body>
  <div class="card">
    <div class="check">✓</div>
    <h1>¡Gracias por tu compra!</h1>
    <p>Tu recetario <b>365 Recetas Saludables</b> ya es tuyo, con el contador de calorías en cada receta.</p>
    <?php if ($download): ?>
      <a class="btn" href="<?php echo htmlspecialchars($download); ?>">⬇ Descargar mi recetario</a>
      <div class="mail">📧 También te enviamos el enlace a tu correo. Revisa tu bandeja (y spam).</div>
    <?php else: ?>
      <div class="mail">📧 Te enviamos el enlace de descarga a tu correo electrónico. Revisa tu bandeja de entrada (y la carpeta de spam). Si no llega en unos minutos, escríbenos a hola@365recetas.com</div>
    <?php endif; ?>
    <a class="back" href="index.html">← Volver al inicio</a>
  </div>

<?php $pixel = META_PIXEL_ID; if ($pixel): ?>
<script>
  !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
  n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script',
  'https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', <?php echo json_encode($pixel); ?>);
  fbq('track','PageView');
  <?php if ($paid): ?>
  fbq('track','Purchase',{value:<?php echo json_encode((float)$value); ?>,currency:<?php echo json_encode($currency); ?>},{eventID:<?php echo json_encode($cid); ?>});
  <?php endif; ?>
</script>
<?php endif; ?>
</body>
</html>
