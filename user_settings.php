<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
session_start();
require_login();

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Pobierz dane użytkownika
$stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('Użytkownik nie znaleziony.');
}

// Zmiana hasła
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $errors[] = "Wszystkie pola są wymagane.";
    } elseif ($new_password !== $confirm_password) {
        $errors[] = "Nowe hasła nie pasują do siebie.";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "Hasło musi mieć co najmniej 6 znaków.";
    } else {
        // Sprawdź stare hasło
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
        $stmt->execute(['id' => $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && password_verify($old_password, $result['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->execute(['password' => $hashed_password, 'id' => $user_id]);
            
            require_once 'includes/admin_functions.php';
            admin_log_activity($pdo, $user_id, 'Zmiana hasła', 'Użytkownik zmienił hasło');
            $success = "Hasło zostało zmienione pomyślnie!";
        } else {
            $errors[] = "Stare hasło jest nieprawidłowe.";
        }
    }
}

// Zmiana profilu (imię i nazwisko)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_profile'])) {
    $name = trim($_POST['name'] ?? '');
    
    if (empty($name)) {
        $errors[] = "Imię i nazwisko są wymagane.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = :name WHERE id = :id");
        $stmt->execute(['name' => $name, 'id' => $user_id]);
        
        $_SESSION['user_name'] = $name;
        require_once 'includes/admin_functions.php';
        admin_log_activity($pdo, $user_id, 'Zmiana profilu', 'Użytkownik zaktualizował profil');
        $success = "Profil został zaktualizowany pomyślnie!";
        
        // Odśwież dane użytkownika
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = :id");
        $stmt->execute(['id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Ustawienia konta — <?=APP_NAME?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container narrow">
  <h2>Ustawienia konta</h2>

  <?php if ($errors): foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($e)?></div>
  <?php endforeach; endif; ?>
  
  <?php if ($success): ?>
    <div class="alert alert-success"><?=htmlspecialchars($success)?></div>
  <?php endif; ?>

  <div class="panel" style="margin-bottom:20px">
    <h3>Informacje podstawowe</h3>
    <p><strong>Email:</strong> <?=htmlspecialchars($user['email'])?></p>
    <p><strong>ID użytkownika:</strong> <?=htmlspecialchars($user['id'])?></p>
  </div>

  <!-- Zmiana profilu -->
  <div class="panel" style="margin-bottom:20px">
    <h3>Zmień profil</h3>
    <form method="post">
      <label>Imię i nazwisko
        <input type="text" name="name" value="<?=htmlspecialchars($user['name'])?>" required>
      </label>
      <div class="form-actions">
        <button class="btn btn-primary" type="submit" name="change_profile">Zaktualizuj profil</button>
        <a class="btn" href="user_panel.php">Anuluj</a>
      </div>
    </form>
  </div>

  <!-- Zmiana hasła -->
  <div class="panel">
    <h3>Zmień hasło</h3>
    <form method="post">
      <label>Stare hasło
        <input type="password" name="old_password" required>
      </label>
      <label>Nowe hasło
        <input type="password" name="new_password" required>
      </label>
      <label>Powtórz nowe hasło
        <input type="password" name="confirm_password" required>
      </label>
      <div class="form-actions">
        <button class="btn btn-primary" type="submit" name="change_password">Zmień hasło</button>
        <a class="btn" href="user_panel.php">Anuluj</a>
      </div>
    </form>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>