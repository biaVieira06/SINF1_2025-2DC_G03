<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    flash('error', 'Pedido inválido.');
    header('Location: ' . BASE_URL . '/artists/index.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id) {
    // Delete image file if exists
    $row = $pdo->prepare("SELECT image_path FROM artists WHERE id=?");
    $row->execute([$id]);
    $img = $row->fetchColumn();
    if ($img && file_exists(UPLOAD_DIR . $img)) unlink(UPLOAD_DIR . $img);

    $pdo->prepare("DELETE FROM artists WHERE id=?")->execute([$id]);
    flash('success', 'Artista eliminado.');
}
header('Location: ' . BASE_URL . '/artists/index.php');
exit;
