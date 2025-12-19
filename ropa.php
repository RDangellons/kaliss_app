<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kaliss</title>
</head>
<body>
    
</body><?php
$marcas = [
  ["nombre"=>"Athens", "sub"=>"Boutique PHO3", "ciudad"=>"", "img"=>"assets/img/marca-1.jpg"],
  ["nombre"=>"Vogue Room", "sub"=>"Marca local", "ciudad"=>"Tulancingo", "img"=>"assets/img/logo_tienda1.jpeg"],
  ["nombre"=>"Halo", "sub"=>"Marca local", "ciudad"=>"Tulancingo", "img"=>"assets/img/marca-3.jpg"],
  ["nombre"=>"Camellia", "sub"=>"Marca local", "ciudad"=>"Tulancingo", "img"=>"assets/img/marca-4.jpg"],
  ["nombre"=>"MoRo", "sub"=>"Marca local", "ciudad"=>"Tulancingo", "img"=>"assets/img/marca-5.jpg"],
  ["nombre"=>"Jazmín", "sub"=>"Marca local", "ciudad"=>"Tulancingo", "img"=>"assets/img/marca-6.jpg"],
];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ropa | KALISS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
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
      <div class="breadcrumb">Inicio <span>/</span> Ropa</div>
      <h1 class="page__title">Ropa</h1>

      <div class="grid grid--brands">
        <?php foreach ($marcas as $m): ?>
          <article class="card card--brand">
            <div class="card__img card__img--tall">
              <img src="<?= htmlspecialchars($m["img"]) ?>" alt="<?= htmlspecialchars($m["nombre"]) ?>">
            </div>
            <div class="card__body">
              <h3 class="card__title"><?= htmlspecialchars($m["nombre"]) ?></h3>
              <p class="card__text">
                <?= htmlspecialchars($m["sub"]) ?>
                <?php if (!empty($m["ciudad"])): ?>
                  · <?= htmlspecialchars($m["ciudad"]) ?>
                <?php endif; ?>
              </p>
              <a class="btn btn--tiny" href="tienda.php">Descubrir marca</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <div class="center">
        <a class="btn btn--primary btn--wide" href="#">Ver más marcas de ropa</a>
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

</html>