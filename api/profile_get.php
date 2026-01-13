<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_login_json();

$pdo = db();
$userId = (int)($_SESSION['user_id'] ?? 0);

try {
  $st = $pdo->prepare("SELECT id, name, email, phone FROM users WHERE id=? LIMIT 1");
  $st->execute([$userId]);
  $u = $st->fetch();
  if (!$u) {
    http_response_code(404);
    echo json_encode(["ok"=>false, "error"=>"Usuario no encontrado."]);
    exit;
  }

  $st2 = $pdo->prepare("SELECT phone, address_line1, address_line2, city, state, postal_code FROM user_addresses WHERE user_id=? LIMIT 1");
  $st2->execute([$userId]);
  $a = $st2->fetch() ?: [];

  // phone: prioriza dirección si existe, si no el de users
  $phone = (string)($a['phone'] ?? $u['phone'] ?? '');

  echo json_encode([
    "ok"=>true,
    "data"=>[
      "name"  => (string)($u['name'] ?? ''),
      "email" => (string)($u['email'] ?? ''),
      "phone" => $phone,
      "address_line1" => (string)($a['address_line1'] ?? ''),
      "address_line2" => (string)($a['address_line2'] ?? ''),
      "city" => (string)($a['city'] ?? ''),
      "state" => (string)($a['state'] ?? ''),
      "postal_code" => (string)($a['postal_code'] ?? ''),
    ]
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok"=>false, "error"=>"Error cargando perfil."]);
}
