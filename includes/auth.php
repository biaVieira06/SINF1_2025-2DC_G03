<?php
// ============================================================
// includes/auth.php — Authentication helpers
// ============================================================

require_once __DIR__ . '/../config/db.php';

function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function is_admin(): bool {
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_student(): bool {
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

function require_login(): void {
    if (!is_logged_in()) {
        flash('error', 'Deves fazer login para aceder a esta página.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function require_admin(): void {
    require_login();
    if (!is_admin()) {
        flash('error', 'Acesso restrito a administradores.');
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function current_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function current_user_name(): string {
    return $_SESSION['user_name'] ?? 'Utilizador';
}
