<?php
declare(strict_types=1);

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/media.php';
require_once __DIR__ . '/config/auth.php';
require_login_page('checkout.php');


// Opcional: precargar
$prefName  = $_SESSION['user_name'] ?? '';
$prefEmail = $_SESSION['user_email'] ?? '';

// Precargar dirección guardada (si existe)
$prefPhone = '';
$prefA1 = '';
$prefA2 = '';
$prefCity = '';
$prefState = '';
$prefCP = '';

try {
  $uid = (int)($_SESSION['user_id'] ?? 0);
  if ($uid > 0) {
    $stP = db()->prepare("SELECT phone, address_line1, address_line2, city, state, postal_code FROM user_addresses WHERE user_id=? LIMIT 1");
    $stP->execute([$uid]);
    $p = $stP->fetch() ?: [];
    $prefPhone = (string)($p['phone'] ?? '');
    $prefA1 = (string)($p['address_line1'] ?? '');
    $prefA2 = (string)($p['address_line2'] ?? '');
    $prefCity = (string)($p['city'] ?? '');
    $prefState = (string)($p['state'] ?? '');
    $prefCP = (string)($p['postal_code'] ?? '');
  }
} catch (Throwable $e) {
  // si no existe la tabla aún, no rompemos el checkout
}

$pdo = db();
$cart = cart_get();
$ids = array_keys($cart);

$items = [];
$subtotal = 0.0;

if (!empty($ids)) {
  $in = implode(',', array_fill(0, count($ids), '?'));
  $sql = "
    SELECT p.id, p.name, p.price, p.stock, p.slug,
           s.shop_name, s.slug AS shop_slug,
           (
             SELECT pi.image_path
             FROM product_images pi
             WHERE pi.product_id = p.id
             ORDER BY pi.is_main DESC, pi.sort_order ASC, pi.id ASC
             LIMIT 1
           ) AS main_image
    FROM products p
    INNER JOIN shops s ON s.id = p.shop_id
    WHERE p.id IN ($in)
      AND p.status='active'
      AND s.status='active'
  ";
  $st = $pdo->prepare($sql);
  $st->execute($ids);
  $rows = $st->fetchAll();

  foreach ($rows as $r) {
    $pid = (int)$r['id'];
    $qty = (int)($cart[$pid] ?? 0);
    if ($qty <= 0) continue;

    $price = (float)$r['price'];
    $line = $price * $qty;
    $subtotal += $line;

    $items[] = [
      "id"=>$pid,
      "name"=>$r['name'],
      "shop_name"=>$r['shop_name'],
      "price"=>$price,
      "qty"=>$qty,
      "line"=>$line,
      "img"=> resolve_img_url((string)($r['main_image'] ?? ''), $placeholder),
    ];
  }
}

if (empty($items)) {
  header("Location: carrito.php");
  exit;
}

$shipping = 0.00;
$total = $subtotal + $shipping;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Checkout | KALISS</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="assets/css/index.css" />
  <link rel="stylesheet" href="assets/css/checkout.css" />
  <link rel="stylesheet" href="assets/css/perfil.css" />
</head>
<body>

<?php include __DIR__ . '/partials/header.php'; ?>

<main class="section checkout">
  <div class="container">

    <div class="checkout__top">
      <div>
        <h1 class="checkout__title">Finalizar compra</h1>
        <p class="checkout__sub">Confirma tus datos y genera tu pedido.</p>
      </div>

      <div class="checkout__steps">
        <div class="step done"><span>1</span><small>Carrito</small></div>
        <div class="step active"><span>2</span><small>Checkout</small></div>
        <div class="step"><span>3</span><small>Listo</small></div>
      </div>
    </div>

    <div class="checkout__grid">

      <!-- Columna izquierda: Formulario -->
      <section class="panel">
        <div class="panel__head">
          <h2 class="panel__title">Datos de entrega</h2>
          <p class="panel__hint">Los campos con * son obligatorios.</p>
        </div>

        <form id="checkoutForm" class="form">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

          <div class="form__row">
            <div class="form__group">
              <label class="form__label">Nombre *</label>
              <input class="form__input" type="text" name="customer_name" value="<?= htmlspecialchars($prefName) ?>" required>
            </div>
            <div class="form__group">
              <label class="form__label">Teléfono</label>
              <input class="form__input" name="customer_phone" value="<?= htmlspecialchars($prefPhone) ?>" placeholder="771 000 0000">
            </div>
          </div>

          <div class="form__group">
            <label class="form__label">Email</label>
            <input class="form__input" type="email" name="customer_email" value="<?= htmlspecialchars($prefEmail) ?>" required>
          </div>

          <div class="form__group">
            <label class="form__label">Dirección *</label>
            <input class="form__input" name="address_line1" required value="<?= htmlspecialchars($prefA1) ?>" placeholder="Calle, número, colonia">
          </div>

          <div class="form__group">
            <label class="form__label">Referencias</label>
            <input class="form__input" name="address_line2" value="<?= htmlspecialchars($prefA2) ?>" placeholder="Entre calles, color de casa, etc.">
          </div>

          <div class="form__row">
            <div class="form__group">
              <label class="form__label">Ciudad</label>
              <input class="form__input" name="city" value="<?= htmlspecialchars($prefCity) ?>" placeholder="Tulancingo">
            </div>
            <div class="form__group">
              <label class="form__label">Estado</label>
              <input class="form__input" name="state" value="<?= htmlspecialchars($prefState) ?>" placeholder="Hidalgo">
            </div>
            <div class="form__group">
              <label class="form__label">C.P.</label>
              <input class="form__input" name="postal_code" value="<?= htmlspecialchars($prefCP) ?>" placeholder="43600">
            </div>
          </div>

          <div class="form__actions">
            <a class="btn btn--ghost" href="carrito.php">Volver al carrito</a>
            <button class="btn btn--primary" type="submit" id="btnConfirm">
              Pagar con Mercado Pago
            </button>
          </div>


          <p id="msg" class="form__msg"></p>
        </form>
      </section>

      <!-- Columna derecha: Resumen -->
      <aside class="panel panel--sticky">
        <div class="panel__head">
          <h2 class="panel__title">Resumen</h2>
          <p class="panel__hint"><?= count($items) ?> producto(s)</p>
        </div>

        <div class="summary__list">
          <?php foreach ($items as $it): ?>
            <div class="summary__item">
              <img class="summary__img" src="<?= htmlspecialchars($it['img']) ?>" alt="<?= htmlspecialchars($it['name']) ?>">
              <div class="summary__meta">
                <div class="summary__name"><?= htmlspecialchars($it['name']) ?></div>
                <div class="summary__shop"><?= htmlspecialchars($it['shop_name']) ?></div>
                <div class="summary__qty"><?= (int)$it['qty'] ?> × $<?= number_format($it['price'],2) ?></div>
              </div>
              <div class="summary__price">$<?= number_format($it['line'],2) ?></div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="summary__totals">
          <div class="row"><span>Subtotal</span><span>$<?= number_format($subtotal,2) ?></span></div>
          <div class="row"><span>Envío</span><span>$<?= number_format($shipping,2) ?></span></div>
          <div class="row total"><span>Total</span><span>$<?= number_format($total,2) ?></span></div>
        </div>

        <div class="summary__note">
          <strong>Nota:</strong> Por ahora el pedido se crea como <em>pendiente</em>. Luego integramos pago (MercadoPago/Stripe).
        </div>
      </aside>

    </div>
  </div>
</main>

<footer class="footer">
  <div class="container footer__inner">
    <p>© <?= date("Y") ?> KALISS</p>
  </div>
</footer>

<script src="assets/js/menu.js"></script>
<script src="assets/js/checkout.js"></script>
</body>
</html>
