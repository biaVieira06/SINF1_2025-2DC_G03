<?php
// ============================================================
// import_export/import.php — POST: parse CSV and insert
// ============================================================

$page_title = 'Resultado da Importação';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    flash('error', 'Pedido inválido.');
    header('Location: ' . BASE_URL . '/import_export/index.php');
    exit;
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    flash('error', 'Falha no upload do ficheiro.');
    header('Location: ' . BASE_URL . '/import_export/index.php');
    exit;
}

$file        = $_FILES['csv_file']['tmp_name'];
$import_type = $_POST['import_type'] ?? 'auto';

// Open CSV
$handle = fopen($file, 'r');
if (!$handle) {
    flash('error', 'Não foi possível ler o ficheiro CSV.');
    header('Location: ' . BASE_URL . '/import_export/index.php');
    exit;
}

// Read header row
$headers = fgetcsv($handle);
if (!$headers) {
    fclose($handle);
    flash('error', 'Ficheiro CSV vazio ou inválido.');
    header('Location: ' . BASE_URL . '/import_export/index.php');
    exit;
}

// Normalize headers
$headers = array_map(fn($h) => strtolower(trim($h)), $headers);

// Auto-detect type
if ($import_type === 'auto') {
    if (in_array('date_time', $headers) || in_array('type', $headers)) {
        $import_type = 'events';
    } elseif (in_array('genre', $headers) || in_array('country', $headers)) {
        $import_type = 'artists';
    } else {
        fclose($handle);
        flash('error', 'Não foi possível detectar o tipo de dados. Especifica manualmente.');
        header('Location: ' . BASE_URL . '/import_export/index.php');
        exit;
    }
}

$inserted = 0;
$skipped  = 0;
$errors   = [];
$row_num  = 1;

if ($import_type === 'events') {
    $required = ['name', 'date_time', 'location', 'type'];
    foreach ($required as $req) {
        if (!in_array($req, $headers)) {
            fclose($handle);
            flash('error', "Coluna obrigatória ausente: $req");
            header('Location: ' . BASE_URL . '/import_export/index.php');
            exit;
        }
    }

    $stmt = $pdo->prepare(
        "INSERT INTO events (name, description, date_time, location, type)
         VALUES (?, ?, ?, ?, ?)"
    );

    $valid_types = ['academic_ceremony', 'concert', 'cultural_activity'];

    while (($row = fgetcsv($handle)) !== false) {
        $row_num++;
        $data = array_combine($headers, array_pad($row, count($headers), ''));
        if (!$data) { $skipped++; continue; }

        $name      = trim($data['name']        ?? '');
        $desc      = trim($data['description'] ?? '');
        $dt        = trim($data['date_time']   ?? '');
        $location  = trim($data['location']    ?? '');
        $type      = trim($data['type']        ?? '');

        if (!$name || !$dt || !$location) {
            $errors[] = "Linha $row_num: campos obrigatórios em falta (name, date_time, location).";
            $skipped++;
            continue;
        }

        if (!in_array($type, $valid_types)) {
            $errors[] = "Linha $row_num: tipo inválido «$type». A usar 'concert'.";
            $type = 'concert';
        }

        // Validate date
        $parsed_dt = date('Y-m-d H:i:s', strtotime($dt));
        if (!$parsed_dt || $parsed_dt === '1970-01-01 00:00:00') {
            $errors[] = "Linha $row_num: data inválida «$dt».";
            $skipped++;
            continue;
        }

        try {
            $stmt->execute([$name, $desc, $parsed_dt, $location, $type]);
            $inserted++;
        } catch (PDOException $e) {
            $errors[] = "Linha $row_num: erro ao inserir — " . $e->getMessage();
            $skipped++;
        }
    }

} elseif ($import_type === 'artists') {
    $required = ['name', 'genre', 'country'];
    foreach ($required as $req) {
        if (!in_array($req, $headers)) {
            fclose($handle);
            flash('error', "Coluna obrigatória ausente: $req");
            header('Location: ' . BASE_URL . '/import_export/index.php');
            exit;
        }
    }

    $stmt = $pdo->prepare(
        "INSERT INTO artists (name, genre, country, biography) VALUES (?, ?, ?, ?)"
    );

    while (($row = fgetcsv($handle)) !== false) {
        $row_num++;
        $data = array_combine($headers, array_pad($row, count($headers), ''));
        if (!$data) { $skipped++; continue; }

        $name      = trim($data['name']      ?? '');
        $genre     = trim($data['genre']     ?? '');
        $country   = trim($data['country']   ?? '');
        $biography = trim($data['biography'] ?? '');

        if (!$name || !$genre || !$country) {
            $errors[] = "Linha $row_num: campos obrigatórios em falta.";
            $skipped++;
            continue;
        }

        try {
            $stmt->execute([$name, $genre, $country, $biography]);
            $inserted++;
        } catch (PDOException $e) {
            $errors[] = "Linha $row_num: erro — " . $e->getMessage();
            $skipped++;
        }
    }
}

fclose($handle);

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="page-header">
        <h1><i class="fas fa-upload me-2"></i>Resultado da Importação</h1>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="qf-form-card">
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="stat-card">
                            <div class="stat-number text-success"><?= $inserted ?></div>
                            <div class="stat-label">Registos importados</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card">
                            <div class="stat-number text-warning"><?= $skipped ?></div>
                            <div class="stat-label">Registos ignorados</div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-<?= $skipped === 0 ? 'success' : 'warning' ?>">
                    <i class="fas fa-<?= $skipped === 0 ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                    Importação de <strong><?= ucfirst($import_type) ?></strong> concluída:
                    <?= $inserted ?> inseridos<?= $skipped > 0 ? ", $skipped ignorados" : '' ?>.
                </div>

                <?php if (!empty($errors)): ?>
                <h6 class="text-warning mb-2">Erros/Avisos:</h6>
                <ul class="list-unstyled">
                    <?php foreach ($errors as $err): ?>
                    <li class="text-muted small"><i class="fas fa-exclamation-circle me-1 text-warning"></i><?= h($err) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <div class="d-flex gap-2 mt-4">
                    <a href="<?= BASE_URL ?>/import_export/index.php" class="btn btn-qf-outline">
                        <i class="fas fa-arrow-left me-2"></i>Voltar
                    </a>
                    <?php if ($import_type === 'events'): ?>
                    <a href="<?= BASE_URL ?>/events/index.php" class="btn btn-qf-primary">
                        <i class="fas fa-calendar-days me-2"></i>Ver Eventos
                    </a>
                    <?php else: ?>
                    <a href="<?= BASE_URL ?>/artists/index.php" class="btn btn-qf-primary">
                        <i class="fas fa-music me-2"></i>Ver Artistas
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
