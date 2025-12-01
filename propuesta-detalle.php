<?php
ob_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$propuestaId = $_GET['id'] ?? 0;
$propuesta = getPropuestaWithStats($propuestaId);

if (!$propuesta) {
    setFlashMessage('Propuesta no encontrada', 'danger');
    redirect('propuestas.php');
}

$pageTitle = $propuesta['titulo'] . ' - MuniOps';

// Verificar si existe una votación activa para esta propuesta
$votacionActiva = getVotacionActivaByPropuesta($propuestaId);
$yaVotoEnVotacion = false;
if ($votacionActiva) {
    $yaVotoEnVotacion = hasVotedInVotacion(getUserId(), $votacionActiva['id']);
}

// Procesar comentario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'comentar') {
    $comentario = trim($_POST['comentario'] ?? '');
    $comentarioPadreId = $_POST['comentario_padre_id'] ?? null;
    
    if (!empty($comentario)) {
        if (createComentario($propuestaId, getUserId(), $comentario, $comentarioPadreId)) {
            setFlashMessage('Comentario publicado. Has ganado ' . PUNTOS_COMENTARIO . ' puntos', 'success');
            redirect('propuesta-detalle.php?id=' . $propuestaId . '#comentarios');
        } else {
            setFlashMessage('Error al publicar comentario', 'danger');
        }
    }
}

// Procesar seguimiento (solo admins)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'agregar_seguimiento' && isAdmin()) {
    $titulo = sanitizeInput($_POST['titulo'] ?? '');
    $descripcion = sanitizeInput($_POST['descripcion'] ?? '');
    $nuevoEstado = $_POST['estado_propuesta'] ?? null;
    
    $imagen = null;
    $debugInfo = '';
    
    if (isset($_FILES['imagen'])) {
        $debugInfo .= "Archivo detectado. ";
        $debugInfo .= "Error code: " . $_FILES['imagen']['error'] . ". ";
        $debugInfo .= "Tamaño: " . ($_FILES['imagen']['size'] ?? 0) . " bytes. ";
        $debugInfo .= "Nombre: " . ($_FILES['imagen']['name'] ?? 'sin nombre') . ". ";
        
        if ($_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $debugInfo .= "Intentando subir... ";
            $imagen = uploadImage($_FILES['imagen'], 'seguimientos');
            if ($imagen) {
                $debugInfo .= "Éxito: " . $imagen;
                setFlashMessage('Imagen subida correctamente: ' . $debugInfo, 'success');
            } else {
                $debugInfo .= "Falló uploadImage()";
                setFlashMessage('Error al subir imagen: ' . $debugInfo, 'danger');
            }
        } elseif ($_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Hay un error en el upload
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por PHP',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario',
                UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal',
                UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en disco',
                UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida'
            ];
            $errorMsg = $errorMessages[$_FILES['imagen']['error']] ?? 'Error desconocido';
            setFlashMessage($errorMsg . ' - ' . $debugInfo, 'danger');
        }
    } else {
        $debugInfo = 'No se detectó archivo en $_FILES';
    }
    
    if (!empty($titulo) && !empty($descripcion)) {
        // Crear seguimiento
        if (createSeguimiento($propuestaId, $titulo, $descripcion, $imagen, getUserId())) {
            // Actualizar estado de la propuesta si se proporcionó
            if ($nuevoEstado && in_array($nuevoEstado, ['borrador', 'activa', 'implementada', 'finalizada'])) {
                execute("UPDATE propuestas SET estado = ? WHERE id = ?", [$nuevoEstado, $propuestaId]);
            }
            if (!$imagen) {
                setFlashMessage('Actualización agregada (sin imagen). Debug: ' . $debugInfo, 'info');
            } else {
                setFlashMessage('Actualización agregada exitosamente con imagen', 'success');
            }
            redirect('propuesta-detalle.php?id=' . $propuestaId . '#seguimiento');
        } else {
            setFlashMessage('Error al agregar seguimiento en BD', 'danger');
        }
    } else {
        setFlashMessage('Debes completar título y descripción. Debug: ' . $debugInfo, 'warning');
    }
}

// Obtener seguimientos si es propuesta ganadora
$seguimientos = [];
if ($propuesta['es_ganadora'] == 1) {
    $seguimientos = getSeguimientosByPropuesta($propuestaId);
}

// Procesar like en comentario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'like') {
    $comentarioId = $_POST['comentario_id'] ?? 0;
    $result = toggleLikeComentario($comentarioId, getUserId());
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'action' => $result]);
    exit;
}

$comentarios = getComentariosByPropuesta($propuestaId);

include 'includes/header.php';
?>

<div class="container my-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="propuestas.php">Propuestas</a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($propuesta['titulo']); ?></li>
        </ol>
    </nav>
    
    <div class="row">
        <!-- Contenido Principal -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <?php if ($propuesta['imagen']): ?>
                    <img src="uploads/<?php echo $propuesta['imagen']; ?>" 
                         class="card-img-top" 
                         alt="<?php echo htmlspecialchars($propuesta['titulo']); ?>"
                         style="max-height: 400px; object-fit: cover;">
                <?php endif; ?>
                
                <div class="card-body">
                    <div class="mb-3">
                        <span class="badge categoria-<?php echo $propuesta['categoria']; ?> me-2">
                            <?php echo ucfirst(str_replace('_', ' ', $propuesta['categoria'])); ?>
                        </span>
                        
                        <?php if ($propuesta['estado'] === 'activa' && $propuesta['dias_restantes'] > 0): ?>
                            <span class="badge bg-success">
                                <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> Activa
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Finalizada</span>
                        <?php endif; ?>
                    </div>
                    
                    <h1 class="fw-bold mb-3"><?php echo htmlspecialchars($propuesta['titulo']); ?></h1>
                    
                    <div class="mb-4">
                        <small class="text-muted">
                            <i class="bi bi-person"></i> Propuesta por: <?php echo htmlspecialchars($propuesta['creado_por_nombre']); ?>
                        </small>
                        <small class="text-muted ms-3">
                            <i class="bi bi-calendar"></i> <?php echo formatDate($propuesta['fecha_inicio']); ?>
                        </small>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="fw-bold">Descripción</h5>
                        <p class="lead"><?php echo nl2br(htmlspecialchars($propuesta['descripcion'])); ?></p>
                    </div>
                    
                    <?php if ($propuesta['presupuesto_estimado']): ?>
                    <div class="mb-4">
                        <h5 class="fw-bold">Presupuesto Estimado</h5>
                        <p class="text-success fs-4 fw-bold">
                            S/. <?php echo number_format($propuesta['presupuesto_estimado'], 2); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="row text-center">
                        <div class="col-md-4">
                            <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                            <h3 class="fw-bold mb-0"><?php echo number_format($propuesta['total_votos']); ?></h3>
                            <small class="text-muted">Votos</small>
                        </div>
                        <div class="col-md-4">
                            <i class="bi bi-chat text-primary" style="font-size: 2rem;"></i>
                            <h3 class="fw-bold mb-0"><?php echo $propuesta['total_comentarios']; ?></h3>
                            <small class="text-muted">Comentarios</small>
                        </div>
                        <div class="col-md-4">
                            <i class="bi bi-clock text-warning" style="font-size: 2rem;"></i>
                            <h3 class="fw-bold mb-0">
                                <?php echo $propuesta['dias_restantes'] > 0 ? $propuesta['dias_restantes'] : 0; ?>
                            </h3>
                            <small class="text-muted">Días Restantes</small>
                        </div>
                    </div>
                    
                    <div class="progress propuesta-progress mt-3" style="height: 15px;">
                        <div class="progress-bar bg-success" 
                             role="progressbar" 
                             style="width: <?php echo min(100, ($propuesta['total_votos'] / 100) * 100); ?>%">
                            <?php echo min(100, round(($propuesta['total_votos'] / 100) * 100)); ?>%
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Seguimiento de Propuesta Ganadora -->
            <?php if ($propuesta['es_ganadora'] == 1): ?>
            <div class="card mb-4" id="seguimiento">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-clipboard-check"></i> Seguimiento de la Propuesta
                        <span class="badge bg-warning text-dark ms-2">
                            <i class="bi bi-trophy-fill"></i> Ganadora
                        </span>
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        Esta propuesta ganó la votación y está en proceso de implementación. 
                        Aquí puedes ver las actualizaciones del progreso.
                    </div>
                    
                    <?php if (isAdmin()): ?>
                    <!-- Formulario para agregar seguimiento (solo admins) -->
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-plus-circle"></i> Agregar Actualización de Seguimiento
                            </h5>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="agregar_seguimiento">
                                
                                <div class="mb-3">
                                    <label for="titulo" class="form-label fw-bold">Título de la Actualización *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="titulo" 
                                           name="titulo" 
                                           required
                                           maxlength="255"
                                           placeholder="Ej: Inicio de obras - Fase 1">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="descripcion" class="form-label fw-bold">Descripción *</label>
                                    <textarea class="form-control" 
                                              id="descripcion" 
                                              name="descripcion" 
                                              rows="4" 
                                              required
                                              placeholder="Describe el progreso, los avances, o cualquier novedad importante..."></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="estado_propuesta" class="form-label fw-bold">Estado de la Propuesta *</label>
                                    <select class="form-select" id="estado_propuesta" name="estado_propuesta" required>
                                        <option value="borrador" <?php echo $propuesta['estado'] === 'borrador' ? 'selected' : ''; ?>>Borrador</option>
                                        <option value="activa" <?php echo $propuesta['estado'] === 'activa' ? 'selected' : ''; ?>>Activa</option>
                                        <option value="implementada" <?php echo $propuesta['estado'] === 'implementada' ? 'selected' : ''; ?>>Implementada</option>
                                        <option value="finalizada" <?php echo $propuesta['estado'] === 'finalizada' ? 'selected' : ''; ?>>Finalizada</option>
                                    </select>
                                    <small class="text-muted">Actualiza el estado según el progreso actual</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="imagen" class="form-label fw-bold">Imagen (opcional)</label>
                                    <input type="file" 
                                           class="form-control" 
                                           id="imagen" 
                                           name="imagen" 
                                           accept="image/*">
                                    <small class="text-muted">Formatos: JPG, PNG, GIF. Máx: 50MB</small>
                                </div>
                                
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Publicar Actualización
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Timeline de Seguimientos -->
                    <?php if (empty($seguimientos)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-clipboard" style="font-size: 3rem;"></i>
                            <p class="mt-2">Aún no hay actualizaciones de seguimiento</p>
                            <?php if (isAdmin()): ?>
                                <small>Como administrador, puedes agregar la primera actualización usando el formulario arriba</small>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($seguimientos as $index => $seg): ?>
                                <div class="seguimiento-box <?php echo $index === 0 ? 'seguimiento-latest' : ''; ?>">
                                    <div class="seguimiento-timeline-icon">
                                        <i class="bi bi-<?php echo $index === 0 ? 'star-fill' : 'circle-fill'; ?>"></i>
                                    </div>
                                    
                                    <div class="row g-0 align-items-start">
                                        <!-- Contenido del seguimiento -->
                                        <div class="<?php echo $seg['imagen'] ? 'col-md-7' : 'col-12'; ?>">
                                            <div class="seguimiento-content">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($seg['titulo']); ?></h5>
                                                        <small class="text-muted">
                                                            <i class="bi bi-calendar"></i> 
                                                            <?php echo date('d/m/Y H:i', strtotime($seg['fecha_actualizacion'])); ?>
                                                            • <i class="bi bi-person"></i> 
                                                            <?php echo htmlspecialchars($seg['autor_nombre']); ?>
                                                        </small>
                                                    </div>
                                                    <?php if ($index === 0): ?>
                                                        <span class="badge bg-success">Más reciente</span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($seg['descripcion'])); ?></p>
                                            </div>
                                        </div>
                                        
                                        <!-- Imagen del seguimiento (si existe) -->
                                        <?php if ($seg['imagen']): ?>
                                        <div class="col-md-5">
                                            <div class="seguimiento-image">
                                                <img src="uploads/<?php echo $seg['imagen']; ?>" 
                                                     class="img-fluid rounded-end" 
                                                     alt="<?php echo htmlspecialchars($seg['titulo']); ?>">
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Comentarios -->
            <div class="card" id="comentarios">
                <div class="card-header bg-white">
                    <h4 class="mb-0">
                        <i class="bi bi-chat-left-text"></i> Comentarios y Debates
                        <span class="badge bg-primary"><?php echo count($comentarios); ?></span>
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Formulario nuevo comentario -->
                    <form method="POST" action="" class="mb-4">
                        <input type="hidden" name="action" value="comentar">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Deja tu opinión</label>
                            <textarea class="form-control" 
                                      name="comentario" 
                                      rows="3" 
                                      placeholder="¿Qué opinas sobre esta propuesta?"
                                      required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Publicar Comentario (+<?php echo PUNTOS_COMENTARIO; ?> pts)
                        </button>
                    </form>
                    
                    <hr>
                    
                    <!-- Lista de comentarios -->
                    <?php if (empty($comentarios)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-chat" style="font-size: 3rem;"></i>
                            <p class="mt-2">Sé el primero en comentar</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($comentarios as $comentario): ?>
                            <div class="comment-box">
                                <div class="d-flex align-items-start">
                                    <div class="user-avatar me-3">
                                        <?php echo strtoupper(substr($comentario['autor_nombre'], 0, 2)); ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <span class="comment-author"><?php echo htmlspecialchars($comentario['autor_nombre']); ?></span>
                                                <span class="comment-time ms-2"><?php echo timeAgo($comentario['fecha_comentario']); ?></span>
                                            </div>
                                        </div>
                                        <p class="comment-text"><?php echo nl2br(htmlspecialchars($comentario['comentario'])); ?></p>
                                        <div class="comment-actions">
                                            <button class="btn btn-sm btn-outline-primary me-2 btn-like" 
                                                    data-comentario-id="<?php echo $comentario['id']; ?>">
                                                <i class="bi bi-hand-thumbs-up"></i> 
                                                Me gusta (<span class="like-count"><?php echo $comentario['total_likes']; ?></span>)
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" 
                                                    onclick="mostrarFormularioRespuesta(<?php echo $comentario['id']; ?>)">
                                                <i class="bi bi-reply"></i> Responder
                                            </button>
                                        </div>
                                        
                                        <!-- Formulario de respuesta -->
                                        <div id="respuesta-<?php echo $comentario['id']; ?>" class="mt-3" style="display: none;">
                                            <form method="POST" action="">
                                                <input type="hidden" name="action" value="comentar">
                                                <input type="hidden" name="comentario_padre_id" value="<?php echo $comentario['id']; ?>">
                                                <div class="input-group">
                                                    <textarea class="form-control" 
                                                              name="comentario" 
                                                              rows="2" 
                                                              placeholder="Escribe tu respuesta..."
                                                              required></textarea>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="bi bi-send"></i>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                        
                                        <!-- Respuestas -->
                                        <?php
                                        $respuestas = getRespuestas($comentario['id']);
                                        if (!empty($respuestas)):
                                        ?>
                                            <div class="mt-3">
                                                <?php foreach ($respuestas as $respuesta): ?>
                                                    <div class="reply-box">
                                                        <div class="d-flex align-items-start">
                                                            <div class="user-avatar me-2" style="width: 35px; height: 35px; font-size: 0.9rem;">
                                                                <?php echo strtoupper(substr($respuesta['autor_nombre'], 0, 2)); ?>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <span class="comment-author"><?php echo htmlspecialchars($respuesta['autor_nombre']); ?></span>
                                                                <span class="comment-time ms-2"><?php echo timeAgo($respuesta['fecha_comentario']); ?></span>
                                                                <p class="comment-text mb-1"><?php echo nl2br(htmlspecialchars($respuesta['comentario'])); ?></p>
                                                                <button class="btn btn-sm btn-link p-0 btn-like" 
                                                                        data-comentario-id="<?php echo $respuesta['id']; ?>">
                                                                    <i class="bi bi-hand-thumbs-up"></i> 
                                                                    <span class="like-count"><?php echo $respuesta['total_likes']; ?></span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Estado de Votación -->
            <?php if ($votacionActiva): ?>
                <div class="card mb-4 border-primary">
                    <div class="card-body text-center">
                        <?php if ($yaVotoEnVotacion): ?>
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                            <h4 class="mt-3 mb-2">¡Ya Votaste!</h4>
                            <p class="text-muted mb-3">Ya has votado en esta votación</p>
                            <a href="votaciones.php?id=<?php echo $votacionActiva['id']; ?>" class="btn btn-outline-primary w-100">
                                <i class="bi bi-eye"></i> Ver Resultados
                            </a>
                        <?php else: ?>
                            <i class="bi bi-box-arrow-in-right text-primary" style="font-size: 4rem;"></i>
                            <h4 class="mt-3 mb-2">Votación Activa</h4>
                            <p class="text-muted mb-3">Esta propuesta está en una votación activa</p>
                            <div class="alert alert-info mb-3 text-start">
                                <small>
                                    <i class="bi bi-info-circle"></i> 
                                    <strong><?php echo htmlspecialchars($votacionActiva['titulo']); ?></strong><br>
                                    Participa y gana <?php echo PUNTOS_VOTO; ?> puntos
                                </small>
                            </div>
                            <a href="votaciones.php?id=<?php echo $votacionActiva['id']; ?>" class="btn btn-primary btn-lg w-100">
                                <i class="bi bi-box-arrow-in-right"></i> Ir a Votación
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($propuesta['archivada']): ?>
                <div class="card mb-4 bg-secondary text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-archive" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">Propuesta Archivada</h5>
                        <p class="mb-0">Esta propuesta ha sido archivada</p>
                        <?php if ($propuesta['veces_usada_votacion'] > 0): ?>
                            <small class="mt-2 d-block">Participó en <?php echo $propuesta['veces_usada_votacion']; ?> votación(es)</small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($propuesta['estado'] === 'implementada'): ?>
                <div class="card mb-4 bg-success text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-trophy-fill" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">Propuesta Implementada</h5>
                        <p class="mb-0">Esta propuesta ganó una votación y está siendo implementada</p>
                    </div>
                </div>
            <?php elseif ($propuesta['estado'] === 'finalizada'): ?>
                <div class="card mb-4 bg-light">
                    <div class="card-body text-center">
                        <i class="bi bi-clock-history text-muted" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">Propuesta Finalizada</h5>
                        <p class="text-muted mb-2">Esta propuesta ha finalizado</p>
                        <small class="text-muted">Puede ser incluida en una votación próximamente</small>
                    </div>
                </div>
            <?php else: ?>
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <i class="bi bi-info-circle text-info" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">Propuesta en Revisión</h5>
                        <p class="text-muted mb-2">Esta propuesta está siendo evaluada</p>
                        <small class="text-muted">Pronto podrá ser incluida en una votación</small>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Información adicional -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Información</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <strong><i class="bi bi-calendar-event"></i> Inicio:</strong><br>
                            <?php echo formatDateTime($propuesta['fecha_inicio']); ?>
                        </li>
                        <li class="mb-2">
                            <strong><i class="bi bi-calendar-x"></i> Cierre:</strong><br>
                            <?php echo formatDateTime($propuesta['fecha_fin']); ?>
                        </li>
                        <li class="mb-2">
                            <strong><i class="bi bi-tag"></i> Categoría:</strong><br>
                            <?php echo ucfirst(str_replace('_', ' ', $propuesta['categoria'])); ?>
                        </li>
                        <li>
                            <strong><i class="bi bi-graph-up"></i> Estado:</strong><br>
                            <span class="badge bg-<?php echo $propuesta['estado'] === 'activa' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($propuesta['estado']); ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Compartir -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-share"></i> Compartir</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Comparte esta propuesta con tu comunidad</p>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="compartir('facebook')">
                            <i class="bi bi-facebook"></i> Facebook
                        </button>
                        <button class="btn btn-outline-info" onclick="compartir('twitter')">
                            <i class="bi bi-twitter"></i> Twitter
                        </button>
                        <button class="btn btn-outline-success" onclick="compartir('whatsapp')">
                            <i class="bi bi-whatsapp"></i> WhatsApp
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function mostrarFormularioRespuesta(comentarioId) {
    const form = document.getElementById('respuesta-' + comentarioId);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

// Manejar likes
document.querySelectorAll('.btn-like').forEach(btn => {
    btn.addEventListener('click', function() {
        const comentarioId = this.dataset.comentarioId;
        const likeCount = this.querySelector('.like-count');
        
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=like&comentario_id=${comentarioId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const currentCount = parseInt(likeCount.textContent);
                if (data.action === 'added') {
                    likeCount.textContent = currentCount + 1;
                    this.classList.remove('btn-outline-primary');
                    this.classList.add('btn-primary');
                } else {
                    likeCount.textContent = currentCount - 1;
                    this.classList.remove('btn-primary');
                    this.classList.add('btn-outline-primary');
                }
            }
        });
    });
});

function compartir(red) {
    const url = window.location.href;
    const titulo = '<?php echo addslashes($propuesta['titulo']); ?>';
    
    let shareUrl = '';
    
    switch(red) {
        case 'facebook':
            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
            break;
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(titulo)}&url=${encodeURIComponent(url)}`;
            break;
        case 'whatsapp':
            shareUrl = `https://wa.me/?text=${encodeURIComponent(titulo + ' ' + url)}`;
            break;
    }
    
    window.open(shareUrl, '_blank', 'width=600,height=400');
}
</script>

<style>
/* Estilos para Seguimiento */
.timeline {
    position: relative;
    padding-left: 0;
}

.seguimiento-box {
    position: relative;
    padding-left: 60px;
    padding-bottom: 40px;
    border-left: 3px solid #dee2e6;
    margin-left: 20px;
}

.seguimiento-box:last-child {
    border-left-color: transparent;
    padding-bottom: 0;
}

.seguimiento-timeline-icon {
    position: absolute;
    left: -14px;
    top: 0;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #198754;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    border: 3px solid white;
    box-shadow: 0 0 0 3px #198754;
}

.seguimiento-latest .seguimiento-timeline-icon {
    background: #ffc107;
    box-shadow: 0 0 0 3px #ffc107;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(255, 193, 7, 0);
    }
}

.seguimiento-content {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.seguimiento-content:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.seguimiento-latest .seguimiento-content {
    border: 2px solid #ffc107;
    background: #fffbf0;
}

.seguimiento-image {
    border-radius: 8px;
    overflow: hidden;
    max-width: 100%;
    width: 100%;
}

.seguimiento-image img {
    transition: transform 0.3s ease;
    object-fit: cover;
    width: 100%;
    max-height: 300px;
    display: block;
}

.seguimiento-image img:hover {
    transform: scale(1.02);
}

/* Responsive */
@media (max-width: 768px) {
    .seguimiento-box {
        padding-left: 40px;
        margin-left: 10px;
    }
    
    .seguimiento-timeline-icon {
        left: -12px;
        width: 24px;
        height: 24px;
        font-size: 12px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
