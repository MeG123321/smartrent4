<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

// session early (navbar/auth may rely on it)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Safety helpers local to this file so we don't depend on external helpers
if (!function_exists('shorten')) {
    function shorten(string $text, int $max = 60): string {
        $text = trim($text);
        if (mb_strlen($text) <= $max) return $text;
        return rtrim(mb_substr($text, 0, $max - 1)) . '…';
    }
}

if (!function_exists('format_price')) {
    // Simple PLN formatter (no external helper)
    function format_price($amount): string {
        if ($amount === null || $amount === '' || !is_numeric($amount)) return '-';
        $val = (float)$amount;
        if (floor($val) == $val) {
            return number_format($val, 0, ',', ' ') . ' zł';
        }
        return number_format($val, 2, ',', ' ') . ' zł';
    }
}

// Input
$q = trim((string)($_GET['q'] ?? ''));
$city = trim((string)($_GET['city'] ?? ''));

// Build SQL with prepared params - only show available properties
$sql = "SELECT id, title, city, price, image FROM properties WHERE is_rented = 0";
$params = [];

if ($q !== '') {
    $sql .= " AND (title LIKE :q OR description LIKE :q)";
    $params['q'] = '%' . $q . '%';
}
if ($city !== '') {
    // keep exact match as original, but use parameterized value
    $sql .= " AND city = :city";
    $params['city'] = $city;
}

$sql .= " ORDER BY id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $props = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // in dev show error; in production log and show friendly message
    // for now output a readable error so you can debug
    http_response_code(500);
    echo "<h2>Błąd serwera</h2><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Oferty — smartrent</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Lista mieszkań</h2>

  <form class="filters" method="get" action="property_list.php">
    <input type="search" name="q" placeholder="Szukaj (tytuł, opis)" value="<?=htmlspecialchars($q, ENT_QUOTES)?>">
    <input type="text" name="city" placeholder="Miasto" value="<?=htmlspecialchars($city, ENT_QUOTES)?>">
    <button class="btn" type="submit">Szukaj</button>
    <a class="btn btn-ghost" href="property_list.php">Wyczyść</a>
  </form>

  <?php if (empty($props)): ?>
    <p>Brak ofert do wyświetlenia.</p>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($props as $p): ?>
        <?php
          // sanitize/prepare image URL
          if (!empty($p['image'])) {
              // rawurlencode filename to avoid breaking URL, but keep path readable
              $imgSrc = 'uploads/properties/' . rawurlencode($p['image']);
          } else {
              $imgSrc = 'assets/img/placeholder.png';
          }
          // escape for output
          $imgSrcEsc = htmlspecialchars($imgSrc, ENT_QUOTES);
          $title = htmlspecialchars($p['title'] ?? '', ENT_QUOTES);
          $cityOut = htmlspecialchars($p['city'] ?? '', ENT_QUOTES);
          $idOut = urlencode($p['id']);
        ?>
        <article class="card">
          <a href="property_details.php?id=<?=$idOut?>">
            <div class="card-img" style="background-image:url('<?=$imgSrcEsc?>')"></div>
            <div class="card-body">
              <h3><?=htmlspecialchars(shorten($p['title'] ?? ''), ENT_QUOTES)?></h3>
              <p class="muted"><?=$cityOut?></p>
              <div class="price"><?=htmlspecialchars(format_price($p['price']))?> / miesiąc</div>
            </div>
          </a>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>