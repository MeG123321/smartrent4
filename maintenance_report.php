<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!is_logged_in()) { header('Location: login.php'); exit; }
$me = $_SESSION['user_id'];

$assignment_id = intval($_GET['assignment_id'] ?? 0);
$property_id = intval($_GET['property_id'] ?? 0);

// jeśli mamy tylko przypisanie, pobierz property_id z assignments
if ($assignment_id && !$property_id) {
    $stmt = $pdo->prepare("SELECT property_id FROM assignments WHERE id = :id LIMIT 1");
    $stmt->execute(['id'=>$assignment_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $property_id = $row['property_id'] ?? 0;
}

// obsługa formularza
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    if ($title === '') $errors[] = "Wpisz tytuł zgłoszenia.";
    if ($desc === '') $errors[] = "Opisz usterkę.";
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO maintenance_reports (assignment_id, property_id, reported_by, title, description, status, created_at) VALUES (:aid,:pid,:by,:title,:desc,'open',NOW())");
        $stmt->execute([
            'aid' => $assignment_id ?: null,
            'pid' => $property_id ?: null,
            'by'  => $me,
            'title' => $title,
            'desc'  => $desc
        ]);
        $success = "Zgłoszenie zostało utworzone.";
    }
}
?>
<!doctype html>
<html lang="pl">
<head><meta charset="utf-8"><title>Zgłoś usterkę</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container narrow">
  <h2>Zgłoś usterkę</h2>

  <?php if ($errors): foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($e)?></div>
  <?php endforeach; endif; ?>
  <?php if ($success): ?><div class="alert alert-info"><?=htmlspecialchars($success)?></div><?php endif; ?>

  <form method="post">
    <label>Tytuł
      <input type="text" name="title" required value="<?=htmlspecialchars($_POST['title'] ?? '')?>">
    </label>
    <label>Opis
      <textarea name="description" rows="6" required><?=htmlspecialchars($_POST['description'] ?? '')?></textarea>
    </label>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Wyślij zgłoszenie</button>
    </div>
  </form>

  <p><a href="admin_assignment_list.php">Powrót</a></p>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>