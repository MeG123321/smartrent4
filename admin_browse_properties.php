<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/admin_functions.php';

// session early (navbar/auth may rely on it)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin-only access
require_role('admin');

// Safety helpers local to this file
if (!function_exists('shorten')) {
    function shorten(string $text, int $max = 60): string {
        $text = trim($text);
        if (mb_strlen($text) <= $max) return $text;
        return rtrim(mb_substr($text, 0, $max - 1)) . '…';
    }
}

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

// Handle property deletion
$delete_error = '';
$delete_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_property'])) {
    $property_id = intval($_POST['property_id'] ?? 0);
    
    if ($property_id > 0) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Delete associated rentals first
            $stmt = $pdo->prepare("DELETE FROM rentals WHERE property_id = :pid");
            $stmt->execute(['pid' => $property_id]);
            
            // Delete associated assignments
            $stmt = $pdo->prepare("DELETE FROM assignments WHERE property_id = :pid");
            $stmt->execute(['pid' => $property_id]);
            
            // Delete associated messages
            $stmt = $pdo->prepare("DELETE FROM messages WHERE property_id = :pid");
            $stmt->execute(['pid' => $property_id]);
            
            // Delete the property
            $stmt = $pdo->prepare("DELETE FROM properties WHERE id = :pid");
            $stmt->execute(['pid' => $property_id]);
            
            // Commit transaction
            $pdo->commit();
            
            // Log activity
            admin_log_activity($pdo, $_SESSION['user_id'] ?? null, 'Usunięto ofertę', "property_id:{$property_id}");
            
            $delete_success = "Nieruchomość została pomyślnie usunięta.";
        } catch (Exception $e) {
            // Rollback on error
            $pdo->rollBack();
            error_log("Błąd przy usuwaniu nieruchomości: " . $e->getMessage());
            $delete_error = "Wystąpił błąd podczas usuwania nieruchomości.";
        }
    }
}

// Input
$q = trim((string)($_GET['q'] ?? ''));
$city = trim((string)($_GET['city'] ?? ''));
$sort = trim((string)($_GET['sort'] ?? ''));

// Build SQL with prepared params - show all properties for admin
$sql = "SELECT id, title, city, price, image, is_rented, created_at, owner_id FROM properties WHERE 1=1";
$params = [];

if ($q !== '') {
    $sql .= " AND (title LIKE :q OR description LIKE :q)";
    $params['q'] = '%' . $q . '%';
}
if ($city !== '') {
    $sql .= " AND city = :city";
    $params['city'] = $city;
}

// Sorting
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY price DESC";
        break;
    case 'newest':
        $sql .= " ORDER BY created_at DESC";
        break;
    default:
        $sql .= " ORDER BY id DESC";
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $props = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo "<h2>Błąd serwera</h2><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Zarządzaj ofertami — smartrent</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .property-admin-card {
      position: relative;
    }
    .delete-btn-container {
      margin-top: 8px;
    }
  </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Zarządzaj ofertami (Admin)</h2>

  <?php if ($delete_error): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($delete_error)?></div>
  <?php endif; ?>
  
  <?php if ($delete_success): ?>
    <div class="alert alert-success"><?=htmlspecialchars($delete_success)?></div>
  <?php endif; ?>

  <form class="filters" method="get" action="admin_browse_properties.php">
    <input type="search" name="q" placeholder="Szukaj (tytuł, opis)" value="<?=htmlspecialchars($q, ENT_QUOTES)?>">
    <input type="text" name="city" placeholder="Miasto" value="<?=htmlspecialchars($city, ENT_QUOTES)?>">
    <select name="sort">
      <option value="">Sortowanie</option>
      <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Cena: od najniższej</option>
      <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Cena: od najwyższej</option>
      <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Najnowsze</option>
    </select>
    <button class="btn" type="submit">Szukaj</button>
    <a class="btn btn-ghost" href="admin_browse_properties.php">Wyczyść</a>
  </form>

  <div style="margin-bottom:12px">
    <a class="btn btn-ghost" href="admin_panel.php">Powrót do panelu</a>
  </div>

  <?php if (empty($props)): ?>
    <p>Brak ofert do wyświetlenia.</p>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($props as $p): ?>
        <?php
          if (!empty($p['image'])) {
              $imgSrc = 'uploads/properties/' . rawurlencode($p['image']);
          } else {
              $imgSrc = 'assets/img/placeholder.png';
          }
          $imgSrcEsc = htmlspecialchars($imgSrc, ENT_QUOTES);
          $title = htmlspecialchars($p['title'] ?? '', ENT_QUOTES);
          $cityOut = htmlspecialchars($p['city'] ?? '', ENT_QUOTES);
          $idOut = intval($p['id']);
          $status = ($p['is_rented'] == 1) ? 'Wynajęte' : 'Dostępne';
          $statusClass = ($p['is_rented'] == 1) ? 'badge status-rented' : 'badge status-available';
        ?>
        <article class="card property-admin-card">
          <a href="property_details.php?id=<?=$idOut?>">
            <div class="card-img" style="background-image:url('<?=$imgSrcEsc?>')">
              <span class="<?=$statusClass?>"><?=htmlspecialchars($status)?></span>
            </div>
            <div class="card-body">
              <h3><?=htmlspecialchars(shorten($p['title'] ?? ''), ENT_QUOTES)?></h3>
              <p class="muted"><?=$cityOut?></p>
              <div class="price"><?=htmlspecialchars(format_price($p['price']))?> / miesiąc</div>
            </div>
          </a>
          <div class="delete-btn-container" style="padding: 0 12px 12px;">
            <a class="btn btn-sm" href="edit_property.php?id=<?=$idOut?>">Edytuj</a>
            <form method="post" style="display:inline" onsubmit="return confirm('Czy na pewno chcesz usunąć tę nieruchomość? Zostaną usunięte wszystkie powiązane rezerwacje i dane.');">
              <input type="hidden" name="property_id" value="<?=$idOut?>">
              <button type="submit" name="delete_property" class="btn btn-sm" style="background:#dc3545;color:#fff;">Usuń</button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
