<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$pageTitle = 'Propuestas - MuniOps';

// Filtros
$categoria = $_GET['categoria'] ?? 'todas';
$estado = $_GET['estado'] ?? 'activa';

// Obtener propuestas
$sql = "SELECT * FROM propuestas_estadisticas WHERE 1=1";
$params = [];

// Filtrar por municipio del usuario
$usuario = getUserById(getUserId());
if ($usuario && $usuario['municipio_id']) {
    $sql .= " AND municipio_id = ?";
    $params[] = $usuario['municipio_id'];
}

if ($categoria !== 'todas') {
    $sql .= " AND categoria = ?";
    $params[] = $categoria;
}

if ($estado !== 'todas') {
    $sql .= " AND estado = ?";
    $params[] = $estado;
}

$sql .= " ORDER BY fecha_inicio DESC";

$propuestas = fetchAll($sql, $params);

// Obtener categorías
$categorias = [
    'infraestructura' => 'Infraestructura',
    'salud' => 'Salud',
    'educacion' => 'Educación',
    'seguridad' => 'Seguridad',
    'medio_ambiente' => 'Medio Ambiente',
    'deporte' => 'Deporte',
    'cultura' => 'Cultura',
    'otros' => 'Otros'
];

include 'includes/header.php';
?>

<div class="container my-5">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="fw-bold">
                <i class="bi bi-lightbulb"></i> Propuestas Ciudadanas
            </h1>
            <p class="text-muted">Vota por las propuestas que transformarán tu comunidad</p>
            <?php if ($usuario && $usuario['municipio_id']): ?>
                <?php $muni = getMunicipioById($usuario['municipio_id']); ?>
                <div class="alert alert-info mt-2 mb-0" style="max-width: 400px;">
                    <i class="bi bi-info-circle"></i> Viendo propuestas de: <strong><?php echo htmlspecialchars($muni['nombre']); ?></strong>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-md-4 text-end">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="mb-0">Tus Puntos</h5>
                    <h2 class="fw-bold mb-0"><?php echo getUserPoints(getUserId()); ?></h2>
                    <small>Posición: #<?php echo getUserRank(getUserId()) ?? 'N/A'; ?></small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        <i class="bi bi-funnel"></i> Categoría
                    </label>
                    <select class="form-select" id="filtroCategoria" onchange="aplicarFiltros()">
                        <option value="todas" <?php echo $categoria === 'todas' ? 'selected' : ''; ?>>Todas las categorías</option>
                        <?php foreach ($categorias as $key => $nombre): ?>
                            <option value="<?php echo $key; ?>" <?php echo $categoria === $key ? 'selected' : ''; ?>>
                                <?php echo $nombre; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        <i class="bi bi-filter"></i> Estado
                    </label>
                    <select class="form-select" id="filtroEstado" onchange="aplicarFiltros()">
                        <option value="activa" <?php echo $estado === 'activa' ? 'selected' : ''; ?>>Activas</option>
                        <option value="finalizada" <?php echo $estado === 'finalizada' ? 'selected' : ''; ?>>Finalizadas</option>
                        <option value="todas" <?php echo $estado === 'todas' ? 'selected' : ''; ?>>Todas</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Propuestas -->
    <?php if (empty($propuestas)): ?>
        <div class="alert alert-info text-center py-5">
            <i class="bi bi-info-circle" style="font-size: 3rem;"></i>
            <h4 class="mt-3">No hay propuestas disponibles</h4>
            <p class="text-muted">Por el momento no hay propuestas con los filtros seleccionados</p>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($propuestas as $propuesta): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card propuesta-card h-100">
                    <?php if ($propuesta['imagen']): ?>
                        <img src="uploads/<?php echo $propuesta['imagen']; ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlspecialchars($propuesta['titulo']); ?>" 
                             style="height: 200px; object-fit: cover;">
                    <?php else: ?>
                        <div class="card-img-top bg-gradient d-flex align-items-center justify-content-center" 
                             style="height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="bi bi-lightbulb text-white" style="font-size: 4rem;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-body d-flex flex-column">
                        <div class="mb-2">
                            <span class="badge categoria-<?php echo $propuesta['categoria']; ?>">
                                <?php echo $categorias[$propuesta['categoria']] ?? 'Otros'; ?>
                            </span>
                            
                            <?php if ($propuesta['estado'] === 'activa'): ?>
                                <span class="badge bg-success">
                                    <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> Activa
                                </span>
                            <?php elseif ($propuesta['estado'] === 'finalizada'): ?>
                                <span class="badge bg-secondary">Finalizada</span>
                            <?php endif; ?>
                        </div>
                        
                        <h5 class="card-title"><?php echo htmlspecialchars($propuesta['titulo']); ?></h5>
                        <p class="card-text text-muted flex-grow-1">
                            <?php echo substr(htmlspecialchars($propuesta['descripcion']), 0, 120) . '...'; ?>
                        </p>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">
                                    <i class="bi bi-check-circle"></i> <?php echo number_format($propuesta['total_votos']); ?> votos
                                </small>
                                <?php if ($propuesta['dias_restantes'] > 0): ?>
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> <?php echo $propuesta['dias_restantes']; ?> días
                                    </small>
                                <?php else: ?>
                                    <small class="text-danger">
                                        <i class="bi bi-exclamation-circle"></i> Finalizada
                                    </small>
                                <?php endif; ?>
                            </div>
                            <div class="progress propuesta-progress">
                                <div class="progress-bar bg-success" 
                                     role="progressbar" 
                                     style="width: <?php echo min(100, ($propuesta['total_votos'] / 100) * 100); ?>%">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">
                                <i class="bi bi-chat"></i> <?php echo $propuesta['total_comentarios']; ?> comentarios
                            </small>
                            <small class="text-muted ms-2">
                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($propuesta['creado_por_nombre']); ?>
                            </small>
                        </div>
                        
                        <div class="mt-auto">
                            <?php if ($propuesta['estado'] === 'activa' && $propuesta['dias_restantes'] > 0): ?>
                                <?php if (hasVoted(getUserId(), $propuesta['id'])): ?>
                                    <button class="btn btn-secondary w-100" disabled>
                                        <i class="bi bi-check2-circle"></i> Ya Votaste
                                    </button>
                                <?php else: ?>
                                    <a href="propuesta-detalle.php?id=<?php echo $propuesta['id']; ?>" 
                                       class="btn btn-vote w-100">
                                        <i class="bi bi-hand-thumbs-up"></i> Votar Ahora
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="propuesta-detalle.php?id=<?php echo $propuesta['id']; ?>" 
                                   class="btn btn-outline-primary w-100">
                                    <i class="bi bi-eye"></i> Ver Detalles
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function aplicarFiltros() {
    const categoria = document.getElementById('filtroCategoria').value;
    const estado = document.getElementById('filtroEstado').value;
    
    window.location.href = `propuestas.php?categoria=${categoria}&estado=${estado}`;
}
</script>

<?php include 'includes/footer.php'; ?>
