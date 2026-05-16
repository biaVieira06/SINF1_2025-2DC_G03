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
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$id]);
    flash('success', 'Evento eliminado com sucesso.');
} else {
    flash('error', 'ID inválido.');
}

header('Location: ' . BASE_URL . '/events/index.php');
exit;
