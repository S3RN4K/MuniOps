<?php
require_once 'config/config.php';

// Destruir sesión
session_destroy();

// Redirigir al login
redirect('login.php');
?>
