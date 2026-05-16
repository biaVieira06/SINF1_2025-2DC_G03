<?php
// ============================================================
// artists/edit.php — Admin: edit artist
// ============================================================

$page_title = 'Editar Artista';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM artists WHERE id = ?");
$stmt->execute([$id]);
$artist = $stmt->fetch();

if (!$artist) {
    flash('error', 'Artista não encontrado.');
    header('Location: ' . BASE_URL . '/artists/index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Token inválido.';
    } else {
        $name       = trim($_POST['name']      ?? '');
        $genre      = trim($_POST['genre']     ?? '');
        $country    = trim($_POST['country']   ?? '');
        $biography  = trim($_POST['biography'] ?? '');
        $image_path = $artist['image_path'];

        if (!$name) $errors[] = 'Nome obrigatório.';

        if (!empty($_FILES['image']['name'])) {
            $file     = $_FILES['image'];
            $allowed  = ['image/jpeg','image/png','image/webp','image/gif'];
            if (!in_array($file['type'], $allowed)) {
                $errors[] = 'Formato inválido.';
            } elseif ($file['size'] > 2*1024*1024) {
                $errors[] = 'Imagem demasiado grande (máx. 2MB).';
            } elseif ($file['error'] === UPLOAD_ERR_OK) {
                $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'artist_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
                    // Delete old image
                    if ($image_path && file_exists(UPLOAD_DIR . $image_path)) {
                        unlink(UPLOAD_DIR . $image_path);
                    }
                    $image_path = $filename;
                }
            }
        }

        if (empty($errors)) {
            $upd = $pdo->prepare("UPDATE artists SET name=?,genre=?,country=?,biography=?,image_path=? WHERE id=?");
            $upd->execute([$name,$genre,$country,$biography,$image_path,$id]);
            flash('success', 'Artista actualizado!');
            header('Location: ' . BASE_URL . '/artists/view.php?id=' . $id);
            exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="page-header"><h1><i class="fas fa-pen me-2"></i>Editar Artista</h1></div>
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="qf-form-card">
                <?php if ($errors): ?>
                <div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?></div>
                <?php endif; ?>
                <form method="post" enctype="multipart/form-data" class="qf-validate" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="name" class="form-control" required value="<?= h($_POST['name'] ?? $artist['name']) ?>">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col">
                            <label class="form-label">Género *</label>
                            <input type="text" name="genre" class="form-control" required value="<?= h($_POST['genre'] ?? $artist['genre']) ?>">
                        </div>
                        <div class="col">
                            <label class="form-label">País *</label>
                            <input type="text" name="country" class="form-control" required value="<?= h($_POST['country'] ?? $artist['country']) ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Foto</label>
                        <?php if ($artist['image_path'] && file_exists(UPLOAD_DIR . $artist['image_path'])): ?>
                        <div class="mb-2">
                            <img src="<?= UPLOAD_URL . h($artist['image_path']) ?>" id="img-preview"
                                 style="width:100px;height:100px;object-fit:cover;border-radius:50%;border:2px solid var(--qf-red);">
                        </div>
                        <?php else: ?>
                        <img id="img-preview" src="" alt="" style="display:none;max-width:120px;border-radius:50%;">
                        <?php endif; ?>
                        <input type="file" name="image" class="form-control" accept="image/*" data-preview="img-preview">
                        <div class="form-text text-muted">Deixa em branco para manter a imagem actual.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Biografia</label>
                        <textarea name="biography" class="form-control" rows="5"><?= h($_POST['biography'] ?? $artist['biography']) ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-qf-primary"><i class="fas fa-floppy-disk me-2"></i>Guardar</button>
                        <a href="<?= BASE_URL ?>/artists/view.php?id=<?= $id ?>" class="btn btn-qf-outline">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
