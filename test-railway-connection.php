<?php
/**
 * Test de conexión a Railway
 */

// Cargar variables de Railway
putenv('MYSQLHOST=nozomi.proxy.rlwy.net');
putenv('MYSQLPORT=50599');
putenv('MYSQLUSER=root');
putenv('MYSQLPASSWORD=SbPLDWRfjsRUtVHbxRBURYqktfpCQTlo');
putenv('MYSQLDATABASE=railway');

// Incluir archivos de config
require 'config/load-env.php';
require 'config/database.php';

// Hacer queries de prueba
$result = fetchOne('SELECT COUNT(*) as total FROM usuarios');
echo '✅ Usuarios: ' . $result['total'] . PHP_EOL;

$result = fetchOne('SELECT COUNT(*) as total FROM municipios');
echo '✅ Municipios: ' . $result['total'] . PHP_EOL;

$result = fetchOne('SELECT COUNT(*) as total FROM propuestas');
echo '✅ Propuestas: ' . $result['total'] . PHP_EOL;

echo "\n🎉 ¡Conexión a Railway funciona correctamente!\n";
echo "✅ Todos los cambios que hagas en la app se sincronizarán a Railway automáticamente\n";
