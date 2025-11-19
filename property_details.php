<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// start session early so includes/auth.php can use it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/auth.php';
require_once 'includes/admin_functions.php';

// Pobierz id oferty
$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: property_list.php');
    exit;
}

// Pobierz ofertę
$stmt = $pdo->prepare("SELECT p.*, u.name AS owner_name, u.email AS owner_email, u.id AS owner_id FROM properties p LEFT JOIN users u ON p.owner_id = u.id WHERE p.id = :id LIMIT 1");
$stmt->execute(['id' => $id]);
$prop = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$prop) {
    header('Location: property_list.php');
    exit;
}

// Format ceny bez zewnętrznego helpera (PLN)
$display_price = '-';
if (isset($prop['price']) && is_numeric($prop['price'])) {
    $val = (float)$prop['price'];
    if (floor($val) == $val) {
        $display_price = number_format($val, 0, ',', ' ') . ' zł';
    } else {
        $display_price = number_format($val, 2, ',', ' ') . ' zł';
    }
}

/*
  ZAMIANA: Usunięto mechanizm rezerwacji datami.
  Dodano prosty formularz do wysyłania wiadomości do właściciela.
*/

$msg_errors = [];
$msg_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    // Wysyłanie wiadomości do właściciela (tylko dla zalogowanych)
    if (!is_logged_in()) {
        $msg_errors[] = "Musisz być zalogowany, aby wysłać wiadomość do właściciela.";
    } else {
        $body = trim($_POST['message'] ?? '');
        $from_user = $_SESSION['user_id'] ?? null;
        $to_user = $prop['owner_id'] ?? null;

        if (!$to_user) {
            $msg_errors[] = "Adresat wiadomości (właściciel) nie jest dostępny.";
        }

        if ($from_user && $to_user && $from_user == $to_user) {
            $msg_errors[] = "Nie możesz wysłać wiadomości do samego siebie.";
        }

        if ($body === '') {
            $msg_errors[] = "Wpisz treść wiadomości.";
        }

        if (empty($msg_errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO messages (from_user_id, to_user_id, property_id, body, sent_at, read_flag) VALUES (:from, :to, :pid, :body, NOW(), 0)");
                $stmt->execute([
                    'from' => $from_user,
                    'to'   => $to_user,
                    'pid'  => $id,
                    'body' => $body
                ]);

                // Log aktywności (opcjonalne)
                admin_log_activity($pdo, $from_user, 'Wysłano wiadomość do właściciela', "property_id:{$id}, to_user:{$to_user}");

                $msg_success = "Wiadomość została wysłana do właściciela.";
                // wyczyść textarea po sukcesie
                $_POST['message'] = '';
            } catch (Exception $e) {
                error_log("Błąd przy zapisie wiadomości: " . $e->getMessage());
                $msg_errors[] = "Wystąpił błąd podczas wysyłania wiadomości. Spróbuj ponownie później.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?=htmlspecialchars($prop['title'] ?? 'Oferta')?> — smartrent</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <div class="property-header">
    <div class="property-gallery">
      <img src="<?= $prop['image'] ? 'uploads/properties/'.rawurlencode($prop['image']) : 'assets/img/placeholder.png' ?>" alt="<?=htmlspecialchars($prop['title'])?>">
    </div>
    <div class="property-meta">
      <h1><?=htmlspecialchars($prop['title'])?></h1>
      <p class="muted"><?=htmlspecialchars($prop['city'])?> — <?=htmlspecialchars($display_price)?> / miesiąc</p>
      <p><?=nl2br(htmlspecialchars($prop['description'] ?? ''))?></p>
      <p>Właściciel: <?=htmlspecialchars($prop['owner_name'] ?? '—')?></p>

      <!-- Komunikat o dostępności -->
      <?php if ($prop['is_rented'] == 1): ?>
        <div class="alert alert-info" style="margin-top:12px">
          <strong>Niedostępne</strong> — To mieszkanie jest obecnie wynajęte.
        </div>
      <?php else: ?>
        <div class="alert alert-info" style="margin-top:12px">
          <strong>Dostępne</strong> — To mieszkanie jest dostępne do wynajęcia.
        </div>
      <?php endif; ?>

      <?php if (!empty($msg_errors)): ?>
        <div class="alert alert-danger">
          <?php foreach ($msg_errors as $e): ?>
            <div><?=htmlspecialchars($e)?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($msg_success): ?>
        <div class="alert alert-info"><?=htmlspecialchars($msg_success)?></div>
      <?php endif; ?>

      <!-- Formularz wysyłki wiadomości do właściciela -->
      <?php if (!empty($prop['owner_id'])): ?>
        <?php if (is_logged_in()): ?>
          <form method="post" class="message-form">
            <h4>Napisz do właściciela</h4>
            <label>Treść wiadomości
              <textarea name="message" rows="6" required><?=htmlspecialchars($_POST['message'] ?? '')?></textarea>
            </label>
            <div class="form-actions">
              <button class="btn btn-primary" type="submit" name="send_message">Wyślij wiadomość</button>
            </div>
          </form>
        <?php else: ?>
          <p>Aby wysłać wiadomość do właściciela, <a class="btn btn-primary" href="login.php">zaloguj się</a> lub <a href="register.php">zarejestruj</a>.</p>
        <?php endif; ?>
      <?php else: ?>
        <p class="muted">Brak przypisanego właściciela — nie można wysłać wiadomości.</p>
      <?php endif; ?>

    </div>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>