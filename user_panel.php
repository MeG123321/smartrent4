<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
session_start();
require_login();

$user_id = $_SESSION['user_id'];

// krótkie statystyki użytkownika
$stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE user_id = :uid");
$stmt->execute(['uid'=>$user_id]);
$totalRentals = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE user_id = :uid AND start_date >= CURDATE()");
$stmt->execute(['uid'=>$user_id]);
$upcoming = (int)$stmt->fetchColumn();

// Liczba mieszkań posiadanych przez użytkownika
$stmt = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE owner_id = :uid");
$stmt->execute(['uid'=>$user_id]);
$ownedProperties = (int)$stmt->fetchColumn();

// Liczba wynajętych mieszkań przez użytkownika
$stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE user_id = :uid AND status = 'active'");
$stmt->execute(['uid'=>$user_id]);
$rentedProperties = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT id,property_id,start_date,end_date,price,created_at FROM rentals WHERE user_id = :uid ORDER BY created_at DESC LIMIT 10");
$stmt->execute(['uid'=>$user_id]);
$recentRents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT id,subject,status,created_at FROM support_tickets WHERE user_id = :uid ORDER BY created_at DESC LIMIT 5");
$stmt->execute(['uid'=>$user_id]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Moje konto — <?=APP_NAME?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Witaj, <?=htmlspecialchars($_SESSION['user_name'])?></h2>

  <div class="grid" style="grid-template-columns:1fr 320px;gap:18px">
    <section>
      <div class="panel">
        <h3>Podsumowanie konta</h3>
        <p>Łączne rezerwacje: <strong><?=intval($totalRentals)?></strong></p>
        <p>Nadchodzące: <strong><?=intval($upcoming)?></strong></p>
        <p><a class="btn" href="rent_history.php">Zobacz historię wynajmów</a> <a class="btn" href="user_settings.php">Ustawienia konta</a></p>
      </div>

      <div class="panel" style="margin-top:12px">
        <h3>Moje nieruchomości</h3>
        <p>Moje mieszkania: <strong><?=intval($ownedProperties)?></strong></p>
        <p>Wynajęte mieszkania: <strong><?=intval($rentedProperties)?></strong></p>
        <p>
          <a class="btn" href="my_properties.php">Moje mieszkania</a>
          <a class="btn" href="rented_properties.php">Wynajęte mieszkania</a>
        </p>
      </div>

      <h3>Ostatnie rezerwacje</h3>
      <div class="panel">
        <?php if (!$recentRents): ?>
          <p>Brak rezerwacji.</p>
        <?php else: ?>
          <table class="table">
            <thead><tr><th>#</th><th>Okres</th><th>Cena</th><th>Data rezerwacji</th></tr></thead>
            <tbody>
            <?php foreach ($recentRents as $r): ?>
              <tr>
                <td><?=htmlspecialchars($r['id'])?></td>
                <td><?=htmlspecialchars($r['start_date'])?> → <?=htmlspecialchars($r['end_date'])?></td>
                <td><?=number_format((float)$r['price'],2,',',' ')?> zł</td>
                <td><?=htmlspecialchars($r['created_at'])?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

      <h3 style="margin-top:12px">Wiadomości i pomoc</h3>
      <div class="panel">
        <p><a class="btn" href="messages.php">Wiadomości</a> <a class="btn" href="support_ticket.php">Zgłoś problem</a></p>
        <h4>Twoje zgłoszenia</h4>
        <?php if (!$tickets): ?>
          <p>Brak zgłoszeń</p>
        <?php else: foreach ($tickets as $t): ?>
          <div style="padding:8px;border-bottom:1px solid rgba(255,255,255,0.03)">
            <strong>#<?=htmlspecialchars($t['id'])?></strong> <?=htmlspecialchars($t['subject'])?><br>
            <span class="muted"><?=htmlspecialchars($t['status'])?> • <?=htmlspecialchars($t['created_at'])?></span>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </section>

    <aside>
      <div class="panel">
        <h4>Pobierz dane</h4>
        <p class="muted">Możesz pobrać historię rezerwacji w formacie CSV</p>
        <form method="post" action="export_my_rentals.php">
          <input type="hidden" name="user_id" value="<?=intval($user_id)?>">
          <button class="btn" type="submit">Pobierz CSV</button>
        </form>
      </div>

      <div class="panel" style="margin-top:12px">
        <h4>Pomoc</h4>
        <p class="muted">Masz pytanie? Sprawdź FAQ lub zgłoś problem.</p>
        <p><a class="btn" href="help.php">FAQ / Dok.</a> <a class="btn" href="support_ticket.php">Zgłoś problem</a></p>
      </div>
    </aside>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>