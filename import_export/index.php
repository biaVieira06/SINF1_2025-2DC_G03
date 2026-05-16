<?php
// ============================================================
// import_export/index.php — Import/Export UI
// ============================================================

$page_title = 'Import / Export';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="page-header">
        <h1><i class="fas fa-file-csv me-2"></i>Import / Export CSV</h1>
        <p class="text-muted">Importa ou exporta eventos e artistas em formato CSV.</p>
    </div>

    <div class="row g-4">
        <!-- Import -->
        <div class="col-md-6">
            <div class="qf-form-card h-100">
                <h4 class="text-gold mb-3"><i class="fas fa-upload me-2"></i>Importar CSV</h4>
                <p class="text-muted small">
                    O ficheiro CSV deve ter cabeçalhos na primeira linha.<br>
                    <strong>Para eventos:</strong> name, description, date_time (YYYY-MM-DD HH:MM), location, type<br>
                    <strong>Para artistas:</strong> name, genre, country, biography
                </p>
                <form method="post" action="<?= BASE_URL ?>/import_export/import.php"
                      enctype="multipart/form-data" class="qf-validate" novalidate>
                    <?= csrf_field() ?>
                    <div class="import-zone mb-3">
                        <i class="fas fa-file-arrow-up fa-3x text-muted mb-2 d-block"></i>
                        <label class="form-label">Ficheiro CSV</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv,text/csv" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de dados</label>
                        <select name="import_type" class="form-select" required>
                            <option value="auto">Detecção automática</option>
                            <option value="events">Eventos</option>
                            <option value="artists">Artistas</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-qf-primary w-100">
                        <i class="fas fa-upload me-2"></i>Importar
                    </button>
                </form>
            </div>
        </div>

        <!-- Export -->
        <div class="col-md-6">
            <div class="qf-form-card h-100">
                <h4 class="text-gold mb-3"><i class="fas fa-download me-2"></i>Exportar CSV</h4>
                <p class="text-muted small">Exporta os dados do sistema para ficheiros CSV.</p>

                <div class="d-grid gap-3">
                    <a href="<?= BASE_URL ?>/import_export/export.php?type=events" class="btn btn-qf-outline">
                        <i class="fas fa-calendar-days me-2"></i>Exportar Eventos
                    </a>
                    <a href="<?= BASE_URL ?>/import_export/export.php?type=artists" class="btn btn-qf-outline">
                        <i class="fas fa-music me-2"></i>Exportar Artistas
                    </a>
                    <a href="<?= BASE_URL ?>/import_export/export.php?type=tents" class="btn btn-qf-outline">
                        <i class="fas fa-tent me-2"></i>Exportar Tendas
                    </a>
                    <a href="<?= BASE_URL ?>/import_export/export.php?type=ratings" class="btn btn-qf-outline">
                        <i class="fas fa-star me-2"></i>Exportar Avaliações
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- CSV format help -->
    <div class="qf-form-card mt-4">
        <h5 class="text-gold mb-3"><i class="fas fa-circle-info me-2"></i>Formato dos Ficheiros CSV</h5>
        <div class="row g-4">
            <div class="col-md-6">
                <h6 class="text-muted">Eventos (events)</h6>
                <pre class="p-3 rounded text-success small" style="background:var(--qf-dark3)">name,description,date_time,location,type
"Concerto X","Descrição","2026-05-06 22:00","Porto","concert"
"Cerimónia Y","Desc","2026-05-07 10:00","UP","academic_ceremony"</pre>
                <small class="text-muted">type: academic_ceremony | concert | cultural_activity</small>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted">Artistas (artists)</h6>
                <pre class="p-3 rounded text-success small" style="background:var(--qf-dark3)">name,genre,country,biography
"Nome Artista","Pop","Portugal","Biografia aqui"
"Outro Artista","Rock","Brasil","Bio..."</pre>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
