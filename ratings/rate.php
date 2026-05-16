<?php
// ============================================================
// ratings/rate.php — POST: submit or update rating
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if (!csrf_verify()) {
    flash('error', 'Token de segurança inválido.');
    header('Location: ' . ($_POST['redirect'] ?? BASE_URL . '/index.php'));
    exit;
}

require_login();

if (!is_student()) {
    flash('error', 'Apenas estudantes podem submeter avaliações.');
    header('Location: ' . ($_POST['redirect'] ?? BASE_URL . '/index.php'));
    exit;
}

$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : null;
$tent_id  = isset($_POST['tent_id'])  ? (int)$_POST['tent_id']  : null;
$score    = isset($_POST['score'])    ? (int)$_POST['score']    : 0;
$comment  = trim($_POST['comment']   ?? '');
$redirect = $_POST['redirect']        ?? BASE_URL . '/index.php';
$user_id  = current_user_id();

if ($score < 1 || $score > 5) {
    flash('error', 'Avaliação inválida. Escolhe entre 1 e 5 estrelas.');
    header('Location: ' . $redirect);
    exit;
}

if (!$event_id && !$tent_id) {
    flash('error', 'Nenhum evento ou tenda especificado.');
    header('Location: ' . $redirect);
    exit;
}

// Check for existing rating
if ($event_id) {
    $chk = $pdo->prepare("SELECT id FROM ratings WHERE user_id=? AND event_id=?");
    $chk->execute([$user_id, $event_id]);
} else {
    $chk = $pdo->prepare("SELECT id FROM ratings WHERE user_id=? AND tent_id=?");
    $chk->execute([$user_id, $tent_id]);
}
$existing = $chk->fetchColumn();

if ($existing) {
    $upd = $pdo->prepare("UPDATE ratings SET score=?, comment=? WHERE id=?");
    $upd->execute([$score, $comment ?: null, $existing]);
    flash('success', 'Avaliação actualizada!');
} else {
    $ins = $pdo->prepare(
        "INSERT INTO ratings (user_id, event_id, tent_id, score, comment) VALUES (?,?,?,?,?)"
    );
    $ins->execute([$user_id, $event_id ?: null, $tent_id ?: null, $score, $comment ?: null]);
    flash('success', 'Avaliação submetida! Obrigado pelo teu feedback.');
}

header('Location: ' . $redirect);
exit;
