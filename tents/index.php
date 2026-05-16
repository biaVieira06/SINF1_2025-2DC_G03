<?php
// ============================================================
// tents/index.php — List all tents with avg rating
// ============================================================

$page_title = 'Tendas';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$tents = $pdo->query(
    "SELECT t.*, f.name AS faculty_name, f.acronym, f.color,
            COALESCE(AVG(r.score), 0) AS avg_rating,
            COUNT(DISTINCT r.id)     AS rating_count
     FROM tents t
     JOIN faculties f ON f.id = t.faculty_id
     LEFT JOIN ratings r ON r.tent_id = t.id
     GROUP BY t.id
     ORDER BY f.name, t.name"
)->fetchAll();

function stars_html(float $score): string {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($score >= $i) $out .= '<i class="fas fa-star"></i>';
        elseif ($score >= $i - 0.5) $out .= '<i class="fas fa-star-half-stroke"></i>';
        else $out .= '<i class="far fa-star empty-star"></i>';
    }
    return $out;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1><i class="fas fa-tent me-2"></i>Tendas</h1>
        <?php if (is_admin()): ?>
        <a href="<?= BASE_URL ?>/tents/create.php" class="btn btn-qf-primary">
            <i class="fas fa-plus me-2"></i>Nova Tenda
        </a>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <?php foreach ($tents as $tent): ?>
        <div class="col-md-6 col-lg-4">
            <div class="qf-card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="faculty-dot" style="background:<?= h($tent['color']) ?>"></span>
                        <small class="text-muted"><?= h($tent['faculty_name']) ?></small>
                    </div>
                    <h5 class="card-title"><?= h($tent['name']) ?></h5>
                    <p class="card-text">
                        <i class="fas fa-map-marker-alt me-1 text-red"></i><?= h($tent['location']) ?><br>
                        <i class="fas fa-clock me-1 text-red"></i>
                        <?= date('H:i', strtotime($tent['opening_time'])) ?> — <?= date('H:i', strtotime($tent['closing_time'])) ?>
                    </p>

                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="stars-display small"><?= stars_html((float)$tent['avg_rating']) ?></span>
                        <small class="text-muted">
                            <?= $tent['rating_count'] > 0
                                ? number_format($tent['avg_rating'],1).' ('.$tent['rating_count'].')'
                                : 'Sem avaliações' ?>
                        </small>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="<?= BASE_URL ?>/tents/view.php?id=<?= $tent['id'] ?>" class="btn btn-qf-outline btn-sm flex-grow-1">
                            <i class="fas fa-eye me-1"></i>Ver
                        </a>
                        <?php if (is_admin()): ?>
                        <a href="<?= BASE_URL ?>/tents/edit.php?id=<?= $tent['id'] ?>" class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-pen"></i>
                        </a>
                        <form method="post" action="<?= BASE_URL ?>/tents/delete.php"
                              data-confirm="Eliminar «<?= h($tent['name']) ?>»?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $tent['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
