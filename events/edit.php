<?php
// ============================================================
// events/edit.php — Admin: edit event
// ============================================================

$page_title = 'Editar Evento';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$id]);
$event = $stmt->fetch();

if (!$event) {
    flash('error', 'Evento não encontrado.');
    header('Location: ' . BASE_URL . '/events/index.php');
    exit;
}

$errors      = [];
$tents       = $pdo->query("SELECT t.id, t.name, f.acronym FROM tents t JOIN faculties f ON f.id=t.faculty_id ORDER BY t.name")->fetchAll();
$artists_all = $pdo->query("SELECT id, name, genre FROM artists ORDER BY name")->fetchAll();

// Current artists for this event
$cur_artists = $pdo->prepare("SELECT artist_id FROM event_artists WHERE event_id=?");
$cur_artists->execute([$id]);
$cur_artist_ids = $cur_artists->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Token de segurança inválido.';
    } else {
        $name        = trim($_POST['name']        ?? '');
        $description = trim($_POST['description'] ?? '');
        $date_time   = trim($_POST['date_time']   ?? '');
        $location    = trim($_POST['location']    ?? '');
        $type        = $_POST['type']             ?? '';
        $tent_id     = !empty($_POST['tent_id'])  ? (int)$_POST['tent_id'] : null;
        $sel_artists = $_POST['artists']          ?? [];
        $image_path  = $event['image_path'];

        if (!$name)      $errors[] = 'Nome obrigatório.';
        if (!$date_time) $errors[] = 'Data e hora obrigatórias.';
        if (!$location)  $errors[] = 'Local obrigatório.';
        if (!in_array($type, ['academic_ceremony','concert','cultural_activity'])) $errors[] = 'Tipo inválido.';

        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $file     = $_FILES['image'];
            $max_size = 2 * 1024 * 1024; // 2MB

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Erro ao fazer upload da imagem.';
            } elseif ($file['size'] > $max_size) {
                $errors[] = 'A imagem não pode exceder 2MB.';
            } elseif (!is_uploaded_file($file['tmp_name'])) {
                $errors[] = 'Ficheiro inválido.';
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                $map = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                    'image/gif'  => 'gif',
                ];

                if (!isset($map[$mime])) {
                    $errors[] = 'Formato de imagem inválido (permitido: JPG, PNG, WEBP, GIF).';
                } else {
                    $ext      = $map[$mime];
                    $filename = 'event_' . uniqid() . '.' . $ext;
                    $dest     = UPLOAD_DIR . $filename;
                    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        // delete old image safely
                        if ($image_path) {
                            $old = realpath(UPLOAD_DIR . $image_path);
                            $updir = realpath(UPLOAD_DIR);
                            if ($old && $updir && strpos($old, $updir) === 0 && file_exists($old)) unlink($old);
                        }
                        $image_path = $filename;
                    } else {
                        $errors[] = 'Não foi possível guardar a imagem.';
                    }
                }
            }
        }

        if (empty($errors)) {
            $upd = $pdo->prepare(
                "UPDATE events SET name=?, description=?, date_time=?, location=?, type=?, tent_id=?, image_path=? WHERE id=?"
            );
            $upd->execute([$name, $description, $date_time, $location, $type, $tent_id, $image_path, $id]);

            // Sync artists
            $pdo->prepare("DELETE FROM event_artists WHERE event_id=?")->execute([$id]);
            if ($sel_artists) {
                $ea = $pdo->prepare("INSERT IGNORE INTO event_artists (event_id, artist_id) VALUES (?,?)");
                foreach ($sel_artists as $aid) $ea->execute([$id, (int)$aid]);
            }

            flash('success', 'Evento actualizado com sucesso!');
            header('Location: ' . BASE_URL . '/events/view.php?id=' . $id);
            exit;
        }
        // Re-populate for redisplay
        $cur_artist_ids = array_map('intval', $sel_artists);
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="page-header">
        <h1><i class="fas fa-pen me-2"></i>Editar Evento</h1>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="qf-form-card">
                <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" class="qf-validate" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Nome do evento *</label>
                        <input type="text" name="name" class="form-control" required
                               value="<?= h($_POST['name'] ?? $event['name']) ?>">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipo *</label>
                            <select name="type" id="event-type-select" class="form-select" required>
                                <?php foreach (['academic_ceremony'=>'Cerimónia Académica','concert'=>'Concerto','cultural_activity'=>'Actividade Cultural'] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= ($_POST['type'] ?? $event['type']) === $v ? 'selected' : '' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data e hora *</label>
                            <input type="datetime-local" name="date_time" class="form-control" required
                                   value="<?= h(str_replace(' ','T', $_POST['date_time'] ?? $event['date_time'])) ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Local *</label>
                        <input type="text" name="location" class="form-control" required
                               value="<?= h($_POST['location'] ?? $event['location']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Poster/Imagem <small class="text-muted">(JPG, PNG, WEBP — máx. 2MB, opcional)</small></label>
                        <?php if ($event['image_path'] && file_exists(UPLOAD_DIR . $event['image_path'])): ?>
                        <div class="mb-2">
                            <img src="<?= UPLOAD_URL . h($event['image_path']) ?>" id="img-preview"
                                 style="width:150px;height:auto;object-fit:cover;border-radius:8px;border:2px solid var(--qf-red);">
                        </div>
                        <?php else: ?>
                        <img id="img-preview" src="" alt="" style="display:none;max-width:150px;border-radius:8px;">
                        <?php endif; ?>
                        <input type="file" name="image" class="form-control" accept="image/*" data-preview="img-preview">
                        <div class="form-text text-muted">Deixa em branco para manter a imagem actual.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tenda (opcional)</label>
                        <select name="tent_id" class="form-select">
                            <option value="">Nenhuma</option>
                            <?php foreach ($tents as $t): ?>
                            <option value="<?= $t['id'] ?>"
                                <?= (int)($_POST['tent_id'] ?? $event['tent_id']) === (int)$t['id'] ? 'selected' : '' ?>>
                                <?= h($t['name']) ?> (<?= h($t['acronym']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Artistas (CTRL+clique para vários)</label>
                        <select name="artists[]" class="form-select" multiple size="5">
                            <?php foreach ($artists_all as $a): ?>
                            <option value="<?= $a['id'] ?>" <?= in_array((int)$a['id'], array_map('intval', $cur_artist_ids)) ? 'selected' : '' ?>>
                                <?= h($a['name']) ?> — <?= h($a['genre']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Descrição</label>
                        <textarea name="description" class="form-control" rows="4"><?= h($_POST['description'] ?? $event['description']) ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-qf-primary">
                            <i class="fas fa-floppy-disk me-2"></i>Guardar Alterações
                        </button>
                        <a href="<?= BASE_URL ?>/events/view.php?id=<?= $id ?>" class="btn btn-qf-outline">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
