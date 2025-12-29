<?php
$categorias = [
  ["slug"=>"ropa", "titulo"=>"Ropa", "desc"=>"Marcas locales para todos los estilos", "img"=>"assets/img/ropa.jpeg"],
  ["slug"=>"comida", "titulo"=>"Comida", "desc"=>"Antojos, snacks y sabores locales", "img"=>"assets/img/comida.jpeg"],
  ["slug"=>"cuidado-personal", "titulo"=>"Cuidado personal", "desc"=>"Dermatología, skin care y bienestar", "img"=>"assets/img/cuidado_personal.jpeg"],
  ["slug"=>"zapatos", "titulo"=>"Zapatos", "desc"=>"Tenis, squees", "img"=>"assets/img/zapatos.jpeg"],
  ["slug"=>"accesorios", "titulo"=>"Accesorios", "desc"=>"Perletes y estilo", "img"=>"assets/img/accesorios.jpeg"],
  ["slug"=>"fiestas", "titulo"=>"Fiestas", "desc"=>"Todo para celebrar", "img"=>"assets/img/fiesta.jpeg"],
];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>KALISS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/index.css" />
  <link rel="stylesheet" href="assets/css/hero.css" />
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

  <main>
    <section class="hero">
      <div class="container hero__grid">
        <div class="hero__copy">
          <h1 class="hero__title">KALISS</h1>
          <p class="hero__subtitle">Lo mejor de tu ciudad,<br>en un solo lugar.</p>

          <p class="hero__text">
            KALISS es una plataforma que conecta marcas locales con personas reales. Creamos experiencias,
            impulsamos negocios y acercamos productos de calidad a una comunidad que valora lo auténtico,
            lo local y lo bien hecho.
          </p>
          <p class="hero__text">
            Aquí no solo compras: descubres, apoyas y formas parte de algo más grande.
          </p>

          <div class="hero__actions">
            <a class="btn btn--primary" href="#negocios">Descubrir marcas locales</a>
            <a class="btn btn--ghost" href="#sobre">Conocer KALISS</a>
          </div>
        </div>
      </div>
    </section>

    <section class="section" id="negocios">
      <div class="container">
        <h2 class="section__title">Negocios</h2>

        <div class="grid">
          <?php foreach ($categorias as $c): ?>
            <a class="card" href="categoria.php?slug=<?= urlencode($c['slug']) ?>">
              <div class="card__img">
                <img src="<?= htmlspecialchars($c['img']) ?>" alt="<?= htmlspecialchars($c['titulo']) ?>">
              </div>
              <div class="card__body">
                <h3 class="card__title"><?= htmlspecialchars($c['titulo']) ?></h3>
                <p class="card__text"><?= htmlspecialchars($c['desc']) ?></p>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="section section--soft" id="sobre">
      <div class="container">
        <h2 class="section__title">Sobre Nosotros</h2>
        <p class="lead">
          Lorem ipsum dolor sit amet consectetur adipisicing elit. Rem debitis adipisci inventore sed, laborum mollitia totam eos ab facilis? Quos a harum impedit aliquid qui. Ipsam cupiditate optio assumenda officiis!
        </p>
      </div>
    </section>
  </main>

  <footer class="footer" id="unete">
    <div class="container footer__inner">
      <p>© <?= date("Y") ?> KALISS — Marcas locales, comunidad real.</p>
    </div>
  </footer>

  <script src="assets/js/menu.js"></script>
</body>
</html>
