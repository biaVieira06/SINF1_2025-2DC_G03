<?php
// ============================================================
// events/delete.php — Admin: delete event
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    flash('error', 'Pedido inválido.');
    header('Location: ' . BASE_URL . '/events/index.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id) {
    // Remove image file if exists (safe unlink)
    $row = $pdo->prepare("SELECT image_path FROM events WHERE id = ?");
    $row->execute([$id]);
    $img = $row->fetchColumn();
    if ($img) {
        $real = realpath(UPLOAD_DIR . $img);
        $updir = realpath(UPLOAD_DIR);
        if ($real && $updir && strpos($real, $updir) === 0 && file_exists($real)) unlink($real);
    }

    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$id]);
    flash('success', 'Evento eliminado com sucesso.');
} else {
    flash('error', 'ID inválido.');
}

header('Location: ' . BASE_URL . '/events/index.php');
exit;
