<?php
// ============================================================
// logout.php — Destroy session + redirect
// ============================================================

require_once __DIR__ . '/config/db.php';
session_destroy();
session_start();
flash('info', 'Sessão terminada com sucesso.');
header('Location: ' . BASE_URL . '/login.php');
exit;
