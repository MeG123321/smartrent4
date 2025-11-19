<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
session_start();
require_login();

$user_id = $_SESSION['user_id'];

// Check if filtering by property_id (for property owners)
$property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;

if ($property_id > 0) {
    // Verify the user owns this property
    $stmt = $pdo->prepare("SELECT id FROM properties WHERE id = :pid AND owner_id = :uid");
    $stmt->execute(['pid' => $property_id, 'uid' => $user_id]);
    if (!$stmt->fetch()) {
        // User doesn't own this property
        header('Location: my_properties.php');
        exit;
    }
    
    // Get rentals for this specific property
    $stmt = $pdo->prepare("SELECT r.*, p.title, p.city, u.name AS renter_name 
                           FROM rentals r 
                           LEFT JOIN properties p ON r.property_id = p.id 
                           LEFT JOIN users u ON r.user_id = u.id
                           WHERE r.property_id = :pid 
                           ORDER BY r.created_at DESC");
    $stmt->execute(['pid' => $property_id]);
    $rents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $title = "Historia wynajmów dla mieszkania";
} else {
    // Get all rentals for the user (as renter)
    $stmt = $pdo->prepare("SELECT r.*, p.title, p.city FROM rentals r LEFT JOIN properties p ON r.property_id = p.id WHERE r.user_id = :uid ORDER BY r.created_at DESC");
    $stmt->execute(['uid'=>$user_id]);
    $rents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $title = "Historia wynajmów";
}

// Format price helper
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
  <title>Historia wynajmów — smartrent</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2><?=htmlspecialchars($title)?></h2>

  <?php if ($property_id > 0): ?>
    <div style="margin-bottom:12px">
      <a class="btn btn-ghost" href="my_properties.php">Powrót do moich mieszkań</a>
    </div>
  <?php endif; ?>

  <?php if (!$rents): ?>
    <div class="panel">Brak historii wynajmów.</div>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Mieszkanie</th>
          <?php if ($property_id > 0): ?>
            <th>Wynajmujący</th>
          <?php endif; ?>
          <th>Okres</th>
          <th>Cena</th>
          <th>Status</th>
          <th>Data rezerwacji</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rents as $r): ?>
        <tr>
          <td><?=htmlspecialchars($r['id'])?></td>
          <td><?=htmlspecialchars($r['title'].' — '.$r['city'])?></td>
          <?php if ($property_id > 0): ?>
            <td><?=htmlspecialchars($r['renter_name'] ?? 'Nieznany')?></td>
          <?php endif; ?>
          <td><?=htmlspecialchars($r['start_date'])?> → <?=htmlspecialchars($r['end_date'])?></td>
          <td><?=format_price($r['price'])?></td>
          <td>
            <?php 
              $status_display = [
                'active' => 'Aktywne',
                'completed' => 'Zakończone', 
                'cancelled' => 'Anulowane'
              ];
              echo htmlspecialchars($status_display[$r['status'] ?? 'active'] ?? $r['status']);
            ?>
          </td>
          <td><?=htmlspecialchars($r['created_at'])?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>