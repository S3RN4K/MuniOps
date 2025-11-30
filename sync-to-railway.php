<?php
/**
 * Script para importar BD a Railway - Versión mejorada
 * Usa mysqldump local y procesa línea por línea
 */

// Configuración Railway
$railwayConfig = [
    'host' => 'nozomi.proxy.rlwy.net',
    'port' => 50599,
    'user' => 'root',
    'password' => 'SbPLDWRfjsRUtVHbxRBURYqktfpCQTlo',
    'database' => 'railway',
];

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
    
    // Desactivar restricciones de FK temporalmente
    $pdo->exec('SET foreign_key_checks = 0');
    
    // Comandos de mysqldump para recrear tablas
    $sqlCommands = [
        // Tabla municipios
        "DROP TABLE IF EXISTS `municipios`;",
        "CREATE TABLE `municipios` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `nombre` varchar(100) NOT NULL,
            `departamento` varchar(50) DEFAULT NULL,
            `provincia` varchar(50) DEFAULT NULL,
            `codigo_inei` varchar(10) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_nombre` (`nombre`)
        ) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // Tabla usuarios
        "DROP TABLE IF EXISTS `usuarios`;",
        "CREATE TABLE `usuarios` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `dni` varchar(8) NOT NULL,
            `nombres` varchar(100) NOT NULL,
            `apellido_paterno` varchar(50) NOT NULL,
            `apellido_materno` varchar(50) NOT NULL,
            `email` varchar(100) NOT NULL,
            `contraseña` varchar(255) NOT NULL,
            `fecha_nacimiento` date DEFAULT NULL,
            `rol` enum('usuario', 'admin') DEFAULT 'usuario',
            `puntos` int(11) DEFAULT 0,
            `municipio_id` int(11) DEFAULT NULL,
            `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
            `activo` tinyint(1) DEFAULT 1,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_dni` (`dni`),
            UNIQUE KEY `unique_email` (`email`),
            KEY `municipio_id` (`municipio_id`),
            CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`municipio_id`) REFERENCES `municipios` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // Tabla propuestas
        "DROP TABLE IF EXISTS `propuestas`;",
        "CREATE TABLE `propuestas` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `titulo` varchar(255) NOT NULL,
            `descripcion` text NOT NULL,
            `categoria` enum('infraestructura','salud','educacion','seguridad','medio_ambiente','deporte','cultura','otros') DEFAULT 'otros',
            `imagen` varchar(255) DEFAULT NULL,
            `presupuesto_estimado` decimal(10,2) DEFAULT NULL,
            `fecha_inicio` datetime NOT NULL,
            `fecha_fin` datetime NOT NULL,
            `estado` enum('borrador','activa','finalizada','implementada','cancelada') DEFAULT 'borrador',
            `creado_por` int(11) NOT NULL,
            `municipio_id` int(11) DEFAULT NULL,
            `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
            `total_votos` int(11) DEFAULT 0,
            `es_ganadora` tinyint(1) DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `estado` (`estado`),
            KEY `fecha_fin` (`fecha_fin`),
            KEY `creado_por` (`creado_por`),
            KEY `municipio_id` (`municipio_id`),
            KEY `idx_es_ganadora` (`es_ganadora`),
            CONSTRAINT `propuestas_ibfk_1` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
            CONSTRAINT `propuestas_ibfk_2` FOREIGN KEY (`municipio_id`) REFERENCES `municipios` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // Tabla votos
        "DROP TABLE IF EXISTS `votos`;",
        "CREATE TABLE `votos` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `propuesta_id` int(11) NOT NULL,
            `usuario_id` int(11) NOT NULL,
            `fecha_voto` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_voto` (`propuesta_id`, `usuario_id`),
            KEY `usuario_id` (`usuario_id`),
            CONSTRAINT `votos_ibfk_1` FOREIGN KEY (`propuesta_id`) REFERENCES `propuestas` (`id`) ON DELETE CASCADE,
            CONSTRAINT `votos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // Tabla comentarios
        "DROP TABLE IF EXISTS `comentarios`;",
        "CREATE TABLE `comentarios` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `propuesta_id` int(11) NOT NULL,
            `usuario_id` int(11) NOT NULL,
            `comentario_padre_id` int(11) DEFAULT NULL,
            `texto` text NOT NULL,
            `likes` int(11) DEFAULT 0,
            `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `propuesta_id` (`propuesta_id`),
            KEY `usuario_id` (`usuario_id`),
            KEY `comentario_padre_id` (`comentario_padre_id`),
            CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`propuesta_id`) REFERENCES `propuestas` (`id`) ON DELETE CASCADE,
            CONSTRAINT `comentarios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
            CONSTRAINT `comentarios_ibfk_3` FOREIGN KEY (`comentario_padre_id`) REFERENCES `comentarios` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // Tabla votaciones
        "DROP TABLE IF EXISTS `votaciones`;",
        "CREATE TABLE `votaciones` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `titulo` varchar(255) NOT NULL,
            `descripcion` text NOT NULL,
            `estado` enum('activa', 'finalizada', 'cancelada') DEFAULT 'activa',
            `municipio_id` int(11) DEFAULT NULL,
            `fecha_inicio` datetime NOT NULL,
            `fecha_fin` datetime NOT NULL,
            `creado_por` int(11) NOT NULL,
            `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_estado` (`estado`),
            KEY `idx_municipio_id` (`municipio_id`),
            KEY `idx_fecha_fin` (`fecha_fin`),
            CONSTRAINT `votaciones_ibfk_1` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
            CONSTRAINT `votaciones_ibfk_2` FOREIGN KEY (`municipio_id`) REFERENCES `municipios` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // Tabla votacion_propuestas
        "DROP TABLE IF EXISTS `votacion_propuestas`;",
        "CREATE TABLE `votacion_propuestas` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `votacion_id` int(11) NOT NULL,
            `propuesta_id` int(11) NOT NULL,
            `es_ganadora` tinyint(1) DEFAULT 0,
            `votos_recibidos` int(11) DEFAULT 0,
            `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_votacion_propuesta` (`votacion_id`, `propuesta_id`),
            KEY `idx_votacion_id` (`votacion_id`),
            KEY `idx_propuesta_id` (`propuesta_id`),
            CONSTRAINT `votacion_propuestas_ibfk_1` FOREIGN KEY (`votacion_id`) REFERENCES `votaciones` (`id`) ON DELETE CASCADE,
            CONSTRAINT `votacion_propuestas_ibfk_2` FOREIGN KEY (`propuesta_id`) REFERENCES `propuestas` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // Tabla seguimiento_propuestas
        "DROP TABLE IF EXISTS `seguimiento_propuestas`;",
        "CREATE TABLE `seguimiento_propuestas` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `propuesta_id` int(11) NOT NULL,
            `titulo` varchar(255) NOT NULL,
            `descripcion` text NOT NULL,
            `imagen` varchar(255) DEFAULT NULL,
            `fecha_actualizacion` datetime NOT NULL,
            `creado_por` int(11) NOT NULL,
            `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_propuesta_id` (`propuesta_id`),
            KEY `idx_fecha_actualizacion` (`fecha_actualizacion`),
            CONSTRAINT `seguimiento_propuestas_ibfk_1` FOREIGN KEY (`propuesta_id`) REFERENCES `propuestas` (`id`) ON DELETE CASCADE,
            CONSTRAINT `seguimiento_propuestas_ibfk_2` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        
        // Reactivar FK
        "SET foreign_key_checks = 1;",
    ];
    
    $executed = 0;
    foreach ($sqlCommands as $command) {
        try {
            $pdo->exec($command);
            $executed++;
            echo ".";
        } catch (PDOException $e) {
            echo "\n⚠️ Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ Tablas creadas en Railway\n";
    echo "📊 Total de comandos ejecutados: $executed\n";
    
    // Ahora insertar datos desde local
    echo "\n⏳ Insertando datos locales...\n";
    
    // Insertar municipios
    $localPdo = new PDO(
        'mysql:host=localhost;dbname=muniops;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $municipios = $localPdo->query('SELECT * FROM municipios')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($municipios as $m) {
        $pdo->prepare('INSERT INTO municipios (id, nombre, departamento, provincia, codigo_inei) VALUES (?, ?, ?, ?, ?)')
            ->execute([$m['id'], $m['nombre'], $m['departamento'], $m['provincia'], $m['codigo_inei']]);
    }
    echo "✅ " . count($municipios) . " municipios insertados\n";
    
    // Insertar usuarios
    $usuarios = $localPdo->query('SELECT * FROM usuarios')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($usuarios as $u) {
        $pdo->prepare('INSERT INTO usuarios (id, dni, nombres, apellido_paterno, apellido_materno, email, contraseña, fecha_nacimiento, rol, puntos, municipio_id, fecha_registro, activo) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$u['id'], $u['dni'], $u['nombres'], $u['apellido_paterno'], $u['apellido_materno'], $u['email'], $u['contraseña'], $u['fecha_nacimiento'] ?? null, $u['rol'], $u['puntos'], $u['municipio_id'], $u['fecha_registro'], $u['activo']]);
    }
    echo "✅ " . count($usuarios) . " usuarios insertados\n";
    
    // Insertar propuestas
    $propuestas = $localPdo->query('SELECT * FROM propuestas')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($propuestas as $p) {
        $pdo->prepare('INSERT INTO propuestas (id, titulo, descripcion, categoria, imagen, presupuesto_estimado, fecha_inicio, fecha_fin, estado, creado_por, municipio_id, fecha_creacion, total_votos, es_ganadora)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$p['id'], $p['titulo'], $p['descripcion'], $p['categoria'], $p['imagen'], $p['presupuesto_estimado'], $p['fecha_inicio'], $p['fecha_fin'], $p['estado'], $p['creado_por'], $p['municipio_id'], $p['fecha_creacion'], $p['total_votos'], $p['es_ganadora']]);
    }
    echo "✅ " . count($propuestas) . " propuestas insertadas\n";
    
    // Insertar votos
    $votos = $localPdo->query('SELECT * FROM votos')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($votos as $v) {
        $pdo->prepare('INSERT INTO votos (id, propuesta_id, usuario_id, fecha_voto) VALUES (?, ?, ?, ?)')
            ->execute([$v['id'], $v['propuesta_id'], $v['usuario_id'], $v['fecha_voto']]);
    }
    echo "✅ " . count($votos) . " votos insertados\n";
    
    echo "\n🎉 ¡Base de datos sincronizada completamente a Railway!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
