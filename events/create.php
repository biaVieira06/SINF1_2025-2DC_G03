<?php
// ============================================================
// events/create.php — Admin: create event
// ============================================================

$page_title = 'Novo Evento';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$errors = [];
$tents  = $pdo->query("SELECT t.id, t.name, f.acronym FROM tents t JOIN faculties f ON f.id=t.faculty_id ORDER BY t.name")->fetchAll();
$artists_all = $pdo->query("SELECT id, name, genre FROM artists ORDER BY name")->fetchAll();

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

        if (!$name)      $errors[] = 'Nome obrigatório.';
        if (!$date_time) $errors[] = 'Data e hora obrigatórias.';
        if (!$location)  $errors[] = 'Local obrigatório.';
        if (!in_array($type, ['academic_ceremony','concert','cultural_activity'])) $errors[] = 'Tipo inválido.';

        if (empty($errors)) {
            $ins = $pdo->prepare(
                "INSERT INTO events (name, description, date_time, location, type, tent_id)
                 VALUES (?,?,?,?,?,?)"
            );
            $ins->execute([$name, $description, $date_time, $location, $type, $tent_id]);
            $new_id = $pdo->lastInsertId();

            if ($sel_artists) {
                $ea = $pdo->prepare("INSERT IGNORE INTO event_artists (event_id, artist_id) VALUES (?,?)");
                foreach ($sel_artists as $aid) {
                    $ea->execute([$new_id, (int)$aid]);
                }
            }

            flash('success', 'Evento criado com sucesso!');
            header('Location: ' . BASE_URL . '/events/view.php?id=' . $new_id);
            exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="page-header">
        <h1><i class="fas fa-calendar-plus me-2"></i>Novo Evento</h1>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="qf-form-card">
                <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form method="post" class="qf-validate" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Nome do evento *</label>
                        <input type="text" name="name" class="form-control" required
                               value="<?= h($_POST['name'] ?? '') ?>" placeholder="Nome do evento">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipo *</label>
                            <select name="type" id="event-type-select" class="form-select" required>
                                <option value="">Seleccionar tipo</option>
                                <option value="academic_ceremony"  <?= ($_POST['type']??'')==='academic_ceremony'?'selected':'' ?>>Cerimónia Académica</option>
                                <option value="concert"            <?= ($_POST['type']??'')==='concert'?'selected':'' ?>>Concerto</option>
                                <option value="cultural_activity"  <?= ($_POST['type']??'')==='cultural_activity'?'selected':'' ?>>Actividade Cultural</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data e hora *</label>
                            <input type="datetime-local" name="date_time" class="form-control" required
                                   value="<?= h(str_replace(' ','T',$_POST['date_time'] ?? '')) ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Local *</label>
                        <input type="text" name="location" class="form-control" required
                               value="<?= h($_POST['location'] ?? '') ?>" placeholder="Local do evento">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tenda (opcional)</label>
                        <select name="tent_id" class="form-select">
                            <option value="">Nenhuma</option>
                            <?php foreach ($tents as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= ($_POST['tent_id']??'')==$t['id']?'selected':'' ?>>
                                <?= h($t['name']) ?> (<?= h($t['acronym']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Artistas (opcional — CTRL+clique para seleccionar vários)</label>
                        <select name="artists[]" class="form-select" multiple size="5">
                            <?php foreach ($artists_all as $a): ?>
                            <option value="<?= $a['id'] ?>"
                                <?= in_array($a['id'], (array)($_POST['artists'] ?? [])) ? 'selected' : '' ?>>
                                <?= h($a['name']) ?> — <?= h($a['genre']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Descrição</label>
                        <textarea name="description" class="form-control" rows="4"
                                  placeholder="Descrição do evento"><?= h($_POST['description'] ?? '') ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-qf-primary">
                            <i class="fas fa-floppy-disk me-2"></i>Criar Evento
                        </button>
                        <a href="<?= BASE_URL ?>/events/index.php" class="btn btn-qf-outline">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
