<?php
// ============================================================
// setup_passwords.php — One-time script to fix demo passwords
// Run this ONCE after importing the SQL, then DELETE it.
// Access: http://localhost/SINF1_2025-2DC_G03/setup_passwords.php
// ============================================================

// Simple security: only allow from localhost
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    http_response_code(403);
    die('Forbidden.');
}

require_once __DIR__ . '/config/db.php';

$password = 'queima2026';
$hash     = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

$emails = ['admin@queima.pt', 'ana@queima.pt', 'bruno@queima.pt', 'catarina@queima.pt'];

$updated = 0;
foreach ($emails as $email) {
    $stmt = $pdo->prepare("UPDATE users SET password_hash=? WHERE email=?");
    $stmt->execute([$hash, $email]);
    $updated += $stmt->rowCount();
}

echo '<pre style="font-family:monospace;background:#1a1a2e;color:#2ecc71;padding:2rem;">';
echo "Password hash generated: $hash\n\n";
echo "Updated $updated user(s).\n\n";
echo "All demo accounts now use password: $password\n\n";
echo "Accounts:\n";
foreach ($emails as $email) {
    echo "  - $email / $password\n";
}
echo "\n=== DELETE THIS FILE NOW: setup_passwords.php ===\n";
echo '</pre>';
