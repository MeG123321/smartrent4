<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!is_logged_in()) { header('Location: login.php'); exit; }
$me = $_SESSION['user_id'];

$id = intval($_GET['id'] ?? 0);
if (!$id) { echo "Brak id przypisania"; exit; }

// Pobierz przypisanie i sprawdź uprawnienia (owner lub admin lub tenant)
$stmt = $pdo->prepare("SELECT a.*, p.title AS property_title, p.owner_id, u.name AS tenant_name, u.email AS tenant_email, p.price AS rent_price FROM assignments a JOIN properties p ON a.property_id = p.id LEFT JOIN users u ON a.tenant_id = u.id WHERE a.id = :id LIMIT 1");
$stmt->execute(['id'=>$id]);
$assign = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$assign) { echo "Brak przypisania"; exit; }

$canView = false;
if (function_exists('is_admin') && is_admin()) $canView = true;
if ($assign['tenant_id'] == $me) $canView = true;
if ($assign['owner_id'] == $me) $canView = true;
if (!$canView) { echo "Brak uprawnień"; exit; }

// Pobierz płatności powiązane z przypisaniem
$stmt = $pdo->prepare("SELECT * FROM payments WHERE assignment_id = :aid ORDER BY due_date ASC");
$stmt->execute(['aid'=>$id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pobierz zgłoszenia maintenance
$stmt = $pdo->prepare("SELECT * FROM maintenance_reports WHERE assignment_id = :aid ORDER BY created_at DESC");
$stmt->execute(['aid'=>$id]);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pl">
<head><meta charset="utf-8"><title>Przypisanie #<?=htmlspecialchars($assign['id'])?></title><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Mieszkanie: <?=htmlspecialchars($assign['property_title'])?></h2>
  <p>Najemca: <?=htmlspecialchars($assign['tenant_name'] ?? $assign['tenant_id'])?> (<?=htmlspecialchars($assign['tenant_email'] ?? '')?>)</p>
  <p>Status: <?=htmlspecialchars($assign['status'])?></p>
  <p>Kwota (domyślny czynsz): <?=htmlspecialchars(number_format((float)($assign['rent_price'] ?? 0), 2, ',', ' '))?> zł</p>

  <h3>Harmonogram płatności</h3>
  <?php if (empty($payments)): ?>
    <p>Brak zapisanych płatności.</p>
  <?php else: ?>
    <table border="1" cellpadding="6">
      <tr><th>Due date</th><th>Amount</th><th>Status</th><th>Paid at</th></tr>
      <?php foreach ($payments as $p): ?>
        <tr>
          <td><?=htmlspecialchars($p['due_date'])?></td>
          <td><?=htmlspecialchars(number_format((float)$p['amount'], 2, ',', ' '))?> zł</td>
          <td><?=htmlspecialchars($p['status'])?></td>
          <td><?=htmlspecialchars($p['paid_at'] ?? '-')?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

  <h3>Zgłoszenia usterek</h3>
  <p><a class="btn" href="maintenance_report.php?assignment_id=<?=urlencode($assign['id'])?>">Zgłoś usterkę</a></p>
  <?php if (empty($reports)): ?>
    <p>Brak zgłoszeń.</p>
  <?php else: ?>
    <ul>
      <?php foreach ($reports as $r): ?>
        <li><?=htmlspecialchars($r['title'])?> — <?=htmlspecialchars($r['status'])?> (<?=htmlspecialchars($r['created_at'])?>)</li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <p><a href="admin_assignment_list.php">Powrót do listy przypisań</a></p>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>