<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
session_start();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $errors[] = "Podaj email i hasło.";
    } else {
        $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            // log aktywności
            require_once 'includes/admin_functions.php';
            admin_log_activity($pdo, $user['id'], 'Logowanie', 'Zalogowano użytkownika: ' . $user['email']);

            header('Location: user_panel.php');
            exit;
        } else {
            $errors[] = "Błędne dane logowania.";
        }
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Logowanie — smartrent</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container narrow">
  <h2>Logowanie</h2>
  <?php if ($errors): foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($e)?></div>
  <?php endforeach; endif; ?>
  <form method="post" id="loginForm" novalidate>
    <label>Email
      <input type="email" name="email" required>
    </label>
    <label>Hasło
      <input type="password" name="password" required>
    </label>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Zaloguj</button>
      <a class="btn btn-ghost" href="register.php">Zarejestruj się</a>
    </div>
  </form>
</main>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>