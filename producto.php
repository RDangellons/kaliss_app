<?php
// producto.php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';


$shopSlug = isset($_GET['shop']) ? trim((string)$_GET['shop']) : '';
$prodSlug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';

if ($shopSlug === '' || $prodSlug === '') {
  http_response_code(400);
  echo "Faltan parámetros (shop, slug).";
  exit;
}

$pdo = db();

/**
 * 1) Cargar tienda ACTIVA + theme
 */
$sqlShop = "
  SELECT
    s.id, s.slug, s.shop_name, s.city, s.description, s.logo_path, s.status,
    t.template, t.primary_color, t.accent_color, t.background_color,
    t.hero_image_path, t.custom_css
  FROM shops s
  LEFT JOIN shop_theme t ON t.shop_id = s.id
  WHERE s.slug = ? AND s.status = 'active'
  LIMIT 1
";
$stShop = $pdo->prepare($sqlShop);
$stShop->execute([$shopSlug]);
$shop = $stShop->fetch();

if (!$shop) {
  // Si existe pero está pending/blocked, mensaje claro
  $stAny = $pdo->prepare("SELECT status FROM shops WHERE slug=? LIMIT 1");
  $stAny->execute([$shopSlug]);
  $any = $stAny->fetch();

  http_response_code(404);
  if ($any && $any['status'] === 'pending') {
    echo "Esta tienda está en revisión (pendiente de activación por el admin).";
    exit;
  }
  if ($any && $any['status'] === 'blocked') {
    echo "Esta tienda está bloqueada.";
    exit;
  }
  echo "Tienda no encontrada.";
  exit;
}

/**
 * 2) Cargar producto ACTIVO por slug (único por tienda)
 */
$sqlProduct = "
  SELECT
    p.id, p.name, p.slug, p.description, p.price, p.stock, p.status,
    c.name AS category_name, c.slug AS category_slug
  FROM products p
  INNER JOIN categories c ON c.id = p.category_id
  WHERE p.shop_id = ? AND p.slug = ? AND p.status = 'active'
  LIMIT 1
";
$stProd = $pdo->prepare($sqlProduct);
$stProd->execute([(int)$shop['id'], $prodSlug]);
$product = $stProd->fetch();

if (!$product) {
  http_response_code(404);
  echo "Producto no encontrado o no está disponible.";
  exit;
}

/**
 * 3) Galería de imágenes
 */
$sqlImgs = "
  SELECT image_path, is_main, sort_order
  FROM product_images
  WHERE product_id = ?
  ORDER BY is_main DESC, sort_order ASC, id ASC
";
$stImgs = $pdo->prepare($sqlImgs);
$stImgs->execute([(int)$product['id']]);
$images = $stImgs->fetchAll();

/**
 * 4) Theme -> CSS variables
 */
$primary   = $shop['primary_color'] ?? '#5F3E2E';
$accent    = $shop['accent_color'] ?? '#8F684F';
$bg        = $shop['background_color'] ?? '#F4E5DC';
$template  = $shop['template'] ?? 'premium';
$customCss = (string)($shop['custom_css'] ?? '');

$customCssSafe = str_ireplace(['</style', '<script'], ['/*blocked*/', '/*blocked*/'], $customCss);

// Placeholder imagen
$placeholder = "data:image/svg+xml;utf8," . rawurlencode(
  '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="600">
    <rect width="100%" height="100%" fill="#f3e3dc"/>
    <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
      fill="#6a5246" font-family="Arial" font-size="28">Sin imagen</text>
  </svg>'
);

$mainImage = $placeholder;
if (!empty($images)) {
  $mainImage = (string)$images[0]['image_path'];
}

$price = number_format((float)$product['price'], 2);
$stock = (int)$product['stock'];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($product['name']) ?> | <?= htmlspecialchars($shop['shop_name']) ?> | KALISS</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="assets/css/index.css" />

  <style>
    :root{
      --primary: <?= htmlspecialchars($primary) ?>;
      --primary2: <?= htmlspecialchars($accent) ?>;
      --bg: <?= htmlspecialchars($bg) ?>;
      --bg2: <?= htmlspecialchars($bg) ?>;
    }
    body[data-template="<?= htmlspecialchars($template) ?>"]{}
    <?= $customCssSafe ?>

    /* mini estilos del detalle (para no tocar tu CSS aún) */
    .product{
      display:grid;
      grid-template-columns: 1fr 1fr;
      gap:18px;
      align-items:start;
    }
    .gallery{
      background: rgba(255,255,255,.28);
      border:1px solid var(--border);
      border-radius: var(--radius);
      overflow:hidden;
      box-shadow: 0 10px 28px rgba(30, 15, 8, .06);
    }
    .gallery__main img{
      width:100%;
      height:360px;
      object-fit:cover;
      display:block;
    }
    .thumbs{
      display:flex;
      gap:10px;
      padding:12px;
      overflow:auto;
      border-top:1px solid var(--border);
      background: rgba(255,255,255,.22);
    }
    .thumb{
      width:74px; height:58px;
      border-radius:12px;
      overflow:hidden;
      border:1px solid var(--border);
      cursor:pointer;
      flex:0 0 auto;
      background: rgba(255,255,255,.3);
    }
    .thumb img{ width:100%; height:100%; object-fit:cover; display:block; }
    .info{
      background: rgba(255,255,255,.22);
      border:1px solid var(--border);
      border-radius: var(--radius);
      padding:16px;
    }
    .p-title{
      font-family:"Playfair Display", serif;
      font-size:34px;
      margin:0 0 10px;
    }
    .p-meta{ color:var(--muted); font-size:13px; margin:0 0 10px; }
    .p-price{
      font-size:22px;
      margin:10px 0 6px;
      color: var(--text);
      font-weight:500;
    }
    .p-stock{ color:var(--muted); font-size:13px; margin:0 0 14px; }
    .p-desc{ color:rgba(58,43,36,.85); font-weight:300; margin:0 0 14px; }

    @media (max-width: 900px){
      .product{ grid-template-columns: 1fr; }
      .gallery__main img{ height:320px; }
    }
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

  <main class="section">
    <div class="container">

      <div class="breadcrumb">
        <a href="index.php" style="color:inherit;text-decoration:none;">Inicio</a>
        <span>/</span>
        <a href="tienda.php?slug=<?= urlencode($shop['slug']) ?>" style="color:inherit;text-decoration:none;">
          <?= htmlspecialchars($shop['shop_name']) ?>
        </a>
        <span>/</span>
        <?= htmlspecialchars($product['name']) ?>
      </div>

      <div class="product" style="margin-top:14px;">
        <div class="gallery">
          <div class="gallery__main">
            <img id="mainImg"
                 src="<?= htmlspecialchars($mainImage) ?>"
                 alt="<?= htmlspecialchars($product['name']) ?>"
                 onerror="this.onerror=null;this.src='<?= $placeholder ?>';">
          </div>

          <?php if (!empty($images)): ?>
            <div class="thumbs">
              <?php foreach ($images as $img): ?>
                <?php $src = $img['image_path'] ? (string)$img['image_path'] : $placeholder; ?>
                <div class="thumb" data-src="<?= htmlspecialchars($src) ?>">
                  <img src="<?= htmlspecialchars($src) ?>" alt="Imagen">
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="info">
          <h1 class="p-title"><?= htmlspecialchars($product['name']) ?></h1>
          <p class="p-meta">
            Tienda: <strong><?= htmlspecialchars($shop['shop_name']) ?></strong>
            · Categoría: <?= htmlspecialchars($product['category_name']) ?>
          </p>

          <p class="p-price">$<?= $price ?> MXN</p>
          <p class="p-stock">Stock: <?= $stock ?> <?= $stock <= 0 ? "(Agotado)" : "" ?></p>

          <?php if (!empty($product['description'])): ?>
            <p class="p-desc"><?= nl2br(htmlspecialchars((string)$product['description'])) ?></p>
          <?php endif; ?>

          <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a class="btn btn--primary" href="tienda.php?slug=<?= urlencode($shop['slug']) ?>#productos">
              Volver a la tienda
            </a>
            <a class="btn btn--ghost" href="index.php#negocios">Explorar más</a>
          </div>


           <p style="margin:14px 0 0; color:var(--muted); font-size:12px;">
          <input type="hidden" id="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
<button class="btn btn--primary" id="btnAddCart" data-product-id="<?= (int)$product['id'] ?>">
  Agregar al carrito
</button>

          </p>
        </div>
      </div>
    </div>
  </main>

  <footer class="footer">
    <div class="container footer__inner">
      <p>© <?= date("Y") ?> KALISS</p>
    </div>
  </footer>

  <script src="assets/js/menu.js"></script>

  <script>
const csrf = document.getElementById('csrf')?.value || '';
const btn = document.getElementById('btnAddCart');

btn?.addEventListener('click', async () => {
  const form = new FormData();
  form.append('csrf', csrf);
  form.append('product_id', btn.dataset.productId);
  form.append('qty', 1);

  const res = await fetch('api/cart_add.php', { method:'POST', body: form });
  const r = await res.json();
  if (!r.ok) return alert(r.error || 'Error');

  window.location.href = 'carrito.php';
});
</script>

  <script>
    // galería simple: cambiar imagen principal al dar clic en miniaturas
    const mainImg = document.getElementById('mainImg');
    document.querySelectorAll('.thumb').forEach(t => {
      t.addEventListener('click', () => {
        const src = t.getAttribute('data-src');
        if (src && mainImg) mainImg.src = src;
      });
    });
  </script>
</body>
</html>
