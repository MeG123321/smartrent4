<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/admin_functions.php';
session_start();
require_role('admin');

$stats = admin_get_stats($pdo);
$recent = admin_get_recent($pdo);
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Panel administratora — <?=APP_NAME?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Panel administratora</h2>

  <div class="grid-3 stats" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:18px">
    <div class="panel">
      <h3>Użytkownicy</h3>
      <p style="font-size:1.6rem;font-weight:700"><?=intval($stats['users'])?></p>
      <p class="muted">Zarejestrowani</p>
      <p><a class="btn" href="manage_users.php">Zarządzaj</a></p>
    </div>
    <div class="panel">
      <h3>Oferty</h3>
      <p style="font-size:1.6rem;font-weight:700"><?=intval($stats['properties'])?></p>
      <p class="muted">Aktywne oferty</p>
      <p><a class="btn" href="property_list.php">Przeglądaj</a></p>
    </div>
    <div class="panel">
      <h3>Wynajmy</h3>
      <p style="font-size:1.6rem;font-weight:700"><?=intval($stats['rentals'])?></p>
      <p class="muted">Rezerwacje</p>
      <p><a class="btn" href="rent_history.php">Historia</a></p>
    </div>
    <div class="panel">
      <h3>Przychód</h3>
      <p style="font-size:1.6rem;font-weight:700"><?=number_format($stats['revenue'],2,',',' ')?> zł</p>
      <p class="muted">Łączny przychód</p>
      <p><a class="btn" href="admin_reports.php">Raporty</a></p>
    </div>
  </div>

  <div class="grid" style="grid-template-columns:1fr 360px;gap:18px">
    <section>
      <h3>Ostatnie aktywności</h3>
      <div class="panel">
        <?php foreach ($recent['rentals'] as $r): ?>
          <div style="padding:8px;border-bottom:1px solid rgba(255,255,255,0.03)">
            <strong>#<?=htmlspecialchars($r['id'])?></strong>
            <?=htmlspecialchars($r['title'] ?? '—')?> —
            <?=htmlspecialchars($r['start_date'])?> → <?=htmlspecialchars($r['end_date'])?>
            <div class="muted"><?=htmlspecialchars($r['created_at'])?></div>
          </div>
        <?php endforeach; ?>
        <p class="center"><a class="btn" href="admin_logs.php">Zobacz logi i szczegóły</a></p>
      </div>

      <h3 style="margin-top:18px">Szybkie akcje</h3>
      <div class="panel">
        <a class="btn btn-primary" href="add_property.php">Dodaj ofertę</a>
        <a class="btn" href="manage_users.php">Zarządzaj użytkownikami</a>
        <a class="btn" href="admin_reports.php">Generuj raport</a>
        <a class="btn" href="admin_settings.php">Ustawienia serwisu</a>
      </div>
    </section>

    <aside>
      <div class="panel">
        <h4>Pomoc / Dokumentacja</h4>
        <p class="muted">Krótka pomoc administracyjna:</p>
        <ul class="muted">
          <li>Zarządzaj ofertami i użytkownikami przez odpowiednie sekcje.</li>
          <li>Raporty: wybierz zakres dat, pobierz CSV z zestawieniami.</li>
          <li>Logi: sprawdź ostatnie działania i błędy.</li>
        </ul>
        <p><a class="btn" href="admin_help.php">Szczegóły pomocy</a></p>
      </div>

      <div class="panel" style="margin-top:12px">
        <h4>Zgłoszenia support</h4>
        <?php
        $stmt = $pdo->query("SELECT id, user_id, subject, status, created_at FROM support_tickets ORDER BY created_at DESC LIMIT 5");
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tickets as $t): ?>
          <div style="padding:6px;border-bottom:1px solid rgba(255,255,255,0.03)">
            <strong>#<?=htmlspecialchars($t['id'])?></strong> <?=htmlspecialchars($t['subject'])?><br>
            <span class="muted"><?=htmlspecialchars($t['status'])?> • <?=htmlspecialchars($t['created_at'])?></span>
          </div>
        <?php endforeach; ?>
        <p class="center"><a class="btn" href="admin_tickets.php">Zarządzaj zgłoszeniami</a></p>
      </div>
    </aside>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>