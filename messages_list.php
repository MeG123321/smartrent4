<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}
$me = $_SESSION['user_id'];

// Pobierz ostatnie wiadomości / konwersacje powiązane z użytkownikiem
// Grupujemy po property + partner (inny user), wybieramy najnowszą wiadomość
try {
    $sql = "
      SELECT m.*, 
             u_from.name AS from_name, u_to.name AS to_name,
             p.title AS property_title
      FROM messages m
      LEFT JOIN users u_from ON m.from_user_id = u_from.id
      LEFT JOIN users u_to ON m.to_user_id = u_to.id
      LEFT JOIN properties p ON m.property_id = p.id
      WHERE m.from_user_id = :me OR m.to_user_id = :me
      ORDER BY m.sent_at DESC
      LIMIT 200
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['me' => $me]);
    $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Błąd bazy: " . htmlspecialchars($e->getMessage()));
}
?>
<!doctype html>
<html lang="pl">
<head><meta charset="utf-8"><title>Wiadomości</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Wiadomości</h2>
  <?php if (empty($msgs)): ?>
    <p>Brak wiadomości.</p>
  <?php else: ?>
    <ul class="message-list">
      <?php foreach ($msgs as $m): 
         // partner = other user
         $partnerId = ($m['from_user_id'] == $me) ? $m['to_user_id'] : $m['from_user_id'];
         $partnerName = ($m['from_user_id'] == $me) ? ($m['to_name'] ?? 'Użytkownik') : ($m['from_name'] ?? 'Użytkownik');
         $propertyId = $m['property_id'];
         $snippet = mb_substr(strip_tags($m['body']), 0, 120);
      ?>
      <li>
        <a href="message_detail.php?property_id=<?=urlencode($propertyId)?>&partner_id=<?=urlencode($partnerId)?>">
          <strong><?=htmlspecialchars($partnerName)?></strong>
          <span class="muted"> — <?=htmlspecialchars($m['property_title'] ?? 'oferta')?></span>
          <div class="snippet"><?=htmlspecialchars($snippet)?></div>
          <div class="meta muted"><?=htmlspecialchars($m['sent_at'])?></div>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>