<?php
/**
 * Sincronizar BD local a Railway - Versión mejorada v2
 */

// Configuración Railway
$railwayConfig = [
    'host' => 'nozomi.proxy.rlwy.net',
    'port' => 50599,
    'user' => 'root',
    'password' => 'SbPLDWRfjsRUtVHbxRBURYqktfpCQTlo',
    'database' => 'railway',
];

// Conectar a BD local
$localPdo = new PDO(
    'mysql:host=localhost;dbname=muniops;charset=utf8mb4',
    'root',
    '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Conectar a Railway
$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    $railwayConfig['host'],
    $railwayConfig['port'],
    $railwayConfig['database']
);

$remotePdo = new PDO(
    $dsn,
    $railwayConfig['user'],
    $railwayConfig['password'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]
);

echo "✅ Conectado a Railway\n";

// Desactivar FK
$remotePdo->exec('SET foreign_key_checks = 0');

// Obtener todas las tablas
$tables = $localPdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

echo "📋 Tablas encontradas: " . implode(', ', $tables) . "\n";

foreach ($tables as $table) {
    echo "\n⏳ Procesando tabla: $table\n";
    
    try {
        // Obtener definición de tabla
        $createResult = $localPdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        
        // Si es una vista, obtener el CREATE VIEW en lugar del CREATE TABLE
        if (!isset($createResult['Create Table'])) {
            $createResult = $localPdo->query("SHOW CREATE VIEW `$table`")->fetch(PDO::FETCH_ASSOC);
            $createSql = $createResult['Create View'] ?? null;
            
            if (!$createSql) {
                echo "⏭️  Saltando vista: $table\n";
                continue;
            }
            
            // Dropear vista en remoto
            $remotePdo->exec("DROP VIEW IF EXISTS `$table`");
        } else {
            $createSql = $createResult['Create Table'];
            // Dropear tabla en remoto
            $remotePdo->exec("DROP TABLE IF EXISTS `$table`");
        }
        
        // Crear tabla/vista en remoto
        $remotePdo->exec($createSql);
        
        echo "✅ Tabla $table creada\n";
        
        // Obtener datos
        $rows = $localPdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rows)) {
            echo "   (sin datos)\n";
            continue;
        }
        
        // Insertar datos
        foreach ($rows as $row) {
            $cols = implode('`, `', array_keys($row));
            $vals = array_fill(0, count($row), '?');
            $vals = implode(',', $vals);
            
            $sql = "INSERT INTO `$table` (`$cols`) VALUES ($vals)";
            $remotePdo->prepare($sql)->execute(array_values($row));
        }
        
        echo "✅ " . count($rows) . " registros insertados en $table\n";
        
    } catch (Exception $e) {
        echo "❌ Error en $table: " . $e->getMessage() . "\n";
    }
}

// Reactivar FK
$remotePdo->exec('SET foreign_key_checks = 1');

echo "\n🎉 ¡Base de datos sincronizada completamente a Railway!\n";
echo "✅ Puedes acceder a: mysql://root:***@nozomi.proxy.rlwy.net:50599/railway\n";
