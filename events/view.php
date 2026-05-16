<?php
// ============================================================
// events/view.php — Event detail
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare(
    "SELECT e.*, t.name AS tent_name, t.id AS tent_id_fk
     FROM events e
     LEFT JOIN tents t ON t.id = e.tent_id
     WHERE e.id = ?"
);
$stmt->execute([$id]);
$event = $stmt->fetch();

if (!$event) {
    flash('error', 'Evento não encontrado.');
    header('Location: ' . BASE_URL . '/events/index.php');
    exit;
}

$page_title = $event['name'];

// Artists for this event
$artists_stmt = $pdo->prepare(
    "SELECT a.* FROM artists a
     JOIN event_artists ea ON ea.artist_id = a.id
     WHERE ea.event_id = ?"
);
$artists_stmt->execute([$id]);
$artists = $artists_stmt->fetchAll();

// Ratings
$ratings_stmt = $pdo->prepare(
    "SELECT r.*, u.name AS user_name FROM ratings r
     JOIN users u ON u.id = r.user_id
     WHERE r.event_id = ?
     ORDER BY r.created_at DESC"
);
$ratings_stmt->execute([$id]);
$ratings = $ratings_stmt->fetchAll();

$avg_rating = $ratings ? array_sum(array_column($ratings, 'score')) / count($ratings) : 0;

// Is event in user's agenda?
$in_agenda = false;
$user_rating = null;
if (is_logged_in()) {
    $ag = $pdo->prepare("SELECT id FROM personal_agenda WHERE user_id=? AND event_id=?");
    $ag->execute([current_user_id(), $id]);
    $in_agenda = (bool)$ag->fetch();

    if (is_student()) {
        $ur = $pdo->prepare("SELECT * FROM ratings WHERE user_id=? AND event_id=?");
        $ur->execute([current_user_id(), $id]);
        $user_rating = $ur->fetch();
    }
}

$type_labels = [
    'academic_ceremony' => ['Cerimónia Académica', 'badge-academic', 'fa-graduation-cap'],
    'concert'           => ['Concerto',            'badge-concert',  'fa-music'],
    'cultural_activity' => ['Actividade Cultural', 'badge-cultural', 'fa-palette'],
];
[$type_label, $type_cls, $type_icon] = $type_labels[$event['type']];

function stars_html(float $score, bool $large = false): string {
    $size = $large ? '' : ' small';
    $out  = '';
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
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/events/index.php" class="text-gold">Eventos</a></li>
            <li class="breadcrumb-item active text-muted"><?= h($event['name']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Main content -->
        <div class="col-lg-8">
            <div class="qf-form-card mb-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <span class="badge <?= $type_cls ?> fs-6">
                        <i class="fas <?= $type_icon ?> me-1"></i><?= $type_label ?>
                    </span>
                    <?php if (is_admin()): ?>
                    <div class="d-flex gap-2">
                        <a href="<?= BASE_URL ?>/events/edit.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-pen me-1"></i>Editar
                        </a>
                        <form method="post" action="<?= BASE_URL ?>/events/delete.php"
                              data-confirm="Eliminar este evento?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $event['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash me-1"></i>Eliminar
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>

                <h1 class="text-gold fs-2 mb-3"><?= h($event['name']) ?></h1>

                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-calendar fa-lg text-red"></i>
                            <div>
                                <div class="fw-semibold"><?= date('d \d\e F \d\e Y', strtotime($event['date_time'])) ?></div>
                                <div class="text-muted small"><?= date('H:i', strtotime($event['date_time'])) ?>h</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-map-marker-alt fa-lg text-red"></i>
                            <div>
                                <div class="fw-semibold"><?= h($event['location']) ?></div>
                                <?php if ($event['tent_name']): ?>
                                <div class="small">
                                    <a href="<?= BASE_URL ?>/tents/view.php?id=<?= $event['tent_id_fk'] ?>" class="text-gold">
                                        <i class="fas fa-tent me-1"></i><?= h($event['tent_name']) ?>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($event['description']): ?>
                <p class="text-muted"><?= nl2br(h($event['description'])) ?></p>
                <?php endif; ?>

                <!-- Countdown -->
                <?php if (strtotime($event['date_time']) > time()): ?>
                <div class="mt-4 p-3 rounded" style="background:var(--qf-dark3)">
                    <p class="text-muted small mb-2"><i class="fas fa-clock me-1"></i>Conta decrescente</p>
                    <div class="qf-countdown" data-target="<?= h($event['date_time']) ?>"></div>
                </div>
                <?php else: ?>
                <div class="alert alert-secondary mt-4">
                    <i class="fas fa-check-circle me-2 text-success"></i>Este evento já decorreu.
                </div>
                <?php endif; ?>
            </div>

            <!-- Artists -->
            <?php if (!empty($artists)): ?>
            <div class="qf-form-card mb-4">
                <h4 class="text-gold mb-3"><i class="fas fa-microphone me-2"></i>Artistas</h4>
                <div class="row g-3">
                    <?php foreach ($artists as $art): ?>
                    <div class="col-sm-6">
                        <a href="<?= BASE_URL ?>/artists/view.php?id=<?= $art['id'] ?>" class="text-decoration-none">
                            <div class="d-flex align-items-center gap-3 p-3 rounded" style="background:var(--qf-dark3);">
                                <?php if ($art['image_path'] && file_exists(UPLOAD_DIR . $art['image_path'])): ?>
                                <img src="<?= UPLOAD_URL . h($art['image_path']) ?>" class="artist-avatar" alt="">
                                <?php else: ?>
                                <div class="artist-avatar-placeholder"><i class="fas fa-user-music"></i></div>
                                <?php endif; ?>
                                <div>
                                    <div class="fw-semibold text-gold"><?= h($art['name']) ?></div>
                                    <div class="text-muted small"><?= h($art['genre']) ?></div>
                                    <div class="text-muted small"><i class="fas fa-globe me-1"></i><?= h($art['country']) ?></div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Ratings list -->
            <div class="qf-form-card">
                <h4 class="text-gold mb-3">
                    <i class="fas fa-star me-2"></i>Avaliações
                    <?php if ($avg_rating > 0): ?>
                    <span class="fs-6 fw-normal text-muted ms-2">
                        <?= number_format($avg_rating, 1) ?>/5
                        (<?= count($ratings) ?> avaliação<?= count($ratings) !== 1 ? 'ões' : '' ?>)
                    </span>
                    <?php endif; ?>
                </h4>

                <?php if (empty($ratings)): ?>
                <p class="text-muted">Ainda não há avaliações para este evento.</p>
                <?php else: ?>
                <div class="stars-display fs-5 mb-3"><?= stars_html($avg_rating, true) ?></div>
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

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Agenda button -->
            <?php if (is_student()): ?>
            <div class="qf-form-card mb-4">
                <h5 class="text-gold mb-3"><i class="fas fa-bookmark me-2"></i>A Minha Agenda</h5>
                <?php if ($in_agenda): ?>
                <p class="text-success small"><i class="fas fa-check-circle me-1"></i>Este evento está na tua agenda.</p>
                <form method="post" action="<?= BASE_URL ?>/agenda/remove.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                    <input type="hidden" name="redirect" value="<?= BASE_URL ?>/events/view.php?id=<?= $event['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="fas fa-bookmark-slash me-2"></i>Remover da Agenda
                    </button>
                </form>
                <?php else: ?>
                <p class="text-muted small">Guarda este evento para não perderes.</p>
                <form method="post" action="<?= BASE_URL ?>/agenda/add.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                    <input type="hidden" name="redirect" value="<?= BASE_URL ?>/events/view.php?id=<?= $event['id'] ?>">
                    <button type="submit" class="btn btn-qf-primary w-100">
                        <i class="fas fa-bookmark me-2"></i>Adicionar à Agenda
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <?php elseif (!is_logged_in()): ?>
            <div class="qf-form-card mb-4">
                <p class="text-muted small">
                    <a href="<?= BASE_URL ?>/login.php" class="text-gold">Faz login</a> para adicionar à agenda e avaliar.
                </p>
            </div>
            <?php endif; ?>

            <!-- Rating form -->
            <?php if (is_student()): ?>
            <div class="qf-form-card mb-4">
                <h5 class="text-gold mb-3"><i class="fas fa-star me-2"></i>
                    <?= $user_rating ? 'A tua avaliação' : 'Avaliar este evento' ?>
                </h5>
                <form method="post" action="<?= BASE_URL ?>/ratings/rate.php" class="qf-validate" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                    <input type="hidden" name="redirect" value="<?= BASE_URL ?>/events/view.php?id=<?= $event['id'] ?>">

                    <div class="star-rating mb-3" role="group" aria-label="Avaliação">
                        <?php for ($s = 5; $s >= 1; $s--): ?>
                        <input type="radio" id="star<?= $s ?>" name="score" value="<?= $s ?>"
                               <?= ($user_rating && $user_rating['score'] == $s) ? 'checked' : '' ?>
                               <?= (!$user_rating && $s == 5) ? '' : '' ?> required>
                        <label for="star<?= $s ?>" title="<?= $s ?> estrela<?= $s > 1 ? 's' : '' ?>">
                            <i class="fas fa-star"></i>
                        </label>
                        <?php endfor; ?>
                    </div>
                    <div class="mb-3">
                        <textarea name="comment" class="form-control" rows="3"
                                  placeholder="Comentário (opcional)"><?= h($user_rating['comment'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-qf-gold w-100">
                        <i class="fas fa-paper-plane me-2"></i>
                        <?= $user_rating ? 'Actualizar Avaliação' : 'Submeter Avaliação' ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Quick info -->
            <div class="qf-form-card">
                <h5 class="text-gold mb-3"><i class="fas fa-info-circle me-2"></i>Informação</h5>
                <ul class="list-unstyled text-muted small">
                    <li class="mb-2"><i class="fas fa-users me-2 text-red"></i><strong><?= count($ratings) ?></strong> avaliações</li>
                    <li class="mb-2">
                        <i class="fas fa-bookmark me-2 text-red"></i>
                        <?php
                            $ag_cnt = $pdo->prepare("SELECT COUNT(*) FROM personal_agenda WHERE event_id=?");
                            $ag_cnt->execute([$id]);
                            echo '<strong>' . $ag_cnt->fetchColumn() . '</strong> pessoas na agenda';
                        ?>
                    </li>
                    <li><i class="fas fa-clock me-2 text-red"></i>Criado em <?= date('d/m/Y', strtotime($event['created_at'])) ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
