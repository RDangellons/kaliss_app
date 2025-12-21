<?php
// categoria.php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
$pdo = db();

$catSlug = strtolower(trim((string)($_GET['slug'] ?? '')));
if ($catSlug === '') {
  header("Location: index.php#negocios");
  exit;
}

// 1) Buscar la categoría real
$stCat = $pdo->prepare("SELECT id, name, slug FROM categories WHERE slug = ? AND is_active = 1 LIMIT 1");
$stCat->execute([$catSlug]);
$cat = $stCat->fetch();

if (!$cat) {
  http_response_code(404);
  echo "Categoría no encontrada.";
  exit;
}

// Placeholder imagen
$placeholder = "data:image/svg+xml;utf8," . rawurlencode(
  '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="600">
    <rect width="100%" height="100%" fill="#f3e3dc"/>
    <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
      fill="#6a5246" font-family="Arial" font-size="28">Sin imagen</text>
  </svg>'
);

// 2) Traer TIENDAS activas que tengan AL MENOS 1 producto activo en esa categoría
$sqlShops = "
  SELECT
    s.id, s.slug, s.shop_name, s.city,
    COALESCE(t.hero_image_path, '') AS hero_image,
    COALESCE(s.logo_path, '') AS logo_path
  FROM shops s
  INNER JOIN products p ON p.shop_id = s.id
  LEFT JOIN shop_theme t ON t.shop_id = s.id
  WHERE
    s.status = 'active'
    AND p.status = 'active'
    AND p.category_id = ?
  GROUP BY s.id
  ORDER BY s.created_at DESC
";
$stShops = $pdo->prepare($sqlShops);
$stShops->execute([(int)$cat['id']]);
$shops = $stShops->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($cat['name']) ?> | KALISS</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">

  <!-- Puedes usar tu ropa.css como CSS general de categorías -->
  <link rel="stylesheet" href="assets/css/ropa.css" />
</head>
<body>
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

  <main class="section">
    <div class="container">
      <div class="breadcrumb">Inicio <span>/</span> <?= htmlspecialchars($cat['name']) ?></div>
      <h1 class="page__title"><?= htmlspecialchars($cat['name']) ?></h1>

      <?php if (empty($shops)): ?>
        <p style="text-align:center; opacity:.8;">Aún no hay tiendas con productos en esta categoría.</p>
      <?php else: ?>
        <div class="grid grid--brands">
          <?php foreach ($shops as $s): ?>
            <?php
              $img = $placeholder;
              if (!empty($s['hero_image'])) $img = (string)$s['hero_image'];
              else if (!empty($s['logo_path'])) $img = (string)$s['logo_path'];
            ?>
            <article class="card card--brand">
              <div class="card__img card__img--tall">
                <img src="<?= htmlspecialchars($img) ?>"
                     alt="<?= htmlspecialchars($s['shop_name']) ?>"
                     onerror="this.onerror=null;this.src='<?= $placeholder ?>';">
              </div>
              <div class="card__body">
                <h3 class="card__title"><?= htmlspecialchars($s['shop_name']) ?></h3>
                <p class="card__text">
                  Marca local
                  <?php if (!empty($s['city'])): ?>
                    · <?= htmlspecialchars($s['city']) ?>
                  <?php endif; ?>
                </p>
                <a class="btn btn--tiny" href="tienda.php?slug=<?= urlencode($s['slug']) ?>">Descubrir marca</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="center">
        <a class="btn btn--primary btn--wide" href="index.php">Volver a categorías</a>
      </div>
    </div>
  </main>

  <footer class="footer">
    <div class="container footer__inner">
      <p>© <?= date("Y") ?> KALISS</p>
    </div>
  </footer>

  <script src="assets/js/menu.js"></script>
</body>
</html>
