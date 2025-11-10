<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$pageTitle = 'Recompensas - MuniOps';

$recompensas = getRecompensas();
$misRecompensas = getUserRecompensas(getUserId());
$misPuntos = getUserPoints(getUserId());

// Crear array de IDs de recompensas obtenidas
$recompensasObtenidas = array_column($misRecompensas, 'id');

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="text-center mb-5">
        <h1 class="fw-bold">
            <i class="bi bi-award text-warning"></i> Recompensas y Logros
        </h1>
        <p class="lead text-muted">Desbloquea logros participando activamente</p>
        
        <div class="card bg-primary text-white d-inline-block mt-3">
            <div class="card-body">
                <h3 class="mb-0">
                    <i class="bi bi-stars"></i> <?php echo number_format($misPuntos); ?> Puntos
                </h3>
                <small>Has desbloqueado <?php echo count($misRecompensas); ?> de <?php echo count($recompensas); ?> logros</small>
            </div>
        </div>
    </div>
    
    <!-- Progreso general -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <span class="fw-bold">Progreso de Logros</span>
                <span class="text-muted">
                    <?php echo count($misRecompensas); ?> / <?php echo count($recompensas); ?>
                </span>
            </div>
            <div class="progress" style="height: 25px;">
                <div class="progress-bar bg-success" 
                     role="progressbar" 
                     style="width: <?php echo count($recompensas) > 0 ? (count($misRecompensas) / count($recompensas)) * 100 : 0; ?>%">
                    <?php echo count($recompensas) > 0 ? round((count($misRecompensas) / count($recompensas)) * 100) : 0; ?>%
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtros por tipo -->
    <ul class="nav nav-pills mb-4 justify-content-center" id="tipoRecompensaTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="todas-tab" data-bs-toggle="pill" data-bs-target="#todas" type="button">
                <i class="bi bi-grid"></i> Todas
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="medallas-tab" data-bs-toggle="pill" data-bs-target="#medallas" type="button">
                <i class="bi bi-award"></i> Medallas
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="niveles-tab" data-bs-toggle="pill" data-bs-target="#niveles" type="button">
                <i class="bi bi-trophy"></i> Niveles
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="insignias-tab" data-bs-toggle="pill" data-bs-target="#insignias" type="button">
                <i class="bi bi-patch-check"></i> Insignias
            </button>
        </li>
    </ul>
    
    <!-- Contenido de tabs -->
    <div class="tab-content" id="tipoRecompensaTabsContent">
        <!-- Todas -->
        <div class="tab-pane fade show active" id="todas" role="tabpanel">
            <div class="row">
                <?php foreach ($recompensas as $recompensa): ?>
                    <?php
                    $obtenida = in_array($recompensa['id'], $recompensasObtenidas);
                    $bloqueada = $misPuntos < $recompensa['puntos_requeridos'];
                    ?>
                    <div class="col-md-4 col-lg-3 mb-4">
                        <div class="card recompensa-card h-100 <?php echo $obtenida ? 'recompensa-unlocked border-success' : 'recompensa-locked'; ?>">
                            <div class="card-body text-center">
                                <?php if ($obtenida): ?>
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <i class="bi bi-check-circle-fill text-success fs-4"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="recompensa-icon">
                                    <?php
                                    $iconos = [
                                        'medalla' => 'bi-award',
                                        'nivel' => 'bi-trophy',
                                        'insignia' => 'bi-patch-check'
                                    ];
                                    $color = $obtenida ? 'text-warning' : 'text-muted';
                                    $icono = $iconos[$recompensa['tipo']] ?? 'bi-star';
                                    ?>
                                    <i class="bi <?php echo $icono; ?> <?php echo $color; ?>"></i>
                                </div>
                                
                                <h5 class="fw-bold"><?php echo htmlspecialchars($recompensa['nombre']); ?></h5>
                                <p class="text-muted small"><?php echo htmlspecialchars($recompensa['descripcion']); ?></p>
                                
                                <?php if ($obtenida): ?>
                                    <span class="badge bg-success w-100">
                                        <i class="bi bi-unlock"></i> Desbloqueado
                                    </span>
                                <?php elseif ($bloqueada): ?>
                                    <span class="badge bg-secondary w-100 mb-2">
                                        <i class="bi bi-lock"></i> <?php echo number_format($recompensa['puntos_requeridos']); ?> pts
                                    </span>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar" 
                                             style="width: <?php echo min(100, ($misPuntos / $recompensa['puntos_requeridos']) * 100); ?>%">
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        Te faltan <?php echo number_format($recompensa['puntos_requeridos'] - $misPuntos); ?> pts
                                    </small>
                                <?php else: ?>
                                    <span class="badge bg-warning w-100">
                                        <i class="bi bi-hourglass"></i> Próximamente
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Medallas -->
        <div class="tab-pane fade" id="medallas" role="tabpanel">
            <div class="row">
                <?php foreach ($recompensas as $recompensa): ?>
                    <?php if ($recompensa['tipo'] === 'medalla'): ?>
                        <?php
                        $obtenida = in_array($recompensa['id'], $recompensasObtenidas);
                        $bloqueada = $misPuntos < $recompensa['puntos_requeridos'];
                        ?>
                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="card recompensa-card h-100 <?php echo $obtenida ? 'recompensa-unlocked border-success' : 'recompensa-locked'; ?>">
                                <div class="card-body text-center">
                                    <?php if ($obtenida): ?>
                                        <div class="position-absolute top-0 end-0 m-2">
                                            <i class="bi bi-check-circle-fill text-success fs-4"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="recompensa-icon">
                                        <i class="bi bi-award <?php echo $obtenida ? 'text-warning' : 'text-muted'; ?>"></i>
                                    </div>
                                    
                                    <h5 class="fw-bold"><?php echo htmlspecialchars($recompensa['nombre']); ?></h5>
                                    <p class="text-muted small"><?php echo htmlspecialchars($recompensa['descripcion']); ?></p>
                                    
                                    <?php if ($obtenida): ?>
                                        <span class="badge bg-success w-100">Desbloqueado</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary w-100"><?php echo number_format($recompensa['puntos_requeridos']); ?> pts</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Niveles -->
        <div class="tab-pane fade" id="niveles" role="tabpanel">
            <div class="row">
                <?php foreach ($recompensas as $recompensa): ?>
                    <?php if ($recompensa['tipo'] === 'nivel'): ?>
                        <?php
                        $obtenida = in_array($recompensa['id'], $recompensasObtenidas);
                        ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card recompensa-card h-100 <?php echo $obtenida ? 'recompensa-unlocked border-warning' : 'recompensa-locked'; ?>">
                                <div class="card-body text-center">
                                    <?php if ($obtenida): ?>
                                        <div class="position-absolute top-0 end-0 m-2">
                                            <i class="bi bi-check-circle-fill text-warning fs-4"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="recompensa-icon">
                                        <i class="bi bi-trophy <?php echo $obtenida ? 'text-warning' : 'text-muted'; ?>"></i>
                                    </div>
                                    
                                    <h5 class="fw-bold"><?php echo htmlspecialchars($recompensa['nombre']); ?></h5>
                                    <p class="text-muted small"><?php echo htmlspecialchars($recompensa['descripcion']); ?></p>
                                    
                                    <?php if ($obtenida): ?>
                                        <span class="badge bg-warning text-dark w-100">Nivel Alcanzado</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary w-100 mb-2"><?php echo number_format($recompensa['puntos_requeridos']); ?> pts requeridos</span>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-warning" 
                                                 style="width: <?php echo min(100, ($misPuntos / $recompensa['puntos_requeridos']) * 100); ?>%">
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Insignias -->
        <div class="tab-pane fade" id="insignias" role="tabpanel">
            <div class="row">
                <?php foreach ($recompensas as $recompensa): ?>
                    <?php if ($recompensa['tipo'] === 'insignia'): ?>
                        <?php
                        $obtenida = in_array($recompensa['id'], $recompensasObtenidas);
                        ?>
                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="card recompensa-card h-100 <?php echo $obtenida ? 'recompensa-unlocked border-info' : 'recompensa-locked'; ?>">
                                <div class="card-body text-center">
                                    <?php if ($obtenida): ?>
                                        <div class="position-absolute top-0 end-0 m-2">
                                            <i class="bi bi-check-circle-fill text-info fs-4"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="recompensa-icon">
                                        <i class="bi bi-patch-check <?php echo $obtenida ? 'text-info' : 'text-muted'; ?>"></i>
                                    </div>
                                    
                                    <h5 class="fw-bold"><?php echo htmlspecialchars($recompensa['nombre']); ?></h5>
                                    <p class="text-muted small"><?php echo htmlspecialchars($recompensa['descripcion']); ?></p>
                                    
                                    <?php if ($obtenida): ?>
                                        <span class="badge bg-info w-100">Obtenida</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary w-100"><?php echo number_format($recompensa['puntos_requeridos']); ?> pts</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Call to action -->
    <div class="card bg-light mt-5">
        <div class="card-body text-center py-5">
            <h3 class="fw-bold mb-3">¡Sigue Participando!</h3>
            <p class="text-muted mb-4">Vota, comenta y participa para desbloquear más logros</p>
            <a href="propuestas.php" class="btn btn-primary btn-lg px-5">
                <i class="bi bi-lightbulb"></i> Ver Propuestas
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
