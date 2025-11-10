-- Base de datos para Sistema de Participación Ciudadana Gamificada
-- MuniOps

CREATE DATABASE IF NOT EXISTS muniops CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE muniops;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dni VARCHAR(8) UNIQUE NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellido_paterno VARCHAR(100) NOT NULL,
    apellido_materno VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE,
    sexo ENUM('M', 'F') DEFAULT NULL,
    direccion VARCHAR(255),
    email VARCHAR(100) UNIQUE,
    telefono VARCHAR(15),
    password VARCHAR(255) NOT NULL,
    rol ENUM('ciudadano', 'admin') DEFAULT 'ciudadano',
    puntos INT DEFAULT 0,
    estado ENUM('activo', 'inactivo', 'bloqueado') DEFAULT 'activo',
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso DATETIME DEFAULT NULL,
    INDEX idx_dni (dni),
    INDEX idx_email (email),
    INDEX idx_puntos (puntos DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de propuestas
CREATE TABLE propuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT NOT NULL,
    categoria ENUM('infraestructura', 'salud', 'educacion', 'seguridad', 'medio_ambiente', 'deporte', 'cultura', 'otros') DEFAULT 'otros',
    imagen VARCHAR(255) DEFAULT NULL,
    presupuesto_estimado DECIMAL(10,2) DEFAULT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    estado ENUM('borrador', 'activa', 'finalizada', 'implementada', 'cancelada') DEFAULT 'borrador',
    creado_por INT NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_votos INT DEFAULT 0,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_estado (estado),
    INDEX idx_fecha_fin (fecha_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de votos
CREATE TABLE votos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    propuesta_id INT NOT NULL,
    fecha_voto DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) DEFAULT NULL,
    UNIQUE KEY unique_voto (usuario_id, propuesta_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (propuesta_id) REFERENCES propuestas(id) ON DELETE CASCADE,
    INDEX idx_propuesta (propuesta_id),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de comentarios
CREATE TABLE comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    propuesta_id INT NOT NULL,
    usuario_id INT NOT NULL,
    comentario TEXT NOT NULL,
    comentario_padre_id INT DEFAULT NULL,
    fecha_comentario DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('visible', 'oculto', 'reportado') DEFAULT 'visible',
    likes INT DEFAULT 0,
    FOREIGN KEY (propuesta_id) REFERENCES propuestas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (comentario_padre_id) REFERENCES comentarios(id) ON DELETE CASCADE,
    INDEX idx_propuesta (propuesta_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha_comentario DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de likes en comentarios
CREATE TABLE comentario_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comentario_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_like DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (comentario_id, usuario_id),
    FOREIGN KEY (comentario_id) REFERENCES comentarios(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de historial de puntos
CREATE TABLE historial_puntos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    puntos INT NOT NULL,
    tipo_accion ENUM('voto', 'comentario', 'like_recibido', 'propuesta_implementada', 'bono', 'penalizacion') NOT NULL,
    descripcion VARCHAR(255),
    referencia_id INT DEFAULT NULL,
    fecha_accion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha_accion DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de recompensas/logros
CREATE TABLE recompensas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    icono VARCHAR(255) DEFAULT NULL,
    puntos_requeridos INT NOT NULL,
    tipo ENUM('medalla', 'nivel', 'insignia') DEFAULT 'medalla',
    estado ENUM('activa', 'inactiva') DEFAULT 'activa',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de recompensas obtenidas por usuarios
CREATE TABLE usuario_recompensas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    recompensa_id INT NOT NULL,
    fecha_obtencion DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_recompensa (usuario_id, recompensa_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (recompensa_id) REFERENCES recompensas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de configuración del sistema
CREATE TABLE configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    descripcion TEXT,
    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar datos iniciales

-- Usuario administrador por defecto (password: admin123)
INSERT INTO usuarios (dni, nombres, apellido_paterno, apellido_materno, email, password, rol, estado) 
VALUES ('12345678', 'Administrador', 'Municipal', 'Sistema', 'admin@muniops.gob.pe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'activo');

-- Configuraciones iniciales
INSERT INTO configuracion (clave, valor, descripcion) VALUES
('puntos_por_voto', '10', 'Puntos otorgados al votar en una propuesta'),
('puntos_por_comentario', '5', 'Puntos otorgados al comentar en una propuesta'),
('puntos_por_like_recibido', '2', 'Puntos otorgados cuando tu comentario recibe un like'),
('max_propuestas_activas', '3', 'Número máximo de propuestas activas simultáneamente'),
('duracion_votacion_dias', '30', 'Duración por defecto de una votación en días'),
('api_dni_token', '', 'Token de la API de consulta DNI');

-- Recompensas iniciales
INSERT INTO recompensas (nombre, descripcion, puntos_requeridos, tipo) VALUES
('Nuevo Participante', 'Has realizado tu primera acción en la plataforma', 0, 'medalla'),
('Votante Activo', 'Has votado en tu primera propuesta', 10, 'medalla'),
('Comentarista', 'Has realizado tu primer comentario', 5, 'insignia'),
('Ciudadano Bronce', 'Alcanza 50 puntos de participación', 50, 'nivel'),
('Ciudadano Plata', 'Alcanza 150 puntos de participación', 150, 'nivel'),
('Ciudadano Oro', 'Alcanza 300 puntos de participación', 300, 'nivel'),
('Líder Comunitario', 'Alcanza 500 puntos de participación', 500, 'nivel'),
('Experto en Debate', 'Recibe 50 likes en tus comentarios', 100, 'insignia'),
('Participación Perfecta', 'Vota en todas las propuestas de un mes', 100, 'medalla');

-- Vista para ranking de usuarios
CREATE VIEW ranking_usuarios AS
SELECT 
    u.id,
    u.dni,
    CONCAT(u.nombres, ' ', u.apellido_paterno, ' ', u.apellido_materno) as nombre_completo,
    u.puntos,
    COUNT(DISTINCT v.id) as total_votos,
    COUNT(DISTINCT c.id) as total_comentarios,
    COUNT(DISTINCT ur.id) as total_recompensas,
    RANK() OVER (ORDER BY u.puntos DESC) as posicion
FROM usuarios u
LEFT JOIN votos v ON u.id = v.usuario_id
LEFT JOIN comentarios c ON u.id = c.usuario_id
LEFT JOIN usuario_recompensas ur ON u.id = ur.usuario_id
WHERE u.rol = 'ciudadano' AND u.estado = 'activo'
GROUP BY u.id, u.dni, u.nombres, u.apellido_paterno, u.apellido_materno, u.puntos;

-- Vista para propuestas con estadísticas
CREATE VIEW propuestas_estadisticas AS
SELECT 
    p.id,
    p.titulo,
    p.descripcion,
    p.categoria,
    p.imagen,
    p.presupuesto_estimado,
    p.fecha_inicio,
    p.fecha_fin,
    p.estado,
    p.total_votos,
    p.fecha_creacion,
    COUNT(DISTINCT c.id) as total_comentarios,
    CONCAT(u.nombres, ' ', u.apellido_paterno) as creado_por_nombre,
    DATEDIFF(p.fecha_fin, NOW()) as dias_restantes
FROM propuestas p
LEFT JOIN comentarios c ON p.id = c.propuesta_id
LEFT JOIN usuarios u ON p.creado_por = u.id
GROUP BY p.id, p.titulo, p.descripcion, p.categoria, p.imagen, p.presupuesto_estimado, 
         p.fecha_inicio, p.fecha_fin, p.estado, p.total_votos, p.fecha_creacion, 
         u.nombres, u.apellido_paterno;
