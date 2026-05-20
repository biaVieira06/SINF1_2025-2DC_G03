<?php
// ============================================================
// events/index.php — List all events with filter/sort
// ============================================================

$page_title = 'Eventos';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Filter inputs
$filter_type       = $_GET['type']       ?? '';
$filter_date_from  = $_GET['date_from']  ?? '';
$filter_date_to    = $_GET['date_to']    ?? '';
$filter_min_rating = isset($_GET['min_rating']) ? (int)$_GET['min_rating'] : 0;
$sort              = $_GET['sort']       ?? 'date';

$where  = ['1=1'];
$params = [];

if ($filter_type) {
    $where[]  = 'e.type = ?';
    $params[] = $filter_type;
}
if ($filter_date_from) {
    $where[]  = 'DATE(e.date_time) >= ?';
    $params[] = $filter_date_from;
}
if ($filter_date_to) {
    $where[]  = 'DATE(e.date_time) <= ?';
    $params[] = $filter_date_to;
}

$order = match($sort) {
    'rating'     => 'avg_rating DESC, e.date_time ASC',
    'popularity' => 'agenda_count DESC, e.date_time ASC',
    default      => 'e.date_time ASC',
};

$sql = "SELECT e.*,
               COALESCE(AVG(r.score), 0)        AS avg_rating,
               COUNT(DISTINCT r.id)              AS rating_count,
               COUNT(DISTINCT pa.id)             AS agenda_count,
               t.name                            AS tent_name
        FROM events e
        LEFT JOIN ratings r        ON r.event_id = e.id
        LEFT JOIN personal_agenda pa ON pa.event_id = e.id
        LEFT JOIN tents t          ON t.id = e.tent_id
        WHERE " . implode(' AND ', $where) . "
        GROUP BY e.id
        HAVING avg_rating >= ?
        ORDER BY $order";

$params[] = $filter_min_rating;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

$type_labels = [
    'academic_ceremony' => ['Cerimónia Académica', 'badge-academic'],
    'concert'           => ['Concerto',            'badge-concert'],
    'cultural_activity' => ['Actividade Cultural', 'badge-cultural'],
];

function stars_html(float $score): string {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($score >= $i) {
            $out .= '<i class="fas fa-star"></i>';
        } elseif ($score >= $i - 0.5) {
            $out .= '<i class="fas fa-star-half-stroke"></i>';
        } else {
            $out .= '<i class="far fa-star empty-star"></i>';
        }
    }
    return $out;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1><i class="fas fa-calendar-days me-2"></i>Eventos</h1>
        <?php if (is_admin()): ?>
        <a href="<?= BASE_URL ?>/events/create.php" class="btn btn-qf-primary">
            <i class="fas fa-plus me-2"></i>Novo Evento
        </a>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <form method="get" class="qf-filter-bar">
        <div class="row g-3 align-items-end">
            <div class="col-sm-6 col-md-3">
                <label class="form-label small">Tipo</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="academic_ceremony"  <?= $filter_type==='academic_ceremony' ?'selected':'' ?>>Cerimónia Académica</option>
                    <option value="concert"            <?= $filter_type==='concert'           ?'selected':'' ?>>Concerto</option>
                    <option value="cultural_activity"  <?= $filter_type==='cultural_activity' ?'selected':'' ?>>Actividade Cultural</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label small">Data de</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= h($filter_date_from) ?>">
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label small">Data até</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= h($filter_date_to) ?>">
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label small">Rating mín.</label>
                <select name="min_rating" class="form-select form-select-sm">
                    <?php for ($i = 0; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>" <?= $filter_min_rating==$i?'selected':'' ?>>
                        <?= $i > 0 ? "$i+ estrelas" : 'Qualquer' ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-sm-6 col-md-2">
                <label class="form-label small">Ordenar por</label>
                <select name="sort" class="form-select form-select-sm">
                    <option value="date"       <?= $sort==='date'       ?'selected':'' ?>>Data</option>
                    <option value="rating"     <?= $sort==='rating'     ?'selected':'' ?>>Avaliação</option>
                    <option value="popularity" <?= $sort==='popularity' ?'selected':'' ?>>Popularidade</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-1">
                <button type="submit" class="btn btn-qf-primary btn-sm w-100">
                    <i class="fas fa-filter"></i>
                </button>
            </div>
        </div>
        <?php if ($filter_type || $filter_date_from || $filter_date_to || $filter_min_rating): ?>
        <div class="mt-2">
            <a href="<?= BASE_URL ?>/events/index.php" class="btn btn-sm btn-qf-outline">
                <i class="fas fa-times me-1"></i>Limpar filtros
            </a>
        </div>
        <?php endif; ?>
    </form>

    <!-- Results count -->
    <p class="text-muted small mb-3">
        <i class="fas fa-list me-1"></i><?= count($events) ?> evento(s) encontrado(s)
    </p>

    <?php if (empty($events)): ?>
    <div class="text-center py-5">
        <i class="fas fa-calendar-xmark fa-3x text-muted mb-3"></i>
        <p class="text-muted">Nenhum evento encontrado com os filtros seleccionados.</p>
        <a href="<?= BASE_URL ?>/events/index.php" class="btn btn-qf-outline">Limpar filtros</a>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($events as $ev): ?>
        <div class="col-md-6 col-xl-4">
            <div class="qf-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <?php [$label, $cls] = $type_labels[$ev['type']]; ?>
                        <span class="badge <?= $cls ?>"><?= $label ?></span>
                        <?php if (is_admin()): ?>
                        <div class="d-flex gap-1">
                            <a href="<?= BASE_URL ?>/events/edit.php?id=<?= $ev['id'] ?>"
                               class="btn btn-sm btn-outline-warning py-0 px-1" title="Editar">
                                <i class="fas fa-pen fa-xs"></i>
                            </a>
                            <form method="post" action="<?= BASE_URL ?>/events/delete.php"
                                  data-confirm="Eliminar o evento «<?= h($ev['name']) ?>»?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $ev['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Eliminar">
                                    <i class="fas fa-trash fa-xs"></i>
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($ev['image_path']) && file_exists(UPLOAD_DIR . $ev['image_path'])): ?>
                    <div class="mb-2 text-center">
                        <img src="<?= UPLOAD_URL . h($ev['image_path']) ?>" alt="<?= h($ev['name']) ?>"
                             style="max-width:100%;height:120px;object-fit:cover;border-radius:6px;">
                    </div>
                    <?php endif; ?>
                    <h5 class="card-title"><?= h($ev['name']) ?></h5>

                    <p class="card-text">
                        <i class="fas fa-calendar me-1 text-red"></i><?= date('d/m/Y H:i', strtotime($ev['date_time'])) ?><br>
                        <i class="fas fa-map-marker-alt me-1 text-red"></i><?= h($ev['location']) ?>
                        <?php if ($ev['tent_name']): ?>
                        <br><i class="fas fa-tent me-1 text-red"></i><?= h($ev['tent_name']) ?>
                        <?php endif; ?>
                    </p>

                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="stars-display small"><?= stars_html((float)$ev['avg_rating']) ?></span>
                        <small class="text-muted">
                            <?= $ev['rating_count'] > 0 ? number_format($ev['avg_rating'],1).' ('.$ev['rating_count'].')' : 'Sem avaliações' ?>
                        </small>
                        <small class="text-muted ms-auto">
                            <i class="fas fa-bookmark me-1"></i><?= (int)$ev['agenda_count'] ?>
                        </small>
                    </div>

                    <a href="<?= BASE_URL ?>/events/view.php?id=<?= $ev['id'] ?>" class="btn btn-qf-outline btn-sm w-100">
                        <i class="fas fa-eye me-1"></i>Ver Detalhes
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
