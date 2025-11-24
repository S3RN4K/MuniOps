<?php
// Evitar cualquier salida antes de los headers
ob_start();

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Si ya está logueado, redirigir
if (isLoggedIn()) {
    redirect('index.php');
}

$pageTitle = 'Registro - MuniOps';
$error = '';
$success = '';
$dniData = null;

// Cargar municipios
$municipios = getAllMunicipios();

// Procesar consulta de DNI via AJAX
if (isset($_GET['consultar_dni'])) {
    header('Content-Type: application/json');
    $dni = $_GET['dni'] ?? '';
    
    if (validarDNI($dni)) {
        // Verificar si ya existe
        $existingUser = getUserByDNI($dni);
        if ($existingUser) {
            echo json_encode(['error' => 'Este DNI ya está registrado']);
            exit;
        }
        
        // Consultar API
        $data = consultarDNI($dni);
        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['error' => 'No se pudo consultar el DNI. Intenta nuevamente']);
        }
    } else {
        echo json_encode(['error' => 'DNI inválido']);
    }
    exit;
}

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni = sanitizeInput($_POST['dni'] ?? '');
    $nombres = sanitizeInput($_POST['nombres'] ?? '');
    $apellido_paterno = sanitizeInput($_POST['apellido_paterno'] ?? '');
    $apellido_materno = sanitizeInput($_POST['apellido_materno'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefono = sanitizeInput($_POST['telefono'] ?? '');
    $municipio_id = (int)($_POST['municipio_id'] ?? 0);
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Validaciones
    if (empty($dni) || empty($nombres) || empty($apellido_paterno) || empty($apellido_materno) || empty($email) || empty($password) || empty($municipio_id)) {
        $error = 'Por favor, completa todos los campos obligatorios';
    } elseif (!validarDNI($dni)) {
        $error = 'DNI inválido. Debe tener 8 dígitos';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif ($password !== $password_confirm) {
        $error = 'Las contraseñas no coinciden';
    } else {
        // Verificar si el DNI ya existe
        if (getUserByDNI($dni)) {
            $error = 'Este DNI ya está registrado';
        } elseif (fetchOne("SELECT id FROM usuarios WHERE email = ?", [$email])) {
            $error = 'Este email ya está registrado';
        } else {
            // Crear usuario
            $userData = [
                'dni' => $dni,
                'nombres' => $nombres,
                'apellido_paterno' => $apellido_paterno,
                'apellido_materno' => $apellido_materno,
                'email' => $email,
                'telefono' => $telefono,
                'municipio_id' => $municipio_id,
                'password' => $password
            ];
            
            try {
                $userId = createUser($userData);
                
                if ($userId && $userId > 0) {
                    // Otorgar primer logro
                    addPoints($userId, 0, 'bono', 'Registro completado', null);
                    
                    setFlashMessage('¡Registro exitoso! Ya puedes iniciar sesión', 'success');
                    
                    // Limpiar buffer y redirigir
                    ob_end_clean();
                    header('Location: ' . BASE_URL . 'login.php');
                    exit();
                } else {
                    $error = 'Error al crear la cuenta. Intenta nuevamente';
                }
            } catch (Exception $e) {
                $error = 'Error al crear la cuenta: ' . $e->getMessage();
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="auth-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card auth-card shadow-lg">
                    <div class="card-body p-5">
                        <div class="auth-header">
                            <i class="bi bi-person-plus"></i>
                            <h2 class="fw-bold mt-3">Crear Cuenta</h2>
                            <p class="text-muted">Únete a la comunidad de MuniOps</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <!-- DNI -->
                            <div class="mb-4">
                                <label for="dni" class="form-label fw-bold">
                                    <i class="bi bi-card-text"></i> DNI <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="text" 
                                           class="form-control form-control-lg" 
                                           id="dni" 
                                           name="dni" 
                                           placeholder="Ingresa tu DNI" 
                                           maxlength="8"
                                           pattern="\d{8}"
                                           required>
                                    <button class="btn btn-primary" type="button" id="btnConsultarDNI">
                                        <i class="bi bi-search"></i> Consultar
                                    </button>
                                </div>
                                <small class="text-muted">Consultaremos tus datos automáticamente</small>
                                <div class="invalid-feedback">
                                    Por favor ingresa un DNI válido de 8 dígitos
                                </div>
                                <div id="dniMessage"></div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="nombres" class="form-label fw-bold">
                                        Nombres <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="nombres" 
                                           name="nombres"
                                           required>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="apellido_paterno" class="form-label fw-bold">
                                        Apellido Paterno <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="apellido_paterno" 
                                           name="apellido_paterno"
                                           required>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="apellido_materno" class="form-label fw-bold">
                                        Apellido Materno <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="apellido_materno" 
                                           name="apellido_materno"
                                           required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label fw-bold">
                                        <i class="bi bi-envelope"></i> Email <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email"
                                           placeholder="tu@email.com"
                                           required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="telefono" class="form-label fw-bold">
                                        <i class="bi bi-telephone"></i> Teléfono
                                    </label>
                                    <input type="tel" 
                                           class="form-control" 
                                           id="telefono" 
                                           name="telefono"
                                           placeholder="999999999"
                                           maxlength="9">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="municipio_id" class="form-label fw-bold">
                                        <i class="bi bi-geo-alt"></i> Municipio <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control" id="municipio_id" name="municipio_id" required>
                                        <option value="">-- Selecciona tu municipio --</option>
                                        <?php foreach ($municipios as $muni): ?>
                                            <option value="<?php echo $muni['id']; ?>">
                                                <?php echo htmlspecialchars($muni['nombre']) . ' (' . htmlspecialchars($muni['departamento']) . ')'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Solo podrás votar propuestas de tu municipio</small>
                                </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label fw-bold">
                                        <i class="bi bi-lock"></i> Contraseña <span class="text-danger">*</span>
                                    </label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password"
                                           minlength="6"
                                           required>
                                    <small class="text-muted">Mínimo 6 caracteres</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="password_confirm" class="form-label fw-bold">
                                        <i class="bi bi-lock-fill"></i> Confirmar Contraseña <span class="text-danger">*</span>
                                    </label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password_confirm" 
                                           name="password_confirm"
                                           minlength="6"
                                           required>
                                </div>
                            </div>
                            
                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    Acepto los <a href="#" class="text-primary">términos y condiciones</a>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                                <i class="bi bi-person-plus"></i> Crear Cuenta
                            </button>
                            
                            <div class="text-center">
                                <p class="text-muted">
                                    ¿Ya tienes cuenta? 
                                    <a href="login.php" class="text-primary fw-bold text-decoration-none">
                                        Inicia sesión aquí
                                    </a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validación solo números en DNI
document.getElementById('dni').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').substring(0, 8);
});

// Consultar DNI
document.getElementById('btnConsultarDNI').addEventListener('click', function() {
    const dni = document.getElementById('dni').value;
    const messageDiv = document.getElementById('dniMessage');
    const btn = this;
    
    if (!MuniOps.validarDNI(dni)) {
        messageDiv.innerHTML = '<div class="alert alert-warning mt-2"><i class="bi bi-exclamation-triangle"></i> Por favor ingresa un DNI válido</div>';
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Consultando...';
    
    fetch(`?consultar_dni=1&dni=${dni}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                messageDiv.innerHTML = `<div class="alert alert-danger mt-2"><i class="bi bi-x-circle"></i> ${data.error}</div>`;
            } else if (data.success) {
                messageDiv.innerHTML = '<div class="alert alert-success mt-2"><i class="bi bi-check-circle"></i> DNI encontrado. Datos cargados automáticamente</div>';
                
                // Llenar campos
                document.getElementById('nombres').value = data.data.nombres || '';
                document.getElementById('apellido_paterno').value = data.data.apellidoPaterno || '';
                document.getElementById('apellido_materno').value = data.data.apellidoMaterno || '';
            }
        })
        .catch(error => {
            messageDiv.innerHTML = '<div class="alert alert-danger mt-2"><i class="bi bi-x-circle"></i> Error al consultar. Puedes llenar los datos manualmente</div>';
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-search"></i> Consultar';
        });
});

// Validar que las contraseñas coincidan
document.getElementById('password_confirm').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    if (this.value !== password) {
        this.setCustomValidity('Las contraseñas no coinciden');
    } else {
        this.setCustomValidity('');
    }
});

// Validar teléfono
document.getElementById('telefono').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').substring(0, 9);
});
</script>

<?php include 'includes/footer.php'; ?>
