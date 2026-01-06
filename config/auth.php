<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';

function is_logged_in(): bool {
  return !empty($_SESSION['user_id']);
}

function login_user(array $user): void {
  session_regenerate_id(true);
  $_SESSION['user_id'] = (int)$user['id'];
  $_SESSION['user_name'] = (string)$user['name'];
  $_SESSION['user_email'] = (string)$user['email'];
}

function logout_user(): void {
  unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
}

function require_login_page(string $next = ''): void {
  if (is_logged_in()) return;
  $next = $next !== '' ? $next : ($_SERVER['REQUEST_URI'] ?? 'checkout.php');
  header("Location: /auth/login.php?next=" . urlencode($next));
  exit;
}

function require_login_json(): void {
  if (is_logged_in()) return;
  http_response_code(401);
  header('Content-Type: application/json');
  echo json_encode(["ok"=>false,"error"=>"Debes iniciar sesión para continuar."]);
  exit;
}
