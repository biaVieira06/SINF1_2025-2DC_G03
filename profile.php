<?php
// ============================================================
// profile.php — View/edit personal info
// ============================================================

$page_title = 'Perfil';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$user_id = current_user_id();
$errors  = [];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Token de segurança inválido.';
    } else {
        $name           = trim($_POST['name']           ?? '');
        $student_number = trim($_POST['student_number'] ?? '');
        $new_password   = $_POST['new_password']        ?? '';
        $confirm_pass   = $_POST['confirm_password']    ?? '';

        if (!$name) $errors[] = 'Nome obrigatório.';

        if ($new_password) {
            if (strlen($new_password) < 6) {
                $errors[] = 'Nova palavra-passe deve ter pelo menos 6 caracteres.';
            } elseif ($new_password !== $confirm_pass) {
                $errors[] = 'As palavras-passe não coincidem.';
            }
        }

        if (empty($errors)) {
            if ($new_password) {
                $hash = password_hash($new_password, PASSWORD_BCRYPT);
                $upd  = $pdo->prepare("UPDATE users SET name=?, student_number=?, password_hash=? WHERE id=?");
                $upd->execute([$name, $student_number ?: null, $hash, $user_id]);
            } else {
                $upd = $pdo->prepare("UPDATE users SET name=?, student_number=? WHERE id=?");
                $upd->execute([$name, $student_number ?: null, $user_id]);
            }
            $_SESSION['user_name'] = $name;
            flash('success', 'Perfil actualizado com sucesso.');
            header('Location: ' . BASE_URL . '/profile.php');
            exit;
        }
    }
}

// Fetch agenda count and rating count
$agenda_count = $pdo->prepare("SELECT COUNT(*) FROM personal_agenda WHERE user_id=?");
$agenda_count->execute([$user_id]);
$agenda_count = $agenda_count->fetchColumn();

$rating_count = $pdo->prepare("SELECT COUNT(*) FROM ratings WHERE user_id=?");
$rating_count->execute([$user_id]);
$rating_count = $rating_count->fetchColumn();

include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="page-header">
                <h1><i class="fas fa-id-card me-2"></i>O Meu Perfil</h1>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="stat-card">
                        <div class="stat-number"><?= (int)$agenda_count ?></div>
                        <div class="stat-label"><i class="fas fa-bookmark me-1"></i>Eventos na Agenda</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card">
                        <div class="stat-number"><?= (int)$rating_count ?></div>
                        <div class="stat-label"><i class="fas fa-star me-1"></i>Avaliações dadas</div>
                    </div>
                </div>
            </div>

            <div class="qf-form-card">
                <h5 class="text-gold mb-4"><i class="fas fa-pen me-2"></i>Editar Informação</h5>

                <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form method="post" class="qf-validate" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-user me-1"></i>Nome</label>
                        <input type="text" name="name" class="form-control"
                               value="<?= h($user['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-envelope me-1"></i>Email</label>
                        <input type="email" class="form-control" value="<?= h($user['email']) ?>" disabled>
                        <div class="form-text text-muted">O email não pode ser alterado.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-id-badge me-1"></i>Número de estudante</label>
                        <input type="text" name="student_number" class="form-control"
                               value="<?= h($user['student_number'] ?? '') ?>"
                               placeholder="upXXXXXXXX">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-user-tag me-1"></i>Papel</label>
                        <input type="text" class="form-control" value="<?= h($_SESSION['role']) ?>" disabled>
                    </div>
                    <hr style="border-color:var(--qf-border)">
                    <h6 class="text-muted mb-3">Alterar palavra-passe <small>(deixar em branco para manter a actual)</small></h6>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-lock me-1"></i>Nova palavra-passe</label>
                        <input type="password" name="new_password" class="form-control"
                               minlength="6" placeholder="Mínimo 6 caracteres">
                    </div>
                    <div class="mb-4">
                        <label class="form-label"><i class="fas fa-lock me-1"></i>Confirmar nova palavra-passe</label>
                        <input type="password" name="confirm_password" class="form-control"
                               placeholder="Repetir nova palavra-passe">
                    </div>
                    <button type="submit" class="btn btn-qf-primary w-100">
                        <i class="fas fa-floppy-disk me-2"></i>Guardar Alterações
                    </button>
                </form>
            </div>

            <div class="text-muted small text-center mt-3">
                Conta criada em <?= date('d/m/Y', strtotime($user['created_at'])) ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
