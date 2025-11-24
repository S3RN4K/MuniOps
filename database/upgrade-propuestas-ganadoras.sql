-- Script para agregar sistema de propuestas ganadoras y seguimiento
-- Fecha: 2025-11-24

-- 1. Agregar columna es_ganadora a propuestas
ALTER TABLE propuestas 
ADD COLUMN es_ganadora TINYINT(1) DEFAULT 0 AFTER veces_usada_votacion,
ADD INDEX idx_es_ganadora (es_ganadora);

-- 2. Crear tabla para seguimiento de propuestas ganadoras
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

-- 3. Crear carpeta para imágenes de seguimiento
-- NOTA: Crear manualmente la carpeta: uploads/seguimientos/

-- 4. Actualizar propuestas ganadoras existentes (si hay votaciones finalizadas)
UPDATE propuestas p
INNER JOIN votacion_propuestas vp ON p.id = vp.propuesta_id
INNER JOIN votaciones v ON vp.votacion_id = v.id
SET p.es_ganadora = 1
WHERE vp.es_ganadora = 1 
AND v.estado = 'finalizada';

-- 5. Recrear la vista propuestas_estadisticas para incluir es_ganadora
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
    p.archivada,
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
