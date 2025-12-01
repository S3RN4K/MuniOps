<?php
// Configuración general del sistema
date_default_timezone_set('America/Lima');

// URLs del sistema
define('BASE_URL', 'http://localhost/MuniOps/');
define('SITE_NAME', 'MuniOps - Participación Ciudadana');

// Configuración de sesiones
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 si usas HTTPS

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// API DNI - RENIEC (Perú)
define('API_DNI_URL', 'https://dniruc.apisperu.com/api/v1/dni/');
define('API_DNI_TOKEN', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJlbWFpbCI6ImRhbmllbC5sb3Blem1AZ21haWwuY29tIn0.WfNJyYYlD7_yNjb9GcDoKN3sBJNNMaz8MhWGfQQfUD0'); // Reemplazar con tu token

// Puntos por acción
define('PUNTOS_VOTO', 10);
define('PUNTOS_COMENTARIO', 5);
define('PUNTOS_LIKE_RECIBIDO', 2);

// Configuración de archivos
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 52428800); // 50MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Funciones auxiliares
function redirect($url) {
    // Si hay buffer de salida, limpiarlo
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    header("Location: " . BASE_URL . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        redirect('index.php');
    }
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'hace ' . $diff . ' segundos';
    } elseif ($diff < 3600) {
        return 'hace ' . floor($diff / 60) . ' minutos';
    } elseif ($diff < 86400) {
        return 'hace ' . floor($diff / 3600) . ' horas';
    } elseif ($diff < 2592000) {
        return 'hace ' . floor($diff / 86400) . ' días';
    } else {
        return formatDate($datetime);
    }
}
