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

// Procesar voto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'votar') {
    if ($propuesta['estado'] === 'activa' && $propuesta['dias_restantes'] > 0) {
        if (!hasVoted(getUserId(), $propuestaId)) {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            if (registerVote(getUserId(), $propuestaId, $ipAddress)) {
                setFlashMessage('¡Gracias por tu voto! Has ganado ' . PUNTOS_VOTO . ' puntos', 'success');
                redirect('propuesta-detalle.php?id=' . $propuestaId);
            } else {
                setFlashMessage('Error al registrar tu voto', 'danger');
            }
        } else {
            setFlashMessage('Ya has votado en esta propuesta', 'warning');
        }
    }
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

// Procesar like en comentario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'like') {
    $comentarioId = $_POST['comentario_id'] ?? 0;
    $result = toggleLikeComentario($comentarioId, getUserId());
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'action' => $result]);
    exit;
}

$yaVoto = hasVoted(getUserId(), $propuestaId);
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
            <!-- Botón de voto -->
            <?php if ($propuesta['estado'] === 'activa' && $propuesta['dias_restantes'] > 0): ?>
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <?php if ($yaVoto): ?>
                            <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                            <h4 class="mt-3 mb-3">¡Ya Votaste!</h4>
                            <p class="text-muted">Gracias por tu participación</p>
                            <button class="btn btn-secondary w-100" disabled>
                                <i class="bi bi-check2-circle"></i> Voto Registrado
                            </button>
                        <?php else: ?>
                            <i class="bi bi-hand-thumbs-up text-primary" style="font-size: 4rem;"></i>
                            <h4 class="mt-3 mb-3">¿Apoyas esta propuesta?</h4>
                            <p class="text-muted mb-3">Tu voto ayudará a priorizar esta iniciativa</p>
                            <form method="POST" action="" onsubmit="return confirm('¿Confirmas tu voto por esta propuesta?')">
                                <input type="hidden" name="action" value="votar">
                                <button type="submit" class="btn btn-vote btn-lg w-100">
                                    <i class="bi bi-hand-thumbs-up"></i> Votar Ahora
                                    <br><small>(+<?php echo PUNTOS_VOTO; ?> puntos)</small>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card mb-4 bg-light">
                    <div class="card-body text-center">
                        <i class="bi bi-exclamation-circle text-warning" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">Votación Finalizada</h5>
                        <p class="text-muted mb-0">Esta propuesta ya no acepta votos</p>
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

<?php include 'includes/footer.php'; ?>
