<?php
/**
 * Crear todas las vistas en Railway
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

// Obtener todas las vistas
$views = $localPdo->query(
    "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = 'muniops'"
)->fetchAll(PDO::FETCH_COLUMN);

echo "📋 Vistas encontradas: " . implode(', ', $views) . "\n";

foreach ($views as $view) {
    echo "\n⏳ Procesando vista: $view\n";
    
    try {
        // Obtener definición de vista
        $createResult = $localPdo->query("SHOW CREATE VIEW `$view`")->fetch(PDO::FETCH_ASSOC);
        $createSql = $createResult['Create View'];
        
        // Dropear vista en remoto
        $remotePdo->exec("DROP VIEW IF EXISTS `$view`");
        echo "   Vista dropeada\n";
        
        // Crear vista en remoto
        $remotePdo->exec($createSql);
        echo "✅ Vista $view creada\n";
        
    } catch (Exception $e) {
        echo "❌ Error en $view: " . $e->getMessage() . "\n";
    }
}

echo "\n🎉 ¡Todas las vistas sincronizadas a Railway!\n";
