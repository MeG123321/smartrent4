<?php
session_start();

// Bezpośrednie połączenie do bazy
$host = 'localhost';
$db = 'smartrent';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Błąd połączenia: " . $e->getMessage());
}

// Sprawdzenie czy user jest zalogowany
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Pobranie wynajęć użytkownika
$sql = "SELECT r.*, p.title, p.city, p.price, u.name as owner_name 
        FROM rentals r
        JOIN properties p ON r.property_id = p.id
        JOIN users u ON p.owner_id = u.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ustawienie headers dla CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="moje_wynajecia_' . date('Y-m-d') . '.csv"');

// Otwarcie outputu
$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF"); // UTF-8 BOM dla Excel

// Nagłówki CSV
fputcsv($output, array(
    'ID Wynajęcia',
    'Mieszkanie',
    'Miasto',
    'Właściciel',
    'Cena za noc',
    'Data rozpoczęcia',
    'Data zakończenia',
    'Liczba dni',
    'Suma',
    'Status',
    'Data rezerwacji'
), ';');

// Dane wynajęć
foreach ($rentals as $rental) {
    $start = new DateTime($rental['start_date']);
    $end = new DateTime($rental['end_date']);
    $days = $end->diff($start)->days + 1;
    $suma = $rental['price'] * $days;
    
    $status = $rental['status'] == 'active' ? 'Aktywne' : 'Zakończone';
    
    fputcsv($output, array(
        $rental['id'],
        $rental['title'],
        $rental['city'],
        $rental['owner_name'],
        $rental['price'] . ' PLN',
        date('Y-m-d', strtotime($rental['start_date'])),
        date('Y-m-d', strtotime($rental['end_date'])),
        $days,
        $suma . ' PLN',
        $status,
        date('Y-m-d H:i', strtotime($rental['created_at']))
    ), ';');
}

fclose($output);
exit;
?>