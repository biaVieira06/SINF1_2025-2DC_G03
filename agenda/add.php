<?php
// ============================================================
// agenda/add.php — POST: add event to agenda
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    flash('error', 'Pedido inválido.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$redirect = $_POST['redirect'] ?? BASE_URL . '/agenda/index.php';

if (!$event_id) {
    flash('error', 'Evento inválido.');
    header('Location: ' . $redirect);
    exit;
}

// Verify event exists
$chk = $pdo->prepare("SELECT id FROM events WHERE id=?");
$chk->execute([$event_id]);
if (!$chk->fetch()) {
    flash('error', 'Evento não encontrado.');
    header('Location: ' . $redirect);
    exit;
}

$ins = $pdo->prepare("INSERT IGNORE INTO personal_agenda (user_id, event_id) VALUES (?,?)");
$ins->execute([current_user_id(), $event_id]);

flash('success', 'Evento adicionado à tua agenda!');
header('Location: ' . $redirect);
exit;
