<?php
/* Descarga protegida del PDF: solo con un token firmado válido (de pago confirmado). */
require_once __DIR__ . '/lib.php';

$tok = verify_download_token($_GET['token'] ?? '');
if (!$tok) { http_response_code(403); echo 'Enlace inválido o expirado.'; exit; }

$name = basename($tok['file']);                 // sin path traversal
$file = __DIR__ . '/../descargas/' . $name;
if (!is_file($file)) { http_response_code(404); echo 'Archivo no encontrado.'; exit; }

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Length: ' . filesize($file));
header('Cache-Control: private, no-store');
readfile($file);
exit;
