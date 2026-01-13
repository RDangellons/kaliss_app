<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_login_json();

require_post_csrf();

$pdo = db();
$userId = (int)($_SESSION['user_id'] ?? 0);

$name  = trim((string)($_POST['name'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$a1 = trim((string)($_POST['address_line1'] ?? ''));
$a2 = trim((string)($_POST['address_line2'] ?? ''));
$city = trim((string)($_POST['city'] ?? ''));
$state = trim((string)($_POST['state'] ?? ''));
$cp = trim((string)($_POST['postal_code'] ?? ''));

if ($name === '') {
  echo json_encode(["ok"=>false, "error"=>"El nombre es obligatorio."]);
  exit;
}

try {
  $pdo->beginTransaction();

  // Actualiza nombre/phone en users (phone es opcional)
  $st = $pdo->prepare("UPDATE users SET name=?, phone=? WHERE id=? LIMIT 1");
  $st->execute([$name, $phone !== '' ? $phone : null, $userId]);

  // UPSERT dirección
  $st2 = $pdo->prepare("
    INSERT INTO user_addresses (user_id, phone, address_line1, address_line2, city, state, postal_code)
    VALUES (?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
      phone=VALUES(phone),
      address_line1=VALUES(address_line1),
      address_line2=VALUES(address_line2),
      city=VALUES(city),
      state=VALUES(state),
      postal_code=VALUES(postal_code)
  ");
  $st2->execute([
    $userId,
    $phone !== '' ? $phone : null,
    $a1 !== '' ? $a1 : null,
    $a2 !== '' ? $a2 : null,
    $city !== '' ? $city : null,
    $state !== '' ? $state : null,
    $cp !== '' ? $cp : null,
  ]);

  $pdo->commit();

  // refrescar nombre en sesión para que el header cambie al instante
  $_SESSION['user_name'] = $name;

  echo json_encode(["ok"=>true]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(["ok"=>false, "error"=>"No se pudo guardar el perfil."]);
}
