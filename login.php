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

$pageTitle = 'Iniciar Sesión - MuniOps';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni = sanitizeInput($_POST['dni'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($dni) || empty($password)) {
        $error = 'Por favor, completa todos los campos';
    } elseif (!validarDNI($dni)) {
        $error = 'DNI inválido. Debe tener 8 dígitos';
    } else {
        $user = getUserByDNI($dni);
        
        if ($user && verifyPassword($password, $user['password'])) {
            if ($user['estado'] !== 'activo') {
                $error = 'Tu cuenta está inactiva o bloqueada. Contacta al administrador';
            } else {
                // Iniciar sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_dni'] = $user['dni'];
                $_SESSION['user_name'] = $user['nombres'] . ' ' . $user['apellido_paterno'];
                $_SESSION['user_role'] = $user['rol'];
                
                updateLastAccess($user['id']);
                
                setFlashMessage('¡Bienvenido de nuevo, ' . $user['nombres'] . '!', 'success');
                
                // Limpiar buffer y redirigir
                ob_end_clean();
                if ($user['rol'] === 'admin') {
                    header('Location: ' . BASE_URL . 'admin/dashboard.php');
                } else {
                    header('Location: ' . BASE_URL . 'propuestas.php');
                }
                exit();
            }
        } else {
            $error = 'DNI o contraseña incorrectos';
        }
    }
}

include 'includes/header.php';
?>

<div class="auth-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card auth-card shadow-lg">
                    <div class="card-body p-5">
                        <div class="auth-header">
                            <i class="bi bi-shield-lock"></i>
                            <h2 class="fw-bold mt-3">Iniciar Sesión</h2>
                            <p class="text-muted">Accede a tu cuenta de MuniOps</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="mb-4">
                                <label for="dni" class="form-label fw-bold">
                                    <i class="bi bi-card-text"></i> DNI
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg" 
                                       id="dni" 
                                       name="dni" 
                                       placeholder="Ingresa tu DNI" 
                                       maxlength="8" 
                                       pattern="\d{8}"
                                       value="<?php echo htmlspecialchars($dni ?? ''); ?>"
                                       required>
                                <div class="invalid-feedback">
                                    Por favor ingresa un DNI válido de 8 dígitos
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label fw-bold">
                                    <i class="bi bi-lock"></i> Contraseña
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control form-control-lg" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Ingresa tu contraseña"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Por favor ingresa tu contraseña
                                </div>
                            </div>
                            
                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Recordarme
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                                <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                            </button>
                            
                            <div class="text-center">
                                <p class="text-muted">
                                    ¿No tienes cuenta? 
                                    <a href="registro.php" class="text-primary fw-bold text-decoration-none">
                                        Regístrate aquí
                                    </a>
                                </p>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <small class="text-muted">
                                <i class="bi bi-shield-check"></i> Tus datos están protegidos
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle mostrar/ocultar contraseña
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const icon = this.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
});

// Validación en tiempo real de DNI
document.getElementById('dni').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').substring(0, 8);
});
</script>

<?php include 'includes/footer.php'; ?>
