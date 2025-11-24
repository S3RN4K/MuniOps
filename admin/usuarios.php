<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();
requireAdmin();

$pageTitle = 'Gestión de Usuarios - MuniOps';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $usuarioId = $_POST['usuario_id'] ?? 0;
    
    if ($_POST['action'] === 'toggle_estado') {
        $nuevoEstado = $_POST['nuevo_estado'];
        execute("UPDATE usuarios SET estado = ? WHERE id = ?", [$nuevoEstado, $usuarioId]);
        setFlashMessage('Estado del usuario actualizado', 'success');
        redirect('admin/usuarios.php');
    } elseif ($_POST['action'] === 'cambiar_rol') {
        $nuevoRol = $_POST['nuevo_rol'];
        execute("UPDATE usuarios SET rol = ? WHERE id = ?", [$nuevoRol, $usuarioId]);
        setFlashMessage('Rol del usuario actualizado', 'success');
        redirect('admin/usuarios.php');
    }
}

// Filtros
$filtroRol = $_GET['rol'] ?? 'todos';
$filtroEstado = $_GET['estado'] ?? 'todos';

$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM votos WHERE usuario_id = u.id) as total_votos,
        (SELECT COUNT(*) FROM comentarios WHERE usuario_id = u.id) as total_comentarios
        FROM usuarios u
        WHERE 1=1";
$params = [];

if ($filtroRol !== 'todos') {
    $sql .= " AND u.rol = ?";
    $params[] = $filtroRol;
}

if ($filtroEstado !== 'todos') {
    $sql .= " AND u.estado = ?";
    $params[] = $filtroEstado;
}

$sql .= " ORDER BY u.fecha_registro DESC";
$usuarios = fetchAll($sql, $params);

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
                <a href="usuarios.php" class="list-group-item list-group-item-action active">
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
            <h1 class="fw-bold mb-4">
                <i class="bi bi-people"></i> Gestión de Usuarios
            </h1>
            
            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Rol</label>
                            <select class="form-select" id="filtroRol" onchange="aplicarFiltros()">
                                <option value="todos" <?php echo $filtroRol === 'todos' ? 'selected' : ''; ?>>Todos</option>
                                <option value="ciudadano" <?php echo $filtroRol === 'ciudadano' ? 'selected' : ''; ?>>Ciudadanos</option>
                                <option value="admin" <?php echo $filtroRol === 'admin' ? 'selected' : ''; ?>>Administradores</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Estado</label>
                            <select class="form-select" id="filtroEstado" onchange="aplicarFiltros()">
                                <option value="todos" <?php echo $filtroEstado === 'todos' ? 'selected' : ''; ?>>Todos</option>
                                <option value="activo" <?php echo $filtroEstado === 'activo' ? 'selected' : ''; ?>>Activos</option>
                                <option value="inactivo" <?php echo $filtroEstado === 'inactivo' ? 'selected' : ''; ?>>Inactivos</option>
                                <option value="bloqueado" <?php echo $filtroEstado === 'bloqueado' ? 'selected' : ''; ?>>Bloqueados</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Total</label>
                            <div class="alert alert-info mb-0">
                                <strong><?php echo count($usuarios); ?></strong> usuarios
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabla -->
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Usuario</th>
                                    <th>DNI</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Puntos</th>
                                    <th>Actividad</th>
                                    <th>Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $u): ?>
                                    <tr>
                                        <td><?php echo $u['id']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-2" style="width: 35px; height: 35px; font-size: 0.9rem;">
                                                    <?php echo strtoupper(substr($u['nombres'], 0, 2)); ?>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($u['nombres'] . ' ' . $u['apellido_paterno']); ?></strong>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo $u['dni']; ?></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $u['rol'] === 'admin' ? 'danger' : 'primary'; ?>">
                                                <?php echo ucfirst($u['rol']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $u['estado'] === 'activo' ? 'success' : ($u['estado'] === 'bloqueado' ? 'danger' : 'secondary'); ?>">
                                                <?php echo ucfirst($u['estado']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($u['puntos']); ?></td>
                                        <td>
                                            <small>
                                                <?php echo $u['total_votos']; ?> votos<br>
                                                <?php echo $u['total_comentarios']; ?> comentarios
                                            </small>
                                        </td>
                                        <td>
                                            <small><?php echo formatDate($u['fecha_registro']); ?></small>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" 
                                                        type="button" 
                                                        data-bs-toggle="dropdown">
                                                    <i class="bi bi-gear"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <?php if ($u['estado'] === 'activo'): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="cambiarEstado(<?php echo $u['id']; ?>, 'inactivo')">
                                                                <i class="bi bi-pause-circle"></i> Desactivar
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="#" onclick="cambiarEstado(<?php echo $u['id']; ?>, 'bloqueado')">
                                                                <i class="bi bi-slash-circle"></i> Bloquear
                                                            </a>
                                                        </li>
                                                    <?php else: ?>
                                                        <li>
                                                            <a class="dropdown-item text-success" href="#" onclick="cambiarEstado(<?php echo $u['id']; ?>, 'activo')">
                                                                <i class="bi bi-check-circle"></i> Activar
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($u['rol'] === 'ciudadano'): ?>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="cambiarRol(<?php echo $u['id']; ?>, 'admin')">
                                                                <i class="bi bi-shield"></i> Hacer Admin
                                                            </a>
                                                        </li>
                                                    <?php elseif ($u['id'] != getUserId()): ?>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="cambiarRol(<?php echo $u['id']; ?>, 'ciudadano')">
                                                                <i class="bi bi-person"></i> Hacer Ciudadano
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
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

<!-- Forms ocultos -->
<form id="estadoForm" method="POST" action="" style="display: none;">
    <input type="hidden" name="action" value="toggle_estado">
    <input type="hidden" name="usuario_id" id="estadoUsuarioId">
    <input type="hidden" name="nuevo_estado" id="nuevoEstado">
</form>

<form id="rolForm" method="POST" action="" style="display: none;">
    <input type="hidden" name="action" value="cambiar_rol">
    <input type="hidden" name="usuario_id" id="rolUsuarioId">
    <input type="hidden" name="nuevo_rol" id="nuevoRol">
</form>

<script>
function aplicarFiltros() {
    const rol = document.getElementById('filtroRol').value;
    const estado = document.getElementById('filtroEstado').value;
    window.location.href = `usuarios.php?rol=${rol}&estado=${estado}`;
}

function cambiarEstado(usuarioId, estado) {
    if (confirm(`¿Confirmas cambiar el estado del usuario a "${estado}"?`)) {
        document.getElementById('estadoUsuarioId').value = usuarioId;
        document.getElementById('nuevoEstado').value = estado;
        document.getElementById('estadoForm').submit();
    }
}

function cambiarRol(usuarioId, rol) {
    if (confirm(`¿Confirmas cambiar el rol del usuario a "${rol}"?`)) {
        document.getElementById('rolUsuarioId').value = usuarioId;
        document.getElementById('nuevoRol').value = rol;
        document.getElementById('rolForm').submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
