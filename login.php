<?php
// ============================================================
// login.php — Login form + POST handler
// ============================================================

$page_title = 'Entrar';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Token de segurança inválido. Tenta novamente.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $errors[] = 'Por favor preenche todos os campos.';
        } else {
            $stmt = $pdo->prepare(
                "SELECT u.*, r.name AS role_name FROM users u
                 JOIN roles r ON r.id = u.role_id
                 WHERE u.email = ? LIMIT 1"
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role']      = $user['role_name'];

                flash('success', 'Bem-vindo/a, ' . $user['name'] . '!');
                $redirect = $_GET['redirect'] ?? BASE_URL . '/index.php';
                header('Location: ' . $redirect);
                exit;
            } else {
                $errors[] = 'Email ou palavra-passe incorrectos.';
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="qf-form-card">
                <div class="text-center mb-4">
                    <i class="fas fa-fire-flame-curved fa-3x text-warning mb-2"></i>
                    <h2 class="text-gold">Entrar</h2>
                    <p class="text-muted small">Acede à tua conta Queima das Fitas</p>
                </div>

                <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form method="post" class="qf-validate" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="email"><i class="fas fa-envelope me-1"></i>Email</label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?= h($_POST['email'] ?? '') ?>" required
                               placeholder="exemplo@queima.pt" autocomplete="email">
                        <div class="invalid-feedback">Insere um email válido.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="password"><i class="fas fa-lock me-1"></i>Palavra-passe</label>
                        <input type="password" id="password" name="password" class="form-control"
                               required placeholder="••••••••" autocomplete="current-password">
                        <div class="invalid-feedback">Insere a tua palavra-passe.</div>
                    </div>
                    <button type="submit" class="btn btn-qf-primary w-100">
                        <i class="fas fa-right-to-bracket me-2"></i>Entrar
                    </button>
                </form>

                <hr style="border-color:var(--qf-border)">
                <p class="text-center text-muted small mb-0">
                    Ainda não tens conta?
                    <a href="<?= BASE_URL ?>/register.php" class="text-gold">Registar</a>
                </p>
            </div>

            <div class="mt-3 text-center">
                <small class="text-muted">
                    Conta demo: <strong>admin@queima.pt</strong> / <strong>queima2026</strong><br>
                    Estudante: <strong>ana@queima.pt</strong> / <strong>queima2026</strong><br>
                    <em>Se o login falhar, usa <a href="<?= BASE_URL ?>/register.php" class="text-gold">Registar</a> para criar uma nova conta.</em>
                </small>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
