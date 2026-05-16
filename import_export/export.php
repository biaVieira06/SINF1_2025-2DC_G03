<?php
// ============================================================
// import_export/export.php — GET: export as CSV download
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$type = $_GET['type'] ?? 'events';
$allowed = ['events', 'artists', 'tents', 'ratings'];

if (!in_array($type, $allowed)) {
    flash('error', 'Tipo de exportação inválido.');
    header('Location: ' . BASE_URL . '/import_export/index.php');
    exit;
}

$filename = 'queima_fitas_' . $type . '_' . date('Ymd_His') . '.csv';

// Send CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');

$out = fopen('php://output', 'w');
// BOM for Excel UTF-8 compatibility
fputs($out, "\xEF\xBB\xBF");

switch ($type) {
    case 'events':
        fputcsv($out, ['id', 'name', 'description', 'date_time', 'location', 'type', 'tent_name', 'created_at', 'avg_rating', 'agenda_count']);
        $rows = $pdo->query(
            "SELECT e.id, e.name, e.description, e.date_time, e.location, e.type,
                    t.name AS tent_name, e.created_at,
                    ROUND(COALESCE(AVG(r.score),0),2) AS avg_rating,
                    COUNT(DISTINCT pa.id) AS agenda_count
             FROM events e
             LEFT JOIN tents t ON t.id = e.tent_id
             LEFT JOIN ratings r ON r.event_id = e.id
             LEFT JOIN personal_agenda pa ON pa.event_id = e.id
             GROUP BY e.id ORDER BY e.date_time"
        )->fetchAll();
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['id'], $row['name'], $row['description'], $row['date_time'],
                $row['location'], $row['type'], $row['tent_name'] ?? '',
                $row['created_at'], $row['avg_rating'], $row['agenda_count']
            ]);
        }
        break;

    case 'artists':
        fputcsv($out, ['id', 'name', 'genre', 'country', 'biography', 'event_count']);
        $rows = $pdo->query(
            "SELECT a.id, a.name, a.genre, a.country, a.biography,
                    COUNT(DISTINCT ea.event_id) AS event_count
             FROM artists a
             LEFT JOIN event_artists ea ON ea.artist_id = a.id
             GROUP BY a.id ORDER BY a.name"
        )->fetchAll();
        foreach ($rows as $row) {
            fputcsv($out, [$row['id'], $row['name'], $row['genre'], $row['country'], $row['biography'], $row['event_count']]);
        }
        break;

    case 'tents':
        fputcsv($out, ['id', 'name', 'faculty', 'location', 'opening_time', 'closing_time', 'avg_rating']);
        $rows = $pdo->query(
            "SELECT t.id, t.name, f.acronym AS faculty, t.location, t.opening_time, t.closing_time,
                    ROUND(COALESCE(AVG(r.score),0),2) AS avg_rating
             FROM tents t
             JOIN faculties f ON f.id = t.faculty_id
             LEFT JOIN ratings r ON r.tent_id = t.id
             GROUP BY t.id ORDER BY f.name, t.name"
        )->fetchAll();
        foreach ($rows as $row) {
            fputcsv($out, [$row['id'], $row['name'], $row['faculty'], $row['location'],
                           $row['opening_time'], $row['closing_time'], $row['avg_rating']]);
        }
        break;

    case 'ratings':
        fputcsv($out, ['id', 'user_name', 'event_or_tent', 'target_name', 'score', 'comment', 'created_at']);
        $rows = $pdo->query(
            "SELECT r.id, u.name AS user_name,
                    CASE WHEN r.event_id IS NOT NULL THEN 'event' ELSE 'tent' END AS target_type,
                    COALESCE(e.name, t.name) AS target_name,
                    r.score, r.comment, r.created_at
             FROM ratings r
             JOIN users u ON u.id = r.user_id
             LEFT JOIN events e ON e.id = r.event_id
             LEFT JOIN tents  t ON t.id = r.tent_id
             ORDER BY r.created_at DESC"
        )->fetchAll();
        foreach ($rows as $row) {
            fputcsv($out, [$row['id'], $row['user_name'], $row['target_type'],
                           $row['target_name'], $row['score'], $row['comment'], $row['created_at']]);
        }
        break;
}

fclose($out);
exit;
