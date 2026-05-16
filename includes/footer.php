<?php
// ============================================================
// includes/footer.php
// ============================================================
?>
</main>

<footer class="qf-footer mt-auto">
    <div class="container py-4">
        <div class="row g-4">
            <div class="col-md-4">
                <h5 class="text-warning mb-3"><i class="fas fa-fire-flame-curved me-2"></i>Queima das Fitas</h5>
                <p class="text-muted small">A maior festa académica do Porto. Celebra o fim do ano lectivo com música, tradição e emoção.</p>
            </div>
            <div class="col-md-4">
                <h6 class="text-light mb-3">Navegação Rápida</h6>
                <ul class="list-unstyled">
                    <li><a href="<?= BASE_URL ?>/events/index.php" class="footer-link"><i class="fas fa-angle-right me-1"></i>Eventos</a></li>
                    <li><a href="<?= BASE_URL ?>/tents/index.php" class="footer-link"><i class="fas fa-angle-right me-1"></i>Tendas</a></li>
                    <li><a href="<?= BASE_URL ?>/artists/index.php" class="footer-link"><i class="fas fa-angle-right me-1"></i>Artistas</a></li>
                    <li><a href="<?= BASE_URL ?>/faculties/index.php" class="footer-link"><i class="fas fa-angle-right me-1"></i>Faculdades</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6 class="text-light mb-3">Informação</h6>
                <p class="text-muted small"><i class="fas fa-map-marker-alt me-1 text-warning"></i>Parque da Cidade do Porto</p>
                <p class="text-muted small"><i class="fas fa-calendar me-1 text-warning"></i>Maio 2026</p>
            </div>
        </div>
        <hr class="border-secondary">
        <p class="text-center text-muted small mb-0">
            &copy; <?= date('Y') ?> Queima das Fitas do Porto. Sistema de Gestão de Eventos.
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
