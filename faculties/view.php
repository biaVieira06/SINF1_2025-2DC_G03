<?php
// ============================================================
// faculties/view.php — Faculty detail
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM faculties WHERE id = ?");
$stmt->execute([$id]);
$faculty = $stmt->fetch();

if (!$faculty) {
    flash('error', 'Faculdade não encontrada.');
    header('Location: ' . BASE_URL . '/faculties/index.php');
    exit;
}

$page_title = $faculty['name'];

$tents = $pdo->prepare(
    "SELECT t.*, COALESCE(AVG(r.score),0) AS avg_rating, COUNT(DISTINCT r.id) AS rating_count
     FROM tents t LEFT JOIN ratings r ON r.tent_id = t.id
     WHERE t.faculty_id = ? GROUP BY t.id ORDER BY t.name"
);
$tents->execute([$id]);
$tents = $tents->fetchAll();

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
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/faculties/index.php" class="text-gold">Faculdades</a></li>
            <li class="breadcrumb-item active text-muted"><?= h($faculty['acronym']) ?></li>
        </ol>
    </nav>

    <div class="qf-form-card mb-4" style="border-left:4px solid <?= h($faculty['color']) ?>">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <span class="badge fs-5 px-3 py-2" style="background:<?= h($faculty['color']) ?>;color:#fff;">
                <?= h($faculty['acronym']) ?>
            </span>
            <?php if (is_admin()): ?>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/faculties/edit.php?id=<?= $id ?>" class="btn btn-sm btn-outline-warning">
                    <i class="fas fa-pen me-1"></i>Editar
                </a>
                <form method="post" action="<?= BASE_URL ?>/faculties/delete.php"
                      data-confirm="Eliminar esta faculdade? As tendas associadas também serão eliminadas.">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash me-1"></i>Eliminar</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <h1 class="text-gold fs-2 mb-3"><?= h($faculty['name']) ?></h1>
        <?php if ($faculty['description']): ?>
        <p class="text-muted"><?= nl2br(h($faculty['description'])) ?></p>
        <?php endif; ?>
    </div>

    <h2 class="qf-section-title"><i class="fas fa-tent me-2"></i>Tendas</h2>

    <?php if (empty($tents)): ?>
    <p class="text-muted">Nenhuma tenda associada a esta faculdade.</p>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($tents as $tent): ?>
        <div class="col-md-6 col-lg-4">
            <div class="qf-card">
                <div class="card-body">
                    <h5 class="card-title"><?= h($tent['name']) ?></h5>
                    <p class="card-text">
                        <i class="fas fa-map-marker-alt me-1 text-red"></i><?= h($tent['location']) ?><br>
                        <i class="fas fa-clock me-1 text-red"></i>
                        <?= date('H:i',strtotime($tent['opening_time'])) ?> — <?= date('H:i',strtotime($tent['closing_time'])) ?>
                    </p>
                    <div class="stars-display small mb-3"><?= stars_html((float)$tent['avg_rating']) ?></div>
                    <a href="<?= BASE_URL ?>/tents/view.php?id=<?= $tent['id'] ?>" class="btn btn-qf-outline btn-sm w-100">
                        <i class="fas fa-eye me-1"></i>Ver Tenda
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
