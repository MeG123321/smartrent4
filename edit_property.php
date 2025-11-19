<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
session_start();

// Require login
require_login();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: my_properties.php');
    exit;
}

// Get property details
$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = :id LIMIT 1");
$stmt->execute(['id'=>$id]);
$prop = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prop) {
    header('Location: my_properties.php');
    exit;
}

// Check permission: owner or admin
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_owner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $prop['owner_id'];

if (!$is_admin && !$is_owner) {
    // Access denied
    http_response_code(403);
    ?>
    <!doctype html>
    <html lang="pl">
    <head>
      <meta charset="utf-8">
      <title>Brak dostępu — smartrent</title>
      <link rel="stylesheet" href="assets/css/style.css">
    </head>
    <body>
    <?php include 'includes/navbar.php'; ?>
    <main class="container narrow">
      <h2>Brak dostępu</h2>
      <div class="alert alert-danger">Nie masz uprawnień do edycji tej nieruchomości.</div>
      <p><a class="btn" href="my_properties.php">Powrót do moich nieruchomości</a></p>
    </main>
    <?php include 'includes/footer.php'; ?>
    </body>
    </html>
    <?php
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $price = floatval($_POST['price'] ?? 0);

    if (!$title || !$city || $price <= 0) {
        $errors[] = "Wypełnij pola: tytuł, miasto, cena.";
    } else {
        $imageName = $prop['image'];
        if (!empty($_FILES['image']['name'])) {
            $f = $_FILES['image'];
            $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg','jpeg','png','webp'];
            if (!in_array(strtolower($ext), $allowed)) {
                $errors[] = "Nieprawidłowy format obrazu.";
            } else {
                $imageName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                move_uploaded_file($f['tmp_name'], __DIR__ . "/uploads/properties/$imageName");
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE properties SET title=:t,description=:d,price=:p,city=:c,image=:i WHERE id=:id");
            $stmt->execute(['t'=>$title,'d'=>$desc,'p'=>$price,'c'=>$city,'i'=>$imageName,'id'=>$id]);

            // Log activity if admin functions exist
            if (function_exists('admin_log_activity')) {
                admin_log_activity($pdo, $_SESSION['user_id'] ?? null, 'Edytowano ofertę', "property_id:{$id}, title: " . $title);
            }

            // Redirect based on role
            if ($is_admin) {
                header('Location: admin_browse_properties.php');
            } else {
                header('Location: my_properties.php');
            }
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
  <title>Edytuj ofertę — smartrent</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container narrow">
  <h2>Edytuj mieszkanie</h2>
  <?php if ($errors): foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($e)?></div>
  <?php endforeach; endif; ?>
  <form method="post" enctype="multipart/form-data">
    <label>Tytuł
      <input type="text" name="title" required value="<?=htmlspecialchars($prop['title'])?>">
    </label>
    <label>Opis
      <textarea name="description" rows="6"><?=htmlspecialchars($prop['description'])?></textarea>
    </label>
    <label>Miasto
      <input type="text" name="city" required value="<?=htmlspecialchars($prop['city'])?>">
    </label>
    <label>Cena (PLN)
      <input type="number" step="0.01" name="price" required value="<?=htmlspecialchars($prop['price'])?>">
    </label>
    <label>Nowy obraz (opcjonalnie)
      <input type="file" name="image" accept="image/*">
    </label>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Zapisz</button>
      <a class="btn" href="<?= $is_admin ? 'admin_browse_properties.php' : 'my_properties.php' ?>">Anuluj</a>
    </div>
  </form>
</main>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>