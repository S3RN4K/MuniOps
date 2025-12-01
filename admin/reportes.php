<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();
requireAdmin();

$pageTitle = 'Reportes - MuniOps';

// Estadísticas generales
$votosDirectos = fetchOne("SELECT COUNT(*) as total FROM votos")['total'];
$votosVotaciones = fetchOne("SELECT COUNT(*) as total FROM votacion_votos")['total'];
$stats = [
    'total_usuarios' => fetchOne("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'ciudadano'")['total'],
    'total_propuestas' => fetchOne("SELECT COUNT(*) as total FROM propuestas")['total'],
    'total_votos' => $votosDirectos + $votosVotaciones,
    'total_comentarios' => fetchOne("SELECT COUNT(*) as total FROM comentarios")['total'],
];

// Propuestas más votadas (usar vista que calcula votos correctamente)
$propuestasMasVotadas = fetchAll("SELECT * FROM propuestas_estadisticas ORDER BY total_votos DESC LIMIT 10");

// Usuarios más activos
$usuariosMasActivos = fetchAll("SELECT * FROM ranking_usuarios LIMIT 10");

// Actividad por categoría
$actividadPorCategoria = fetchAll("
    SELECT 
        p.categoria,
        COUNT(*) as total_propuestas,
        SUM(
            (SELECT COUNT(*) FROM votos WHERE votos.propuesta_id = p.id) + 
            (SELECT COUNT(*) FROM votacion_votos WHERE votacion_votos.propuesta_id = p.id)
        ) as total_votos
    FROM propuestas p
    GROUP BY p.categoria
    ORDER BY total_votos DESC
");

include '../includes/header.php';
?>

<div class="container-fluid my-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 mb-4">
            <div class="list-group">
                <a href="dashboard.php" class="list-group-item list-group-item-action">
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
                <a href="reportes.php" class="list-group-item list-group-item-action active">
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
            <h1 class="fw-bold mb-4">
                <i class="bi bi-graph-up"></i> Reportes y Estadísticas
            </h1>
            
            <!-- Resumen General -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-people" style="font-size: 2rem;"></i>
                            <h2 class="fw-bold mt-2"><?php echo number_format($stats['total_usuarios']); ?></h2>
                            <small>Ciudadanos Registrados</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-lightbulb" style="font-size: 2rem;"></i>
                            <h2 class="fw-bold mt-2"><?php echo number_format($stats['total_propuestas']); ?></h2>
                            <small>Propuestas Creadas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-hand-thumbs-up" style="font-size: 2rem;"></i>
                            <h2 class="fw-bold mt-2"><?php echo number_format($stats['total_votos']); ?></h2>
                            <small>Votos Emitidos</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-chat" style="font-size: 2rem;"></i>
                            <h2 class="fw-bold mt-2"><?php echo number_format($stats['total_comentarios']); ?></h2>
                            <small>Comentarios</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Propuestas Más Votadas -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-trophy"></i> Propuestas Más Votadas
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Propuesta</th>
                                            <th>Votos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($propuestasMasVotadas as $index => $p): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($p['titulo']); ?></strong>
                                                    <br>
                                                    <span class="badge categoria-<?php echo $p['categoria']; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $p['categoria'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong class="text-success"><?php echo number_format($p['total_votos']); ?></strong>
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
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-star"></i> Usuarios Más Activos
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Usuario</th>
                                            <th>Puntos</th>
                                            <th>Actividad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuariosMasActivos as $u): ?>
                                            <tr>
                                                <td><?php echo $u['posicion']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($u['nombre_completo']); ?></strong>
                                                </td>
                                                <td>
                                                    <strong class="text-primary"><?php echo number_format($u['puntos']); ?></strong>
                                                </td>
                                                <td>
                                                    <small>
                                                        <?php echo $u['total_votos']; ?> votos<br>
                                                        <?php echo $u['total_comentarios']; ?> comentarios
                                                    </small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actividad por Categoría -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-pie-chart"></i> Actividad por Categoría
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Categoría</th>
                                    <th>Propuestas</th>
                                    <th>Votos Totales</th>
                                    <th>Promedio Votos/Propuesta</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($actividadPorCategoria as $cat): ?>
                                    <tr>
                                        <td>
                                            <span class="badge categoria-<?php echo $cat['categoria']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $cat['categoria'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($cat['total_propuestas']); ?></td>
                                        <td><?php echo number_format($cat['total_votos']); ?></td>
                                        <td>
                                            <?php echo number_format($cat['total_votos'] / $cat['total_propuestas'], 1); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
