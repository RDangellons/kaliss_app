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
// Placeholder estilo "Zapatillas princesa" (fondo negro + dorado fino)




$placeholder = "data:image/svg+xml;utf8," . rawurlencode(
'<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800">
  <!-- Fondo negro -->
  <rect width="100%" height="100%" fill="#050505"/>

  <!-- Color dorado -->
  <defs>
    <style>
      .gold-stroke{ stroke:#c9ab63; stroke-width:3.2; fill:none; stroke-linecap:round; stroke-linejoin:round; opacity:.95; }
      .gold-fill{ fill:#c9ab63; }
      .script{ font-family: "Brush Script MT", "Segoe Script", "Snell Roundhand", cursive; }
      .sans{ font-family: Arial, Helvetica, sans-serif; }
    </style>
  </defs>

  <!-- Marco geométrico (dos polígonos superpuestos, fino como tu imagen) -->
  <g class="gold-stroke">
    <!-- Polígono 1 -->
    <path d="M600 180
             L770 230
             L900 360
             L835 520
             L600 620
             L365 520
             L300 360
             L430 230 Z"/>
    <!-- Polígono 2 (ligeramente rotado/alterno) -->
    <path opacity=".65"
          d="M600 160
             L810 260
             L880 420
             L720 600
             L600 650
             L480 600
             L320 420
             L390 260 Z"/>
  </g>

  <!-- Corona (más pequeña y centrada arriba) -->
  <g class="gold-fill" transform="translate(0,0)">
    <path d="M600 265
             L575 305
             L540 290
             L550 332
             L600 318
             L650 332
             L660 290
             L625 305 Z"/>
    <circle cx="540" cy="290" r="5"/>
    <circle cx="575" cy="305" r="5"/>
    <circle cx="600" cy="265" r="5"/>
    <circle cx="625" cy="305" r="5"/>
    <circle cx="660" cy="290" r="5"/>
  </g>

  <!-- Brillitos (tipo estrellitas a la derecha) -->
  <g class="gold-fill" opacity=".9">
    <!-- estrellita 1 -->
    <path d="M865 385 l8 10 l-8 10 l-8-10 z" opacity=".75"/>
    <circle cx="895" cy="410" r="3.8"/>
    <circle cx="838" cy="420" r="3.2"/>
    <path d="M875 445
             m0-10 l2.5 7.5 l7.5 2.5 l-7.5 2.5 l-2.5 7.5 l-2.5-7.5 l-7.5-2.5 l7.5-2.5 z"
          opacity=".85"/>
  </g>

  <!-- Texto principal (script dorado) -->
  <text x="50%" y="460" text-anchor="middle"
        class="gold-fill script"
        font-size="72"
        opacity=".98">
    Zapatillas princesa
  </text>

  <!-- Subtítulo MICHELLE -->
  <text x="50%" y="525" text-anchor="middle"
        class="gold-fill sans"
        font-size="22"
        letter-spacing="12"
        opacity=".85">
    MICHELLE
  </text>
</svg>'
);

/**
 * Resolución de imágenes desde carpeta (LOCAL) con fallback.
 *
 * ✅ Tú puedes cambiar estas rutas cuando lo implementes en tu hosting.
 */
function is_external_url(string $url): bool {
  $url = trim($url);
  if ($url === '') return false;
  return (bool)preg_match('#^(https?:)?//#i', $url) || str_starts_with($url, 'data:');
}

function resolve_img_url(string $path, string $fallback): string {
  $path = trim($path);
  if ($path === '') return $fallback;

  // Si ya viene como URL externa o data URI, úsalo tal cual.
  if (is_external_url($path)) return $path;

  // Si viene como ruta relativa/absoluta del proyecto.
  $candidate = ltrim($path, '/');
  $fs = __DIR__ . '/' . $candidate;
  if (file_exists($fs)) return $candidate;

  // Si solo viene un nombre de archivo, intentamos en carpetas comunes.
  $basename = basename($candidate);
  $tryDirs = [
    'uploads/shops/',
    'assets/img/shops/',
    'img/shops/',
    'img/',
  ];
  foreach ($tryDirs as $dir) {
    $try = $dir . $basename;
    if (file_exists(__DIR__ . '/' . $try)) return $try;
  }

  return $fallback;
}

// HERO (banner): si no hay hero_image_path, intentamos traer logo/imagen local por carpeta.
$heroImageResolved = '';

if (!empty($heroImage)) {
  $heroImageResolved = resolve_img_url((string)$heroImage, $placeholder);
} else {
  $candidates = [];

  // 1) Si la tienda trae logo_path desde BD, lo probamos
  $logoDb = trim((string)($shop['logo_path'] ?? ''));
  if ($logoDb !== '') {
    $candidates[] = $logoDb;
  }

  // 2) Por convención, probamos por slug en carpetas típicas
  foreach (['jpg','jpeg','png','webp'] as $ext) {
    $candidates[] = "uploads/shops/{$shop['slug']}.{$ext}";
    $candidates[] = "assets/img/shops/{$shop['slug']}.{$ext}";
    $candidates[] = "img/shops/{$shop['slug']}.{$ext}";
    $candidates[] = "img/{$shop['slug']}.{$ext}";
  }

  foreach ($candidates as $cand) {
    $resolved = resolve_img_url($cand, '');
    if ($resolved !== '') {
      $heroImageResolved = $resolved;
      break;
    }
  }

  if ($heroImageResolved === '') {
    $heroImageResolved = $placeholder;
  }
}
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

  <link rel="stylesheet" href="assets/css/tienda.css" />
  <link rel="stylesheet" href="assets/css/perfil.css" />

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

  <?php include __DIR__ . '/partials/header.php'; ?>

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
          <img
            src="<?= htmlspecialchars($heroImageResolved) ?>"
            alt="Banner de <?= htmlspecialchars($shop['shop_name']) ?>"
            onerror="this.onerror=null;this.src='<?= $placeholder ?>';"
          >
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
                $imgPath = $p['main_image'] ? (string)$p['main_image'] : '';
                $img = resolve_img_url($imgPath, $placeholder);
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
