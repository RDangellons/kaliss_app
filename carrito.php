<?php
declare(strict_types=1);

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/media.php';

$pdo = db();
$cart = cart_get();
$ids = array_keys($cart);

$items = [];
$subtotal = 0.0;

$placeholderLocal = $placeholder; // de media.php

if (!empty($ids)) {
  $in = implode(',', array_fill(0, count($ids), '?'));

  $sql = "
    SELECT
      p.id, p.name, p.price, p.stock, p.slug,
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

  // Convertimos a items con qty del carrito
  foreach ($rows as $r) {
    $pid = (int)$r['id'];
    $qty = (int)($cart[$pid] ?? 0);
    if ($qty <= 0) continue;

    $price = (float)$r['price'];
    $line = $price * $qty;
    $subtotal += $line;

    $img = resolve_img_url((string)($r['main_image'] ?? ''), $placeholderLocal);

    $items[] = [
      "id"=>$pid,
      "name"=>$r['name'],
      "price"=>$price,
      "qty"=>$qty,
      "line"=>$line,
      "stock"=>(int)$r['stock'],
      "shop_name"=>$r['shop_name'],
      "shop_slug"=>$r['shop_slug'],
      "img"=>$img,
      "prod_slug"=>$r['slug'],
    ];
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Carrito | KALISS</title>
  <link rel="stylesheet" href="assets/css/index.css" />
</head>
<body>


  <header class="topbar">
    <div class="container topbar__inner">
      <a class="brand" href="index.php">
        <img src="assets/img/logo.jpeg" alt="Logo" height="50">
      </a>

      <button class="hamburger" id="btnMenu" aria-label="Abrir menú">
        <span></span><span></span><span></span>
      </button>

      <nav class="nav" id="nav">
        <a href="index.php" class="nav__link is-active">Inicio</a>
        <a href="#negocios" class="nav__link">Descubrir Marcas</a>
        <a href="#sobre" class="nav__link">Sobre Nosotros</a>
        <a href="#unete" class="nav__cta">ÚNETE A KALISS</a>
      </nav>
    </div>
  </header>


  <main class="section">
    <div class="container">
      <h1 class="page__title">Carrito</h1>
      

      <?php if (empty($items)): ?>
        <p class="lead">Tu carrito está vacío.</p>
        <a class="btn btn--primary" href="index.php#negocios">Explorar categorías</a>
      <?php else: ?>
        <input type="hidden" id="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

        <div class="grid grid--brands">
          <?php foreach ($items as $it): ?>
            <div class="card card--brand" style="cursor:default;">
              <div class="card__img card__img--tall">
                <img src="<?= htmlspecialchars($it['img']) ?>" alt="<?= htmlspecialchars($it['name']) ?>">
              </div>
              <div class="card__body">
                <h3 class="card__title"><?= htmlspecialchars($it['name']) ?></h3>
                <p class="card__text">
                  $<?= number_format($it['price'],2) ?> · <?= htmlspecialchars($it['shop_name']) ?>
                </p>

                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:10px;">
                  <label style="font-size:13px;opacity:.8;">Cantidad</label>
                  <input class="qty"
                         type="number"
                         min="1"
                         value="<?= (int)$it['qty'] ?>"
                         data-product-id="<?= (int)$it['id'] ?>"
                         style="width:80px;padding:8px;border-radius:10px;border:1px solid var(--border);">

                  <button class="btn btn--tiny btn-remove" data-product-id="<?= (int)$it['id'] ?>">Quitar</button>
                </div>

                <p style="margin-top:10px;font-size:13px;opacity:.8;">
                  Subtotal: <strong>$<?= number_format($it['line'],2) ?></strong>
                </p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="margin-top:18px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
          <button class="btn btn--ghost" id="btnClear">Vaciar carrito</button>
          <div style="text-align:right;">
            <div style="font-size:14px;opacity:.8;">Subtotal</div>
            <div style="font-size:22px;"><strong>$<?= number_format($subtotal,2) ?></strong></div>
            <a class="btn btn--primary" href="checkout.php" style="margin-top:10px;display:inline-block;">Continuar a pago</a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </main>
  <footer class="footer">
  <div class="container footer__inner">
    <p>© <?= date("Y") ?> KALISS</p>
  </div>
</footer>


  <script src="assets/js/carrito.js"></script>
  <script src="assets/js/menu.js"></script>
</body>
</html>
