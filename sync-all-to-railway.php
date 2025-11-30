<?php
/**
 * Script maestro para sincronizar BD local a Railway
 * Sincroniza: tablas, vistas y datos
 * Uso: php sync-all-to-railway.php
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

echo "✅ Conectado a Railway\n\n";

// Desactivar FK
$remotePdo->exec('SET foreign_key_checks = 0');

// ============================================
// PASO 1: Sincronizar TABLAS
// ============================================
echo "📊 PASO 1: Sincronizando tablas...\n";
echo "================================================\n";

$tables = $localPdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$tablesProcessed = 0;
$tablesSkipped = [];

foreach ($tables as $table) {
    try {
        $createResult = $localPdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        
        if (!isset($createResult['Create Table'])) {
            $tablesSkipped[] = $table;
            continue;
        }
        
        $createSql = $createResult['Create Table'];
        
        // Dropear y recrear
        $remotePdo->exec("DROP TABLE IF EXISTS `$table`");
        $remotePdo->exec($createSql);
        
        // Insertar datos
        $rows = $localPdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $cols = implode('`, `', array_keys($row));
                $vals = array_fill(0, count($row), '?');
                $vals = implode(',', $vals);
                
                $sql = "INSERT INTO `$table` (`$cols`) VALUES ($vals)";
                $remotePdo->prepare($sql)->execute(array_values($row));
            }
            echo "✅ $table (" . count($rows) . " registros)\n";
        } else {
            echo "✅ $table (vacía)\n";
        }
        
        $tablesProcessed++;
        
    } catch (Exception $e) {
        echo "⚠️  $table: " . substr($e->getMessage(), 0, 50) . "\n";
    }
}

echo "\n✅ Tablas sincronizadas: $tablesProcessed\n";
if (!empty($tablesSkipped)) {
    echo "⏭️  Tablas saltadas (vistas): " . implode(', ', $tablesSkipped) . "\n";
}

// ============================================
// PASO 2: Sincronizar VISTAS
// ============================================
echo "\n📊 PASO 2: Sincronizando vistas...\n";
echo "================================================\n";

$views = $localPdo->query(
    "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = 'muniops'"
)->fetchAll(PDO::FETCH_COLUMN);

$viewsProcessed = 0;

foreach ($views as $view) {
    try {
        $createResult = $localPdo->query("SHOW CREATE VIEW `$view`")->fetch(PDO::FETCH_ASSOC);
        $createSql = $createResult['Create View'];
        
        $remotePdo->exec("DROP VIEW IF EXISTS `$view`");
        $remotePdo->exec($createSql);
        
        echo "✅ $view\n";
        $viewsProcessed++;
        
    } catch (Exception $e) {
        echo "⚠️  $view: " . substr($e->getMessage(), 0, 50) . "\n";
    }
}

echo "\n✅ Vistas sincronizadas: $viewsProcessed\n";

// Reactivar FK
$remotePdo->exec('SET foreign_key_checks = 1');

echo "\n" . str_repeat("=", 50) . "\n";
echo "🎉 ¡Sincronización completada!\n";
echo "📊 Total: $tablesProcessed tablas + $viewsProcessed vistas\n";
echo "✅ La BD remota está actualizada y lista para usar\n";
