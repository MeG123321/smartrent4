<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
session_start();
require_login();

$user_id = $_SESSION['user_id'];

// Pobierz wszystkie wynajęte mieszkania przez użytkownika
$stmt = $pdo->prepare("
    SELECT r.*, 
           p.title, p.city, p.price AS property_price, p.image,
           u.name AS owner_name, u.id AS owner_id
    FROM rentals r
    LEFT JOIN properties p ON r.property_id = p.id
    LEFT JOIN users u ON p.owner_id = u.id
    WHERE r.user_id = :uid
    ORDER BY r.created_at DESC
");
$stmt->execute(['uid' => $user_id]);
$rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
  <title>Wynajęte mieszkania — <?=APP_NAME?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Wynajęte mieszkania</h2>
  
  <div style="margin-bottom:12px">
    <a class="btn btn-ghost" href="user_panel.php">Powrót do panelu</a>
    <a class="btn" href="property_list.php">Przeglądaj oferty</a>
  </div>

  <?php if (empty($rentals)): ?>
    <div class="panel">
      <p>Nie wynajmujesz obecnie żadnych mieszkań.</p>
      <p><a class="btn" href="property_list.php">Przeglądaj dostępne oferty</a></p>
    </div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($rentals as $r): ?>
        <?php
          $imgSrc = !empty($r['image']) 
              ? 'uploads/properties/' . rawurlencode($r['image'])
              : 'assets/img/placeholder.png';
          $statusText = '';
          $statusClass = '';
          
          switch($r['status']) {
              case 'active':
                  $statusText = 'Aktywne';
                  $statusClass = 'status-active';
                  break;
              case 'completed':
                  $statusText = 'Zakończone';
                  $statusClass = 'status-completed';
                  break;
              case 'cancelled':
                  $statusText = 'Anulowane';
                  $statusClass = 'status-cancelled';
                  break;
              default:
                  $statusText = 'Nieznany';
                  $statusClass = 'status-unknown';
          }
        ?>
        <article class="card">
          <div class="card-img" style="background-image:url('<?=htmlspecialchars($imgSrc, ENT_QUOTES)?>')">
            <span class="badge <?=$statusClass?>"><?=htmlspecialchars($statusText)?></span>
          </div>
          <div class="card-body">
            <h3><?=htmlspecialchars($r['title'] ?? 'Bez tytułu')?></h3>
            <p class="muted"><?=htmlspecialchars($r['city'] ?? '-')?></p>
            <p class="muted" style="font-size:0.9rem">
              Właściciel: <?=htmlspecialchars($r['owner_name'] ?? 'Nieznany')?>
            </p>
            <div class="price"><?=format_price($r['price'])?> / miesiąc</div>
            <p style="font-size:0.9rem; margin-top:8px">
              <strong>Okres wynajmu:</strong><br>
              <?=htmlspecialchars($r['start_date'])?> → <?=htmlspecialchars($r['end_date'])?>
            </p>
            <p class="muted" style="font-size:0.85rem">
              Zarezerwowano: <?=htmlspecialchars($r['created_at'])?>
            </p>
            <div style="margin-top:8px">
              <a class="btn btn-sm" href="property_details.php?id=<?=intval($r['property_id'])?>">Szczegóły</a>
              <?php if ($r['owner_id']): ?>
                <a class="btn btn-sm" href="messages.php?user=<?=intval($r['owner_id'])?>">Napisz do właściciela</a>
              <?php endif; ?>
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
