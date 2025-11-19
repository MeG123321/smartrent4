<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/admin_functions.php';
session_start();
require_role('admin');

$logs = admin_get_logs($pdo, 500);
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Logi aktywności — Panel admina</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Logi aktywności</h2>
  <div class="panel">
    <table class="table">
      <thead><tr><th>Data</th><th>Akcja</th><th>Użytkownik</th><th>Meta</th></tr></thead>
      <tbody>
      <?php foreach ($logs as $l): ?>
        <tr>
          <td><?=htmlspecialchars($l['created_at'])?></td>
          <td><?=htmlspecialchars($l['action'])?></td>
          <td><?=htmlspecialchars($l['actor_name'] ?? $l['actor_email'] ?? '—')?></td>
          <td><?=htmlspecialchars($l['meta'] ?? '')?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>