<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();
requireAdmin();

$pageTitle = 'Dashboard Admin - MuniOps';

// Estadísticas generales
$totalUsuarios = fetchOne("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'ciudadano'")['total'];
$totalPropuestas = fetchOne("SELECT COUNT(*) as total FROM propuestas")['total'];
$propuestasActivas = fetchOne("SELECT COUNT(*) as total FROM propuestas WHERE estado = 'activa'")['total'];
// Contar votos directos + votos en votaciones
$votosDirectos = fetchOne("SELECT COUNT(*) as total FROM votos")['total'];
$votosVotaciones = fetchOne("SELECT COUNT(*) as total FROM votacion_votos")['total'];
$totalVotos = $votosDirectos + $votosVotaciones;
$totalComentarios = fetchOne("SELECT COUNT(*) as total FROM comentarios")['total'];
$totalVotaciones = fetchOne("SELECT COUNT(*) as total FROM votaciones")['total'];
$votacionesActivas = fetchOne("SELECT COUNT(*) as total FROM votaciones WHERE estado = 'activa'")['total'];

// Propuestas recientes
$propuestasRecientes = fetchAll("SELECT * FROM propuestas ORDER BY fecha_creacion DESC LIMIT 5");

// Usuarios más activos
$usuariosActivos = fetchAll("SELECT * FROM ranking_usuarios LIMIT 5");

include '../includes/header.php';
?>

<div class="container-fluid my-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 mb-4">
            <div class="list-group">
                <a href="dashboard.php" class="list-group-item list-group-item-action active">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="propuestas.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-lightbulb"></i> Propuestas
                </a>
                <a href="votaciones.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-box-arrow-in-right"></i> Votaciones
                </a>
                <a href="usuarios.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-people"></i> Usuarios
                </a>
                <a href="reportes.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-graph-up"></i> Reportes
                </a>
                <?php /* TEMPORALMENTE OCULTO - Funcionalidad en desarrollo
                <a href="configuracion.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-gear"></i> Configuración
                </a>
                */ ?>
                <hr>
                <a href="../index.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-arrow-left"></i> Volver al Sitio
                </a>
            </div>
        </div>
        
        <!-- Contenido Principal -->
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="fw-bold">
                    <i class="bi bi-speedometer2"></i> Dashboard Administrativo
                </h1>
                <a href="propuestas.php?action=create" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nueva Propuesta
                </a>
            </div>
            
            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Usuarios</h6>
                                    <h2 class="fw-bold mb-0"><?php echo number_format($totalUsuarios); ?></h2>
                                </div>
                                <i class="bi bi-people" style="font-size: 3rem; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Propuestas</h6>
                                    <h2 class="fw-bold mb-0"><?php echo number_format($totalPropuestas); ?></h2>
                                    <small><?php echo $propuestasActivas; ?> activas</small>
                                </div>
                                <i class="bi bi-lightbulb" style="font-size: 3rem; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Votos</h6>
                                    <h2 class="fw-bold mb-0"><?php echo number_format($totalVotos); ?></h2>
                                </div>
                                <i class="bi bi-hand-thumbs-up" style="font-size: 3rem; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Comentarios</h6>
                                    <h2 class="fw-bold mb-0"><?php echo number_format($totalComentarios); ?></h2>
                                </div>
                                <i class="bi bi-chat" style="font-size: 3rem; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Segunda fila de estadísticas -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="card bg-gradient-purple text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Votaciones</h6>
                                    <h2 class="fw-bold mb-0"><?php echo number_format($totalVotaciones); ?></h2>
                                    <small><?php echo $votacionesActivas; ?> activas</small>
                                </div>
                                <i class="bi bi-box-arrow-in-right" style="font-size: 3rem; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="card bg-dark text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Acceso Rápido</h6>
                                    <div class="mt-2">
                                        <a href="votaciones.php?action=create" class="btn btn-light btn-sm me-2">
                                            <i class="bi bi-plus"></i> Nueva Votación
                                        </a>
                                        <a href="votaciones.php" class="btn btn-outline-light btn-sm">
                                            <i class="bi bi-list"></i> Ver Votaciones
                                        </a>
                                    </div>
                                </div>
                                <i class="bi bi-lightning" style="font-size: 3rem; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Propuestas Recientes -->
                <div class="col-lg-7 mb-4">
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-clock-history"></i> Propuestas Recientes
                            </h5>
                            <a href="propuestas.php" class="btn btn-sm btn-outline-primary">Ver Todas</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Título</th>
                                            <th>Categoría</th>
                                            <th>Estado</th>
                                            <th>Votos</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($propuestasRecientes as $propuesta): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($propuesta['titulo']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo formatDate($propuesta['fecha_creacion']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge categoria-<?php echo $propuesta['categoria']; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $propuesta['categoria'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $propuesta['estado'] === 'activa' ? 'success' : ($propuesta['estado'] === 'finalizada' ? 'secondary' : 'warning'); ?>">
                                                        <?php echo ucfirst($propuesta['estado']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo number_format($propuesta['total_votos']); ?></td>
                                                <td>
                                                    <a href="propuestas.php?action=edit&id=<?php echo $propuesta['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Usuarios Más Activos -->
                <div class="col-lg-5 mb-4">
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-star"></i> Top Usuarios
                            </h5>
                            <a href="../ranking.php" class="btn btn-sm btn-outline-primary">Ver Ranking</a>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <?php foreach ($usuariosActivos as $usuario): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-primary me-3">
                                                #<?php echo $usuario['posicion']; ?>
                                            </span>
                                            <div class="user-avatar me-3" style="width: 40px; height: 40px; font-size: 1rem;">
                                                <?php echo strtoupper(substr($usuario['nombre_completo'], 0, 2)); ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <strong><?php echo htmlspecialchars($usuario['nombre_completo']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo number_format($usuario['puntos']); ?> pts • 
                                                    <?php echo $usuario['total_votos']; ?> votos
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
