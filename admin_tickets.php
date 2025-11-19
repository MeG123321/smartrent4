<?php
// Proste zarządzanie zgłoszeniami support (panel admina)
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/admin_functions.php';
session_start();
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = intval($_POST['ticket_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($ticketId && in_array($action, ['assign','close','in_progress'])) {
        if ($action === 'assign') {
            $assigned_to = intval($_POST['assigned_to'] ?? 0) ?: null;
            $stmt = $pdo->prepare("UPDATE support_tickets SET assigned_to = :a, status = 'in_progress', updated_at = NOW() WHERE id = :id");
            $stmt->execute(['a'=>$assigned_to,'id'=>$ticketId]);
            admin_log_activity($pdo, $_SESSION['user_id'], 'Przypisano zgłoszenie', "ticket_id:$ticketId, assigned_to:$assigned_to");
        } elseif ($action === 'close') {
            $stmt = $pdo->prepare("UPDATE support_tickets SET status = 'closed', updated_at = NOW() WHERE id = :id");
            $stmt->execute(['id'=>$ticketId]);
            admin_log_activity($pdo, $_SESSION['user_id'], 'Zamknięto zgłoszenie', "ticket_id:$ticketId");
        } elseif ($action === 'in_progress') {
            $stmt = $pdo->prepare("UPDATE support_tickets SET status = 'in_progress', updated_at = NOW() WHERE id = :id");
            $stmt->execute(['id'=>$ticketId]);
            admin_log_activity($pdo, $_SESSION['user_id'], 'Oznaczono zgłoszenie jako w toku', "ticket_id:$ticketId");
        }
    }
    header('Location: admin_tickets.php');
    exit;
}

// lista zgłoszeń
$stmt = $pdo->query("SELECT t.*, u.name AS user_name, a.name AS assigned_name
                     FROM support_tickets t
                     LEFT JOIN users u ON t.user_id = u.id
                     LEFT JOIN users a ON t.assigned_to = a.id
                     ORDER BY t.created_at DESC LIMIT 500");
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// lista adminów do przypisania
$admins = $pdo->query("SELECT id,name,email FROM users WHERE role = 'admin' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Zarządzaj zgłoszeniami — Panel admina</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="container">
  <h2>Zgłoszenia support</h2>
  <div class="panel">
    <?php if (!$tickets): ?>
      <p>Brak zgłoszeń.</p>
    <?php else: ?>
      <table class="table">
        <thead><tr><th>ID</th><th>Użytkownik</th><th>Temat</th><th>Status</th><th>Przypisany</th><th>Data</th><th>Akcje</th></tr></thead>
        <tbody>
        <?php foreach ($tickets as $t): ?>
          <tr>
            <td><?=intval($t['id'])?></td>
            <td><?=htmlspecialchars($t['user_name'] ?? '—')?></td>
            <td><?=htmlspecialchars($t['subject'])?></td>
            <td><?=htmlspecialchars($t['status'])?></td>
            <td><?=htmlspecialchars($t['assigned_name'] ?? '—')?></td>
            <td><?=htmlspecialchars($t['created_at'])?></td>
            <td>
              <form method="post" style="display:inline">
                <input type="hidden" name="ticket_id" value="<?=intval($t['id'])?>">
                <select name="assigned_to">
                  <option value="">Wybierz admina</option>
                  <?php foreach ($admins as $a): ?>
                    <option value="<?=intval($a['id'])?>" <?=($t['assigned_to']==$a['id'])?'selected':''?>><?=htmlspecialchars($a['name'])?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn" name="action" value="assign" type="submit">Przypisz</button>
              </form>
              <form method="post" style="display:inline">
                <input type="hidden" name="ticket_id" value="<?=intval($t['id'])?>">
                <button class="btn" name="action" value="in_progress" type="submit">W toku</button>
              </form>
              <form method="post" style="display:inline" onsubmit="return confirm('Zamknąć zgłoszenie?')">
                <input type="hidden" name="ticket_id" value="<?=intval($t['id'])?>">
                <button class="btn btn-primary" name="action" value="close" type="submit">Zamknij</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>