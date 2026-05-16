<?php
// ============================================================
// faculties/index.php — List all faculties + their tents
// ============================================================

$page_title = 'Faculdades';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$faculties = $pdo->query(
    "SELECT f.*, COUNT(DISTINCT t.id) AS tent_count
     FROM faculties f
     LEFT JOIN tents t ON t.faculty_id = f.id
     GROUP BY f.id ORDER BY f.name"
)->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1><i class="fas fa-university me-2"></i>Faculdades</h1>
        <?php if (is_admin()): ?>
        <a href="<?= BASE_URL ?>/faculties/create.php" class="btn btn-qf-primary">
            <i class="fas fa-plus me-2"></i>Nova Faculdade
        </a>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <?php foreach ($faculties as $f): ?>
        <div class="col-md-6">
            <div class="qf-card" style="border-left: 4px solid <?= h($f['color']) ?>;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge mb-2" style="background:<?= h($f['color']) ?>;color:#fff;font-size:.85rem;">
                                <?= h($f['acronym']) ?>
                            </span>
                            <h5 class="card-title"><?= h($f['name']) ?></h5>
                        </div>
                        <?php if (is_admin()): ?>
                        <div class="d-flex gap-1">
                            <a href="<?= BASE_URL ?>/faculties/edit.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-outline-warning py-0 px-1">
                                <i class="fas fa-pen fa-xs"></i>
                            </a>
                            <form method="post" action="<?= BASE_URL ?>/faculties/delete.php"
                                  data-confirm="Eliminar «<?= h($f['name']) ?>»? As tendas associadas também serão eliminadas.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger py-0 px-1"><i class="fas fa-trash fa-xs"></i></button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($f['description']): ?>
                    <p class="card-text small"><?= h(mb_strimwidth($f['description'], 0, 120, '…')) ?></p>
                    <?php endif; ?>
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <small class="text-muted">
                            <i class="fas fa-tent me-1"></i><?= $f['tent_count'] ?> tenda(s)
                        </small>
                        <a href="<?= BASE_URL ?>/faculties/view.php?id=<?= $f['id'] ?>" class="btn btn-qf-outline btn-sm">
                            <i class="fas fa-eye me-1"></i>Ver
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
