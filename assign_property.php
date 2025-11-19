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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['assign'])) {
    header('Location: messages_list.php');
    exit;
}

$property_id = intval($_POST['property_id'] ?? 0);
$tenant_id = intval($_POST['tenant_id'] ?? 0);

if (!$property_id || !$tenant_id) {
    die("Brak danych.");
}

// Sprawdź czy current user jest właścicielem tej oferty OR is_admin
$stmt = $pdo->prepare("SELECT owner_id FROM properties WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $property_id]);
$prop = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$prop) { die("Brak oferty."); }

if ($prop['owner_id'] != $me && !function_exists('is_admin') || (function_exists('is_admin') && !is_admin())) {
    // Jeśli brak funkcji is_admin, tylko właściciel ma prawo
    if ($prop['owner_id'] != $me) {
        die("Brak uprawnień do przypisania nieruchomości.");
    }
}

// Utwórz przypisanie (jeśli nie istnieje aktywne)
try {
    // sprawdź czy istnieje już aktywne przypisanie dla tej pary
    $stmt = $pdo->prepare("SELECT id FROM assignments WHERE property_id = :pid AND tenant_id = :tid AND status = 'confirmed' LIMIT 1");
    $stmt->execute(['pid' => $property_id, 'tid' => $tenant_id]);
    $exists = $stmt->fetchColumn();
    if ($exists) {
        header("Location: message_detail.php?property_id={$property_id}&partner_id={$tenant_id}");
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO assignments (property_id, tenant_id, assigned_by, status, created_at) VALUES (:pid, :tid, :by, 'confirmed', NOW())");
    $stmt->execute(['pid' => $property_id, 'tid' => $tenant_id, 'by' => $me]);
    $assignmentId = $pdo->lastInsertId();

    // opcjonalnie: wygeneruj pierwszy wpis płatności (np. miesięczna opłata)
    $stmt = $pdo->prepare("INSERT INTO payments (assignment_id, due_date, amount, status, created_at) VALUES (:aid, DATE_ADD(CURDATE(), INTERVAL 30 DAY), :amt, 'due', NOW())");
    $stmt->execute(['aid' => $assignmentId, 'amt' => (float)($prop['price'] ?? 0.00)]);

    admin_log_activity($pdo, $me, 'Przypisano mieszkanie', "assignment_id:{$assignmentId}, property_id:{$property_id}, tenant_id:{$tenant_id}");
    header("Location: management_assignment.php?id={$assignmentId}");
    exit;
} catch (PDOException $e) {
    die("Błąd bazy: " . htmlspecialchars($e->getMessage()));
}