<?php
// ============================================================
// artists/index.php — List all artists
// ============================================================

$page_title = 'Artistas';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$search = trim($_GET['q'] ?? '');
$params = [];
$where  = '1=1';

if ($search) {
    $where    = 'a.name LIKE ? OR a.genre LIKE ? OR a.country LIKE ?';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare(
    "SELECT a.*, COUNT(DISTINCT ea.event_id) AS event_count
     FROM artists a
     LEFT JOIN event_artists ea ON ea.artist_id = a.id
     WHERE $where
     GROUP BY a.id
     ORDER BY a.name"
);
$stmt->execute($params);
$artists = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1><i class="fas fa-music me-2"></i>Artistas</h1>
        <?php if (is_admin()): ?>
        <a href="<?= BASE_URL ?>/artists/create.php" class="btn btn-qf-primary">
            <i class="fas fa-plus me-2"></i>Novo Artista
        </a>
        <?php endif; ?>
    </div>

    <!-- Search -->
    <form method="get" class="qf-filter-bar">
        <div class="row g-3 align-items-end">
            <div class="col">
                <input type="text" name="q" class="form-control" placeholder="Pesquisar por nome, género, país..."
                       value="<?= h($search) ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-qf-primary"><i class="fas fa-search"></i></button>
            </div>
            <?php if ($search): ?>
            <div class="col-auto">
                <a href="<?= BASE_URL ?>/artists/index.php" class="btn btn-qf-outline">Limpar</a>
            </div>
            <?php endif; ?>
        </div>
    </form>

    <?php if (empty($artists)): ?>
    <div class="text-center py-5">
        <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
        <p class="text-muted">Nenhum artista encontrado.</p>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($artists as $artist): ?>
        <div class="col-sm-6 col-md-4 col-lg-3">
            <div class="qf-card text-center">
                <div class="card-body">
                    <div class="d-flex justify-content-center mb-3">
                        <?php if ($artist['image_path'] && file_exists(UPLOAD_DIR . $artist['image_path'])): ?>
                        <img src="<?= UPLOAD_URL . h($artist['image_path']) ?>" class="artist-avatar" alt="<?= h($artist['name']) ?>">
                        <?php else: ?>
                        <div class="artist-avatar-placeholder"><i class="fas fa-user-music"></i></div>
                        <?php endif; ?>
                    </div>
                    <h5 class="card-title"><?= h($artist['name']) ?></h5>
                    <p class="card-text">
                        <span class="badge bg-secondary"><?= h($artist['genre']) ?></span><br>
                        <small class="text-muted mt-1 d-block"><i class="fas fa-globe me-1"></i><?= h($artist['country']) ?></small>
                        <small class="text-muted"><i class="fas fa-calendar me-1"></i><?= $artist['event_count'] ?> evento(s)</small>
                    </p>
                    <div class="d-flex gap-2 justify-content-center mt-2">
                        <a href="<?= BASE_URL ?>/artists/view.php?id=<?= $artist['id'] ?>" class="btn btn-qf-outline btn-sm">
                            <i class="fas fa-eye me-1"></i>Ver
                        </a>
                        <?php if (is_admin()): ?>
                        <a href="<?= BASE_URL ?>/artists/edit.php?id=<?= $artist['id'] ?>" class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-pen"></i>
                        </a>
                        <form method="post" action="<?= BASE_URL ?>/artists/delete.php"
                              data-confirm="Eliminar «<?= h($artist['name']) ?>»?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $artist['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
