<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
session_start();
require_role('admin');

// Obsługa usuwania użytkownika
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id_to_delete = intval($_POST['user_id'] ?? 0);
    
    if ($user_id_to_delete) {
        try {
            // Sprawdź czy użytkownik nie próbuje usunąć samego siebie
            if ($user_id_to_delete == $_SESSION['user_id']) {
                $error = "Nie możesz usunąć samego siebie.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
                $stmt->execute(['id' => $user_id_to_delete]);
                $message = "Użytkownik został usunięty.";
            }
        } catch (Exception $e) {
            $error = "Błąd podczas usuwania użytkownika: " . $e->getMessage();
        }
    }
}

// prosty listing wszystkich użytkowników
$stmt = $pdo->query("SELECT id,name,email,role,created_at FROM users ORDER BY id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Zarządzaj użytkownikami — smartrent</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Użytkownicy</h2>
  
  <?php if ($message): ?>
    <div class="alert alert-info"><?=htmlspecialchars($message)?></div>
  <?php endif; ?>
  
  <?php if ($error): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($error)?></div>
  <?php endif; ?>
  
  <table class="table">
    <thead><tr><th>ID</th><th>Imię</th><th>Email</th><th>Rola</th><th>Data</th><th>Akcje</th></tr></thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?=htmlspecialchars($u['id'])?></td>
          <td><?=htmlspecialchars($u['name'])?></td>
          <td><?=htmlspecialchars($u['email'])?></td>
          <td><?=htmlspecialchars($u['role'])?></td>
          <td><?=htmlspecialchars($u['created_at'])?></td>
          <td>
            <form method="post" style="display:inline;" onsubmit="return confirm('Czy na pewno chcesz usunąć tego użytkownika?');">
              <input type="hidden" name="user_id" value="<?=htmlspecialchars($u['id'])?>">
              <button type="submit" name="delete_user" class="btn btn-danger">Usuń użytkownika</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>