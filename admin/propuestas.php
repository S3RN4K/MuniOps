<?php
ob_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();
requireAdmin();

$pageTitle = 'Gestión de Propuestas - MuniOps';
$action = $_GET['action'] ?? 'list';
$propuestaId = $_GET['id'] ?? 0;

// Cargar municipios
$municipios = getAllMunicipios();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
            case 'update':
                $titulo = sanitizeInput($_POST['titulo'] ?? '');
                $descripcion = sanitizeInput($_POST['descripcion'] ?? '');
                $categoria = $_POST['categoria'] ?? 'otros';
                $municipio_id = (int)($_POST['municipio_id'] ?? 0);
                $presupuesto = $_POST['presupuesto_estimado'] ?? null;
                $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-d H:i:s');
                $fechaFin = $_POST['fecha_fin'] ?? date('Y-m-d H:i:s', strtotime('+30 days'));
                $estado = $_POST['estado'] ?? 'borrador';
                
                $imagen = null;
                if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                    $imagen = uploadImage($_FILES['imagen'], 'propuestas');
                }
                
                $data = [
                    'titulo' => $titulo,
                    'descripcion' => $descripcion,
                    'categoria' => $categoria,
                    'municipio_id' => $municipio_id,
                    'imagen' => $imagen,
                    'presupuesto_estimado' => $presupuesto,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'estado' => $estado,
                    'creado_por' => getUserId()
                ];
                
                if ($_POST['action'] === 'create') {
                    $id = createPropuesta($data);
                    if ($id) {
                        setFlashMessage('Propuesta creada exitosamente', 'success');
                        redirect('admin/propuestas.php');
                    }
                } else {
                    $id = $_POST['propuesta_id'];
                    if (updatePropuesta($id, $data)) {
                        setFlashMessage('Propuesta actualizada exitosamente', 'success');
                        redirect('admin/propuestas.php');
                    }
                }
                break;
                
            case 'delete':
                $id = $_POST['propuesta_id'];
                if (deletePropuesta($id)) {
                    setFlashMessage('Propuesta eliminada exitosamente', 'success');
                } else {
                    setFlashMessage('Error al eliminar propuesta', 'danger');
                }
                redirect('admin/propuestas.php');
                break;
        }
    }
}

// Obtener propuestas
if ($action === 'list') {
    $propuestas = fetchAll("SELECT * FROM propuestas ORDER BY fecha_creacion DESC");
} elseif ($action === 'edit' && $propuestaId) {
    $propuesta = getPropuestaById($propuestaId);
    if (!$propuesta) {
        setFlashMessage('Propuesta no encontrada', 'danger');
        redirect('admin/propuestas.php');
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
                <a href="usuarios.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-people"></i> Usuarios
                </a>
                <a href="reportes.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-graph-up"></i> Reportes
                </a>
                <a href="configuracion.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-gear"></i> Configuración
                </a>
                <hr>
                <a href="../index.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-arrow-left"></i> Volver al Sitio
                </a>
            </div>
        </div>
        
        <!-- Contenido Principal -->
        <div class="col-md-9 col-lg-10">
            <?php if ($action === 'list'): ?>
                <!-- Lista de Propuestas -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="fw-bold">
                        <i class="bi bi-lightbulb"></i> Gestión de Propuestas
                    </h1>
                    <a href="propuestas.php?action=create" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Nueva Propuesta
                    </a>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Título</th>
                                        <th>Categoría</th>
                                        <th>Estado</th>
                                        <th>Votos</th>
                                        <th>Fecha Inicio</th>
                                        <th>Fecha Fin</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($propuestas as $p): ?>
                                        <tr>
                                            <td><?php echo $p['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($p['titulo']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge categoria-<?php echo $p['categoria']; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $p['categoria'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $p['estado'] === 'activa' ? 'success' : ($p['estado'] === 'finalizada' ? 'secondary' : 'warning'); ?>">
                                                    <?php echo ucfirst($p['estado']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo number_format($p['total_votos']); ?></td>
                                            <td><?php echo formatDate($p['fecha_inicio']); ?></td>
                                            <td><?php echo formatDate($p['fecha_fin']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="../propuesta-detalle.php?id=<?php echo $p['id']; ?>" 
                                                       class="btn btn-outline-info" 
                                                       target="_blank"
                                                       title="Ver">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="propuestas.php?action=edit&id=<?php echo $p['id']; ?>" 
                                                       class="btn btn-outline-primary"
                                                       title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-outline-danger"
                                                            onclick="eliminarPropuesta(<?php echo $p['id']; ?>)"
                                                            title="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($action === 'create' || $action === 'edit'): ?>
                <!-- Formulario Crear/Editar -->
                <div class="mb-4">
                    <a href="propuestas.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                </div>
                
                <h1 class="fw-bold mb-4">
                    <?php echo $action === 'create' ? 'Nueva Propuesta' : 'Editar Propuesta'; ?>
                </h1>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="<?php echo $action === 'create' ? 'create' : 'update'; ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="propuesta_id" value="<?php echo $propuesta['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="titulo" class="form-label fw-bold">Título *</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="titulo" 
                                               name="titulo" 
                                               value="<?php echo htmlspecialchars($propuesta['titulo'] ?? ''); ?>"
                                               required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="descripcion" class="form-label fw-bold">Descripción *</label>
                                        <textarea class="form-control" 
                                                  id="descripcion" 
                                                  name="descripcion" 
                                                  rows="6" 
                                                  required><?php echo htmlspecialchars($propuesta['descripcion'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="categoria" class="form-label fw-bold">Categoría *</label>
                                            <select class="form-select" id="categoria" name="categoria" required>
                                                <option value="infraestructura" <?php echo ($propuesta['categoria'] ?? '') === 'infraestructura' ? 'selected' : ''; ?>>Infraestructura</option>
                                                <option value="salud" <?php echo ($propuesta['categoria'] ?? '') === 'salud' ? 'selected' : ''; ?>>Salud</option>
                                                <option value="educacion" <?php echo ($propuesta['categoria'] ?? '') === 'educacion' ? 'selected' : ''; ?>>Educación</option>
                                                <option value="seguridad" <?php echo ($propuesta['categoria'] ?? '') === 'seguridad' ? 'selected' : ''; ?>>Seguridad</option>
                                                <option value="medio_ambiente" <?php echo ($propuesta['categoria'] ?? '') === 'medio_ambiente' ? 'selected' : ''; ?>>Medio Ambiente</option>
                                                <option value="deporte" <?php echo ($propuesta['categoria'] ?? '') === 'deporte' ? 'selected' : ''; ?>>Deporte</option>
                                                <option value="cultura" <?php echo ($propuesta['categoria'] ?? '') === 'cultura' ? 'selected' : ''; ?>>Cultura</option>
                                                <option value="otros" <?php echo ($propuesta['categoria'] ?? '') === 'otros' ? 'selected' : ''; ?>>Otros</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="municipio_id" class="form-label fw-bold">Municipio *</label>
                                            <select class="form-select" id="municipio_id" name="municipio_id" required>
                                                <option value="">-- Selecciona municipio --</option>
                                                <?php foreach ($municipios as $muni): ?>
                                                    <option value="<?php echo $muni['id']; ?>" 
                                                        <?php echo ($propuesta['municipio_id'] ?? 0) == $muni['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($muni['nombre']) . ' (' . htmlspecialchars($muni['departamento']) . ')'; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="presupuesto_estimado" class="form-label fw-bold">Presupuesto Estimado (S/.)</label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="presupuesto_estimado" 
                                                   name="presupuesto_estimado"
                                                   step="0.01"
                                                   value="<?php echo $propuesta['presupuesto_estimado'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="imagen" class="form-label fw-bold">Imagen</label>
                                        <?php if ($action === 'edit' && $propuesta['imagen']): ?>
                                            <img src="../uploads/<?php echo $propuesta['imagen']; ?>" 
                                                 class="img-fluid mb-2 rounded" 
                                                 alt="Imagen actual">
                                        <?php endif; ?>
                                        <input type="file" 
                                               class="form-control" 
                                               id="imagen" 
                                               name="imagen"
                                               accept="image/*">
                                        <small class="text-muted">JPG, PNG o GIF. Máximo 5MB</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="estado" class="form-label fw-bold">Estado *</label>
                                        <select class="form-select" id="estado" name="estado" required>
                                            <option value="borrador" <?php echo ($propuesta['estado'] ?? '') === 'borrador' ? 'selected' : ''; ?>>Borrador</option>
                                            <option value="activa" <?php echo ($propuesta['estado'] ?? '') === 'activa' ? 'selected' : ''; ?>>Activa</option>
                                            <option value="finalizada" <?php echo ($propuesta['estado'] ?? '') === 'finalizada' ? 'selected' : ''; ?>>Finalizada</option>
                                            <option value="implementada" <?php echo ($propuesta['estado'] ?? '') === 'implementada' ? 'selected' : ''; ?>>Implementada</option>
                                            <option value="cancelada" <?php echo ($propuesta['estado'] ?? '') === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="fecha_inicio" class="form-label fw-bold">Fecha Inicio *</label>
                                        <input type="datetime-local" 
                                               class="form-control" 
                                               id="fecha_inicio" 
                                               name="fecha_inicio"
                                               value="<?php echo $action === 'edit' ? date('Y-m-d\TH:i', strtotime($propuesta['fecha_inicio'])) : date('Y-m-d\TH:i'); ?>"
                                               required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="fecha_fin" class="form-label fw-bold">Fecha Fin *</label>
                                        <input type="datetime-local" 
                                               class="form-control" 
                                               id="fecha_fin" 
                                               name="fecha_fin"
                                               value="<?php echo $action === 'edit' ? date('Y-m-d\TH:i', strtotime($propuesta['fecha_fin'])) : date('Y-m-d\TH:i', strtotime('+30 days')); ?>"
                                               required>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Guardar
                                </button>
                                <a href="propuestas.php" class="btn btn-secondary">
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

<!-- Form oculto para eliminar -->
<form id="deleteForm" method="POST" action="" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="propuesta_id" id="deletePropuestaId">
</form>

<script>
function eliminarPropuesta(id) {
    if (confirm('¿Estás seguro de eliminar esta propuesta? Esta acción no se puede deshacer.')) {
        document.getElementById('deletePropuestaId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
