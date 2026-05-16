<?php
// ============================================================
// artists/create.php — Admin: create artist with image upload
// ============================================================

$page_title = 'Novo Artista';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Token de segurança inválido.';
    } else {
        $name      = trim($_POST['name']      ?? '');
        $genre     = trim($_POST['genre']     ?? '');
        $country   = trim($_POST['country']   ?? '');
        $biography = trim($_POST['biography'] ?? '');
        $image_path = null;

        if (!$name)    $errors[] = 'Nome obrigatório.';
        if (!$genre)   $errors[] = 'Género musical obrigatório.';
        if (!$country) $errors[] = 'País obrigatório.';

        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $file      = $_FILES['image'];
            $allowed   = ['image/jpeg','image/png','image/webp','image/gif'];
            $max_size  = 2 * 1024 * 1024; // 2MB

            if (!in_array($file['type'], $allowed)) {
                $errors[] = 'Formato de imagem inválido (permitido: JPG, PNG, WEBP, GIF).';
            } elseif ($file['size'] > $max_size) {
                $errors[] = 'A imagem não pode exceder 2MB.';
            } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Erro ao fazer upload da imagem.';
            } else {
                $ext        = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename   = 'artist_' . uniqid() . '.' . $ext;
                $dest       = UPLOAD_DIR . $filename;
                if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $image_path = $filename;
                } else {
                    $errors[] = 'Não foi possível guardar a imagem.';
                }
            }
        }

        if (empty($errors)) {
            $ins = $pdo->prepare(
                "INSERT INTO artists (name, genre, country, biography, image_path) VALUES (?,?,?,?,?)"
            );
            $ins->execute([$name, $genre, $country, $biography, $image_path]);
            flash('success', 'Artista criado com sucesso!');
            header('Location: ' . BASE_URL . '/artists/view.php?id=' . $pdo->lastInsertId());
            exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="page-header"><h1><i class="fas fa-user-plus me-2"></i>Novo Artista</h1></div>
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
                        <input type="text" name="name" class="form-control" required value="<?= h($_POST['name'] ?? '') ?>">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col">
                            <label class="form-label">Género musical *</label>
                            <input type="text" name="genre" class="form-control" required
                                   value="<?= h($_POST['genre'] ?? '') ?>" placeholder="ex: Pop, Rock, Jazz">
                        </div>
                        <div class="col">
                            <label class="form-label">País *</label>
                            <input type="text" name="country" class="form-control" required
                                   value="<?= h($_POST['country'] ?? '') ?>" placeholder="ex: Portugal">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Foto <small class="text-muted">(JPG, PNG, WEBP — máx. 2MB)</small></label>
                        <div class="text-center mb-2">
                            <img id="img-preview" src="" alt="" style="display:none;max-width:120px;border-radius:50%;border:2px solid var(--qf-red);">
                        </div>
                        <input type="file" name="image" class="form-control" accept="image/*" data-preview="img-preview">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Biografia</label>
                        <textarea name="biography" class="form-control" rows="5"
                                  placeholder="Breve biografia do artista"><?= h($_POST['biography'] ?? '') ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-qf-primary"><i class="fas fa-floppy-disk me-2"></i>Criar Artista</button>
                        <a href="<?= BASE_URL ?>/artists/index.php" class="btn btn-qf-outline">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
