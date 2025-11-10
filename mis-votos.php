<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$pageTitle = 'Mis Votos - MuniOps';

// Obtener votos del usuario
$sql = "SELECT v.*, p.titulo, p.descripcion, p.categoria, p.estado, p.imagen,
        (SELECT COUNT(*) FROM votos WHERE propuesta_id = p.id) as total_votos
        FROM votos v
        JOIN propuestas p ON v.propuesta_id = p.id
        WHERE v.usuario_id = ?
        ORDER BY v.fecha_voto DESC";

$misVotos = fetchAll($sql, [getUserId()]);

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="mb-4">
        <h1 class="fw-bold">
            <i class="bi bi-check-circle"></i> Mis Votos
        </h1>
        <p class="text-muted">Propuestas en las que has participado</p>
    </div>
    
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <h2 class="fw-bold mb-0"><?php echo count($misVotos); ?></h2>
                            <small>Total de Votos</small>
                        </div>
                        <div class="col-md-4">
                            <h2 class="fw-bold mb-0"><?php echo count($misVotos) * PUNTOS_VOTO; ?></h2>
                            <small>Puntos Ganados por Votar</small>
                        </div>
                        <div class="col-md-4">
                            <h2 class="fw-bold mb-0"><?php echo getUserPoints(getUserId()); ?></h2>
                            <small>Puntos Totales</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (empty($misVotos)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox text-muted" style="font-size: 5rem;"></i>
                <h3 class="mt-4 mb-3">No has votado aún</h3>
                <p class="text-muted mb-4">Comienza a participar votando en las propuestas activas</p>
                <a href="propuestas.php" class="btn btn-primary btn-lg px-5">
                    <i class="bi bi-lightbulb"></i> Ver Propuestas
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($misVotos as $voto): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <?php if ($voto['imagen']): ?>
                            <img src="uploads/<?php echo $voto['imagen']; ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($voto['titulo']); ?>"
                                 style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-gradient d-flex align-items-center justify-content-center" 
                                 style="height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <i class="bi bi-lightbulb text-white" style="font-size: 4rem;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <div class="mb-2">
                                <span class="badge categoria-<?php echo $voto['categoria']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $voto['categoria'])); ?>
                                </span>
                                <span class="badge bg-<?php echo $voto['estado'] === 'activa' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($voto['estado']); ?>
                                </span>
                            </div>
                            
                            <h5 class="card-title"><?php echo htmlspecialchars($voto['titulo']); ?></h5>
                            <p class="card-text text-muted">
                                <?php echo substr(htmlspecialchars($voto['descripcion']), 0, 100) . '...'; ?>
                            </p>
                            
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="bi bi-calendar"></i> Votaste el <?php echo formatDate($voto['fecha_voto']); ?>
                                </small>
                                <br>
                                <small class="text-muted">
                                    <i class="bi bi-check-circle"></i> <?php echo number_format($voto['total_votos']); ?> votos totales
                                </small>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <a href="propuesta-detalle.php?id=<?php echo $voto['propuesta_id']; ?>" 
                                   class="btn btn-outline-primary flex-grow-1">
                                    <i class="bi bi-eye"></i> Ver Propuesta
                                </a>
                                <button class="btn btn-success" disabled>
                                    <i class="bi bi-check2"></i> Votado
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
