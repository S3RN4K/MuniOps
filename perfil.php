<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$pageTitle = 'Mi Perfil - MuniOps';

$usuario = getUserById(getUserId());
$misPuntos = getUserPoints(getUserId());
$miPosicion = getUserRank(getUserId());
$misRecompensas = getUserRecompensas(getUserId());
$historialPuntos = getHistorialPuntos(getUserId(), 10);

// Obtener estadísticas
$totalVotos = fetchOne("SELECT COUNT(*) as total FROM votos WHERE usuario_id = ?", [getUserId()])['total'];
$totalComentarios = fetchOne("SELECT COUNT(*) as total FROM comentarios WHERE usuario_id = ?", [getUserId()])['total'];

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="user-avatar mx-auto mb-3" style="width: 100px; height: 100px; font-size: 2.5rem;">
                        <?php echo strtoupper(substr($usuario['nombres'], 0, 2)); ?>
                    </div>
                    <h4 class="fw-bold mb-1">
                        <?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellido_paterno']); ?>
                    </h4>
                    <p class="text-muted mb-3">DNI: <?php echo $usuario['dni']; ?></p>
                    
                    <div class="card bg-primary text-white mb-3">
                        <div class="card-body">
                            <h2 class="fw-bold mb-0"><?php echo number_format($misPuntos); ?></h2>
                            <small>Puntos Totales</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <span class="badge bg-warning text-dark fs-6">
                            <i class="bi bi-trophy"></i> Posición #<?php echo $miPosicion ?? 'N/A'; ?>
                        </span>
                    </div>
                    
                    <hr>
                    
                    <ul class="list-unstyled text-start mb-0">
                        <li class="mb-2">
                            <i class="bi bi-envelope text-primary"></i>
                            <strong class="ms-2">Email:</strong><br>
                            <span class="ms-4"><?php echo htmlspecialchars($usuario['email']); ?></span>
                        </li>
                        <?php if ($usuario['telefono']): ?>
                        <li class="mb-2">
                            <i class="bi bi-telephone text-success"></i>
                            <strong class="ms-2">Teléfono:</strong><br>
                            <span class="ms-4"><?php echo htmlspecialchars($usuario['telefono']); ?></span>
                        </li>
                        <?php endif; ?>
                        <li class="mb-2">
                            <i class="bi bi-calendar text-info"></i>
                            <strong class="ms-2">Miembro desde:</strong><br>
                            <span class="ms-4"><?php echo formatDate($usuario['fecha_registro']); ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Contenido Principal -->
        <div class="col-md-8">
            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <i class="bi bi-hand-thumbs-up text-success" style="font-size: 2.5rem;"></i>
                            <h3 class="fw-bold mt-2 mb-0"><?php echo $totalVotos; ?></h3>
                            <small class="text-muted">Votos Emitidos</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <i class="bi bi-chat text-primary" style="font-size: 2.5rem;"></i>
                            <h3 class="fw-bold mt-2 mb-0"><?php echo $totalComentarios; ?></h3>
                            <small class="text-muted">Comentarios</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <i class="bi bi-award text-warning" style="font-size: 2.5rem;"></i>
                            <h3 class="fw-bold mt-2 mb-0"><?php echo count($misRecompensas); ?></h3>
                            <small class="text-muted">Logros</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Historial de Puntos -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history"></i> Historial de Puntos
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($historialPuntos)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-2">No hay actividad aún</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($historialPuntos as $registro): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                <?php
                                                $iconos = [
                                                    'voto' => '<i class="bi bi-hand-thumbs-up text-success"></i>',
                                                    'comentario' => '<i class="bi bi-chat text-primary"></i>',
                                                    'like_recibido' => '<i class="bi bi-heart text-danger"></i>',
                                                    'bono' => '<i class="bi bi-gift text-warning"></i>'
                                                ];
                                                echo $iconos[$registro['tipo_accion']] ?? '<i class="bi bi-star"></i>';
                                                ?>
                                                <span class="ms-2"><?php echo htmlspecialchars($registro['descripcion']); ?></span>
                                            </h6>
                                            <small class="text-muted"><?php echo timeAgo($registro['fecha_accion']); ?></small>
                                        </div>
                                        <span class="badge bg-<?php echo $registro['puntos'] > 0 ? 'success' : 'danger'; ?> fs-6">
                                            <?php echo $registro['puntos'] > 0 ? '+' : ''; ?><?php echo $registro['puntos']; ?> pts
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Últimas Recompensas -->
            <?php if (!empty($misRecompensas)): ?>
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-trophy"></i> Últimos Logros Desbloqueados
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach (array_slice($misRecompensas, 0, 4) as $recompensa): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <i class="bi bi-<?php echo $recompensa['tipo'] === 'medalla' ? 'award' : ($recompensa['tipo'] === 'nivel' ? 'trophy' : 'patch-check'); ?> text-warning" style="font-size: 2.5rem;"></i>
                                        <h6 class="fw-bold mt-2 mb-1"><?php echo htmlspecialchars($recompensa['nombre']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($recompensa['descripcion']); ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="recompensas.php" class="btn btn-outline-primary">
                            Ver Todas las Recompensas <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
