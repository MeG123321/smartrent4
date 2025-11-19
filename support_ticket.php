<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/admin_functions.php';
session_start();
require_login();

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if (!$subject || !$message) {
        $errors[] = "Wypełnij temat i treść zgłoszenia.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO support_tickets (user_id, subject, message, status, created_at) VALUES (:uid,:sub,:msg,'open',NOW())");
        $stmt->execute(['uid'=>$user_id,'sub'=>$subject,'msg'=>$message]);
        $ticketId = (int)$pdo->lastInsertId();

        // log akcji
        admin_log_activity($pdo, $user_id, 'Utworzono zgłoszenie support', "ticket_id:{$ticketId}, subject: {$subject}");

        $success = "Zgłoszenie zostało utworzone. Otrzymasz powiadomienie e-mail gdy zostanie obsłużone.";
    }
}

// formularz
?>
<!doctype html>
<html lang="pl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Zgłoś problem — <?=APP_NAME?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container narrow">
  <h2>Zgłoszenie do supportu</h2>
  <?php if ($errors): foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($e)?></div>
  <?php endforeach; endif; ?>
  <?php if ($success): ?><div class="alert alert-info"><?=htmlspecialchars($success)?></div><?php endif; ?>

  <form method="post">
    <label>Temat
      <input type="text" name="subject" required>
    </label>
    <label>Treść zgłoszenia
      <textarea name="message" rows="6" required></textarea>
    </label>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Wyślij zgłoszenie</button>
      <a class="btn" href="user_panel.php">Anuluj</a>
    </div>
  </form>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>