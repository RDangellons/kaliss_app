<?php
declare(strict_types=1);

/**
 * Placeholder base64 (por si falta la imagen)
 */
$placeholder = "data:image/svg+xml;utf8," . rawurlencode(
  '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="600">
    <rect width="100%" height="100%" fill="#f3e3dc"/>
    <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
      fill="#6a5246" font-family="Arial" font-size="28">Sin imagen</text>
  </svg>'
);

/**
 * Detecta URLs externas o data-uri
 */
function is_external_url(string $url): bool {
  $url = trim($url);
  if ($url === '') return false;
  return (bool)preg_match('#^(https?:)?//#i', $url) || str_starts_with($url, 'data:');
}

/**
 * Resuelve rutas de imagen locales comunes y retorna fallback si no existe.
 * Útil para: assets/img, uploads, etc.
 */
function resolve_img_url(string $path, string $fallback): string {
  $path = trim($path);
  if ($path === '') return $fallback;

  // externa o data-uri
  if (is_external_url($path)) return $path;

  // normaliza (sin slash inicial)
  $candidate = ltrim($path, '/');

  // 1) Si existe tal cual
  $fs = __DIR__ . '/../' . $candidate; // /config -> sube al root
  if (file_exists($fs)) return $candidate;

  // 2) Intentar en carpetas típicas
  $basename = basename($candidate);
  $tryDirs = [
    'uploads/',
    'uploads/products/',
    'uploads/shops/',
    'assets/img/',
    'assets/img/shops/',
    'assets/img/products/',
    'img/',
  ];

  foreach ($tryDirs as $dir) {
    $try = $dir . $basename;
    if (file_exists(__DIR__ . '/../' . $try)) return $try;
  }

  return $fallback;
}
