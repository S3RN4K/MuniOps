<?php
// Configuración de la base de datos
define('DB_HOST', 'muniopsdb-muniops.k.aivencloud.com');
define('DB_PORT', '19400');
define('DB_USER', 'avnadmin');
define('DB_PASS', 'AVNS_Emnaldab8z9DDPTaEkr'); // Reemplazar con tu password real
define('DB_NAME', 'defaultdb');
define('DB_CHARSET', 'utf8mb4');
define('DB_SSL_CA', __DIR__ . '/../Certs/ca.pem');

// Crear conexión
function getConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_SSL_CA => DB_SSL_CA,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}

// Función auxiliar para ejecutar consultas
function query($sql, $params = []) {
    $pdo = getConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

// Función auxiliar para obtener un registro
function fetchOne($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetch();
}

// Función auxiliar para obtener múltiples registros
function fetchAll($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetchAll();
}

// Función auxiliar para ejecutar insert/update/delete
function execute($sql, $params = []) {
    $pdo = getConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

// Función auxiliar para obtener el último ID insertado de una conexión
function lastInsertId($pdo = null) {
    if ($pdo === null) {
        $pdo = getConnection();
    }
    return $pdo->lastInsertId();
}
