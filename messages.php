<?php
// messages.php
// Lista konwersacji + chat w jednym widoku.
// W tym pliku wymuszamy rozmiary miniatur i szerokość lewego panelu inline-ami,
// aby nadpisać reguły z assets/css/style.css, które mają większy priorytet.

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/admin_functions.php';
session_start();
require_login();

$user_id = (int)($_SESSION['user_id'] ?? 0);
if (!$user_id) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = '';

// POST: odpowiedź w wątku
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $property_id = intval($_POST['property_id'] ?? 0);
    $partner_id = intval($_POST['partner_id'] ?? 0);
    $body = trim($_POST['body'] ?? '');
    if (!$partner_id || !$property_id || $body === '') {
        $errors[] = "Brak parametrów odpowiedzi lub pusty tekst.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (from_user_id,to_user_id,property_id,body,sent_at,read_flag) VALUES (:from,:to,:pid,:body,NOW(),0)");
            $stmt->execute(['from'=>$user_id,'to'=>$partner_id,'pid'=>$property_id,'body'=>$body]);
            if (function_exists('admin_log_activity')) admin_log_activity($pdo, $user_id, 'Wysłano wiadomość (w wątku)', "to:{$partner_id}, property:{$property_id}");
            header("Location: messages.php?property_id={$property_id}&partner_id={$partner_id}");
            exit;
        } catch (Exception $e) {
            $errors[] = "Błąd bazy przy wysyłaniu odpowiedzi: " . $e->getMessage();
        }
    }
}

// POST: przypisanie mieszkania
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $property_id = intval($_POST['property_id'] ?? 0);
    $tenant_id = intval($_POST['tenant_id'] ?? 0);

    if (!$property_id || !$tenant_id) {
        $errors[] = "Brakuje danych do przypisania.";
    } else {
        $stm = $pdo->prepare("SELECT owner_id, price FROM properties WHERE id = :id LIMIT 1");
        $stm->execute(['id'=>$property_id]);
        $prop = $stm->fetch(PDO::FETCH_ASSOC);
        if (!$prop) {
            $errors[] = "Nie znaleziono nieruchomości.";
        } elseif ((int)$prop['owner_id'] !== $user_id && !(function_exists('is_admin') && is_admin())) {
            $errors[] = "Brak uprawnień do przypisania tej nieruchomości.";
        } else {
            try {
                $stm = $pdo->prepare("SELECT id FROM assignments WHERE property_id = :pid AND tenant_id = :tid AND status = 'confirmed' LIMIT 1");
                $stm->execute(['pid'=>$property_id,'tid'=>$tenant_id]);
                if ($stm->fetchColumn()) {
                    $success = "Ta osoba jest już przypisana do tej nieruchomości.";
                } else {
                    $pdo->beginTransaction();
                    $ins = $pdo->prepare("INSERT INTO assignments (property_id, tenant_id, assigned_by, status, created_at) VALUES (:pid, :tid, :by, 'confirmed', NOW())");
                    $ins->execute(['pid'=>$property_id,'tid'=>$tenant_id,'by'=>$user_id]);
                    $assignmentId = (int)$pdo->lastInsertId();

                    $amount = (float)($prop['price'] ?? 0.00);
                    $pay = $pdo->prepare("INSERT INTO payments (assignment_id, due_date, amount, status, created_at) VALUES (:aid, DATE_ADD(CURDATE(), INTERVAL 30 DAY), :amt, 'due', NOW())");
                    $pay->execute(['aid'=>$assignmentId,'amt'=>$amount]);

                    // Zmień status nieruchomości na wynajętą
                    $upd = $pdo->prepare("UPDATE properties SET is_rented=1 WHERE id=:property_id");
                    $upd->execute(['property_id'=>$property_id]);

                    if (function_exists('admin_log_activity')) admin_log_activity($pdo, $user_id, 'Przypisano mieszkanie (ajax/submit)', "assignment_id:{$assignmentId}, property_id:{$property_id}, tenant_id:{$tenant_id}");
                    $pdo->commit();

                    header("Location: user_panel.php");
                    exit;
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors[] = "Błąd podczas przypisywania: " . $e->getMessage();
            }
        }
    }
}

// Pobierz wiadomości i miniaturekę oferty
try {
    $stmt = $pdo->prepare("SELECT m.*, u_from.name AS from_name, u_to.name AS to_name, p.title AS property_title, p.image AS property_image
                           FROM messages m
                           LEFT JOIN users u_from ON m.from_user_id = u_from.id
                           LEFT JOIN users u_to ON m.to_user_id = u_to.id
                           LEFT JOIN properties p ON m.property_id = p.id
                           WHERE m.from_user_id = :uid OR m.to_user_id = :uid
                           ORDER BY m.sent_at DESC
                           LIMIT 200");
    $stmt->execute(['uid'=>$user_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $convs = [];
    foreach ($rows as $r) {
        $propertyId = intval($r['property_id'] ?? 0);
        $partnerId = ($r['from_user_id'] == $user_id) ? (int)$r['to_user_id'] : (int)$r['from_user_id'];
        if ($partnerId === 0 && $r['from_user_id'] == $user_id) $partnerId = (int)$r['to_user_id'];
        if ($partnerId === 0 && $r['to_user_id'] == $user_id) $partnerId = (int)$r['from_user_id'];
        $key = $propertyId . ':' . $partnerId;
        if (!isset($convs[$key])) {
            $img = $r['property_image'] ?? null;
            $imgUrl = $img ? ('uploads/properties/' . rawurlencode($img)) : null;
            $convs[$key] = [
                'property_id' => $propertyId,
                'partner_id' => $partnerId,
                'partner_name' => ($r['from_user_id'] == $user_id) ? ($r['to_name'] ?? 'Użytkownik') : ($r['from_name'] ?? 'Użytkownik'),
                'property_title' => $r['property_title'] ?? 'oferta',
                'snippet' => mb_substr(strip_tags($r['body']), 0, 160),
                'sent_at' => $r['sent_at'],
                'property_image' => $imgUrl,
            ];
        }
    }
    $conversations = array_values($convs);
} catch (Exception $e) {
    $errors[] = "Błąd pobierania wiadomości: " . $e->getMessage();
    $conversations = [];
}

// Pobierz wątek jeśli wskazano konwersację
$activePartner = intval($_GET['partner_id'] ?? 0);
$activeProperty = intval($_GET['property_id'] ?? 0);
$thread = [];
$activePropertyRow = null;
$partnerRow = null;
$canAssign = false;

if ($activePartner && $activeProperty) {
    $stm = $pdo->prepare("SELECT id, title, owner_id, price FROM properties WHERE id = :id LIMIT 1");
    $stm->execute(['id' => $activeProperty]);
    $activePropertyRow = $stm->fetch(PDO::FETCH_ASSOC);

    $stm = $pdo->prepare("SELECT id, name, email FROM users WHERE id = :id LIMIT 1");
    $stm->execute(['id' => $activePartner]);
    $partnerRow = $stm->fetch(PDO::FETCH_ASSOC);

    try {
        $stmt = $pdo->prepare("
          SELECT m.*, u_from.name AS from_name, u_to.name AS to_name
          FROM messages m
          LEFT JOIN users u_from ON m.from_user_id = u_from.id
          LEFT JOIN users u_to ON m.to_user_id = u_to.id
          WHERE m.property_id = :pid
            AND ((m.from_user_id = :me AND m.to_user_id = :partner) OR (m.from_user_id = :partner AND m.to_user_id = :me))
          ORDER BY m.sent_at ASC
        ");
        $stmt->execute(['pid'=>$activeProperty, 'me'=>$user_id, 'partner'=>$activePartner]);
        $thread = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $errors[] = "Błąd ładowania wątku: " . $e->getMessage();
        $thread = [];
    }

    if ($activePropertyRow && ((int)$activePropertyRow['owner_id'] === $user_id || (function_exists('is_admin') && is_admin()))) {
        $canAssign = true;
    } else {
        $canAssign = false;
    }
}

function fmt_dt($dt) {
    if (!$dt) return '';
    return htmlspecialchars(substr($dt,0,16));
}
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Wiadomości — <?=htmlspecialchars(APP_NAME ?? 'Aplikacja')?></title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/messages.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Wiadomości</h2>

  <?php if ($errors): foreach ($errors as $e): ?>
    <div class="alert alert-danger"><?=htmlspecialchars($e)?></div>
  <?php endforeach; endif; ?>
  <?php if ($success): ?><div class="alert alert-info"><?=htmlspecialchars($success)?></div><?php endif; ?>

  <div class="messages-wrap">
    <!-- wymuszona szerokość lewego panelu inline aby nadpisać globalne reguły -->
    <aside class="conversations" aria-label="Lista konwersacji"
           style="width:240px;max-width:240px;flex:0 0 240px;">
      <h3>Ostatnie konwersacje</h3>
      <?php if (empty($conversations)): ?>
        <p class="muted">Brak wiadomości.</p>
      <?php else: foreach ($conversations as $c):
          $pid = (int)$c['property_id'];
          $partner = (int)$c['partner_id'];
          $isActive = ($pid === $activeProperty && $partner === $activePartner);
          $thumb = $c['property_image'] ?? null;
      ?>
        <div class="conv-item <?= $isActive ? 'conv-active' : '' ?>"
             onclick="location.href='messages.php?property_id=<?=urlencode($pid)?>&partner_id=<?=urlencode($partner)?>'">
          <div class="conv-thumb" style="width:76px;height:120px;flex:0 0 56px;">
            <?php if ($thumb): ?>
              <img src="<?=htmlspecialchars($thumb, ENT_QUOTES)?>" alt="miniatura"
                   style="width:156px;height:110px;object-fit:cover;display:block;">
            <?php else: ?>
              <div class="thumb-placeholder" style="width:56px;height:40px;"></div>
            <?php endif; ?>
          </div>
          <div class="conv-body">
            <strong class="conv-title"><?=htmlspecialchars($c['partner_name'])?></strong>
            <div class="conv-meta muted"><?=htmlspecialchars($c['property_title'])?> — <?=fmt_dt($c['sent_at'])?></div>
            <div class="snippet"><?=htmlspecialchars($c['snippet'])?></div>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </aside>

    <!-- wymuszone inline style dla sekcji thread aby chat był po prawej i miał odpowiednią szerokość -->
    <section class="thread" aria-live="polite"
             style="min-width:360px;max-width:calc(100% - 260px);order:2;margin-left:auto;">
      <?php if (!$activePartner || !$activeProperty): ?>
        <p class="muted">Wybierz konwersację z listy po lewej, aby otworzyć chat.</p>
      <?php else: ?>
        <div class="panel">
          <h3>Chat — <?=htmlspecialchars($partnerRow['name'] ?? 'Użytkownik')?> — <?=htmlspecialchars($activePropertyRow['title'] ?? 'oferta')?></h3>

          <?php if ($canAssign && $activePartner && $activeProperty): ?>
            <form method="post" onsubmit="return confirm('Przypisać to mieszkanie temu użytkownikowi?');" class="assign-form" style="margin-bottom:12px;">
              <input type="hidden" name="assign" value="1">
              <input type="hidden" name="property_id" value="<?=htmlspecialchars($activeProperty)?>">
              <input type="hidden" name="tenant_id" value="<?=htmlspecialchars($activePartner)?>">
              <button class="btn" type="submit">Przypisz mieszkanie</button>
            </form>
          <?php endif; ?>

          <div class="chat-box" id="chatBox" role="log" aria-atomic="false">
            <?php if (empty($thread)): ?>
              <p class="muted">Brak wiadomości w tej konwersacji.</p>
            <?php else: foreach ($thread as $m):
                  $isMe = ((int)$m['from_user_id'] === $user_id);
            ?>
              <div class="message <?= $isMe ? 'me' : 'other' ?>">
                <div class="meta"><strong><?=htmlspecialchars($m['from_name'] ?? ($isMe ? 'Ty' : 'Użytkownik'))?></strong> <span class="muted"><?=htmlspecialchars($m['sent_at'])?></span></div>
                <div class="body"><?=nl2br(htmlspecialchars($m['body']))?></div>
                <div style="clear:both"></div>
              </div>
            <?php endforeach; endif; ?>
          </div>

          <div class="controls">
            <form method="post" class="reply-form">
              <input type="hidden" name="reply" value="1">
              <input type="hidden" name="property_id" value="<?=htmlspecialchars($activeProperty)?>">
              <input type="hidden" name="partner_id" value="<?=htmlspecialchars($activePartner)?>">
              <label style="display:block">Odpowiedź
                <textarea name="body" rows="4" required style="width:100%"></textarea>
              </label>
              <div style="margin-top:8px;">
                <button class="btn" type="submit">Wyślij</button>
                <a class="btn-ghost" href="messages.php">Zamknij chat</a>
              </div>
            </form>
          </div>
        </div>

        <script>
          // Scrolluj chat do dołu po załadowaniu
          document.addEventListener('DOMContentLoaded', function(){
            var chat = document.getElementById('chatBox');
            if (chat) { chat.scrollTop = chat.scrollHeight; }
          });
        </script>
      <?php endif; ?>
    </section>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>