<?php
// ============================================================
// artists/view.php — Artist detail + associated events
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM artists WHERE id = ?");
$stmt->execute([$id]);
$artist = $stmt->fetch();

if (!$artist) {
    flash('error', 'Artista não encontrado.');
    header('Location: ' . BASE_URL . '/artists/index.php');
    exit;
}

$page_title = $artist['name'];

$events = $pdo->prepare(
    "SELECT e.*, COALESCE(AVG(r.score),0) AS avg_rating
     FROM events e
     JOIN event_artists ea ON ea.event_id = e.id
     LEFT JOIN ratings r ON r.event_id = e.id
     WHERE ea.artist_id = ?
     GROUP BY e.id
     ORDER BY e.date_time"
);
$events->execute([$id]);
$events = $events->fetchAll();

$type_labels = [
    'academic_ceremony' => ['Cerimónia Académica', 'badge-academic'],
    'concert'           => ['Concerto',            'badge-concert'],
    'cultural_activity' => ['Actividade Cultural', 'badge-cultural'],
];

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
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/artists/index.php" class="text-gold">Artistas</a></li>
            <li class="breadcrumb-item active text-muted"><?= h($artist['name']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-lg-4 col-md-5">
            <div class="qf-form-card text-center">
                <?php if ($artist['image_path'] && file_exists(UPLOAD_DIR . $artist['image_path'])): ?>
                <img src="<?= UPLOAD_URL . h($artist['image_path']) ?>"
                     class="rounded-circle mb-3" style="width:140px;height:140px;object-fit:cover;border:3px solid var(--qf-red);"
                     alt="<?= h($artist['name']) ?>">
                <?php else: ?>
                <div class="artist-avatar-placeholder mx-auto mb-3" style="width:140px;height:140px;font-size:4rem;">
                    <i class="fas fa-user-music"></i>
                </div>
                <?php endif; ?>

                <h2 class="text-gold"><?= h($artist['name']) ?></h2>
                <p class="text-muted">
                    <span class="badge bg-secondary me-1"><?= h($artist['genre']) ?></span>
                    <i class="fas fa-globe me-1"></i><?= h($artist['country']) ?>
                </p>

                <?php if (is_admin()): ?>
                <div class="d-flex gap-2 justify-content-center mt-3">
                    <a href="<?= BASE_URL ?>/artists/edit.php?id=<?= $id ?>" class="btn btn-outline-warning btn-sm">
                        <i class="fas fa-pen me-1"></i>Editar
                    </a>
                    <form method="post" action="<?= BASE_URL ?>/artists/delete.php" data-confirm="Eliminar este artista?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash me-1"></i>Eliminar</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-8 col-md-7">
            <?php if ($artist['biography']): ?>
            <div class="qf-form-card mb-4">
                <h4 class="text-gold mb-3"><i class="fas fa-book me-2"></i>Biografia</h4>
                <p class="text-muted"><?= nl2br(h($artist['biography'])) ?></p>
            </div>
            <?php endif; ?>

            <div class="qf-form-card">
                <h4 class="text-gold mb-3"><i class="fas fa-calendar-days me-2"></i>Eventos</h4>
                <?php if (empty($events)): ?>
                <p class="text-muted">Nenhum evento associado a este artista.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-qf table-hover mb-0">
                        <thead>
                            <tr><th>Evento</th><th>Data</th><th>Tipo</th><th>Rating</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $ev): ?>
                            <?php [$lbl,$cls] = $type_labels[$ev['type']]; ?>
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
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
