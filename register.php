<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
session_start();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (!$name || !$email || !$password) {
        $errors[] = "Wypełnij wszystkie pola.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Nieprawidłowy adres email.";
    } elseif ($password !== $password2) {
        $errors[] = "Hasła nie są identyczne.";
    } else {
        // Sprawdź czy email zajęty
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = "Konto z tym emailem już istnieje.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role,created_at) VALUES (:name,:email,:password,'user',NOW())");
            $stmt->execute(['name'=>$name,'email'=>$email,'password'=>$hash]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $name;
            $_SESSION['user_role'] = 'user';
            header('Location: user_panel.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Rejestracja — smartrent</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container narrow">
  <h2>Rejestracja</h2>
  <?php if ($errors): foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($e)?></div>
  <?php endforeach; endif; ?>
  <form method="post" id="registerForm" novalidate>
    <label>Imię i nazwisko
      <input type="text" name="name" required>
    </label>
    <label>Email
      <input type="email" name="email" required>
    </label>
    <label>Hasło
      <input type="password" name="password" required>
    </label>
    <label>Powtórz hasło
      <input type="password" name="password2" required>
    </label>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Zarejestruj</button>
      <a class="btn btn-ghost" href="login.php">Masz konto? Zaloguj się</a>
    </div>
  </form>
</main>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>