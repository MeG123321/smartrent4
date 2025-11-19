<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/admin_functions.php';
session_start();
require_role('admin');

$id = intval($_GET['id'] ?? 0);
if ($id) {
    // logujemy przed kasowaniem (przydatne do audytu)
    admin_log_activity($pdo, $_SESSION['user_id'] ?? null, 'Usunięto ofertę', "property_id:{$id}");
    $stmt = $pdo->prepare("DELETE FROM properties WHERE id = :id");
    $stmt->execute(['id'=>$id]);
}
header('Location: admin_panel.php');
exit;