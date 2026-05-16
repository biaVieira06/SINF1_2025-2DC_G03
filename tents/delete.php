<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    flash('error', 'Pedido inválido.');
    header('Location: ' . BASE_URL . '/tents/index.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id) {
    $pdo->prepare("DELETE FROM tents WHERE id = ?")->execute([$id]);
    flash('success', 'Tenda eliminada.');
}
header('Location: ' . BASE_URL . '/tents/index.php');
exit;
