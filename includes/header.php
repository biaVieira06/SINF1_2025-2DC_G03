<?php
// ============================================================
// includes/header.php — Bootstrap 5 navbar + alert banner
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';

// Check for events within the next 48 hours
$imminent_events = [];
try {
    $stmt = $pdo->query(
        "SELECT id, name, date_time, location FROM events
         WHERE date_time BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)
         ORDER BY date_time ASC LIMIT 5"
    );
    $imminent_events = $stmt->fetchAll();
} catch (Exception $e) {
    // silently ignore if DB isn't available yet
}

$page_title = $page_title ?? 'Queima das Fitas do Porto';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($page_title) ?> — Queima das Fitas do Porto</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark qf-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/index.php">
            <i class="fas fa-fire-flame-curved text-warning"></i>
            <span>Queima das Fitas</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/index.php"><i class="fas fa-home me-1"></i>Início</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/events/index.php"><i class="fas fa-calendar-days me-1"></i>Eventos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/tents/index.php"><i class="fas fa-tent me-1"></i>Tendas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/artists/index.php"><i class="fas fa-music me-1"></i>Artistas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/faculties/index.php"><i class="fas fa-university me-1"></i>Faculdades</a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php if (is_logged_in()): ?>
                    <?php if (is_student()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/agenda/index.php"><i class="fas fa-bookmark me-1"></i>A Minha Agenda</a>
                    </li>
                    <?php endif; ?>
                    <?php if (is_admin()): ?>
                    <li class="nav-item">
                        <a class="nav-link text-warning" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="fas fa-gauge me-1"></i>Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/import_export/index.php"><i class="fas fa-file-csv me-1"></i>Import/Export</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?= h(current_user_name()) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end qf-dropdown">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/profile.php"><i class="fas fa-id-card me-2"></i>Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="fas fa-right-from-bracket me-2"></i>Sair</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/login.php"><i class="fas fa-right-to-bracket me-1"></i>Entrar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-warning btn-sm px-3 ms-2" href="<?= BASE_URL ?>/register.php">
                            <i class="fas fa-user-plus me-1"></i>Registar
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- 48h Imminent Events Banner -->
<?php if (!empty($imminent_events)): ?>
<div class="alert qf-alert-banner alert-dismissible fade show mb-0 rounded-0" role="alert" id="imminentBanner">
    <div class="container">
        <i class="fas fa-bell fa-shake me-2"></i>
        <strong>Eventos nas próximas 48 horas:</strong>
        <?php foreach ($imminent_events as $ie): ?>
            <a href="<?= BASE_URL ?>/events/view.php?id=<?= $ie['id'] ?>" class="alert-link ms-2">
                <?= h($ie['name']) ?> (<?= date('d/m H:i', strtotime($ie['date_time'])) ?>)
            </a>
        <?php endforeach; ?>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Flash messages -->
<?php
$flash_success = get_flash('success');
$flash_error   = get_flash('error');
$flash_info    = get_flash('info');
?>
<?php if ($flash_success): ?>
<div class="alert alert-success alert-dismissible fade show mb-0 rounded-0 qf-flash" role="alert">
    <div class="container"><i class="fas fa-circle-check me-2"></i><?= h($flash_success) ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($flash_error): ?>
<div class="alert alert-danger alert-dismissible fade show mb-0 rounded-0 qf-flash" role="alert">
    <div class="container"><i class="fas fa-circle-xmark me-2"></i><?= h($flash_error) ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($flash_info): ?>
<div class="alert alert-info alert-dismissible fade show mb-0 rounded-0 qf-flash" role="alert">
    <div class="container"><i class="fas fa-circle-info me-2"></i><?= h($flash_info) ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<main class="qf-main">
