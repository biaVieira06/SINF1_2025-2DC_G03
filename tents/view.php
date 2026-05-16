<?php
// ============================================================
// tents/view.php — Tent detail + ratings
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare(
    "SELECT t.*, f.name AS faculty_name, f.acronym, f.color, f.id AS faculty_id
     FROM tents t JOIN faculties f ON f.id = t.faculty_id WHERE t.id = ?"
);
$stmt->execute([$id]);
$tent = $stmt->fetch();

if (!$tent) {
    flash('error', 'Tenda não encontrada.');
    header('Location: ' . BASE_URL . '/tents/index.php');
    exit;
}

$page_title = $tent['name'];

// Events at this tent
$events = $pdo->prepare(
    "SELECT e.*, COALESCE(AVG(r.score),0) AS avg_rating
     FROM events e LEFT JOIN ratings r ON r.event_id = e.id
     WHERE e.tent_id = ? GROUP BY e.id ORDER BY e.date_time"
);
$events->execute([$id]);
$events = $events->fetchAll();

// Ratings
$ratings_stmt = $pdo->prepare(
    "SELECT r.*, u.name AS user_name FROM ratings r
     JOIN users u ON u.id = r.user_id WHERE r.tent_id = ? ORDER BY r.created_at DESC"
);
$ratings_stmt->execute([$id]);
$ratings = $ratings_stmt->fetchAll();

$avg_rating  = $ratings ? array_sum(array_column($ratings, 'score')) / count($ratings) : 0;
$user_rating = null;

if (is_student()) {
    $ur = $pdo->prepare("SELECT * FROM ratings WHERE user_id=? AND tent_id=?");
    $ur->execute([current_user_id(), $id]);
    $user_rating = $ur->fetch();
}

function stars_html(float $score): string {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($score >= $i) $out .= '<i class="fas fa-star"></i>';
        elseif ($score >= $i - 0.5) $out .= '<i class="fas fa-star-half-stroke"></i>';
        else $out .= '<i class="far fa-star empty-star"></i>';
    }
    return $out;
}

$type_labels = [
    'academic_ceremony' => ['Cerimónia Académica', 'badge-academic'],
    'concert'           => ['Concerto',            'badge-concert'],
    'cultural_activity' => ['Actividade Cultural', 'badge-cultural'],
];

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/tents/index.php" class="text-gold">Tendas</a></li>
            <li class="breadcrumb-item active text-muted"><?= h($tent['name']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="qf-form-card mb-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <span class="d-flex align-items-center gap-2">
                        <span class="faculty-dot" style="background:<?= h($tent['color']) ?>; width:18px; height:18px;"></span>
                        <a href="<?= BASE_URL ?>/faculties/view.php?id=<?= $tent['faculty_id'] ?>" class="text-gold">
                            <?= h($tent['faculty_name']) ?>
                        </a>
                    </span>
                    <?php if (is_admin()): ?>
                    <div class="d-flex gap-2">
                        <a href="<?= BASE_URL ?>/tents/edit.php?id=<?= $tent['id'] ?>" class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-pen me-1"></i>Editar
                        </a>
                        <form method="post" action="<?= BASE_URL ?>/tents/delete.php" data-confirm="Eliminar esta tenda?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $tent['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash me-1"></i>Eliminar</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>

                <h1 class="text-gold fs-2 mb-3"><i class="fas fa-tent me-2"></i><?= h($tent['name']) ?></h1>

                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <i class="fas fa-map-marker-alt text-red me-2"></i>
                        <strong><?= h($tent['location']) ?></strong>
                    </div>
                    <div class="col-sm-6">
                        <i class="fas fa-clock text-red me-2"></i>
                        <?= date('H:i', strtotime($tent['opening_time'])) ?>h — <?= date('H:i', strtotime($tent['closing_time'])) ?>h
                    </div>
                </div>

                <div class="stars-display mb-2"><?= stars_html($avg_rating) ?></div>
                <?php if ($avg_rating > 0): ?>
                <p class="text-muted small"><?= number_format($avg_rating,1) ?>/5 baseado em <?= count($ratings) ?> avaliação(ões)</p>
                <?php endif; ?>

                <?php if ($tent['description']): ?>
                <p class="text-muted mt-3"><?= nl2br(h($tent['description'])) ?></p>
                <?php endif; ?>
            </div>

            <!-- Events at this tent -->
            <?php if (!empty($events)): ?>
            <div class="qf-form-card mb-4">
                <h4 class="text-gold mb-3"><i class="fas fa-calendar-days me-2"></i>Eventos nesta Tenda</h4>
                <div class="table-responsive">
                    <table class="table table-qf table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Evento</th>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $ev): ?>
                            <?php [$lbl, $cls] = $type_labels[$ev['type']]; ?>
                            <tr>
                                <td><a href="<?= BASE_URL ?>/events/view.php?id=<?= $ev['id'] ?>" class="text-gold"><?= h($ev['name']) ?></a></td>
                                <td><?= date('d/m/Y H:i', strtotime($ev['date_time'])) ?></td>
                                <td><span class="badge <?= $cls ?>"><?= $lbl ?></span></td>
                                <td><span class="stars-display small"><?= stars_html((float)$ev['avg_rating']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Ratings -->
            <div class="qf-form-card">
                <h4 class="text-gold mb-3"><i class="fas fa-star me-2"></i>Avaliações da Tenda</h4>
                <?php if (empty($ratings)): ?>
                <p class="text-muted">Ainda não há avaliações para esta tenda.</p>
                <?php else: ?>
                <?php foreach ($ratings as $rt): ?>
                <div class="rating-comment">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="comment-author"><?= h($rt['user_name']) ?></span>
                        <span class="comment-date"><?= date('d/m/Y', strtotime($rt['created_at'])) ?></span>
                    </div>
                    <div class="stars-display small mb-1"><?= stars_html((float)$rt['score']) ?></div>
                    <?php if ($rt['comment']): ?>
                    <p class="text-muted small mb-0"><?= nl2br(h($rt['comment'])) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar: rating form -->
        <div class="col-lg-4">
            <?php if (is_student()): ?>
            <div class="qf-form-card">
                <h5 class="text-gold mb-3"><i class="fas fa-star me-2"></i>
                    <?= $user_rating ? 'A tua avaliação' : 'Avaliar esta Tenda' ?>
                </h5>
                <form method="post" action="<?= BASE_URL ?>/ratings/rate.php" class="qf-validate" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="tent_id" value="<?= $tent['id'] ?>">
                    <input type="hidden" name="redirect" value="<?= BASE_URL ?>/tents/view.php?id=<?= $tent['id'] ?>">
                    <div class="star-rating mb-3">
                        <?php for ($s = 5; $s >= 1; $s--): ?>
                        <input type="radio" id="tstar<?= $s ?>" name="score" value="<?= $s ?>"
                               <?= ($user_rating && $user_rating['score'] == $s) ? 'checked' : '' ?> required>
                        <label for="tstar<?= $s ?>"><i class="fas fa-star"></i></label>
                        <?php endfor; ?>
                    </div>
                    <textarea name="comment" class="form-control mb-3" rows="3"
                              placeholder="Comentário (opcional)"><?= h($user_rating['comment'] ?? '') ?></textarea>
                    <button type="submit" class="btn btn-qf-gold w-100">
                        <i class="fas fa-paper-plane me-2"></i>
                        <?= $user_rating ? 'Actualizar' : 'Submeter Avaliação' ?>
                    </button>
                </form>
            </div>
            <?php elseif (!is_logged_in()): ?>
            <div class="qf-form-card">
                <p class="text-muted small">
                    <a href="<?= BASE_URL ?>/login.php" class="text-gold">Faz login</a> para avaliar esta tenda.
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
