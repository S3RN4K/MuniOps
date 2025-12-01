<?php
ob_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();
requireAdmin();

$propuestaId = $_GET['id'] ?? 0;
$propuesta = getPropuestaById($propuestaId);

if (!$propuesta || $propuesta['es_ganadora'] != 1) {
    setFlashMessage('Propuesta no encontrada o no es una propuesta ganadora', 'danger');
    redirect('admin/propuestas.php?tab=ganadoras');
}

$pageTitle = 'Seguimiento: ' . $propuesta['titulo'] . ' - MuniOps';
$action = $_GET['action'] ?? 'list';
$seguimientoId = $_GET['seg_id'] ?? 0;

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $titulo = sanitizeInput($_POST['titulo'] ?? '');
                $descripcion = sanitizeInput($_POST['descripcion'] ?? '');
                
                $imagen = null;
                if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                    $imagen = uploadImage($_FILES['imagen'], 'seguimientos');
                }
                
                if (createSeguimiento($propuestaId, $titulo, $descripcion, $imagen, getUserId())) {
                    setFlashMessage('Actualización de seguimiento agregada exitosamente', 'success');
                    redirect('admin/seguimiento-propuesta.php?id=' . $propuestaId);
                } else {
                    setFlashMessage('Error al crear seguimiento', 'danger');
                }
                break;
                
            case 'update':
                $segId = $_POST['seguimiento_id'];
                $titulo = sanitizeInput($_POST['titulo'] ?? '');
                $descripcion = sanitizeInput($_POST['descripcion'] ?? '');
                
                $imagen = null;
                if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                    $imagen = uploadImage($_FILES['imagen'], 'seguimientos');
                }
                
                if (updateSeguimiento($segId, $titulo, $descripcion, $imagen)) {
                    setFlashMessage('Seguimiento actualizado exitosamente', 'success');
                    redirect('admin/seguimiento-propuesta.php?id=' . $propuestaId);
                } else {
                    setFlashMessage('Error al actualizar seguimiento', 'danger');
                }
                break;
                
            case 'delete':
                $segId = $_POST['seguimiento_id'];
                if (deleteSeguimiento($segId)) {
                    setFlashMessage('Seguimiento eliminado exitosamente', 'success');
                } else {
                    setFlashMessage('Error al eliminar seguimiento', 'danger');
                }
                redirect('admin/seguimiento-propuesta.php?id=' . $propuestaId);
                break;
        }
    }
}

// Obtener seguimientos
$seguimientos = getSeguimientosByPropuesta($propuestaId);

// Si estamos editando, obtener el seguimiento
$seguimiento = null;
if ($action === 'edit' && $seguimientoId) {
    $seguimiento = getSeguimientoById($seguimientoId);
    if (!$seguimiento || $seguimiento['propuesta_id'] != $propuestaId) {
        setFlashMessage('Seguimiento no encontrado', 'danger');
        redirect('admin/seguimiento-propuesta.php?id=' . $propuestaId);
    }
}

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
                <a href="propuestas.php" class="list-group-item list-group-item-action active">
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
            <!-- Información de la Propuesta -->
            <div class="mb-4">
                <a href="propuestas.php?tab=ganadoras" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Volver a Propuestas Ganadoras
                </a>
            </div>
            
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0">
                        <i class="bi bi-trophy-fill"></i> <?php echo htmlspecialchars($propuesta['titulo']); ?>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <p class="lead"><?php echo nl2br(htmlspecialchars($propuesta['descripcion'])); ?></p>
                            <div class="mt-3">
                                <span class="badge categoria-<?php echo $propuesta['categoria']; ?> me-2">
                                    <?php echo ucfirst(str_replace('_', ' ', $propuesta['categoria'])); ?>
                                </span>
                                <span class="badge bg-success me-2">
                                    <?php echo ucfirst($propuesta['estado']); ?>
                                </span>
                                <?php if ($propuesta['presupuesto_estimado']): ?>
                                    <span class="badge bg-info">
                                        Presupuesto: S/. <?php echo number_format($propuesta['presupuesto_estimado'], 2); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <?php if ($propuesta['imagen']): ?>
                                <img src="../uploads/<?php echo $propuesta['imagen']; ?>" 
                                     class="img-fluid rounded" 
                                     alt="<?php echo htmlspecialchars($propuesta['titulo']); ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($action === 'list'): ?>
                <!-- Lista de Seguimientos -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold">
                        <i class="bi bi-clipboard-check"></i> Actualizaciones de Seguimiento
                    </h2>
                    <a href="seguimiento-propuesta.php?id=<?php echo $propuestaId; ?>&action=create" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Nueva Actualización
                    </a>
                </div>
                
                <?php if (empty($seguimientos)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        No hay actualizaciones de seguimiento aún. Agrega la primera actualización para mantener informados a los ciudadanos.
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($seguimientos as $seg): ?>
                            <div class="card mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($seg['titulo']); ?></h5>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar"></i> <?php echo formatDate($seg['fecha_actualizacion']); ?>
                                                • <i class="bi bi-person"></i> <?php echo htmlspecialchars($seg['autor_nombre']); ?>
                                            </small>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <a href="seguimiento-propuesta.php?id=<?php echo $propuestaId; ?>&action=edit&seg_id=<?php echo $seg['id']; ?>" 
                                               class="btn btn-outline-primary"
                                               title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-danger"
                                                    onclick="eliminarSeguimiento(<?php echo $seg['id']; ?>)"
                                                    title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($seg['descripcion'])); ?></p>
                                    
                                    <?php if ($seg['imagen']): ?>
                                        <div class="mt-3">
                                            <img src="../uploads/<?php echo $seg['imagen']; ?>" 
                                                 class="img-fluid rounded" 
                                                 style="max-height: 400px;"
                                                 alt="<?php echo htmlspecialchars($seg['titulo']); ?>">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($action === 'create' || $action === 'edit'): ?>
                <!-- Formulario Crear/Editar Seguimiento -->
                <div class="mb-4">
                    <a href="seguimiento-propuesta.php?id=<?php echo $propuestaId; ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                </div>
                
                <h2 class="fw-bold mb-4">
                    <?php echo $action === 'create' ? 'Nueva Actualización de Seguimiento' : 'Editar Actualización'; ?>
                </h2>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="<?php echo $action === 'create' ? 'create' : 'update'; ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="seguimiento_id" value="<?php echo $seguimiento['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título de la Actualización *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="titulo" 
                                       name="titulo" 
                                       required
                                       maxlength="255"
                                       value="<?php echo $action === 'edit' ? htmlspecialchars($seguimiento['titulo']) : ''; ?>"
                                       placeholder="Ej: Inicio de obras - Fase 1">
                            </div>
                            
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción *</label>
                                <textarea class="form-control" 
                                          id="descripcion" 
                                          name="descripcion" 
                                          rows="6" 
                                          required
                                          placeholder="Describe el progreso, los avances, o cualquier novedad importante..."><?php echo $action === 'edit' ? htmlspecialchars($seguimiento['descripcion']) : ''; ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="imagen" class="form-label">Imagen</label>
                                <?php if ($action === 'edit' && $seguimiento['imagen']): ?>
                                    <div class="mb-2">
                                        <img src="../uploads/<?php echo $seguimiento['imagen']; ?>" 
                                             class="img-thumbnail" 
                                             style="max-height: 200px;"
                                             alt="Imagen actual">
                                        <p class="text-muted small mt-1">Imagen actual (se reemplazará si subes una nueva)</p>
                                    </div>
                                <?php endif; ?>
                                <input type="file" 
                                       class="form-control" 
                                       id="imagen" 
                                       name="imagen" 
                                       accept="image/*">
                                <small class="text-muted">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 5MB</small>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> 
                                    <?php echo $action === 'create' ? 'Crear Actualización' : 'Guardar Cambios'; ?>
                                </button>
                                <a href="seguimiento-propuesta.php?id=<?php echo $propuestaId; ?>" class="btn btn-secondary">
                                    Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<form method="POST" id="formEliminar">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="seguimiento_id" id="seguimientoIdEliminar">
</form>

<script>
function eliminarSeguimiento(id) {
    if (confirm('¿Estás seguro de que deseas eliminar esta actualización de seguimiento?')) {
        document.getElementById('seguimientoIdEliminar').value = id;
        document.getElementById('formEliminar').submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
