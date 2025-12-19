<?php
// tienda.php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';

$slug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';
if ($slug === '') {
  http_response_code(400);
  echo "Falta el parámetro slug.";
  exit;
}

$pdo = db();

/**
 * 1) Intentamos cargar tienda ACTIVA
 */
$sqlShopActive = "
  SELECT
    s.id, s.slug, s.shop_name, s.city, s.description, s.logo_path, s.status,
    t.template, t.primary_color, t.accent_color, t.background_color,
    t.hero_image_path, t.custom_css
  FROM shops s
  LEFT JOIN shop_theme t ON t.shop_id = s.id
  WHERE s.slug = ? AND s.status = 'active'
  LIMIT 1
";
$stmt = $pdo->prepare($sqlShopActive);
$stmt->execute([$slug]);
$shop = $stmt->fetch();

/**
 * 2) Si no está activa, verificamos si existe (pending/blocked) para dar mensaje claro
 */
if (!$shop) {
  $sqlShopAny = "SELECT id, slug, shop_name, status FROM shops WHERE slug = ? LIMIT 1";
  $stmt2 = $pdo->prepare($sqlShopAny);
  $stmt2->execute([$slug]);
  $shopAny = $stmt2->fetch();

  if ($shopAny) {
    http_response_code(403);
    $status = $shopAny['status'];
    if ($status === 'pending') {
      echo "Esta tienda está en revisión (pendiente de activación por el admin).";
      exit;
    }
    if ($status === 'blocked') {
      echo "Esta tienda está bloqueada.";
      exit;
    }
  }

  http_response_code(404);
  echo "Tienda no encontrada.";
  exit;
}

/**
 * 3) Productos activos de la tienda + imagen principal (si existe)
 */
$sqlProducts = "
  SELECT
    p.id, p.name, p.slug, p.description, p.price, p.stock,
    (
      SELECT pi.image_path
      FROM product_images pi
      WHERE pi.product_id = p.id
      ORDER BY pi.is_main DESC, pi.sort_order ASC, pi.id ASC
      LIMIT 1
    ) AS main_image
  FROM products p
  WHERE p.shop_id = ? AND p.status = 'active'
  ORDER BY p.created_at DESC
";
$stmtP = $pdo->prepare($sqlProducts);
$stmtP->execute([(int)$shop['id']]);
$products = $stmtP->fetchAll();

/**
 * 4) Theme -> CSS variables (fallbacks por si vienen null)
 */
$primary   = $shop['primary_color'] ?? '#5F3E2E';
$accent    = $shop['accent_color'] ?? '#8F684F';
$bg        = $shop['background_color'] ?? '#F4E5DC';
$template  = $shop['template'] ?? 'premium';
$heroImage = $shop['hero_image_path'] ?? '';
$customCss = (string)($shop['custom_css'] ?? '');

/**
 * Seguridad básica: evitamos cierre de <style> por inyección.
 * (Luego, en panel, haremos que solo admin/seller pueda editar theme)
 */
$customCssSafe = str_ireplace(['</style', '<script'], ['/*blocked*/', '/*blocked*/'], $customCss);

// Placeholder imagen (SVG embebido)
$placeholder = "data:image/svg+xml;utf8," . rawurlencode(
  '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="600">
    <rect width="100%" height="100%" fill="#f3e3dc"/>
    <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
      fill="#6a5246" font-family="Arial" font-size="28">Sin imagen</text>
  </svg>'
);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($shop['shop_name']) ?> | KALISS</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="assets/css/index.css" />

  <!-- Theme dinámico por tienda -->
  <style>
    :root{
      --primary: <?= htmlspecialchars($primary) ?>;
      --primary2: <?= htmlspecialchars($accent) ?>;
      --bg: <?= htmlspecialchars($bg) ?>;
      --bg2: <?= htmlspecialchars($bg) ?>;
    }
    /* Template (por si luego quieres variar estilos por plantilla) */
    body[data-template="<?= htmlspecialchars($template) ?>"]{}
    <?= $customCssSafe ?>
  </style>
</head>

<body data-template="<?= htmlspecialchars($template) ?>">
  <header class="topbar">
    <div class="container topbar__inner">
      <a class="brand" href="index.php">KALISS</a>

      <button class="hamburger" id="btnMenu" aria-label="Abrir menú">
        <span></span><span></span><span></span>
      </button>

      <nav class="nav" id="nav">
        <a href="index.php" class="nav__link">Inicio</a>
        <a href="index.php#negocios" class="nav__link is-active">Descubrir Marcas</a>
        <a href="index.php#sobre" class="nav__link">Sobre Nosotros</a>
        <a href="index.php#unete" class="nav__cta">ÚNETE A KALISS</a>
      </nav>
    </div>
  </header>

  <main>
    <section class="hero">
      <div class="container hero__grid">
        <div class="hero__copy">
          <div class="breadcrumb">
            <a href="index.php" style="color:inherit;text-decoration:none;">Inicio</a>
            <span>/</span>
            <?= htmlspecialchars($shop['shop_name']) ?>
          </div>

          <h1 class="hero__title" style="font-size:46px; letter-spacing:.02em;">
            <?= htmlspecialchars($shop['shop_name']) ?>
          </h1>

          <p class="hero__subtitle" style="font-size:18px;">
            <?= htmlspecialchars((string)($shop['city'] ?? '')) ?>
          </p>

          <p class="hero__text">
            <?= nl2br(htmlspecialchars((string)($shop['description'] ?? ''))) ?>
          </p>

          <div class="hero__actions">
            <a class="btn btn--primary" href="#productos">Ver productos</a>
            <a class="btn btn--ghost" href="index.php#negocios">Volver a categorías</a>
          </div>
        </div>

        <div class="hero__image">
          <?php if (!empty($heroImage)): ?>
            <img src="<?= htmlspecialchars($heroImage) ?>" alt="Banner de <?= htmlspecialchars($shop['shop_name']) ?>">
          <?php else: ?>
            <img src="<?= $placeholder ?>" alt="Sin banner">
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="section" id="productos">
      <div class="container">
        <h2 class="section__title">Productos</h2>

        <?php if (empty($products)): ?>
          <p class="lead">Esta tienda aún no tiene productos publicados.</p>
        <?php else: ?>
          <div class="grid grid--brands">
            <?php foreach ($products as $p): ?>
              <?php
                $img = $p['main_image'] ? (string)$p['main_image'] : $placeholder;
                $price = number_format((float)$p['price'], 2);
              ?>
              <a class="card card--brand" href="producto.php?shop=<?= urlencode($shop['slug']) ?>&slug=<?= urlencode($p['slug']) ?>">
                <div class="card__img card__img--tall">
                  <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['name']) ?>"
                       onerror="this.onerror=null;this.src='<?= $placeholder ?>';">
                </div>
                <div class="card__body">
                  <h3 class="card__title"><?= htmlspecialchars($p['name']) ?></h3>
                  <p class="card__text">
                    $<?= $price ?> MXN · Stock: <?= (int)$p['stock'] ?>
                  </p>
                  <span class="btn btn--tiny">Ver detalle</span>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="container footer__inner">
      <p>© <?= date("Y") ?> KALISS</p>
    </div>
  </footer>

  <script src="assets/js/menu.js"></script>
</body>
</html>
