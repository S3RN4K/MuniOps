<?php
// Página de prueba de conexión a la base de datos
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Prueba de Conexión - MuniOps</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .success { color: green; padding: 10px; background: #d4edda; border: 1px solid green; margin: 10px 0; }
    .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid red; margin: 10px 0; }
    .info { color: blue; padding: 10px; background: #d1ecf1; border: 1px solid blue; margin: 10px 0; }
    pre { background: #fff; padding: 10px; border: 1px solid #ccc; overflow-x: auto; }
    h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
</style>";

// PASO 1: Verificar que PHP esté funcionando
echo "<h2>✅ Paso 1: PHP Funcionando</h2>";
echo "<div class='success'>PHP Versión: " . phpversion() . "</div>";

// PASO 2: Verificar extensión MySQL
echo "<h2>Paso 2: Verificar Extensión MySQL/PDO</h2>";
if (extension_loaded('pdo') && extension_loaded('pdo_mysql')) {
    echo "<div class='success'>✅ PDO y PDO_MySQL están habilitados</div>";
} else {
    echo "<div class='error'>❌ PDO o PDO_MySQL NO están habilitados</div>";
    echo "<div class='info'>Solución: Abre php.ini y descomenta: extension=pdo_mysql</div>";
    exit;
}

// PASO 3: Verificar archivo de configuración
echo "<h2>Paso 3: Configuración de Base de Datos</h2>";
if (file_exists('config/database.php')) {
    echo "<div class='success'>✅ Archivo config/database.php existe</div>";
    require_once 'config/database.php';
    
    echo "<div class='info'>";
    echo "<strong>Configuración actual:</strong><br>";
    echo "Host: " . DB_HOST . "<br>";
    echo "Usuario: " . DB_USER . "<br>";
    echo "Base de datos: " . DB_NAME . "<br>";
    echo "Charset: " . DB_CHARSET . "<br>";
    echo "</div>";
} else {
    echo "<div class='error'>❌ Archivo config/database.php NO encontrado</div>";
    exit;
}

// PASO 4: Intentar conexión
echo "<h2>Paso 4: Probar Conexión a MySQL</h2>";
try {
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div class='success'>✅ Conexión al servidor MySQL exitosa</div>";
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error al conectar con MySQL: " . $e->getMessage() . "</div>";
    echo "<div class='info'>";
    echo "<strong>Posibles soluciones:</strong><br>";
    echo "1. Verifica que XAMPP esté corriendo (Apache y MySQL)<br>";
    echo "2. Abre http://localhost/phpmyadmin para verificar MySQL<br>";
    echo "3. Verifica las credenciales en config/database.php<br>";
    echo "4. Si usas contraseña en MySQL, agrégala en DB_PASS<br>";
    echo "</div>";
    exit;
}

// PASO 5: Verificar si la base de datos existe
echo "<h2>Paso 5: Verificar Base de Datos 'muniops'</h2>";
try {
    $stmt = $pdo->query("SHOW DATABASES LIKE 'muniops'");
    $db = $stmt->fetch();
    
    if ($db) {
        echo "<div class='success'>✅ Base de datos 'muniops' existe</div>";
    } else {
        echo "<div class='error'>❌ Base de datos 'muniops' NO existe</div>";
        echo "<div class='info'>";
        echo "<strong>Solución:</strong><br>";
        echo "1. Abre http://localhost/phpmyadmin<br>";
        echo "2. Crea una nueva base de datos llamada 'muniops'<br>";
        echo "3. Importa el archivo database/muniops.sql<br>";
        echo "</div>";
        exit;
    }
} catch (PDOException $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
    exit;
}

// PASO 6: Conectar a la base de datos específica
echo "<h2>Paso 6: Conectar a la Base de Datos</h2>";
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div class='success'>✅ Conexión a base de datos 'muniops' exitosa</div>";
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    exit;
}

// PASO 7: Verificar tablas
echo "<h2>Paso 7: Verificar Tablas</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "<div class='success'>✅ Se encontraron " . count($tables) . " tablas:</div>";
        echo "<pre>";
        foreach ($tables as $table) {
            echo "- " . $table . "\n";
        }
        echo "</pre>";
        
        // Verificar tabla usuarios específicamente
        if (in_array('usuarios', $tables)) {
            echo "<div class='success'>✅ Tabla 'usuarios' existe</div>";
            
            // Contar usuarios
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
            $count = $stmt->fetch();
            echo "<div class='info'>Usuarios registrados: " . $count['total'] . "</div>";
            
            // Mostrar usuario admin
            $stmt = $pdo->query("SELECT dni, nombres, apellido_paterno, email, rol FROM usuarios WHERE rol = 'admin' LIMIT 1");
            $admin = $stmt->fetch();
            if ($admin) {
                echo "<div class='info'>";
                echo "<strong>Usuario Admin encontrado:</strong><br>";
                echo "DNI: " . $admin['dni'] . "<br>";
                echo "Nombre: " . $admin['nombres'] . " " . $admin['apellido_paterno'] . "<br>";
                echo "Email: " . $admin['email'] . "<br>";
                echo "Rol: " . $admin['rol'] . "<br>";
                echo "</div>";
            }
        } else {
            echo "<div class='error'>❌ Tabla 'usuarios' NO existe</div>";
            echo "<div class='info'>Debes importar el archivo database/muniops.sql</div>";
        }
    } else {
        echo "<div class='error'>❌ No se encontraron tablas en la base de datos</div>";
        echo "<div class='info'>";
        echo "<strong>Solución:</strong><br>";
        echo "1. Abre http://localhost/phpmyadmin<br>";
        echo "2. Selecciona la base de datos 'muniops'<br>";
        echo "3. Ve a la pestaña 'Importar'<br>";
        echo "4. Selecciona el archivo database/muniops.sql<br>";
        echo "5. Haz clic en 'Continuar'<br>";
        echo "</div>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
    exit;
}

// PASO 8: Probar función getConnection()
echo "<h2>Paso 8: Probar Función getConnection()</h2>";
try {
    $conn = getConnection();
    if ($conn) {
        echo "<div class='success'>✅ Función getConnection() funciona correctamente</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error en getConnection(): " . $e->getMessage() . "</div>";
}

// PASO 9: Probar consulta
echo "<h2>Paso 9: Probar Consulta SQL</h2>";
try {
    require_once 'config/config.php';
    require_once 'includes/functions.php';
    
    $result = fetchOne("SELECT COUNT(*) as total FROM usuarios");
    echo "<div class='success'>✅ Consulta ejecutada correctamente</div>";
    echo "<div class='info'>Total usuarios: " . $result['total'] . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error en consulta: " . $e->getMessage() . "</div>";
}

// RESUMEN FINAL
echo "<h2>🎉 Resumen</h2>";
echo "<div class='success'>";
echo "<strong>¡Conexión exitosa!</strong><br><br>";
echo "Todo está funcionando correctamente. Puedes:<br>";
echo "1. <a href='login.php'>Iniciar Sesión</a> con DNI: 12345678 / Password: admin123<br>";
echo "2. <a href='registro.php'>Registrar un nuevo usuario</a><br>";
echo "3. <a href='index.php'>Ir a la página principal</a><br>";
echo "</div>";

echo "<div class='info'>";
echo "<strong>Nota:</strong> Por seguridad, elimina este archivo (test-conexion.php) después de verificar la conexión.";
echo "</div>";
?>
