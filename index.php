<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
session_start();
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>smartrent — Wynajmij komfortowo</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<main class="container">
  <header class="hero">
    <div class="hero-inner">
      <h1>SMARTRENT — Znajdź idealne mieszkanie</h1>
      <p>SmartRent to nowoczesna platforma do wynajęcia i rezerwacji mieszkań w całej Polsce, łącząca właścicieli nieruchomości z osobami szukającymi idealnego miejsca do zamieszkania. Dla wynajmujących oferujemy szeroki wybór mieszkań z bezpiecznymi rezerwacjami, ocenami i pełnym wsparciem, a dla właścicieli - prosty system zarządzania rezerwacjami i automatyczne płatności. Platforma gwarantuje transparentność, bezpieczeństwo transakcji i wsparcie 24/7 dla wszystkich użytkowników.</p>
      <div class="cta">
      </div>
    </div>
  </header>

  <section class="featured">
    <h2>Wybrane oferty</h2>
    <div class="grid">
      <?php
      // Pokaż 6 najnowszych
      $stmt = $pdo->query("SELECT id, title, city, price, image FROM properties ORDER BY id DESC LIMIT 6");
      $props = $stmt->fetchAll(PDO::FETCH_ASSOC);
      foreach ($props as $p):
      ?>
      <article class="card">
        <a href="property_details.php?id=<?=htmlspecialchars($p['id'])?>">
          <div class="card-img" style="background-image:url('<?= $p['image'] ? 'uploads/properties/'.htmlspecialchars($p['image']) : 'assets/img/placeholder.png' ?>')"></div>
          <div class="card-body">
            <h3><?=shorten($p['title'], 60)?></h3>
            <p class="muted"><?=htmlspecialchars($p['city'])?></p>
            <div class="price"><?=format_price($p['price'])?> / miesiąc</div>
          </div>
        </a>
      </article>
      <?php endforeach; ?>
    </div>
    <div class="center">
      <a class="btn btn-secondary" href="property_list.php">Zobacz wszystkie oferty</a>
    </div>
  </section>
</main>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>