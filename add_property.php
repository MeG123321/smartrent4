<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
session_start();
require_role('admin');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $owner_id = intval($_POST['owner_id'] ?? 0) ?: $_SESSION['user_id'];

    if (!$title || !$city || $price <= 0) {
        $errors[] = "Wypełnij pola: tytuł, miasto, cena.";
    } else {
        // upload image
        $imageName = null;
        if (!empty($_FILES['image']['name'])) {
            $f = $_FILES['image'];
            $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg','jpeg','png','webp'];
            if (!in_array(strtolower($ext), $allowed)) {
                $errors[] = "Nieprawidłowy format obrazu.";
            } else {
                $imageName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                if (!move_uploaded_file($f['tmp_name'], __DIR__ . "/uploads/properties/$imageName")) {
                    $errors[] = "Błąd zapisu pliku.";
                }
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO properties (title,description,price,city,image,owner_id,created_at) VALUES (:t,:d,:p,:c,:i,:o,NOW())");
            $stmt->execute([
                't'=>$title,'d'=>$desc,'p'=>$price,'c'=>$city,'i'=>$imageName,'o'=>$owner_id
            ]);

            $propId = (int)$pdo->lastInsertId();

            require_once 'includes/admin_functions.php';
            admin_log_activity($pdo, $_SESSION['user_id'] ?? null, 'Dodano ofertę', "property_id:{$propId}, title: " . $title);

            header('Location: admin_panel.php');
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
  <title>Dodaj ofertę — smartrent</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container narrow">
  <h2>Dodaj mieszkanie</h2>
  <?php if ($errors): foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($e)?></div>
  <?php endforeach; endif; ?>
  <form method="post" enctype="multipart/form-data" id="addPropForm" novalidate>
    <label>Tytuł
      <input type="text" name="title" required>
    </label>
    <label>Opis
      <textarea name="description" rows="6"></textarea>
    </label>
    <label>Miasto
      <input type="text" name="city" required>
    </label>
    <label>Cena (PLN)
      <input type="number" step="0.01" name="price" required>
    </label>
    <label>Obraz (jpg/png)
      <input type="file" name="image" accept="image/*">
    </label>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Dodaj</button>
      <a class="btn" href="admin_panel.php">Anuluj</a>
    </div>
  </form>
</main>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>