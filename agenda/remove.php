<?php
// ============================================================
// agenda/remove.php — POST: remove event from agenda
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    flash('error', 'Pedido inválido.');
    header('Location: ' . BASE_URL . '/agenda/index.php');
    exit;
}

$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$redirect = $_POST['redirect'] ?? BASE_URL . '/agenda/index.php';

if ($event_id) {
    $del = $pdo->prepare("DELETE FROM personal_agenda WHERE user_id=? AND event_id=?");
    $del->execute([current_user_id(), $event_id]);
    flash('info', 'Evento removido da tua agenda.');
}

header('Location: ' . $redirect);
exit;
