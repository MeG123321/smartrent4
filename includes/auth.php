<?php
// Funkcje pomocnicze do autoryzacji
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}
function require_role(string $role) {
    if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== $role) {
        header('HTTP/1.1 403 Forbidden');
        echo "Brak dostępu.";
        exit;
    }
}
// helper do pobrania emaila użytkownika
function get_user_email($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id'=>$user_id]);
    $r = $stmt->fetch();
    return $r['email'] ?? '';
}