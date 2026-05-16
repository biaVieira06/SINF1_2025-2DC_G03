<?php
$page_title = 'Nova Faculdade';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Token inválido.';
    } else {
        $name        = trim($_POST['name']        ?? '');
        $acronym     = trim($_POST['acronym']     ?? '');
        $description = trim($_POST['description'] ?? '');
        $color       = $_POST['color']            ?? '#e63946';

        if (!$name)    $errors[] = 'Nome obrigatório.';
        if (!$acronym) $errors[] = 'Sigla obrigatória.';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#e63946';

        if (empty($errors)) {
            $ins = $pdo->prepare("INSERT INTO faculties (name, acronym, description, color) VALUES (?,?,?,?)");
            $ins->execute([$name, strtoupper($acronym), $description, $color]);
            flash('success', 'Faculdade criada!');
            header('Location: ' . BASE_URL . '/faculties/view.php?id=' . $pdo->lastInsertId());
            exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="page-header"><h1><i class="fas fa-university me-2"></i>Nova Faculdade</h1></div>
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="qf-form-card">
                <?php if ($errors): ?>
                <div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?></div>
                <?php endif; ?>
                <form method="post" class="qf-validate" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="name" class="form-control" required value="<?= h($_POST['name'] ?? '') ?>">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col">
                            <label class="form-label">Sigla *</label>
                            <input type="text" name="acronym" class="form-control" required
                                   value="<?= h($_POST['acronym'] ?? '') ?>" placeholder="ex: FEUP" maxlength="10">
                        </div>
                        <div class="col">
                            <label class="form-label">Cor</label>
                            <input type="color" name="color" class="form-control form-control-color"
                                   value="<?= h($_POST['color'] ?? '#e63946') ?>">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Descrição</label>
                        <textarea name="description" class="form-control" rows="3"><?= h($_POST['description'] ?? '') ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-qf-primary"><i class="fas fa-floppy-disk me-2"></i>Criar</button>
                        <a href="<?= BASE_URL ?>/faculties/index.php" class="btn btn-qf-outline">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
