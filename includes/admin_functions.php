<?php
// Funkcje pomocnicze dla panelu administratora

require_once __DIR__ . '/db.php';

/**
 * Pobierz podstawowe statystyki systemu
 */
function admin_get_stats(PDO $pdo): array {
    $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalProperties = (int)$pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
    $totalRentals = (int)$pdo->query("SELECT COUNT(*) FROM rentals")->fetchColumn();
    $totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(price),0) FROM rentals")->fetchColumn();
    return [
        'users'=>$totalUsers,
        'properties'=>$totalProperties,
        'rentals'=>$totalRentals,
        'revenue'=>$totalRevenue,
    ];
}

/**
 * Najnowsze rekordy (użytkownicy, oferty, rezerwacje)
 */
function admin_get_recent(PDO $pdo, int $limit = 6): array {
    $users = $pdo->query("SELECT id,name,email,role,created_at FROM users ORDER BY created_at DESC LIMIT $limit")->fetchAll(PDO::FETCH_ASSOC);
    $props = $pdo->query("SELECT id,title,city,price,created_at FROM properties ORDER BY created_at DESC LIMIT $limit")->fetchAll(PDO::FETCH_ASSOC);
    $rents = $pdo->query("SELECT r.id,r.user_id,r.property_id,r.start_date,r.end_date,r.price,r.created_at, p.title
                          FROM rentals r LEFT JOIN properties p ON r.property_id = p.id
                          ORDER BY r.created_at DESC LIMIT $limit")->fetchAll(PDO::FETCH_ASSOC);
    return ['users'=>$users,'properties'=>$props,'rentals'=>$rents];
}

/**
 * Generuj raport przychodów i liczby rezerwacji w zadanym okresie
 * zwraca tablicę: total_revenue, total_rentals, by_property (top)
 */
function admin_generate_report(PDO $pdo, string $from=null, string $to=null): array {
    $where = "1=1";
    $params = [];
    if ($from) { $where .= " AND r.created_at >= :from"; $params['from']=$from . ' 00:00:00'; }
    if ($to)   { $where .= " AND r.created_at <= :to";   $params['to']=$to . ' 23:59:59'; }

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(r.price),0) AS total_revenue, COUNT(*) AS total_rentals
                           FROM rentals r WHERE $where");
    $stmt->execute($params);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT p.id, p.title, p.city, COUNT(r.id) AS bookings, COALESCE(SUM(r.price),0) AS revenue
                           FROM rentals r
                           JOIN properties p ON r.property_id = p.id
                           WHERE $where
                           GROUP BY p.id
                           ORDER BY bookings DESC, revenue DESC
                           LIMIT 10");
    $stmt->execute($params);
    $by_property = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return ['summary'=>$summary,'by_property'=>$by_property];
}

/**
 * Pobierz logi aktywności (activity_logs)
 */
function admin_get_logs(PDO $pdo, int $limit = 200): array {
    $stmt = $pdo->prepare("SELECT l.*, u.name AS actor_name, u.email AS actor_email
                           FROM activity_logs l
                           LEFT JOIN users u ON l.actor_id = u.id
                           ORDER BY l.created_at DESC
                           LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Zapisz log aktywności
 */
function admin_log_activity(PDO $pdo, ?int $actorId, string $action, ?string $meta = null) {
    $stmt = $pdo->prepare("INSERT INTO activity_logs (actor_id, action, meta, created_at) VALUES (:actor, :action, :meta, NOW())");
    $stmt->execute(['actor'=>$actorId,'action'=>$action,'meta'=>$meta]);
}

/**
 * Eksportuj raport do CSV (wyślij nagłówki i echo)
 */
function admin_export_report_csv(array $by_property, array $summary, string $filename = 'report.csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    $out = fopen('php://output','w');
    fputcsv($out, ['Raport', 'Wartość']);
    fputcsv($out, ['Liczba rezerwacji', $summary['total_rentals']]);
    fputcsv($out, ['Przychód', number_format((float)$summary['total_revenue'],2,',','')]);
    fputcsv($out, []);
    fputcsv($out, ['Top oferty (ID, Tytuł, Miasto, Rezerwacje, Przychód)']);
    fputcsv($out, ['ID','Tytuł','Miasto','Rezerwacje','Przychód']);
    foreach ($by_property as $p) {
        fputcsv($out, [$p['id'],$p['title'],$p['city'],$p['bookings'],$p['revenue']]);
    }
    fclose($out);
    exit;
}