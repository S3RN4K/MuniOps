<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$pageTitle = 'Ranking - MuniOps';

// Obtener ranking
$ranking = getRanking(50);

// Si está logueado, obtener su posición
$miPosicion = null;
$misDatos = null;
if (isLoggedIn()) {
    $miPosicion = getUserRank(getUserId());
    $misDatos = fetchOne("SELECT * FROM ranking_usuarios WHERE id = ?", [getUserId()]);
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="text-center mb-5">
        <h1 class="fw-bold">
            <i class="bi bi-trophy text-warning"></i> Ranking de Ciudadanos
        </h1>
        <p class="lead text-muted">Los ciudadanos más activos de nuestra comunidad</p>
    </div>
    
    <?php if (isLoggedIn() && $misDatos): ?>
    <!-- Mi Posición -->
    <div class="card mb-4 border-primary">
        <div class="card-body">
            <h5 class="card-title">
                <i class="bi bi-person-circle"></i> Tu Posición
            </h5>
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <div class="position-badge bg-primary text-white" style="width: 60px; height: 60px; font-size: 1.5rem;">
                        #<?php echo $miPosicion; ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <h5 class="mb-1"><?php echo htmlspecialchars($misDatos['nombre_completo']); ?></h5>
                    <small class="text-muted">DNI: <?php echo $misDatos['dni']; ?></small>
                </div>
                <div class="col-md-7">
                    <div class="row text-center">
                        <div class="col-3">
                            <h4 class="text-primary mb-0"><?php echo number_format($misDatos['puntos']); ?></h4>
                            <small class="text-muted">Puntos</small>
                        </div>
                        <div class="col-3">
                            <h4 class="text-success mb-0"><?php echo $misDatos['total_votos']; ?></h4>
                            <small class="text-muted">Votos</small>
                        </div>
                        <div class="col-3">
                            <h4 class="text-info mb-0"><?php echo $misDatos['total_comentarios']; ?></h4>
                            <small class="text-muted">Comentarios</small>
                        </div>
                        <div class="col-3">
                            <h4 class="text-warning mb-0"><?php echo $misDatos['total_recompensas']; ?></h4>
                            <small class="text-muted">Logros</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Tabla de Ranking -->
    <div class="card ranking-table">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-center" style="width: 80px;">Posición</th>
                            <th>Ciudadano</th>
                            <th class="text-center">Puntos</th>
                            <th class="text-center">Votos</th>
                            <th class="text-center">Comentarios</th>
                            <th class="text-center">Logros</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ranking)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                    <p class="mt-2">No hay datos de ranking aún</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ranking as $index => $usuario): ?>
                                <tr class="<?php echo isLoggedIn() && $usuario['id'] == getUserId() ? 'table-primary' : ''; ?>">
                                    <td class="text-center">
                                        <?php if ($usuario['posicion'] <= 3): ?>
                                            <span class="position-badge position-<?php echo $usuario['posicion']; ?>">
                                                #<?php echo $usuario['posicion']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                #<?php echo $usuario['posicion']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3">
                                                <?php echo strtoupper(substr($usuario['nombre_completo'], 0, 2)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($usuario['nombre_completo']); ?></strong>
                                                <?php if (isLoggedIn() && $usuario['id'] == getUserId()): ?>
                                                    <span class="badge bg-primary ms-2">Tú</span>
                                                <?php endif; ?>
                                                <br>
                                                <small class="text-muted">DNI: <?php echo $usuario['dni']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary fs-6">
                                            <?php echo number_format($usuario['puntos']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <i class="bi bi-check-circle text-success"></i>
                                        <?php echo $usuario['total_votos']; ?>
                                    </td>
                                    <td class="text-center">
                                        <i class="bi bi-chat text-info"></i>
                                        <?php echo $usuario['total_comentarios']; ?>
                                    </td>
                                    <td class="text-center">
                                        <i class="bi bi-award text-warning"></i>
                                        <?php echo $usuario['total_recompensas']; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Información sobre puntos -->
    <div class="row mt-5">
        <div class="col-md-12">
            <div class="card bg-light">
                <div class="card-body">
                    <h4 class="fw-bold mb-4">
                        <i class="bi bi-info-circle"></i> ¿Cómo ganar puntos?
                    </h4>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center"
                                         style="width: 50px; height: 50px;">
                                        <i class="bi bi-hand-thumbs-up"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="mb-0"><?php echo PUNTOS_VOTO; ?> Puntos</h5>
                                    <small class="text-muted">Por cada voto en una propuesta</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center"
                                         style="width: 50px; height: 50px;">
                                        <i class="bi bi-chat"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="mb-0"><?php echo PUNTOS_COMENTARIO; ?> Puntos</h5>
                                    <small class="text-muted">Por cada comentario publicado</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="rounded-circle bg-warning text-white d-flex align-items-center justify-content-center"
                                         style="width: 50px; height: 50px;">
                                        <i class="bi bi-heart"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="mb-0"><?php echo PUNTOS_LIKE_RECIBIDO; ?> Puntos</h5>
                                    <small class="text-muted">Por cada like que recibas</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Call to Action -->
    <?php if (!isLoggedIn()): ?>
    <div class="text-center mt-5">
        <h3 class="mb-3">¿Quieres aparecer en el ranking?</h3>
        <a href="registro.php" class="btn btn-primary btn-lg px-5">
            <i class="bi bi-person-plus"></i> Únete Ahora
        </a>
    </div>
    <?php else: ?>
    <div class="text-center mt-5">
        <a href="propuestas.php" class="btn btn-primary btn-lg px-5">
            <i class="bi bi-lightbulb"></i> Ver Propuestas y Participar
        </a>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
