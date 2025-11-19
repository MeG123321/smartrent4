<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}
$me = $_SESSION['user_id'];

$property_id = intval($_GET['property_id'] ?? 0);
$partner_id = intval($_GET['partner_id'] ?? 0);
if (!$property_id || !$partner_id) {
    echo "Brak parametru property_id lub partner_id.";
    exit;
}

// sprawdź czy partner i property istnieją
$stmt = $pdo->prepare("SELECT id,title,owner_id FROM properties WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $property_id]);
$prop = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$prop) { echo "Brak oferty."; exit; }

$stmt = $pdo->prepare("SELECT id,name,email,role FROM users WHERE id = :id LIMIT 1");
$stmt->execute(['id'=>$partner_id]);
$partner = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$partner) { echo "Brak użytkownika."; exit; }

// sprawdź uprawnienia: current user musi być jedna ze stron w rozmowie (może też być owner)
if (!($me && $partner_id)) {
    echo "Brak uprawnień.";
    exit;
}

// Obsługa wysłania odpowiedzi
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $body = trim($_POST['body'] ?? '');
    if ($body === '') $errors[] = "Wpisz treść wiadomości.";
    if (empty($errors)) {
        $sql = "INSERT INTO messages (from_user_id,to_user_id,property_id,body,sent_at,read_flag) VALUES (:from,:to,:pid,:body,NOW(),0)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'from' => $me,
            'to'   => $partner_id,
            'pid'  => $property_id,
            'body' => $body
        ]);
        // opcjonalnie: powiadomienie email, activity_log
        admin_log_activity($pdo, $me, 'Wysłano wiadomość', "to:{$partner_id}, property:{$property_id}");
        // redirect aby uniknąć re-submit
        header("Location: message_detail.php?property_id={$property_id}&partner_id={$partner_id}");
        exit;
    }
}

// Pobierz wszystkie wiadomości między tymi użytkownikami dotyczące tej oferty
$stmt = $pdo->prepare("
  SELECT m.*, u_from.name AS from_name, u_to.name AS to_name
  FROM messages m
  LEFT JOIN users u_from ON m.from_user_id = u_from.id
  LEFT JOIN users u_to ON m.to_user_id = u_to.id
  WHERE m.property_id = :pid
    AND ((m.from_user_id = :me AND m.to_user_id = :partner) OR (m.from_user_id = :partner AND m.to_user_id = :me))
  ORDER BY m.sent_at ASC
");
$stmt->execute(['pid'=>$property_id, 'me'=>$me, 'partner'=>$partner_id]);
$thread = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dla właściciela oferty: pokaż przycisk przypisz (assign) jeżeli partner jest najemcą (albo chcesz przypisać)
$canAssign = ($prop['owner_id'] == $me); // właściciel oferty może przypisać
?>
<!doctype html>
<html lang="pl">
<head><meta charset="utf-8"><title>Konwersacja — <?=htmlspecialchars($prop['title'])?></title><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Rozmowa: <?=htmlspecialchars($partner['name'] ?? 'Użytkownik')?> — <?=htmlspecialchars($prop['title'])?></h2>

  <?php if ($canAssign): ?>
    <form method="post" action="assign_property.php" onsubmit="return confirm('Przypisać to mieszkanie temu użytkownikowi?');">
      <input type="hidden" name="property_id" value="<?=htmlspecialchars($property_id)?>">
      <input type="hidden" name="tenant_id" value="<?=htmlspecialchars($partner_id)?>">
      <button class="btn btn-primary" type="submit" name="assign">Przypisz mieszkanie użytkownikowi</button>
    </form>
  <?php endif; ?>

  <div class="chat-box">
    <?php if (empty($thread)): ?>
      <p class="muted">Brak wiadomości w wątku.</p>
    <?php else: foreach ($thread as $m): ?>
      <div class="message <?= $m['from_user_id'] == $me ? 'sent' : 'received' ?>">
        <div class="meta"><strong><?=htmlspecialchars($m['from_name'] ?? 'Użytkownik')?></strong> <span class="muted"><?=htmlspecialchars($m['sent_at'])?></span></div>
        <div class="body"><?=nl2br(htmlspecialchars($m['body']))?></div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <h3>Odpowiedz</h3>
  <?php if ($errors): foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($e)?></div>
  <?php endforeach; endif; ?>

  <form method="post">
    <textarea name="body" rows="6" required></textarea>
    <div class="form-actions"><button class="btn btn-primary" type="submit" name="reply">Wyślij</button></div>
  </form>

  <p><a href="messages_list.php">Powrót do listy wiadomości</a></p>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>