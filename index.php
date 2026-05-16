<?php
// ============================================================
// index.php — Home page
// ============================================================

$page_title = 'Início';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Fetch next 5 upcoming events
$upcoming = $pdo->query(
    "SELECT e.*, COALESCE(AVG(r.score),0) AS avg_rating,
            COUNT(DISTINCT pa.id) AS agenda_count
     FROM events e
     LEFT JOIN ratings r ON r.event_id = e.id
     LEFT JOIN personal_agenda pa ON pa.event_id = e.id
     WHERE e.date_time >= NOW()
     GROUP BY e.id
     ORDER BY e.date_time ASC LIMIT 5"
)->fetchAll();

// Next single event for countdown
$next_event = $upcoming[0] ?? null;

// Quick stats
$stats = $pdo->query(
    "SELECT
        (SELECT COUNT(*) FROM events)  AS total_events,
        (SELECT COUNT(*) FROM tents)   AS total_tents,
        (SELECT COUNT(*) FROM artists) AS total_artists,
        (SELECT COUNT(*) FROM users WHERE role_id = (SELECT id FROM roles WHERE name='student')) AS total_students"
)->fetch();

$type_labels = [
    'academic_ceremony' => ['Cerimónia Académica', 'badge-academic'],
    'concert'           => ['Concerto',            'badge-concert'],
    'cultural_activity' => ['Actividade Cultural', 'badge-cultural'],
];

include __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<section class="qf-hero">
    <div class="container position-relative">
        <p class="text-warning fw-semibold mb-2 letter-spacing">
            <i class="fas fa-fire me-1"></i> PORTO &middot; MAIO 2026
        </p>
        <h1><i class="fas fa-graduation-cap me-2 text-red"></i>Queima das Fitas do Porto</h1>
        <p class="lead mt-3 mb-4">A maior festa académica do Porto. Música, tradição e emoção durante uma semana inesquecível.</p>

        <?php if ($next_event): ?>
        <div class="mb-4">
            <p class="text-muted small mb-2">Próximo evento: <strong class="text-gold"><?= h($next_event['name']) ?></strong></p>
            <div class="qf-countdown d-inline-flex justify-content-center" data-target="<?= h($next_event['date_time']) ?>">
                <div class="countdown-unit"><span class="countdown-value">--</span><span class="countdown-label">Dias</span></div>
                <div class="countdown-unit"><span class="countdown-value">--</span><span class="countdown-label">Horas</span></div>
                <div class="countdown-unit"><span class="countdown-value">--</span><span class="countdown-label">Min</span></div>
                <div class="countdown-unit"><span class="countdown-value">--</span><span class="countdown-label">Seg</span></div>
            </div>
        </div>
        <?php endif; ?>

        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="<?= BASE_URL ?>/events/index.php" class="btn btn-qf-primary btn-lg px-4">
                <i class="fas fa-calendar-days me-2"></i>Ver Todos os Eventos
            </a>
            <?php if (!is_logged_in()): ?>
            <a href="<?= BASE_URL ?>/register.php" class="btn btn-qf-gold btn-lg px-4">
                <i class="fas fa-user-plus me-2"></i>Registar
            </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Quick Stats -->
<section class="py-4" style="background:var(--qf-dark2); border-bottom:1px solid var(--qf-border);">
    <div class="container">
        <div class="row g-3 text-center">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= (int)$stats['total_events'] ?></div>
                    <div class="stat-label"><i class="fas fa-calendar me-1"></i>Eventos</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= (int)$stats['total_tents'] ?></div>
                    <div class="stat-label"><i class="fas fa-tent me-1"></i>Tendas</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= (int)$stats['total_artists'] ?></div>
                    <div class="stat-label"><i class="fas fa-music me-1"></i>Artistas</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= (int)$stats['total_students'] ?></div>
                    <div class="stat-label"><i class="fas fa-users me-1"></i>Estudantes</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Upcoming Events -->
<section class="container py-5">
    <h2 class="qf-section-title"><i class="fas fa-clock me-2"></i>Próximos Eventos</h2>

    <?php if (empty($upcoming)): ?>
        <div class="alert" style="background:var(--qf-card-bg);border:1px solid var(--qf-border);color:var(--qf-muted);">
            <i class="fas fa-info-circle me-2"></i>Não há eventos próximos agendados.
        </div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($upcoming as $ev): ?>
        <div class="col-md-6 col-lg-4">
            <div class="qf-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <?php [$label, $cls] = $type_labels[$ev['type']]; ?>
                        <span class="badge <?= $cls ?>"><?= $label ?></span>
                        <?php if ($ev['avg_rating'] > 0): ?>
                        <small class="text-gold"><i class="fas fa-star me-1"></i><?= number_format($ev['avg_rating'],1) ?></small>
                        <?php endif; ?>
                    </div>
                    <h5 class="card-title"><?= h($ev['name']) ?></h5>
                    <p class="card-text small">
                        <i class="fas fa-calendar me-1 text-red"></i><?= date('d/m/Y H:i', strtotime($ev['date_time'])) ?><br>
                        <i class="fas fa-map-marker-alt me-1 text-red"></i><?= h($ev['location']) ?>
                    </p>
                    <div class="mt-3">
                        <div class="qf-countdown small" data-target="<?= h($ev['date_time']) ?>"></div>
                    </div>
                    <a href="<?= BASE_URL ?>/events/view.php?id=<?= $ev['id'] ?>" class="btn btn-qf-outline btn-sm mt-3 w-100">
                        <i class="fas fa-eye me-1"></i>Ver Detalhes
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
        <a href="<?= BASE_URL ?>/events/index.php" class="btn btn-qf-primary">
            <i class="fas fa-calendar-days me-2"></i>Ver Todos os Eventos
        </a>
    </div>
    <?php endif; ?>
</section>

<!-- Quick Links -->
<section class="py-5" style="background:var(--qf-dark2); border-top:1px solid var(--qf-border);">
    <div class="container">
        <h2 class="qf-section-title"><i class="fas fa-link me-2"></i>Explorar</h2>
        <div class="row g-4">
            <div class="col-sm-6 col-lg-3">
                <a href="<?= BASE_URL ?>/tents/index.php" class="qf-card text-decoration-none d-block p-4 text-center">
                    <i class="fas fa-tent fa-2x text-gold mb-3"></i>
                    <h5 class="card-title mb-1">Tendas</h5>
                    <p class="card-text">Encontra a tenda da tua faculdade</p>
                </a>
            </div>
            <div class="col-sm-6 col-lg-3">
                <a href="<?= BASE_URL ?>/artists/index.php" class="qf-card text-decoration-none d-block p-4 text-center">
                    <i class="fas fa-microphone fa-2x text-gold mb-3"></i>
                    <h5 class="card-title mb-1">Artistas</h5>
                    <p class="card-text">Descobre quem actua na Queima</p>
                </a>
            </div>
            <div class="col-sm-6 col-lg-3">
                <a href="<?= BASE_URL ?>/faculties/index.php" class="qf-card text-decoration-none d-block p-4 text-center">
                    <i class="fas fa-university fa-2x text-gold mb-3"></i>
                    <h5 class="card-title mb-1">Faculdades</h5>
                    <p class="card-text">Conhece todas as faculdades participantes</p>
                </a>
            </div>
            <div class="col-sm-6 col-lg-3">
                <?php if (is_student()): ?>
                <a href="<?= BASE_URL ?>/agenda/index.php" class="qf-card text-decoration-none d-block p-4 text-center">
                    <i class="fas fa-bookmark fa-2x text-gold mb-3"></i>
                    <h5 class="card-title mb-1">A Minha Agenda</h5>
                    <p class="card-text">Gere os eventos que queres ver</p>
                </a>
                <?php elseif (!is_logged_in()): ?>
                <a href="<?= BASE_URL ?>/register.php" class="qf-card text-decoration-none d-block p-4 text-center">
                    <i class="fas fa-user-plus fa-2x text-gold mb-3"></i>
                    <h5 class="card-title mb-1">Registar</h5>
                    <p class="card-text">Cria a tua agenda personalizada</p>
                </a>
                <?php else: ?>
                <a href="<?= BASE_URL ?>/admin/dashboard.php" class="qf-card text-decoration-none d-block p-4 text-center">
                    <i class="fas fa-gauge fa-2x text-gold mb-3"></i>
                    <h5 class="card-title mb-1">Dashboard</h5>
                    <p class="card-text">Estatísticas e gestão do festival</p>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
