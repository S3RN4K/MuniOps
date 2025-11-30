<?php
/**
 * Script para importar BD a Railway
 * Uso: php import-to-railway.php
 */

// Configuración Railway
$railwayConfig = [
    'host' => 'nozomi.proxy.rlwy.net',
    'port' => 50599,
    'user' => 'root',
    'password' => 'SbPLDWRfjsRUtVHbxRBURYqktfpCQTlo',
    'database' => 'railway',
];

// Archivo de exportación
$sqlFile = __DIR__ . '/database/muniops-export.sql';

if (!file_exists($sqlFile)) {
    echo "❌ Archivo $sqlFile no encontrado\n";
    exit(1);
}

try {
    // Conectar a Railway
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $railwayConfig['host'],
        $railwayConfig['port'],
        $railwayConfig['database']
    );
    
    $pdo = new PDO(
        $dsn,
        $railwayConfig['user'],
        $railwayConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );
    
    echo "✅ Conectado a Railway\n";
    
    // Leer el archivo SQL
    $sql = file_get_contents($sqlFile);
    
    // Dividir por ; y ejecutar cada sentencia
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    $count = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        try {
            $pdo->exec($statement);
            $count++;
            if ($count % 10 === 0) {
                echo ".";
            }
        } catch (PDOException $e) {
            // Ignorar algunas advertencias comunes
            if (strpos($e->getMessage(), 'Unknown character set') === false &&
                strpos($e->getMessage(), 'Duplicate column name') === false) {
                echo "\n⚠️ Error en sentencia: " . substr($statement, 0, 50) . "...\n";
                echo "Mensaje: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✅ Base de datos importada exitosamente a Railway\n";
    echo "📊 Total de sentencias ejecutadas: $count\n";
    
} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
}
