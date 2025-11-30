<?php
/**
 * Cargar variables de entorno desde archivo .env
 * Este archivo se incluye en config/database.php
 */

$envFile = __DIR__ . '/../.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorar comentarios
        if (strpos($line, '#') === 0) {
            continue;
        }
        
        // Parsear variable=valor
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Quitar comillas si existen
            if (substr($value, 0, 1) === '"' && substr($value, -1) === '"') {
                $value = substr($value, 1, -1);
            }
            
            // Establecer como variable de entorno
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}
