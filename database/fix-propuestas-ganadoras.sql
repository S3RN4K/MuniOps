-- Script para agregar sistema de propuestas ganadoras
-- Fecha: 2025-11-30

-- 1. Crear tabla de votaciones si no existe
CREATE TABLE IF NOT EXISTS votaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT NOT NULL,
    estado ENUM('activa', 'finalizada', 'cancelada') DEFAULT 'activa',
    municipio_id INT DEFAULT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    creado_por INT NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (municipio_id) REFERENCES municipios(id) ON DELETE SET NULL,
    INDEX idx_estado (estado),
    INDEX idx_municipio_id (municipio_id),
    INDEX idx_fecha_fin (fecha_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Crear tabla de propuestas en votaciones
CREATE TABLE IF NOT EXISTS votacion_propuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    votacion_id INT NOT NULL,
    propuesta_id INT NOT NULL,
    es_ganadora BOOLEAN DEFAULT FALSE,
    votos_recibidos INT DEFAULT 0,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (votacion_id) REFERENCES votaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (propuesta_id) REFERENCES propuestas(id) ON DELETE CASCADE,
    UNIQUE KEY unique_votacion_propuesta (votacion_id, propuesta_id),
    INDEX idx_votacion_id (votacion_id),
    INDEX idx_propuesta_id (propuesta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Crear tabla para seguimiento de propuestas ganadoras
CREATE TABLE IF NOT EXISTS seguimiento_propuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    propuesta_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT NOT NULL,
    imagen VARCHAR(255) DEFAULT NULL,
    fecha_actualizacion DATETIME NOT NULL,
    creado_por INT NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (propuesta_id) REFERENCES propuestas(id) ON DELETE CASCADE,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_propuesta_id (propuesta_id),
    INDEX idx_fecha_actualizacion (fecha_actualizacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Recrear la vista propuestas_estadisticas para incluir es_ganadora
DROP VIEW IF EXISTS propuestas_estadisticas;

CREATE VIEW propuestas_estadisticas AS
SELECT 
    p.id,
    p.titulo,
    p.descripcion,
    p.categoria,
    p.imagen,
    p.municipio_id,
    p.presupuesto_estimado,
    p.fecha_inicio,
    p.fecha_fin,
    p.estado,
    p.creado_por,
    p.fecha_creacion,
    p.total_votos,
    p.es_ganadora,
    u.nombres as creado_por_nombre,
    COALESCE(u.apellido_paterno, '') as creado_por_apellido,
    (SELECT COUNT(*) FROM votos WHERE propuesta_id = p.id) as total_votos_actual,
    (SELECT COUNT(*) FROM comentarios WHERE propuesta_id = p.id AND comentario_padre_id IS NULL) as total_comentarios,
    DATEDIFF(p.fecha_fin, NOW()) as dias_restantes,
    CASE 
        WHEN p.estado = 'activa' AND p.fecha_fin >= NOW() THEN 'activa'
        WHEN p.estado = 'activa' AND p.fecha_fin < NOW() THEN 'finalizada'
        ELSE p.estado 
    END as estado_actual
FROM propuestas p
LEFT JOIN usuarios u ON p.creado_por = u.id;
