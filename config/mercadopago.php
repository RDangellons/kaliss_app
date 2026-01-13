<?php
// config/mercadopago.php
// Integración Checkout Pro (Mercado Pago) usando cURL (sin SDK para evitar problemas de versión)

declare(strict_types=1);

/**
 * Pega tu Access Token aquí (TEST o PROD).
 * - TEST: para pruebas.
 * - PROD: para cobros reales.
 */
function mp_access_token(): string {
  // Si en Hostinger llegas a usar variables de entorno, puedes hacer:
  // $t = getenv('MP_ACCESS_TOKEN'); if ($t) return $t;
  return 'APP_USR-7342577859629581-011314-176c3be371510085b4cea2bd13a1156a-3132600948';
}

/**
 * Si estás en pruebas, puedes usar sandbox_init_point (true).
 * En producción, déjalo en false.
 */
function mp_use_sandbox_init_point(): bool {
  return false;
}

/**
 * Base URL absoluta del sitio (para back_urls y notification_url).
 * Ej: https://tudominio.com/kaliss_app
 */
function site_base_url(): string {
  // 1) Si estás usando ngrok, ponla aquí (o por variable de entorno)
  $ngrok = trim((string)($_ENV['NGROK_URL'] ?? getenv('NGROK_URL') ?? ''));
  if ($ngrok !== 'https://danyel-pseudospherical-nicky.ngrok-free.dev/kaliss_app/') {
    return rtrim($ngrok, '/');
  }

  // 2) Si no hay ngrok, usa lo normal (Hostinger/producción)
  $scheme = 'http';
  $https = $_SERVER['HTTPS'] ?? '';
  $xfp = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
  if ($https && $https !== 'off') $scheme = 'https';
  if ($xfp) $scheme = $xfp;

  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $script = (string)($_SERVER['SCRIPT_NAME'] ?? '/');
  $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');

  if (str_ends_with($dir, '/api/mp')) $dir = substr($dir, 0, -7);
  if (str_ends_with($dir, '/api'))    $dir = substr($dir, 0, -4);

  return $scheme . '://' . $host . ($dir ? $dir : '');
}

/**
 * Hace una petición HTTP a la API de Mercado Pago.
 */
function mp_request(string $method, string $url, ?array $body = null): array {
  $ch = curl_init($url);
  $headers = [
    'Authorization: Bearer ' . mp_access_token(),
    'Content-Type: application/json'
  ];

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_TIMEOUT, 25);

  if ($body !== null) {
    $json = json_encode($body);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
  }

  $resp = curl_exec($ch);
  $err  = curl_error($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($resp === false) {
    return ['ok'=>false, 'http'=>0, 'error'=>'cURL error: ' . $err];
  }

  $data = json_decode($resp, true);
  if (!is_array($data)) {
    $data = ['raw'=>$resp];
  }

  if ($code < 200 || $code >= 300) {
    return ['ok'=>false, 'http'=>$code, 'data'=>$data, 'error'=>'HTTP ' . $code];
  }

  return ['ok'=>true, 'http'=>$code, 'data'=>$data];
}

/**
 * Crea una preferencia (Checkout Pro)
 * POST https://api.mercadopago.com/checkout/preferences
 */
function mp_create_preference(array $payload): array {
  return mp_request('POST', 'https://api.mercadopago.com/checkout/preferences', $payload);
}

/**
 * Obtiene un pago por ID
 * GET https://api.mercadopago.com/v1/payments/{id}
 */
function mp_get_payment(string $paymentId): array {
  $url = 'https://api.mercadopago.com/v1/payments/' . rawurlencode($paymentId);
  return mp_request('GET', $url, null);
}
