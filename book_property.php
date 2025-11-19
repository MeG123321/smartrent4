<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Start session early
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_login();

$user_id = $_SESSION['user_id'];
$property_id = intval($_GET['id'] ?? 0);

if (!$property_id) {
    header('Location: property_list.php');
    exit;
}

// Pobierz informacje o mieszkaniu
$stmt = $pdo->prepare("SELECT p.*, u.name AS owner_name, u.id AS owner_id 
                       FROM properties p 
                       LEFT JOIN users u ON p.owner_id = u.id 
                       WHERE p.id = :id LIMIT 1");
$stmt->execute(['id' => $property_id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    header('Location: property_list.php');
    exit;
}

$errors = [];
$success = false;

// Sprawdź, czy użytkownik próbuje zarezerwować własne mieszkanie
if ($property['owner_id'] == $user_id) {
    $errors[] = "Nie możesz zarezerwować własnego mieszkania.";
}

// Sprawdź, czy mieszkanie jest już wynajęte
if ($property['is_rented'] == 1) {
    $errors[] = "To mieszkanie jest już wynajęte.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    
    // Walidacja dat
    if (empty($start_date) || empty($end_date)) {
        $errors[] = "Proszę podać daty wynajmu.";
    } else {
        $start = strtotime($start_date);
        $end = strtotime($end_date);
        $today = strtotime(date('Y-m-d'));
        
        if ($start < $today) {
            $errors[] = "Data rozpoczęcia nie może być w przeszłości.";
        }
        
        if ($end <= $start) {
            $errors[] = "Data zakończenia musi być po dacie rozpoczęcia.";
        }
    }
    
    // Jeśli brak błędów, utwórz rezerwację
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Utwórz wpis w tabeli rentals
            $stmt = $pdo->prepare("INSERT INTO rentals (user_id, property_id, start_date, end_date, price, status, created_at) 
                                   VALUES (:user_id, :property_id, :start_date, :end_date, :price, 'active', NOW())");
            $stmt->execute([
                'user_id' => $user_id,
                'property_id' => $property_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'price' => $property['price']
            ]);
            
            // Oznacz mieszkanie jako wynajęte
            $stmt = $pdo->prepare("UPDATE properties SET is_rented = 1 WHERE id = :id");
            $stmt->execute(['id' => $property_id]);
            
            $pdo->commit();
            $success = true;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Błąd rezerwacji: " . $e->getMessage());
            $errors[] = "Wystąpił błąd podczas rezerwacji. Spróbuj ponownie później.";
        }
    }
}

// Helper do formatowania ceny
if (!function_exists('format_price')) {
    function format_price($amount): string {
        if ($amount === null || $amount === '' || !is_numeric($amount)) return '-';
        $val = (float)$amount;
        if (floor($val) == $val) {
            return number_format($val, 0, ',', ' ') . ' zł';
        }
        return number_format($val, 2, ',', ' ') . ' zł';
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Rezerwacja — <?=htmlspecialchars($property['title'])?> — <?=APP_NAME?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  
  <?php if ($success): ?>
    <div class="panel">
      <h2>✓ Rezerwacja potwierdzona!</h2>
      <p>Twoja rezerwacja została pomyślnie utworzona.</p>
      <h3><?=htmlspecialchars($property['title'])?></h3>
      <p><strong>Miasto:</strong> <?=htmlspecialchars($property['city'])?></p>
      <p><strong>Cena:</strong> <?=format_price($property['price'])?> / miesiąc</p>
      <p><strong>Okres wynajmu:</strong> <?=htmlspecialchars($start_date)?> → <?=htmlspecialchars($end_date)?></p>
      
      <div style="margin-top:20px">
        <a class="btn" href="rented_properties.php">Zobacz wynajęte mieszkania</a>
        <a class="btn btn-ghost" href="property_list.php">Przeglądaj dalej</a>
      </div>
    </div>
  <?php else: ?>
    
    <h2>Rezerwacja mieszkania</h2>
    
    <div class="property-header" style="margin-bottom:20px">
      <div class="property-gallery" style="max-width:400px">
        <img src="<?= $property['image'] ? 'uploads/properties/'.rawurlencode($property['image']) : 'assets/img/placeholder.png' ?>" 
             alt="<?=htmlspecialchars($property['title'])?>" 
             style="width:100%; border-radius:8px">
      </div>
      <div class="property-meta">
        <h3><?=htmlspecialchars($property['title'])?></h3>
        <p class="muted"><?=htmlspecialchars($property['city'])?></p>
        <div class="price"><?=format_price($property['price'])?> / miesiąc</div>
        <?php if (!empty($property['owner_name'])): ?>
          <p class="muted">Właściciel: <?=htmlspecialchars($property['owner_name'])?></p>
        <?php endif; ?>
      </div>
    </div>
    
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
          <div><?=htmlspecialchars($error)?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    
    <?php if (empty($errors) || (count($errors) === 1 && in_array("Nie możesz zarezerwować własnego mieszkania.", $errors))): ?>
      <?php if (!in_array("Nie możesz zarezerwować własnego mieszkania.", $errors) && !in_array("To mieszkanie jest już wynajęte.", $errors)): ?>
        <div class="panel">
          <form method="post" action="">
            <h4>Wybierz daty wynajmu</h4>
            <label>
              Data rozpoczęcia
              <input type="date" name="start_date" required min="<?=date('Y-m-d')?>" value="<?=htmlspecialchars($_POST['start_date'] ?? '')?>">
            </label>
            <label>
              Data zakończenia
              <input type="date" name="end_date" required min="<?=date('Y-m-d', strtotime('+1 day'))?>" value="<?=htmlspecialchars($_POST['end_date'] ?? '')?>">
            </label>
            <div class="form-actions" style="margin-top:20px">
              <button type="submit" class="btn btn-primary">Potwierdź rezerwację</button>
              <a href="property_details.php?id=<?=intval($property_id)?>" class="btn btn-ghost">Anuluj</a>
            </div>
          </form>
        </div>
      <?php endif; ?>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
      <div style="margin-top:20px">
        <a class="btn" href="property_list.php">Powrót do listy ofert</a>
      </div>
    <?php endif; ?>
    
  <?php endif; ?>
  
</main>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
