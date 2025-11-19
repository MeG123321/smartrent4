<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!is_logged_in()) { header('Location: login.php'); exit; }
$me = $_SESSION['user_id'];

// Pokaż przypisania związane z użytkownikiem (jako właściciel nieruchomości lub admin)
try {
    // jeśli admin pokaż wszystko
    if (function_exists('is_admin') && is_admin()) {
        $stmt = $pdo->query("SELECT a.*, p.title AS property_title, u.name AS tenant_name FROM assignments a LEFT JOIN properties p ON a.property_id = p.id LEFT JOIN users u ON a.tenant_id = u.id ORDER BY a.created_at DESC");
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // tylko przypisania gdzie current user jest owner of property
        $stmt = $pdo->prepare("SELECT a.*, p.title AS property_title, u.name AS tenant_name
            FROM assignments a
            JOIN properties p ON a.property_id = p.id
            LEFT JOIN users u ON a.tenant_id = u.id
            WHERE p.owner_id = :me
            ORDER BY a.created_at DESC");
        $stmt->execute(['me' => $me]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Błąd DB: " . htmlspecialchars($e->getMessage()));
}
?>
<!doctype html>
<html lang="pl">
<head><meta charset="utf-8"><title>Zarządzanie przypisaniami</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Przypisania mieszkań</h2>
  <?php if (empty($assignments)): ?>
    <p>Brak przypisań.</p>
  <?php else: ?>
    <table border="1" cellpadding="6">
      <tr><th>ID</th><th>Mieszkanie</th><th>Najemca</th><th>Status</th><th>Utworzono</th><th>Akcje</th></tr>
      <?php foreach ($assignments as $a): ?>
        <tr>
          <td><?=htmlspecialchars($a['id'])?></td>
          <td><?=htmlspecialchars($a['property_title'])?></td>
          <td><?=htmlspecialchars($a['tenant_name'] ?? $a['tenant_id'])?></td>
          <td><?=htmlspecialchars($a['status'])?></td>
          <td><?=htmlspecialchars($a['created_at'])?></td>
          <td><a href="management_assignment.php?id=<?=urlencode($a['id'])?>">Otwórz</a></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>