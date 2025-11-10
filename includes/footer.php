    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="bi bi-building"></i> MuniOps</h5>
                    <p class="text-muted">Plataforma de Participación Ciudadana Gamificada</p>
                </div>
                <div class="col-md-4">
                    <h6>Enlaces Rápidos</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo BASE_URL; ?>index.php" class="text-muted text-decoration-none">Inicio</a></li>
                        <li><a href="<?php echo BASE_URL; ?>propuestas.php" class="text-muted text-decoration-none">Propuestas</a></li>
                        <li><a href="<?php echo BASE_URL; ?>ranking.php" class="text-muted text-decoration-none">Ranking</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6>Contacto</h6>
                    <p class="text-muted">
                        <i class="bi bi-envelope"></i> contacto@muniops.gob.pe<br>
                        <i class="bi bi-telephone"></i> (01) 123-4567
                    </p>
                </div>
            </div>
            <hr class="bg-light">
            <div class="text-center text-muted">
                <small>&copy; <?php echo date('Y'); ?> MuniOps. Todos los derechos reservados.</small>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (opcional, solo si lo necesitas) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    
    <?php if (isset($extraJS)): ?>
        <?php echo $extraJS; ?>
    <?php endif; ?>
</body>
</html>
