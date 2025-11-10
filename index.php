<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$pageTitle = 'Inicio - MuniOps';

// Obtener estadísticas
$totalUsuarios = fetchOne("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'ciudadano'")['total'];
$totalPropuestas = fetchOne("SELECT COUNT(*) as total FROM propuestas WHERE estado = 'activa'")['total'];
$totalVotos = fetchOne("SELECT COUNT(*) as total FROM votos")['total'];

// Obtener propuestas activas
$propuestasActivas = getActivePropuestas(3);

include 'includes/header.php';
?>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container text-center">
        <h1 class="display-4 fw-bold mb-4">
            <i class="bi bi-megaphone"></i> Tu Voz Cuenta
        </h1>
        <p class="lead mb-4">
            Participa en las decisiones de tu comunidad y gana recompensas por tu compromiso ciudadano
        </p>
        <?php if (!isLoggedIn()): ?>
            <a href="registro.php" class="btn btn-light btn-lg px-5 py-3 me-3">
                <i class="bi bi-person-plus"></i> Regístrate Ahora
            </a>
            <a href="login.php" class="btn btn-outline-light btn-lg px-5 py-3">
                <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
            </a>
        <?php else: ?>
            <a href="propuestas.php" class="btn btn-light btn-lg px-5 py-3">
                <i class="bi bi-lightbulb"></i> Ver Propuestas
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Stats Section -->
<div class="container my-5">
    <div class="row text-center">
        <div class="col-md-4 mb-4">
            <div class="stats-card">
                <i class="bi bi-people text-primary"></i>
                <h3 data-counter="<?php echo $totalUsuarios; ?>">0</h3>
                <p>Ciudadanos Registrados</p>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="stats-card">
                <i class="bi bi-lightbulb text-warning"></i>
                <h3 data-counter="<?php echo $totalPropuestas; ?>">0</h3>
                <p>Propuestas Activas</p>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="stats-card">
                <i class="bi bi-check-circle text-success"></i>
                <h3 data-counter="<?php echo $totalVotos; ?>">0</h3>
                <p>Votos Emitidos</p>
            </div>
        </div>
    </div>
</div>

<!-- Propuestas Activas -->
<?php if (!empty($propuestasActivas)): ?>
<div class="container my-5">
    <div class="text-center mb-4">
        <h2 class="fw-bold">
            <i class="bi bi-fire text-danger"></i> Propuestas Activas
        </h2>
        <p class="text-muted">Vota por las propuestas que transformarán tu comunidad</p>
    </div>
    
    <div class="row">
        <?php foreach ($propuestasActivas as $propuesta): ?>
        <div class="col-md-4 mb-4">
            <div class="card propuesta-card h-100">
                <?php if ($propuesta['imagen']): ?>
                    <img src="uploads/<?php echo $propuesta['imagen']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($propuesta['titulo']); ?>" style="height: 200px; object-fit: cover;">
                <?php else: ?>
                    <div class="card-img-top bg-gradient d-flex align-items-center justify-content-center" style="height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="bi bi-lightbulb text-white" style="font-size: 4rem;"></i>
                    </div>
                <?php endif; ?>
                
                <div class="card-body">
                    <div class="mb-2">
                        <span class="badge categoria-<?php echo $propuesta['categoria']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $propuesta['categoria'])); ?>
                        </span>
                    </div>
                    
                    <h5 class="card-title"><?php echo htmlspecialchars($propuesta['titulo']); ?></h5>
                    <p class="card-text text-muted">
                        <?php echo substr(htmlspecialchars($propuesta['descripcion']), 0, 100) . '...'; ?>
                    </p>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">
                                <i class="bi bi-check-circle"></i> <?php echo $propuesta['total_votos']; ?> votos
                            </small>
                            <small class="text-muted">
                                <i class="bi bi-calendar"></i> 
                                <?php echo $propuesta['dias_restantes']; ?> días restantes
                            </small>
                        </div>
                        <div class="progress propuesta-progress">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo min(100, ($propuesta['total_votos'] / 100) * 100); ?>%">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <small class="text-muted">
                            <i class="bi bi-chat"></i> <?php echo $propuesta['total_comentarios']; ?> comentarios
                        </small>
                    </div>
                    
                    <?php if (isLoggedIn()): ?>
                        <?php if (hasVoted(getUserId(), $propuesta['id'])): ?>
                            <button class="btn btn-secondary w-100" disabled>
                                <i class="bi bi-check2"></i> Ya Votaste
                            </button>
                        <?php else: ?>
                            <a href="propuesta-detalle.php?id=<?php echo $propuesta['id']; ?>" class="btn btn-vote w-100">
                                <i class="bi bi-hand-thumbs-up"></i> Votar Ahora
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right"></i> Inicia Sesión para Votar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="text-center mt-4">
        <a href="propuestas.php" class="btn btn-primary btn-lg px-5">
            Ver Todas las Propuestas <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Cómo Funciona -->
<div class="container my-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold">¿Cómo Funciona?</h2>
        <p class="text-muted">Participa en 4 simples pasos</p>
    </div>
    
    <div class="row">
        <div class="col-md-3 mb-4 text-center">
            <div class="mb-3">
                <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" 
                     style="width: 80px; height: 80px; font-size: 2rem;">
                    <i class="bi bi-person-plus"></i>
                </div>
            </div>
            <h5 class="fw-bold">1. Regístrate</h5>
            <p class="text-muted">Crea tu cuenta con tu DNI</p>
        </div>
        
        <div class="col-md-3 mb-4 text-center">
            <div class="mb-3">
                <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center" 
                     style="width: 80px; height: 80px; font-size: 2rem;">
                    <i class="bi bi-lightbulb"></i>
                </div>
            </div>
            <h5 class="fw-bold">2. Explora</h5>
            <p class="text-muted">Revisa las propuestas activas</p>
        </div>
        
        <div class="col-md-3 mb-4 text-center">
            <div class="mb-3">
                <div class="rounded-circle bg-warning text-white d-inline-flex align-items-center justify-content-center" 
                     style="width: 80px; height: 80px; font-size: 2rem;">
                    <i class="bi bi-hand-thumbs-up"></i>
                </div>
            </div>
            <h5 class="fw-bold">3. Vota</h5>
            <p class="text-muted">Elige la propuesta que prefieras</p>
        </div>
        
        <div class="col-md-3 mb-4 text-center">
            <div class="mb-3">
                <div class="rounded-circle bg-danger text-white d-inline-flex align-items-center justify-content-center" 
                     style="width: 80px; height: 80px; font-size: 2rem;">
                    <i class="bi bi-trophy"></i>
                </div>
            </div>
            <h5 class="fw-bold">4. Gana Puntos</h5>
            <p class="text-muted">Acumula puntos y desbloquea logros</p>
        </div>
    </div>
</div>

<!-- Call to Action -->
<?php if (!isLoggedIn()): ?>
<div class="container my-5">
    <div class="card bg-primary text-white text-center p-5">
        <h2 class="fw-bold mb-3">¿Listo para hacer la diferencia?</h2>
        <p class="lead mb-4">Únete a miles de ciudadanos que ya están transformando su comunidad</p>
        <div>
            <a href="registro.php" class="btn btn-light btn-lg px-5 me-3">
                <i class="bi bi-person-plus"></i> Crear Cuenta
            </a>
            <a href="login.php" class="btn btn-outline-light btn-lg px-5">
                Ya tengo cuenta
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    MuniOps.updateCounters();
});
</script>

<?php include 'includes/footer.php'; ?>
