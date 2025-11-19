<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/admin_functions.php';
session_start();

$actor = $_SESSION['user_id'] ?? null;
if ($actor) {
    admin_log_activity($pdo, $actor, 'Wylogowanie', 'Użytkownik wylogował się');
}

$_SESSION = [];
session_destroy();
header('Location: index.php');
exit;