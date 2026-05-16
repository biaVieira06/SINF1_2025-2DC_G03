<?php
// ============================================================
// agenda/index.php — Student personal agenda
// ============================================================

$page_title = 'A Minha Agenda';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user_id = current_user_id();

// Upcoming events in agenda
$upcoming = $pdo->prepare(
    "SELECT e.*, pa.created_at AS added_at,
            COALESCE(AVG(r.score),0) AS avg_rating
     FROM personal_agenda pa
     JOIN events e ON e.id = pa.event_id
     LEFT JOIN ratings r ON r.event_id = e.id
     WHERE pa.user_id = ? AND e.date_time >= NOW()
     GROUP BY e.id, pa.created_at
     ORDER BY e.date_time ASC"
);
$upcoming->execute([$user_id]);
$upcoming = $upcoming->fetchAll();

// Past events in agenda
$past = $pdo->prepare(
    "SELECT e.*, pa.created_at AS added_at,
            COALESCE(AVG(r.score),0) AS avg_rating,
            (SELECT id FROM ratings WHERE user_id=? AND event_id=e.id LIMIT 1) AS my_rating_id
     FROM personal_agenda pa
     JOIN events e ON e.id = pa.event_id
     LEFT JOIN ratings r ON r.event_id = e.id
     WHERE pa.user_id = ? AND e.date_time < NOW()
     GROUP BY e.id, pa.created_at
     ORDER BY e.date_time DESC"
);
$past->execute([$user_id, $user_id]);
$past = $past->fetchAll();

$type_labels = [
    'academic_ceremony' => ['Cerimónia Académica', 'badge-academic'],
    'concert'           => ['Concerto',            'badge-concert'],
    'cultural_activity' => ['Actividade Cultural', 'badge-cultural'],
];

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1><i class="fas fa-bookmark me-2"></i>A Minha Agenda</h1>
        <a href="<?= BASE_URL ?>/events/index.php" class="btn btn-qf-outline">
            <i class="fas fa-calendar-plus me-2"></i>Adicionar mais eventos
        </a>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-number"><?= count($upcoming) + count($past) ?></div>
                <div class="stat-label">Total de eventos</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-number"><?= count($upcoming) ?></div>
                <div class="stat-label">Próximos</div>
            </div>
        </div>
    </div>

    <!-- Upcoming -->
    <h2 class="qf-section-title"><i class="fas fa-clock me-2"></i>Próximos Eventos</h2>
    <?php if (empty($upcoming)): ?>
    <div class="alert" style="background:var(--qf-card-bg);border:1px solid var(--qf-border);color:var(--qf-muted);" role="alert">
        <i class="fas fa-info-circle me-2"></i>
        Não tens eventos próximos na agenda.
        <a href="<?= BASE_URL ?>/events/index.php" class="text-gold">Explorar eventos</a>
    </div>
    <?php else: ?>
    <?php foreach ($upcoming as $ev): ?>
    <div class="agenda-event-card">
        <div class="row align-items-center g-2">
            <div class="col-md-6">
                <?php [$lbl,$cls] = $type_labels[$ev['type']]; ?>
                <span class="badge <?= $cls ?> mb-1"><?= $lbl ?></span>
                <h5 class="text-gold mb-1">
                    <a href="<?= BASE_URL ?>/events/view.php?id=<?= $ev['id'] ?>" class="text-gold"><?= h($ev['name']) ?></a>
                </h5>
                <small class="text-muted">
                    <i class="fas fa-calendar me-1"></i><?= date('d/m/Y H:i', strtotime($ev['date_time'])) ?>
                    &nbsp;&nbsp;<i class="fas fa-map-marker-alt me-1"></i><?= h($ev['location']) ?>
                </small>
            </div>
            <div class="col-md-4">
                <div class="qf-countdown small" data-target="<?= h($ev['date_time']) ?>"></div>
            </div>
            <div class="col-md-2 text-md-end">
                <form method="post" action="<?= BASE_URL ?>/agenda/remove.php"
                      data-confirm="Remover «<?= h($ev['name']) ?>» da agenda?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                    <input type="hidden" name="redirect" value="<?= BASE_URL ?>/agenda/index.php">
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-bookmark-slash"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- Past -->
    <?php if (!empty($past)): ?>
    <h2 class="qf-section-title mt-5"><i class="fas fa-history me-2"></i>Eventos Passados</h2>
    <?php foreach ($past as $ev): ?>
    <div class="agenda-event-card" style="opacity:.75;border-left-color:var(--qf-border);">
        <div class="row align-items-center g-2">
            <div class="col-md-8">
                <?php [$lbl,$cls] = $type_labels[$ev['type']]; ?>
                <span class="badge <?= $cls ?> mb-1"><?= $lbl ?></span>
                <h6 class="text-muted mb-1">
                    <a href="<?= BASE_URL ?>/events/view.php?id=<?= $ev['id'] ?>" class="text-gold"><?= h($ev['name']) ?></a>
                </h6>
                <small class="text-muted">
                    <i class="fas fa-calendar me-1"></i><?= date('d/m/Y H:i', strtotime($ev['date_time'])) ?>
                </small>
            </div>
            <div class="col-md-4 text-md-end d-flex gap-2 justify-content-md-end">
                <?php if (!$ev['my_rating_id']): ?>
                <a href="<?= BASE_URL ?>/events/view.php?id=<?= $ev['id'] ?>#rate" class="btn btn-sm btn-qf-gold">
                    <i class="fas fa-star me-1"></i>Avaliar
                </a>
                <?php else: ?>
                <span class="text-muted small"><i class="fas fa-check-circle text-success me-1"></i>Avaliado</span>
                <?php endif; ?>
                <form method="post" action="<?= BASE_URL ?>/agenda/remove.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                    <input type="hidden" name="redirect" value="<?= BASE_URL ?>/agenda/index.php">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-trash fa-xs"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
