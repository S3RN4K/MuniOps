<?php
ob_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();
requireAdmin();

$pageTitle = 'Gestión de Votaciones - MuniOps';
$action = $_GET['action'] ?? 'list';
$votacionId = $_GET['id'] ?? 0;

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
            case 'update':
                $titulo = sanitizeInput($_POST['titulo'] ?? '');
                $descripcion = sanitizeInput($_POST['descripcion'] ?? '');
                $municipioId = $_POST['municipio_id'] ?? null;
                $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-d H:i:s');
                $fechaFin = $_POST['fecha_fin'] ?? date('Y-m-d H:i:s', strtotime('+15 days'));
                $estado = $_POST['estado'] ?? 'borrador';
                $propuestas = $_POST['propuestas'] ?? [];
                
                $data = [
                    'titulo' => $titulo,
                    'descripcion' => $descripcion,
                    'municipio_id' => $municipioId,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'estado' => $estado,
                    'creado_por' => getUserId()
                ];
                
                if ($_POST['action'] === 'create') {
                    $id = createVotacion($data);
                    if ($id) {
                        // Agregar propuestas seleccionadas
                        $orden = 1;
                        foreach ($propuestas as $propuestaId) {
                            addPropuestaToVotacion($id, $propuestaId, $orden++);
                        }
                        
                        setFlashMessage('Votación creada exitosamente', 'success');
                        redirect('admin/votaciones.php');
                    } else {
                        setFlashMessage('Error al crear la votación', 'danger');
                    }
                } else {
                    $id = $_POST['votacion_id'];
                    if (updateVotacion($id, $data)) {
                        // Actualizar propuestas
                        if (isset($_POST['actualizar_propuestas']) && $_POST['actualizar_propuestas'] === '1') {
                            // Eliminar propuestas actuales
                            execute("DELETE FROM votacion_propuestas WHERE votacion_id = ?", [$id]);
                            
                            // Agregar nuevas propuestas
                            $orden = 1;
                            foreach ($propuestas as $propuestaId) {
                                addPropuestaToVotacion($id, $propuestaId, $orden++);
                            }
                        }
                        
                        setFlashMessage('Votación actualizada exitosamente', 'success');
                        redirect('admin/votaciones.php');
                    } else {
                        setFlashMessage('Error al actualizar la votación', 'danger');
                    }
                }
                break;
                
            case 'finalizar':
                $id = $_POST['votacion_id'];
                $result = finalizarVotacion($id);
                if ($result['success']) {
                    setFlashMessage($result['message'], 'success');
                } else {
                    setFlashMessage($result['message'], 'danger');
                }
                redirect('admin/votaciones.php');
                break;
                
            case 'cambiar_estado':
                $id = $_POST['votacion_id'];
                $nuevoEstado = $_POST['nuevo_estado'];
                if (updateVotacionEstado($id, $nuevoEstado)) {
                    setFlashMessage('Estado actualizado exitosamente', 'success');
                } else {
                    setFlashMessage('Error al actualizar el estado', 'danger');
                }
                redirect('admin/votaciones.php');
                break;
                
            case 'delete':
                $id = $_POST['votacion_id'];
                if (deleteVotacion($id)) {
                    setFlashMessage('Votación eliminada exitosamente', 'success');
                } else {
                    setFlashMessage('Error al eliminar votación', 'danger');
                }
                redirect('admin/votaciones.php');
                break;
                
            case 'desarchivar':
                $propuestaId = $_POST['propuesta_id'];
                if (desarchivarPropuesta($propuestaId)) {
                    setFlashMessage('Propuesta desarchivada exitosamente', 'success');
                } else {
                    setFlashMessage('Error al desarchivar propuesta', 'danger');
                }
                redirect('admin/votaciones.php?action=archivo');
                break;
        }
    }
}

// Obtener datos según la acción
if ($action === 'list') {
    $votaciones = getAllVotaciones();
} elseif ($action === 'edit' && $votacionId) {
    $votacion = getVotacionById($votacionId);
    if (!$votacion) {
        setFlashMessage('Votación no encontrada', 'danger');
        redirect('admin/votaciones.php');
    }
    $propuestasVotacion = getPropuestasDeVotacion($votacionId);
} elseif ($action === 'view' && $votacionId) {
    $votacion = getVotacionWithStats($votacionId);
    if (!$votacion) {
        setFlashMessage('Votación no encontrada', 'danger');
        redirect('admin/votaciones.php');
    }
    $propuestasVotacion = getPropuestasDeVotacion($votacionId);
} elseif ($action === 'archivo') {
    $propuestasArchivadas = getPropuestasArchivadas();
}

// Obtener municipios y propuestas disponibles para formularios
if ($action === 'create' || $action === 'edit') {
    $municipios = getAllMunicipios();
    $propuestasDisponibles = getPropuestasDisponiblesParaVotacion();
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
                <a href="propuestas.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-lightbulb"></i> Propuestas
                </a>
                <a href="votaciones.php" class="list-group-item list-group-item-action active">
                    <i class="bi bi-box-arrow-in-right"></i> Votaciones
                </a>
                <a href="usuarios.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-people"></i> Usuarios
                </a>
                <a href="reportes.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-graph-up"></i> Reportes
                </a>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="col-md-9 col-lg-10">
            <?php if ($action === 'list'): ?>
                <!-- Lista de votaciones -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="fw-bold">
                        <i class="bi bi-box-arrow-in-right text-primary"></i> Gestión de Votaciones
                    </h1>
                    <div>
                        <a href="votaciones.php?action=archivo" class="btn btn-outline-secondary me-2">
                            <i class="bi bi-archive"></i> Archivo
                        </a>
                        <a href="votaciones.php?action=create" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nueva Votación
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Título</th>
                                        <th>Municipio</th>
                                        <th>Estado</th>
                                        <th>Votos</th>
                                        <th>Propuestas</th>
                                        <th>Fecha Inicio</th>
                                        <th>Fecha Fin</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($votaciones)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-4">
                                                <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                                <p class="text-muted">No hay votaciones registradas</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($votaciones as $v): ?>
                                            <tr>
                                                <td><?php echo $v['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($v['titulo']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($v['municipio_nombre'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php
                                                    $badgeClass = [
                                                        'borrador' => 'bg-secondary',
                                                        'activa' => 'bg-success',
                                                        'finalizada' => 'bg-primary',
                                                        'cancelada' => 'bg-danger'
                                                    ];
                                                    ?>
                                                    <span class="badge <?php echo $badgeClass[$v['estado']] ?? 'bg-secondary'; ?>">
                                                        <?php echo ucfirst($v['estado']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo number_format($v['total_votos']); ?></td>
                                                <td><?php echo $v['total_propuestas']; ?></td>
                                                <td><?php echo formatDate($v['fecha_inicio']); ?></td>
                                                <td>
                                                    <?php echo formatDate($v['fecha_fin']); ?>
                                                    <?php if ($v['dias_restantes'] > 0 && $v['estado'] === 'activa'): ?>
                                                        <small class="text-muted d-block">(<?php echo $v['dias_restantes']; ?> días)</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="votaciones.php?action=view&id=<?php echo $v['id']; ?>" 
                                                           class="btn btn-outline-info" 
                                                           title="Ver detalles">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <?php if ($v['estado'] === 'borrador'): ?>
                                                            <a href="votaciones.php?action=edit&id=<?php echo $v['id']; ?>" 
                                                               class="btn btn-outline-primary"
                                                               title="Editar">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if ($v['estado'] === 'activa' && $v['dias_restantes'] <= 0): ?>
                                                            <button type="button" 
                                                                    class="btn btn-outline-success"
                                                                    onclick="finalizarVotacion(<?php echo $v['id']; ?>)"
                                                                    title="Finalizar">
                                                                <i class="bi bi-check-circle"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($v['estado'] === 'borrador'): ?>
                                                            <button type="button" 
                                                                    class="btn btn-outline-danger"
                                                                    onclick="eliminarVotacion(<?php echo $v['id']; ?>)"
                                                                    title="Eliminar">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($action === 'create' || $action === 'edit'): ?>
                <!-- Formulario Crear/Editar -->
                <div class="mb-4">
                    <a href="votaciones.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                </div>
                
                <h1 class="fw-bold mb-4">
                    <?php echo $action === 'create' ? 'Nueva Votación' : 'Editar Votación'; ?>
                </h1>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="<?php echo $action === 'create' ? 'create' : 'update'; ?>">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="votacion_id" value="<?php echo $votacion['id']; ?>">
                                <input type="hidden" name="actualizar_propuestas" value="0">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="titulo" class="form-label fw-bold">Título *</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="titulo" 
                                               name="titulo" 
                                               value="<?php echo htmlspecialchars($votacion['titulo'] ?? ''); ?>"
                                               required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="descripcion" class="form-label fw-bold">Descripción</label>
                                        <textarea class="form-control" 
                                                  id="descripcion" 
                                                  name="descripcion" 
                                                  rows="4"><?php echo htmlspecialchars($votacion['descripcion'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="municipio_id" class="form-label fw-bold">Municipio *</label>
                                            <select class="form-select" id="municipio_id" name="municipio_id" required>
                                                <option value="">Seleccionar municipio</option>
                                                <?php foreach ($municipios as $m): ?>
                                                    <option value="<?php echo $m['id']; ?>" 
                                                            <?php echo ($votacion['municipio_id'] ?? '') == $m['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($m['nombre'] . ' - ' . $m['departamento']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="estado" class="form-label fw-bold">Estado *</label>
                                            <select class="form-select" id="estado" name="estado" required>
                                                <option value="borrador" <?php echo ($votacion['estado'] ?? '') === 'borrador' ? 'selected' : ''; ?>>Borrador</option>
                                                <option value="activa" <?php echo ($votacion['estado'] ?? '') === 'activa' ? 'selected' : ''; ?>>Activa</option>
                                                <option value="cancelada" <?php echo ($votacion['estado'] ?? '') === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="fecha_inicio" class="form-label fw-bold">Fecha Inicio *</label>
                                            <input type="datetime-local" 
                                                   class="form-control" 
                                                   id="fecha_inicio" 
                                                   name="fecha_inicio"
                                                   value="<?php echo isset($votacion['fecha_inicio']) ? date('Y-m-d\TH:i', strtotime($votacion['fecha_inicio'])) : date('Y-m-d\TH:i'); ?>"
                                                   required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="fecha_fin" class="form-label fw-bold">Fecha Fin *</label>
                                            <input type="datetime-local" 
                                                   class="form-control" 
                                                   id="fecha_fin" 
                                                   name="fecha_fin"
                                                   value="<?php echo isset($votacion['fecha_fin']) ? date('Y-m-d\TH:i', strtotime($votacion['fecha_fin'])) : date('Y-m-d\TH:i', strtotime('+15 days')); ?>"
                                                   required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i>
                                        <strong>Instrucciones:</strong>
                                        <ul class="mb-0 mt-2 small">
                                            <li>Selecciona entre 2 y 3 propuestas</li>
                                            <li>Las propuestas deben ser del mismo municipio</li>
                                            <li>Al finalizar, la ganadora será implementada</li>
                                            <li>Las perdedoras se archivarán</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <h5 class="fw-bold mb-3">
                                <i class="bi bi-list-check"></i> Seleccionar Propuestas (máximo 3)
                            </h5>
                            
                            <?php if ($action === 'edit' && !empty($propuestasVotacion)): ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    Esta votación ya tiene propuestas asignadas. Si cambias las propuestas, se perderán los votos actuales.
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="actualizar_propuestas_check" 
                                               onchange="document.querySelector('input[name=actualizar_propuestas]').value = this.checked ? '1' : '0'">
                                        <label class="form-check-label" for="actualizar_propuestas_check">
                                            Permitir cambiar propuestas
                                        </label>
                                    </div>
                                </div>
                                
                                <h6 class="text-muted mb-3">Propuestas actuales:</h6>
                                <div class="row mb-4">
                                    <?php foreach ($propuestasVotacion as $prop): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h6><?php echo htmlspecialchars($prop['titulo']); ?></h6>
                                                    <p class="small text-muted mb-0">Votos: <?php echo $prop['votos_recibidos']; ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="row" id="propuestas-container">
                                <?php if (empty($propuestasDisponibles)): ?>
                                    <div class="col-12">
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-circle"></i> No hay propuestas disponibles. 
                                            <a href="propuestas.php?action=create">Crear nueva propuesta</a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php if ($action === 'create'): ?>
                                        <div class="col-12 mb-3" id="mensaje-inicial">
                                            <div class="alert alert-info">
                                                <i class="bi bi-info-circle"></i> <strong>Selecciona un municipio</strong> para ver las propuestas disponibles de ese municipio.
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php 
                                    $propuestasSeleccionadas = [];
                                    if ($action === 'edit' && !empty($propuestasVotacion)) {
                                        foreach ($propuestasVotacion as $pv) {
                                            $propuestasSeleccionadas[] = $pv['propuesta_id'];
                                        }
                                    }
                                    ?>
                                    <?php foreach ($propuestasDisponibles as $prop): ?>
                                        <div class="col-md-6 col-lg-4 mb-3 propuesta-item" data-municipio-id="<?php echo $prop['municipio_id'] ?? ''; ?>" style="<?php echo $action === 'create' ? 'display: none;' : ''; ?>">
                                            <div class="card h-100 propuesta-card">
                                                <div class="card-body">
                                                    <div class="form-check">
                                                        <input class="form-check-input propuesta-checkbox" 
                                                               type="checkbox" 
                                                               name="propuestas[]" 
                                                               value="<?php echo $prop['id']; ?>"
                                                               id="prop_<?php echo $prop['id']; ?>"
                                                               <?php echo in_array($prop['id'], $propuestasSeleccionadas) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label w-100" for="prop_<?php echo $prop['id']; ?>">
                                                            <strong><?php echo htmlspecialchars($prop['titulo']); ?></strong>
                                                            <p class="small text-muted mb-1"><?php echo substr(htmlspecialchars($prop['descripcion']), 0, 100); ?>...</p>
                                                            <span class="badge categoria-<?php echo $prop['categoria']; ?>">
                                                                <?php echo ucfirst($prop['categoria']); ?>
                                                            </span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-save"></i> <?php echo $action === 'create' ? 'Crear Votación' : 'Actualizar Votación'; ?>
                                </button>
                                <a href="votaciones.php" class="btn btn-outline-secondary btn-lg">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
                
            <?php elseif ($action === 'view'): ?>
                <!-- Ver detalles de votación -->
                <div class="mb-4">
                    <a href="votaciones.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                </div>
                
                <h1 class="fw-bold mb-4"><?php echo htmlspecialchars($votacion['titulo']); ?></h1>
                
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Información de la Votación</h5>
                                <?php if ($votacion['descripcion']): ?>
                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($votacion['descripcion'])); ?></p>
                                <?php endif; ?>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <p><strong>Municipio:</strong> <?php echo htmlspecialchars($votacion['municipio_nombre']); ?></p>
                                        <p><strong>Estado:</strong> 
                                            <span class="badge bg-<?php echo $votacion['estado'] === 'activa' ? 'success' : ($votacion['estado'] === 'finalizada' ? 'primary' : 'secondary'); ?>">
                                                <?php echo ucfirst($votacion['estado']); ?>
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Inicio:</strong> <?php echo formatDate($votacion['fecha_inicio']); ?></p>
                                        <p><strong>Fin:</strong> <?php echo formatDate($votacion['fecha_fin']); ?></p>
                                        <p><strong>Total Votos:</strong> <?php echo number_format($votacion['total_votos']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">Acciones</h6>
                                <?php if ($votacion['estado'] === 'borrador'): ?>
                                    <form method="POST" class="mb-2">
                                        <input type="hidden" name="action" value="cambiar_estado">
                                        <input type="hidden" name="votacion_id" value="<?php echo $votacion['id']; ?>">
                                        <input type="hidden" name="nuevo_estado" value="activa">
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="bi bi-play-circle"></i> Activar Votación
                                        </button>
                                    </form>
                                <?php elseif ($votacion['estado'] === 'activa'): ?>
                                    <button type="button" 
                                            class="btn btn-primary w-100 mb-2"
                                            onclick="finalizarVotacion(<?php echo $votacion['id']; ?>)">
                                        <i class="bi bi-check-circle"></i> Finalizar Votación
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($votacion['propuesta_ganadora_id']): ?>
                                    <a href="../propuesta-detalle.php?id=<?php echo $votacion['propuesta_ganadora_id']; ?>" 
                                       class="btn btn-outline-success w-100 mb-2" target="_blank">
                                        <i class="bi bi-trophy"></i> Ver Propuesta Ganadora
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h4 class="fw-bold mb-3">Propuestas en Votación</h4>
                <div class="row">
                    <?php foreach ($propuestasVotacion as $prop): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 <?php echo $prop['es_ganadora'] ? 'border-success' : ''; ?>">
                                <?php if ($prop['imagen']): ?>
                                    <img src="../uploads/<?php echo $prop['imagen']; ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($prop['titulo']); ?>"
                                         style="height: 200px; object-fit: cover;">
                                <?php endif; ?>
                                <div class="card-body">
                                    <?php if ($prop['es_ganadora']): ?>
                                        <div class="alert alert-success py-1 px-2 small mb-2">
                                            <i class="bi bi-trophy-fill"></i> GANADORA
                                        </div>
                                    <?php endif; ?>
                                    <h5 class="card-title"><?php echo htmlspecialchars($prop['titulo']); ?></h5>
                                    <p class="card-text small"><?php echo substr(htmlspecialchars($prop['descripcion']), 0, 150); ?>...</p>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="fw-bold"><?php echo number_format($prop['votos_recibidos']); ?> votos</span>
                                            <span class="text-muted"><?php echo number_format($prop['porcentaje'], 1); ?>%</span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar <?php echo $prop['es_ganadora'] ? 'bg-success' : ''; ?>" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $prop['porcentaje']; ?>%"
                                                 aria-valuenow="<?php echo $prop['porcentaje']; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    
                                    <a href="../propuesta-detalle.php?id=<?php echo $prop['propuesta_id']; ?>" 
                                       class="btn btn-outline-primary btn-sm" target="_blank">
                                        <i class="bi bi-eye"></i> Ver Detalles
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
            <?php elseif ($action === 'archivo'): ?>
                <!-- Archivo de propuestas -->
                <div class="mb-4">
                    <a href="votaciones.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                </div>
                
                <h1 class="fw-bold mb-4">
                    <i class="bi bi-archive"></i> Archivo de Propuestas
                </h1>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Estas propuestas perdieron en votaciones anteriores y están archivadas. 
                    Puedes desarchivarlas para utilizarlas en nuevas votaciones.
                </div>
                
                <div class="row">
                    <?php if (empty($propuestasArchivadas)): ?>
                        <div class="col-12">
                            <div class="alert alert-secondary">
                                <i class="bi bi-inbox"></i> No hay propuestas archivadas
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($propuestasArchivadas as $prop): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <?php if ($prop['imagen']): ?>
                                        <img src="../uploads/<?php echo $prop['imagen']; ?>" 
                                             class="card-img-top" 
                                             alt="<?php echo htmlspecialchars($prop['titulo']); ?>"
                                             style="height: 200px; object-fit: cover;">
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <span class="badge categoria-<?php echo $prop['categoria']; ?> mb-2">
                                            <?php echo ucfirst($prop['categoria']); ?>
                                        </span>
                                        <h5 class="card-title"><?php echo htmlspecialchars($prop['titulo']); ?></h5>
                                        <p class="card-text small"><?php echo substr(htmlspecialchars($prop['descripcion']), 0, 150); ?>...</p>
                                        
                                        <div class="mb-3">
                                            <small class="text-muted">
                                                <i class="bi bi-calendar"></i> Archivada: <?php echo formatDate($prop['fecha_archivo']); ?>
                                            </small><br>
                                            <small class="text-muted">
                                                <i class="bi bi-repeat"></i> Usada: <?php echo $prop['veces_usada_votacion']; ?> veces
                                            </small>
                                        </div>
                                        
                                        <div class="btn-group w-100">
                                            <a href="../propuesta-detalle.php?id=<?php echo $prop['id']; ?>" 
                                               class="btn btn-outline-primary btn-sm" target="_blank">
                                                <i class="bi bi-eye"></i> Ver
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-success btn-sm"
                                                    onclick="desarchivarPropuesta(<?php echo $prop['id']; ?>)">
                                                <i class="bi bi-arrow-counterclockwise"></i> Desarchivar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
// Limitar selección de propuestas a 3
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.propuesta-checkbox');
    const maxSelection = 3;
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const checkedCount = document.querySelectorAll('.propuesta-checkbox:checked').length;
            
            if (checkedCount > maxSelection) {
                this.checked = false;
                alert('Solo puedes seleccionar máximo ' + maxSelection + ' propuestas');
            }
        });
    });
    
    // Filtrar propuestas por municipio
    const municipioSelect = document.getElementById('municipio_id');
    if (municipioSelect) {
        municipioSelect.addEventListener('change', function() {
            const selectedMunicipioId = this.value;
            const propuestasContainer = document.getElementById('propuestas-container');
            const propuestasItems = document.querySelectorAll('.propuesta-item');
            const mensajeInicial = document.getElementById('mensaje-inicial');
            
            // Ocultar mensaje inicial si existe
            if (mensajeInicial) {
                mensajeInicial.style.display = 'none';
            }
            
            // Si no hay municipio seleccionado, mostrar mensaje
            if (!selectedMunicipioId) {
                // Ocultar todas las propuestas
                propuestasItems.forEach(item => {
                    item.style.display = 'none';
                    const checkbox = item.querySelector('.propuesta-checkbox');
                    if (checkbox && checkbox.checked) {
                        checkbox.checked = false;
                    }
                });
                
                // Remover alertas existentes
                const existingAlert = propuestasContainer.querySelector('.alert-no-propuestas');
                if (existingAlert) {
                    existingAlert.remove();
                }
                
                // Mostrar mensaje inicial si existe
                if (mensajeInicial) {
                    mensajeInicial.style.display = 'block';
                }
                
                return;
            }
            
            let visibleCount = 0;
            propuestasItems.forEach(item => {
                const propuestaMunicipioId = item.getAttribute('data-municipio-id');
                
                if (propuestaMunicipioId === selectedMunicipioId) {
                    item.style.display = 'block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                    // Desmarcar checkbox si estaba seleccionado
                    const checkbox = item.querySelector('.propuesta-checkbox');
                    if (checkbox && checkbox.checked) {
                        checkbox.checked = false;
                    }
                }
            });
            
            // Remover alerta anterior si existe
            const existingAlert = propuestasContainer.querySelector('.alert-no-propuestas');
            if (existingAlert) {
                existingAlert.remove();
            }
            
            // Mostrar mensaje si no hay propuestas para este municipio
            if (visibleCount === 0) {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'col-12 alert-no-propuestas';
                alertDiv.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-circle"></i> No hay propuestas disponibles para este municipio. 
                        <a href="propuestas.php?action=create">Crear nueva propuesta</a>
                    </div>
                `;
                propuestasContainer.appendChild(alertDiv);
            }
        });
        
        // Trigger inicial si hay municipio preseleccionado (modo edición)
        if (municipioSelect.value) {
            municipioSelect.dispatchEvent(new Event('change'));
        }
    }
});

function eliminarVotacion(id) {
    if (confirm('¿Estás seguro de eliminar esta votación? Esta acción no se puede deshacer.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="votacion_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function finalizarVotacion(id) {
    if (confirm('¿Finalizar esta votación? Se determinará la propuesta ganadora y las perdedoras se archivarán.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="finalizar">
            <input type="hidden" name="votacion_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function desarchivarPropuesta(id) {
    if (confirm('¿Desarchivar esta propuesta para que pueda ser utilizada en nuevas votaciones?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="desarchivar">
            <input type="hidden" name="propuesta_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<style>
.propuesta-card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.propuesta-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.propuesta-card .form-check-input:checked ~ .form-check-label {
    font-weight: bold;
}

.propuesta-item {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.propuesta-item[style*="display: none"] {
    opacity: 0;
    transform: scale(0.8);
}
</style>
