<?php
// ============================================================
// admin/dashboard.php — Admin statistics dashboard
// ============================================================

$page_title = 'Dashboard Administração';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

// Total counts
$counts = $pdo->query(
    "SELECT
        (SELECT COUNT(*) FROM users)   AS total_users,
        (SELECT COUNT(*) FROM events)  AS total_events,
        (SELECT COUNT(*) FROM tents)   AS total_tents,
        (SELECT COUNT(*) FROM artists) AS total_artists,
        (SELECT COUNT(*) FROM ratings) AS total_ratings,
        (SELECT COUNT(*) FROM personal_agenda) AS total_agenda,
        (SELECT COUNT(*) FROM users WHERE role_id=(SELECT id FROM roles WHERE name='student')) AS total_students,
        (SELECT COUNT(*) FROM users WHERE role_id=(SELECT id FROM roles WHERE name='admin'))   AS total_admins"
)->fetch();

// Most popular event (most agenda adds)
$popular_event = $pdo->query(
    "SELECT e.id, e.name, e.date_time, COUNT(pa.id) AS agenda_count
     FROM events e LEFT JOIN personal_agenda pa ON pa.event_id = e.id
     GROUP BY e.id ORDER BY agenda_count DESC LIMIT 1"
)->fetch();

// Highest rated event
$top_event = $pdo->query(
    "SELECT e.id, e.name, AVG(r.score) AS avg_score, COUNT(r.id) AS rating_count
     FROM events e JOIN ratings r ON r.event_id = e.id
     GROUP BY e.id HAVING rating_count >= 1 ORDER BY avg_score DESC LIMIT 1"
)->fetch();

// Highest rated tent
$top_tent = $pdo->query(
    "SELECT t.id, t.name, AVG(r.score) AS avg_score, COUNT(r.id) AS rating_count
     FROM tents t JOIN ratings r ON r.tent_id = t.id
     GROUP BY t.id HAVING rating_count >= 1 ORDER BY avg_score DESC LIMIT 1"
)->fetch();

// Events per type
$type_counts = $pdo->query(
    "SELECT type, COUNT(*) AS cnt FROM events GROUP BY type"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$total_ev  = array_sum($type_counts);
$type_data = [
    'academic_ceremony' => ['Cerimónia Académica', '#2a9d8f'],
    'concert'           => ['Concerto',            '#e63946'],
    'cultural_activity' => ['Actividade Cultural', '#e9c46a'],
];

// Events per faculty (tent-linked events)
$faculty_events = $pdo->query(
    "SELECT f.name, f.acronym, f.color, COUNT(DISTINCT e.id) AS cnt
     FROM faculties f
     JOIN tents t ON t.faculty_id = f.id
     JOIN events e ON e.tent_id = t.id
     GROUP BY f.id ORDER BY cnt DESC"
)->fetchAll();

// Recent ratings
$recent_ratings = $pdo->query(
    "SELECT r.*, u.name AS user_name,
            COALESCE(e.name, t.name) AS target_name,
            CASE WHEN r.event_id IS NOT NULL THEN 'event' ELSE 'tent' END AS target_type,
            COALESCE(r.event_id, r.tent_id) AS target_id
     FROM ratings r
     JOIN users u ON u.id = r.user_id
     LEFT JOIN events e ON e.id = r.event_id
     LEFT JOIN tents  t ON t.id = r.tent_id
     ORDER BY r.created_at DESC LIMIT 8"
)->fetchAll();

// Monthly event distribution (upcoming)
$monthly = $pdo->query(
    "SELECT DATE_FORMAT(date_time,'%Y-%m') AS month, COUNT(*) AS cnt
     FROM events WHERE date_time >= NOW() - INTERVAL 3 MONTH
     GROUP BY month ORDER BY month"
)->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="page-header">
        <h1><i class="fas fa-gauge me-2"></i>Dashboard Administração</h1>
        <p class="text-muted small mb-0">Visão geral do sistema Queima das Fitas do Porto</p>
    </div>

    <!-- Main stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <i class="fas fa-users fa-2x text-gold mb-2"></i>
                <div class="stat-number"><?= (int)$counts['total_users'] ?></div>
                <div class="stat-label">Utilizadores</div>
                <small class="text-muted"><?= $counts['total_students'] ?> estudantes / <?= $counts['total_admins'] ?> admins</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <i class="fas fa-calendar-days fa-2x text-gold mb-2"></i>
                <div class="stat-number"><?= (int)$counts['total_events'] ?></div>
                <div class="stat-label">Eventos</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <i class="fas fa-tent fa-2x text-gold mb-2"></i>
                <div class="stat-number"><?= (int)$counts['total_tents'] ?></div>
                <div class="stat-label">Tendas</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <i class="fas fa-music fa-2x text-gold mb-2"></i>
                <div class="stat-number"><?= (int)$counts['total_artists'] ?></div>
                <div class="stat-label">Artistas</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <i class="fas fa-star fa-2x text-gold mb-2"></i>
                <div class="stat-number"><?= (int)$counts['total_ratings'] ?></div>
                <div class="stat-label">Avaliações</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <i class="fas fa-bookmark fa-2x text-gold mb-2"></i>
                <div class="stat-number"><?= (int)$counts['total_agenda'] ?></div>
                <div class="stat-label">Entradas em Agenda</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Highlights -->
        <div class="col-md-4">
            <div class="qf-form-card h-100">
                <h5 class="text-gold mb-3"><i class="fas fa-fire me-2"></i>Destaques</h5>
                <?php if ($popular_event): ?>
                <div class="mb-3 p-3 rounded" style="background:var(--qf-dark3)">
                    <div class="text-muted small mb-1"><i class="fas fa-bookmark me-1"></i>Evento mais popular</div>
                    <a href="<?= BASE_URL ?>/events/view.php?id=<?= $popular_event['id'] ?>" class="text-gold fw-semibold">
                        <?= h($popular_event['name']) ?>
                    </a>
                    <div class="text-muted small"><?= $popular_event['agenda_count'] ?> agendamentos</div>
                </div>
                <?php endif; ?>
                <?php if ($top_event): ?>
                <div class="mb-3 p-3 rounded" style="background:var(--qf-dark3)">
                    <div class="text-muted small mb-1"><i class="fas fa-star me-1"></i>Evento melhor avaliado</div>
                    <a href="<?= BASE_URL ?>/events/view.php?id=<?= $top_event['id'] ?>" class="text-gold fw-semibold">
                        <?= h($top_event['name']) ?>
                    </a>
                    <div class="text-muted small"><?= number_format($top_event['avg_score'],1) ?>/5 (<?= $top_event['rating_count'] ?> avaliações)</div>
                </div>
                <?php endif; ?>
                <?php if ($top_tent): ?>
                <div class="p-3 rounded" style="background:var(--qf-dark3)">
                    <div class="text-muted small mb-1"><i class="fas fa-tent me-1"></i>Tenda melhor avaliada</div>
                    <a href="<?= BASE_URL ?>/tents/view.php?id=<?= $top_tent['id'] ?>" class="text-gold fw-semibold">
                        <?= h($top_tent['name']) ?>
                    </a>
                    <div class="text-muted small"><?= number_format($top_tent['avg_score'],1) ?>/5 (<?= $top_tent['rating_count'] ?> avaliações)</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Events by type -->
        <div class="col-md-4">
            <div class="qf-form-card h-100">
                <h5 class="text-gold mb-3"><i class="fas fa-chart-bar me-2"></i>Eventos por Tipo</h5>
                <?php if ($total_ev > 0): ?>
                <div class="qf-bar-chart">
                    <?php foreach ($type_data as $key => [$label, $color]): ?>
                    <?php $cnt = $type_counts[$key] ?? 0; ?>
                    <?php $pct = $total_ev > 0 ? round($cnt / $total_ev * 100) : 0; ?>
                    <div class="bar-row">
                        <div class="bar-label"><?= $label ?></div>
                        <div class="bar-track">
                            <div class="bar-fill" data-width="<?= $pct ?>"
                                 style="background:<?= $color ?>; width:0%">
                                <?= $cnt ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted">Sem dados.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Events per faculty -->
        <div class="col-md-4">
            <div class="qf-form-card h-100">
                <h5 class="text-gold mb-3"><i class="fas fa-university me-2"></i>Eventos por Faculdade</h5>
                <?php if (!empty($faculty_events)): ?>
                <?php $max_fac = max(array_column($faculty_events, 'cnt')); ?>
                <div class="qf-bar-chart">
                    <?php foreach ($faculty_events as $fe): ?>
                    <?php $pct = $max_fac > 0 ? round($fe['cnt'] / $max_fac * 100) : 0; ?>
                    <div class="bar-row">
                        <div class="bar-label">
                            <span class="faculty-dot" style="background:<?= h($fe['color']) ?>"></span>
                            <?= h($fe['acronym'] ?? '—') ?>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" data-width="<?= $pct ?>"
                                 style="background:<?= h($fe['color']) ?>; width:0%">
                                <?= $fe['cnt'] ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted small">Nenhum evento com tenda associada.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent ratings -->
    <div class="qf-form-card mb-4">
        <h5 class="text-gold mb-3"><i class="fas fa-star me-2"></i>Avaliações Recentes</h5>
        <?php if (empty($recent_ratings)): ?>
        <p class="text-muted">Sem avaliações ainda.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-qf table-hover mb-0">
                <thead>
                    <tr>
                        <th>Utilizador</th>
                        <th>Avaliado</th>
                        <th>Nota</th>
                        <th>Comentário</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_ratings as $rt): ?>
                    <tr>
                        <td><?= h($rt['user_name']) ?></td>
                        <td>
                            <?php $link = $rt['target_type'] === 'event'
                                ? BASE_URL . '/events/view.php?id=' . $rt['target_id']
                                : BASE_URL . '/tents/view.php?id='  . $rt['target_id']; ?>
                            <a href="<?= $link ?>" class="text-gold"><?= h($rt['target_name'] ?? '—') ?></a>
                            <small class="text-muted d-block"><?= $rt['target_type'] === 'event' ? 'Evento' : 'Tenda' ?></small>
                        </td>
                        <td>
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                            <i class="fas fa-star" style="font-size:.75rem;<?= $s > $rt['score'] ? 'color:white!important;-webkit-text-stroke:0.5px #ffc107;filter:drop-shadow(0 0 0.3px #ffc107)' : 'color:#ffc107!important' ?>"></i>
                            <?php endfor; ?>
                        </td>
                        <td class="text-muted small"><?= h(mb_strimwidth($rt['comment'] ?? '', 0, 60, '…')) ?></td>
                        <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($rt['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Quick admin actions -->
    <div class="qf-form-card">
        <h5 class="text-gold mb-3"><i class="fas fa-tools me-2"></i>Acções Rápidas</h5>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= BASE_URL ?>/events/create.php"    class="btn btn-qf-outline"><i class="fas fa-calendar-plus me-2"></i>Novo Evento</a>
            <a href="<?= BASE_URL ?>/tents/create.php"     class="btn btn-qf-outline"><i class="fas fa-tent me-2"></i>Nova Tenda</a>
            <a href="<?= BASE_URL ?>/artists/create.php"   class="btn btn-qf-outline"><i class="fas fa-user-plus me-2"></i>Novo Artista</a>
            <a href="<?= BASE_URL ?>/faculties/create.php" class="btn btn-qf-outline"><i class="fas fa-university me-2"></i>Nova Faculdade</a>
            <a href="<?= BASE_URL ?>/import_export/index.php" class="btn btn-qf-outline"><i class="fas fa-file-csv me-2"></i>Import/Export</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
