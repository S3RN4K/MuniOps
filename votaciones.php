<?php
ob_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$pageTitle = 'Votaciones Activas - MuniOps';

// Obtener municipio del usuario
$user = getUserById(getUserId());
$municipioId = $user['municipio_id'] ?? null;

if (!$municipioId) {
    setFlashMessage('Debes tener un municipio asignado para participar en votaciones', 'warning');
    redirect('perfil.php');
}

// Obtener votaciones activas del municipio del usuario
$votacionesActivas = getActiveVotaciones($municipioId);

// Procesar voto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'votar') {
    $votacionId = $_POST['votacion_id'] ?? 0;
    $propuestaId = $_POST['propuesta_id'] ?? 0;
    
    if ($votacionId && $propuestaId) {
        $result = registerVotoVotacion($votacionId, $propuestaId, getUserId());
        setFlashMessage($result['message'], $result['success'] ? 'success' : 'danger');
        redirect('votaciones.php');
    }
}

// Ver detalles de votación específica
$votacionDetalle = null;
$propuestasVotacion = [];
$yaVoto = false;

if (isset($_GET['id'])) {
    $votacionId = $_GET['id'];
    $votacionDetalle = getVotacionWithStats($votacionId);
    
    if ($votacionDetalle && $votacionDetalle['municipio_id'] == $municipioId) {
        $propuestasVotacion = getPropuestasDeVotacion($votacionId);
        $yaVoto = hasVotedInVotacion(getUserId(), $votacionId);
    } else {
        setFlashMessage('Votación no encontrada o no pertenece a tu municipio', 'danger');
        redirect('votaciones.php');
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <?php if (!$votacionDetalle): ?>
        <!-- Lista de votaciones activas -->
        <div class="text-center mb-5">
            <h1 class="fw-bold display-4">
                <i class="bi bi-box-arrow-in-right text-primary"></i>
                Votaciones Activas
            </h1>
            <p class="lead text-muted">Elige la propuesta que crees que debe implementarse en tu municipio</p>
        </div>

        <?php if (empty($votacionesActivas)): ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card text-center py-5">
                        <div class="card-body">
                            <i class="bi bi-inbox display-1 text-muted mb-3"></i>
                            <h3 class="text-muted">No hay votaciones activas</h3>
                            <p class="text-muted">Por el momento no hay votaciones disponibles para tu municipio.</p>
                            <a href="propuestas.php" class="btn btn-primary mt-3">
                                <i class="bi bi-lightbulb"></i> Ver Propuestas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($votacionesActivas as $votacion): ?>
                    <?php
                    $yaVotoEsta = hasVotedInVotacion(getUserId(), $votacion['id']);
                    ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 shadow-sm hover-card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-megaphone"></i> <?php echo htmlspecialchars($votacion['titulo']); ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ($votacion['descripcion']): ?>
                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($votacion['descripcion'])); ?></p>
                                <?php endif; ?>
                                
                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <div class="stat-box">
                                            <h4 class="text-primary mb-0"><?php echo $votacion['total_propuestas']; ?></h4>
                                            <small class="text-muted">Propuestas</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="stat-box">
                                            <h4 class="text-success mb-0"><?php echo number_format($votacion['total_votos']); ?></h4>
                                            <small class="text-muted">Votos</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="stat-box">
                                            <h4 class="text-warning mb-0"><?php echo $votacion['dias_restantes']; ?></h4>
                                            <small class="text-muted">Días restantes</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($yaVotoEsta): ?>
                                    <div class="alert alert-success alert-permanent mb-3">
                                        <i class="bi bi-check-circle-fill"></i> Ya has votado en esta votación
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <small class="text-muted d-block">
                                        <i class="bi bi-calendar-check"></i> <strong>Inicio:</strong> <?php echo formatDate($votacion['fecha_inicio']); ?>
                                    </small>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-calendar-x"></i> <strong>Fin:</strong> <?php echo formatDate($votacion['fecha_fin']); ?>
                                    </small>
                                </div>
                                
                                <div class="d-flex justify-content-end">
                                    <a href="votaciones.php?id=<?php echo $votacion['id']; ?>" class="btn btn-primary">
                                        <?php echo $yaVotoEsta ? 'Ver Resultados' : 'Votar Ahora'; ?>
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- Detalle de votación específica -->
        <div class="mb-4">
            <a href="votaciones.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver a Votaciones
            </a>
        </div>
        
        <div class="card mb-4">
            <div class="card-body">
                <h1 class="fw-bold mb-3"><?php echo htmlspecialchars($votacionDetalle['titulo']); ?></h1>
                
                <?php if ($votacionDetalle['descripcion']): ?>
                    <p class="lead"><?php echo nl2br(htmlspecialchars($votacionDetalle['descripcion'])); ?></p>
                <?php endif; ?>
                
                <div class="alert alert-info alert-permanent mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <i class="bi bi-calendar-check"></i> <strong>Fecha de inicio:</strong> 
                            <?php echo date('d/m/Y H:i', strtotime($votacionDetalle['fecha_inicio'])); ?>
                        </div>
                        <div class="col-md-6">
                            <i class="bi bi-calendar-x"></i> <strong>Fecha de fin:</strong> 
                            <?php echo date('d/m/Y H:i', strtotime($votacionDetalle['fecha_fin'])); ?>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="stat-card text-center p-3 bg-light rounded">
                            <i class="bi bi-people-fill text-primary fs-3"></i>
                            <h3 class="mt-2 mb-0"><?php echo number_format($votacionDetalle['total_votos']); ?></h3>
                            <small class="text-muted">Total de Votos</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center p-3 bg-light rounded">
                            <i class="bi bi-list-check text-success fs-3"></i>
                            <h3 class="mt-2 mb-0"><?php echo $votacionDetalle['total_propuestas']; ?></h3>
                            <small class="text-muted">Propuestas</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center p-3 bg-light rounded">
                            <i class="bi bi-calendar-event text-warning fs-3"></i>
                            <h3 class="mt-2 mb-0"><?php echo $votacionDetalle['dias_restantes']; ?></h3>
                            <small class="text-muted">Días Restantes</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center p-3 bg-light rounded">
                            <i class="bi bi-<?php echo $yaVoto ? 'check-circle-fill text-success' : 'x-circle text-muted'; ?> fs-3"></i>
                            <h3 class="mt-2 mb-0"><?php echo $yaVoto ? 'Sí' : 'No'; ?></h3>
                            <small class="text-muted">Has Votado</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($yaVoto): ?>
            <div class="alert alert-info alert-permanent">
                <i class="bi bi-info-circle"></i>
                <strong>Ya has votado en esta votación.</strong> Puedes ver los resultados parciales a continuación.
            </div>
        <?php else: ?>
            <div class="alert alert-warning alert-permanent">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>Selecciona una propuesta para votar.</strong> Solo puedes votar una vez por votación.
            </div>
        <?php endif; ?>
        
        <h3 class="fw-bold mb-4">Propuestas en esta Votación</h3>
        
        <div class="row">
            <?php foreach ($propuestasVotacion as $index => $prop): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 propuesta-votacion <?php echo $prop['es_ganadora'] && $votacionDetalle['estado'] === 'finalizada' ? 'border-success border-3' : ''; ?>">
                        <?php if ($prop['imagen']): ?>
                            <img src="uploads/<?php echo $prop['imagen']; ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($prop['titulo']); ?>"
                                 style="height: 250px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 250px;">
                                <i class="bi bi-image text-muted" style="font-size: 4rem;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body d-flex flex-column">
                            <?php if ($prop['es_ganadora'] && $votacionDetalle['estado'] === 'finalizada'): ?>
                                <div class="alert alert-success py-2 px-3 mb-3">
                                    <i class="bi bi-trophy-fill"></i> <strong>PROPUESTA GANADORA</strong>
                                </div>
                            <?php endif; ?>
                            
                            <span class="badge categoria-<?php echo $prop['categoria']; ?> mb-2 align-self-start">
                                <?php echo ucfirst(str_replace('_', ' ', $prop['categoria'])); ?>
                            </span>
                            
                            <h5 class="card-title"><?php echo htmlspecialchars($prop['titulo']); ?></h5>
                            <p class="card-text flex-grow-1"><?php echo substr(htmlspecialchars($prop['descripcion']), 0, 200); ?>...</p>
                            
                            <?php if ($prop['presupuesto_estimado']): ?>
                                <p class="text-muted mb-2">
                                    <i class="bi bi-cash"></i> 
                                    <strong>Presupuesto:</strong> S/. <?php echo number_format($prop['presupuesto_estimado'], 2); ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($yaVoto || $votacionDetalle['estado'] === 'finalizada'): ?>
                                <!-- Mostrar resultados -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-bold"><?php echo number_format($prop['votos_recibidos']); ?> votos</span>
                                        <span class="text-muted"><?php echo number_format($prop['porcentaje'], 1); ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar <?php echo $prop['es_ganadora'] ? 'bg-success' : 'bg-primary'; ?>" 
                                             role="progressbar" 
                                             style="width: <?php echo $prop['porcentaje']; ?>%"
                                             aria-valuenow="<?php echo $prop['porcentaje']; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?php echo number_format($prop['porcentaje'], 1); ?>%
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-auto">
                                <a href="propuesta-detalle.php?id=<?php echo $prop['propuesta_id']; ?>" 
                                   class="btn btn-outline-primary btn-sm w-100 mb-2">
                                    <i class="bi bi-eye"></i> Ver Detalles
                                </a>
                                
                                <?php if (!$yaVoto && $votacionDetalle['estado'] === 'activa'): ?>
                                    <form method="POST" onsubmit="return confirm('¿Confirmas tu voto por esta propuesta? No podrás cambiar tu decisión.');">
                                        <input type="hidden" name="action" value="votar">
                                        <input type="hidden" name="votacion_id" value="<?php echo $votacionDetalle['id']; ?>">
                                        <input type="hidden" name="propuesta_id" value="<?php echo $prop['propuesta_id']; ?>">
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="bi bi-hand-thumbs-up-fill"></i> Votar por esta
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<style>
.hover-card {
    transition: all 0.3s ease;
}

.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
}

.stat-box {
    padding: 10px;
}

.stat-card {
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: scale(1.05);
}

.propuesta-votacion {
    transition: all 0.3s ease;
}

.propuesta-votacion:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.2);
}
</style>
