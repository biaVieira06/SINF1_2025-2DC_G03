<?php
// ============================================================
// register.php — Registration form + POST handler
// ============================================================

$page_title = 'Registar';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Token de segurança inválido.';
    } else {
        $name           = trim($_POST['name']           ?? '');
        $email          = trim($_POST['email']          ?? '');
        $password       = $_POST['password']            ?? '';
        $password2      = $_POST['password2']           ?? '';
        $student_number = trim($_POST['student_number'] ?? '');

        if (!$name)     $errors[] = 'Nome obrigatório.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
        if (strlen($password) < 6) $errors[] = 'A palavra-passe deve ter pelo menos 6 caracteres.';
        if ($password !== $password2) $errors[] = 'As palavras-passe não coincidem.';

        if (empty($errors)) {
            // Check email uniqueness
            $chk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $errors[] = 'Este email já está registado.';
            } else {
                $role_id = $pdo->query("SELECT id FROM roles WHERE name='student'")->fetchColumn();
                $hash    = password_hash($password, PASSWORD_BCRYPT);
                $ins = $pdo->prepare(
                    "INSERT INTO users (name, email, password_hash, role_id, student_number)
                     VALUES (?,?,?,?,?)"
                );
                $ins->execute([$name, $email, $hash, $role_id, $student_number ?: null]);
                flash('success', 'Conta criada com sucesso! Podes agora fazer login.');
                header('Location: ' . BASE_URL . '/login.php');
                exit;
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="qf-form-card">
                <div class="text-center mb-4">
                    <i class="fas fa-user-plus fa-3x text-warning mb-2"></i>
                    <h2 class="text-gold">Criar Conta</h2>
                    <p class="text-muted small">Junta-te à comunidade Queima das Fitas</p>
                </div>

                <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form method="post" class="qf-validate" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="name"><i class="fas fa-user me-1"></i>Nome completo</label>
                        <input type="text" id="name" name="name" class="form-control"
                               value="<?= h($_POST['name'] ?? '') ?>" required
                               placeholder="O teu nome" autocomplete="name">
                        <div class="invalid-feedback">Nome obrigatório.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email"><i class="fas fa-envelope me-1"></i>Email</label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?= h($_POST['email'] ?? '') ?>" required
                               placeholder="exemplo@up.pt" autocomplete="email">
                        <div class="invalid-feedback">Insere um email válido.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="student_number">
                            <i class="fas fa-id-badge me-1"></i>Número de estudante <small class="text-muted">(opcional)</small>
                        </label>
                        <input type="text" id="student_number" name="student_number" class="form-control"
                               value="<?= h($_POST['student_number'] ?? '') ?>"
                               placeholder="up2021XXXXX">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password"><i class="fas fa-lock me-1"></i>Palavra-passe</label>
                        <input type="password" id="password" name="password" class="form-control"
                               required minlength="6" placeholder="Mínimo 6 caracteres" autocomplete="new-password">
                        <div class="invalid-feedback">Mínimo 6 caracteres.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="password2"><i class="fas fa-lock me-1"></i>Confirmar palavra-passe</label>
                        <input type="password" id="password2" name="password2" class="form-control"
                               required placeholder="Repetir palavra-passe" autocomplete="new-password">
                        <div class="invalid-feedback">Obrigatório.</div>
                    </div>
                    <button type="submit" class="btn btn-qf-primary w-100">
                        <i class="fas fa-user-plus me-2"></i>Criar Conta
                    </button>
                </form>

                <hr style="border-color:var(--qf-border)">
                <p class="text-center text-muted small mb-0">
                    Já tens conta? <a href="<?= BASE_URL ?>/login.php" class="text-gold">Entrar</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
