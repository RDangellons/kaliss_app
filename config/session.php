<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

function csrf_token(): string {
  return $_SESSION['csrf'] ?? '';
}

function require_post_csrf(): void {
  $token = $_POST['csrf'] ?? '';
  if (!$token || !hash_equals(csrf_token(), (string)$token)) {
    http_response_code(403);
    echo json_encode(["ok"=>false, "error"=>"CSRF inválido"]);
    exit;
  }
}

function cart_get(): array {
  return $_SESSION['cart'] ?? [];
}

function cart_set(array $cart): void {
  $_SESSION['cart'] = $cart;
}

function cart_count(): int {
  $c = 0;
  foreach (cart_get() as $qty) $c += (int)$qty;
  return $c;
}
