<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
session_start();
require_login();

$user_id = $_SESSION['user_id'];

// Pobierz wszystkie mieszkania użytkownika z liczbą wynajmów
$stmt = $pdo->prepare("
    SELECT p.*, 
           COUNT(r.id) AS rental_count,
           (SELECT COUNT(*) FROM rentals WHERE property_id = p.id AND status = 'active') AS active_rentals
    FROM properties p
    LEFT JOIN rentals r ON p.id = r.property_id
    WHERE p.owner_id = :uid
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute(['uid' => $user_id]);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper do formatowania ceny
if (!function_exists('format_price')) {
    function format_price($amount): string {
        if ($amount === null || $amount === '' || !is_numeric($amount)) return '-';
        $val = (float)$amount;
        if (floor($val) == $val) {
            return number_format($val, 0, ',', ' ') . ' zł';
        }
        return number_format($val, 2, ',', ' ') . ' zł';
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Moje mieszkania — <?=APP_NAME?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Moje mieszkania</h2>
  
  <div style="margin-bottom:12px">
    <a class="btn" href="add_property.php">Dodaj nowe mieszkanie</a>
    <a class="btn btn-ghost" href="user_panel.php">Powrót do panelu</a>
  </div>

  <?php if (empty($properties)): ?>
    <div class="panel">
      <p>Nie posiadasz jeszcze żadnych mieszkań.</p>
      <p><a class="btn" href="add_property.php">Dodaj pierwsze mieszkanie</a></p>
    </div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($properties as $p): ?>
        <?php
          $imgSrc = !empty($p['image']) 
              ? 'uploads/properties/' . rawurlencode($p['image'])
              : 'assets/img/placeholder.png';
          $status = ($p['is_rented'] == 1) ? 'Wynajęte' : 'Dostępne';
          $statusClass = ($p['is_rented'] == 1) ? 'status-rented' : 'status-available';
        ?>
        <article class="card">
          <div class="card-img" style="background-image:url('<?=htmlspecialchars($imgSrc, ENT_QUOTES)?>')">
            <span class="badge <?=$statusClass?>"><?=htmlspecialchars($status)?></span>
          </div>
          <div class="card-body">
            <h3><?=htmlspecialchars($p['title'])?></h3>
            <p class="muted"><?=htmlspecialchars($p['city'])?></p>
            <div class="price"><?=format_price($p['price'])?> / miesiąc</div>
            <p class="muted" style="font-size:0.9rem">
              Liczba wynajmów: <?=intval($p['rental_count'])?>
              <?php if ($p['active_rentals'] > 0): ?>
                <br>Aktywne: <?=intval($p['active_rentals'])?>
              <?php endif; ?>
            </p>
            <div style="margin-top:8px">
              <a class="btn btn-sm" href="property_details.php?id=<?=intval($p['id'])?>">Szczegóły</a>
              <a class="btn btn-sm" href="edit_property.php?id=<?=intval($p['id'])?>">Edytuj</a>
              <a class="btn btn-sm btn-ghost" href="rent_history.php?property_id=<?=intval($p['id'])?>">Historia</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
