<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/admin_functions.php';
session_start();
require_role('admin');

$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;
$report = ['summary'=>['total_revenue'=>0,'total_rentals'=>0],'by_property'=>[]];

if ($from || $to) {
    $report = admin_generate_report($pdo, $from, $to);
    // jeśli żądanie eksportu CSV
    if (!empty($_GET['export']) && $_GET['export'] === 'csv') {
        admin_export_report_csv($report['by_property'], $report['summary'], 'raport_'.$from.'_'.$to.'.csv');
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Raporty — Panel admina</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Raporty</h2>

  <form method="get" class="panel" style="display:flex;gap:8px;align-items:center">
    <label>Od <input type="date" name="from" value="<?=htmlspecialchars($from)?>"></label>
    <label>Do <input type="date" name="to" value="<?=htmlspecialchars($to)?>"></label>
    <button class="btn btn-primary" type="submit">Generuj</button>
    <?php if ($from || $to): ?>
      <a class="btn" href="?from=<?=urlencode($from)?>&to=<?=urlencode($to)?>&export=csv">Eksportuj CSV</a>
    <?php endif; ?>
  </form>

  <?php if ($report['summary']): ?>
    <div class="panel" style="margin-top:12px">
      <h3>Podsumowanie</h3>
      <p>Liczba rezerwacji: <strong><?=intval($report['summary']['total_rentals'])?></strong></p>
      <p>Przychód: <strong><?=number_format((float)$report['summary']['total_revenue'],2,',',' ')?> zł</strong></p>
    </div>

    <div class="panel" style="margin-top:12px">
      <h3>Top oferty</h3>
      <table class="table">
        <thead><tr><th>ID</th><th>Tytuł</th><th>Miasto</th><th>Rezerwacje</th><th>Przychód</th></tr></thead>
        <tbody>
        <?php foreach ($report['by_property'] as $p): ?>
          <tr>
            <td><?=htmlspecialchars($p['id'])?></td>
            <td><?=htmlspecialchars($p['title'])?></td>
            <td><?=htmlspecialchars($p['city'])?></td>
            <td><?=htmlspecialchars($p['bookings'])?></td>
            <td><?=number_format((float)$p['revenue'],2,',',' ')?> zł</td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>