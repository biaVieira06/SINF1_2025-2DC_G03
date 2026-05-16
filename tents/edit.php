<?php
// ============================================================
// tents/edit.php — Admin: edit tent
// ============================================================

$page_title = 'Editar Tenda';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM tents WHERE id = ?");
$stmt->execute([$id]);
$tent = $stmt->fetch();

if (!$tent) {
    flash('error', 'Tenda não encontrada.');
    header('Location: ' . BASE_URL . '/tents/index.php');
    exit;
}

$errors    = [];
$faculties = $pdo->query("SELECT id, name, acronym FROM faculties ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Token inválido.';
    } else {
        $name         = trim($_POST['name']         ?? '');
        $faculty_id   = (int)($_POST['faculty_id']  ?? 0);
        $location     = trim($_POST['location']     ?? '');
        $opening_time = $_POST['opening_time']      ?? '';
        $closing_time = $_POST['closing_time']      ?? '';
        $description  = trim($_POST['description']  ?? '');

        if (!$name) $errors[] = 'Nome obrigatório.';
        if (!$faculty_id) $errors[] = 'Faculdade obrigatória.';

        if (empty($errors)) {
            $upd = $pdo->prepare(
                "UPDATE tents SET name=?, faculty_id=?, location=?, opening_time=?, closing_time=?, description=? WHERE id=?"
            );
            $upd->execute([$name, $faculty_id, $location, $opening_time, $closing_time, $description, $id]);
            flash('success', 'Tenda actualizada!');
            header('Location: ' . BASE_URL . '/tents/view.php?id=' . $id);
            exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="page-header"><h1><i class="fas fa-pen me-2"></i>Editar Tenda</h1></div>
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="qf-form-card">
                <?php if ($errors): ?>
                <div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?></div>
                <?php endif; ?>
                <form method="post" class="qf-validate" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="name" class="form-control" required value="<?= h($_POST['name'] ?? $tent['name']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Faculdade *</label>
                        <select name="faculty_id" class="form-select" required>
                            <?php foreach ($faculties as $f): ?>
                            <option value="<?= $f['id'] ?>" <?= (int)($_POST['faculty_id'] ?? $tent['faculty_id'])===(int)$f['id']?'selected':'' ?>>
                                <?= h($f['name']) ?> (<?= h($f['acronym']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Local *</label>
                        <input type="text" name="location" class="form-control" required value="<?= h($_POST['location'] ?? $tent['location']) ?>">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col">
                            <label class="form-label">Abertura *</label>
                            <input type="time" name="opening_time" class="form-control" required
                                   value="<?= h($_POST['opening_time'] ?? substr($tent['opening_time'],0,5)) ?>">
                        </div>
                        <div class="col">
                            <label class="form-label">Encerramento *</label>
                            <input type="time" name="closing_time" class="form-control" required
                                   value="<?= h($_POST['closing_time'] ?? substr($tent['closing_time'],0,5)) ?>">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Descrição</label>
                        <textarea name="description" class="form-control" rows="3"><?= h($_POST['description'] ?? $tent['description']) ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-qf-primary"><i class="fas fa-floppy-disk me-2"></i>Guardar</button>
                        <a href="<?= BASE_URL ?>/tents/view.php?id=<?= $id ?>" class="btn btn-qf-outline">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
