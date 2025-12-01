-- Sistema de Votaciones entre Propuestas
-- Actualización de base de datos para MuniOps

USE muniops;

-- Tabla de votaciones (campañas de votación)
CREATE TABLE IF NOT EXISTS votaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    municipio_id INT NOT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    estado ENUM('borrador', 'activa', 'finalizada', 'cancelada') DEFAULT 'borrador',
    propuesta_ganadora_id INT DEFAULT NULL,
    total_votos INT DEFAULT 0,
    creado_por INT NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (municipio_id) REFERENCES municipios(id) ON DELETE CASCADE,
    FOREIGN KEY (propuesta_ganadora_id) REFERENCES propuestas(id) ON DELETE SET NULL,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_estado (estado),
    INDEX idx_municipio (municipio_id),
    INDEX idx_fecha_fin (fecha_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de propuestas en votación (relación muchos a muchos)
CREATE TABLE IF NOT EXISTS votacion_propuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    votacion_id INT NOT NULL,
    propuesta_id INT NOT NULL,
    orden INT NOT NULL DEFAULT 1,
    votos_recibidos INT DEFAULT 0,
    porcentaje DECIMAL(5,2) DEFAULT 0.00,
    es_ganadora BOOLEAN DEFAULT FALSE,
    UNIQUE KEY unique_votacion_propuesta (votacion_id, propuesta_id),
    FOREIGN KEY (votacion_id) REFERENCES votaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (propuesta_id) REFERENCES propuestas(id) ON DELETE CASCADE,
    INDEX idx_votacion (votacion_id),
    INDEX idx_propuesta (propuesta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de votos en votaciones (reemplaza la tabla votos anterior)
CREATE TABLE IF NOT EXISTS votacion_votos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    votacion_id INT NOT NULL,
    propuesta_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_voto DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) DEFAULT NULL,
    UNIQUE KEY unique_voto_votacion (votacion_id, usuario_id),
    FOREIGN KEY (votacion_id) REFERENCES votaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (propuesta_id) REFERENCES propuestas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_votacion (votacion_id),
    INDEX idx_propuesta (propuesta_id),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modificar tabla propuestas para agregar estado de archivo
ALTER TABLE propuestas 
ADD COLUMN archivada BOOLEAN DEFAULT FALSE AFTER estado,
ADD COLUMN fecha_archivo DATETIME DEFAULT NULL AFTER archivada,
ADD COLUMN veces_usada_votacion INT DEFAULT 0 AFTER fecha_archivo;

-- Vista para votaciones con estadísticas
CREATE OR REPLACE VIEW votaciones_estadisticas AS
SELECT 
    v.id,
    v.titulo,
    v.descripcion,
    v.municipio_id,
    m.nombre as municipio_nombre,
    v.fecha_inicio,
    v.fecha_fin,
    v.estado,
    v.total_votos,
    v.propuesta_ganadora_id,
    p.titulo as propuesta_ganadora_titulo,
    v.fecha_creacion,
    CONCAT(u.nombres, ' ', u.apellido_paterno) as creado_por_nombre,
    DATEDIFF(v.fecha_fin, NOW()) as dias_restantes,
    COUNT(DISTINCT vp.propuesta_id) as total_propuestas
FROM votaciones v
LEFT JOIN municipios m ON v.municipio_id = m.id
LEFT JOIN propuestas p ON v.propuesta_ganadora_id = p.id
LEFT JOIN usuarios u ON v.creado_por = u.id
LEFT JOIN votacion_propuestas vp ON v.id = vp.votacion_id
GROUP BY v.id, v.titulo, v.descripcion, v.municipio_id, m.nombre, v.fecha_inicio, 
         v.fecha_fin, v.estado, v.total_votos, v.propuesta_ganadora_id, p.titulo,
         v.fecha_creacion, u.nombres, u.apellido_paterno;

-- Actualizar configuración
INSERT INTO configuracion (clave, valor, descripcion) VALUES
('max_propuestas_por_votacion', '3', 'Número máximo de propuestas por votación'),
('duracion_votacion_dias', '15', 'Duración por defecto de una votación en días')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

-- Trigger para actualizar contador de votos en votación
DELIMITER //
CREATE TRIGGER after_votacion_voto_insert
AFTER INSERT ON votacion_votos
FOR EACH ROW
BEGIN
    -- Actualizar votos de la propuesta en la votación
    UPDATE votacion_propuestas 
    SET votos_recibidos = votos_recibidos + 1
    WHERE votacion_id = NEW.votacion_id AND propuesta_id = NEW.propuesta_id;
    
    -- Actualizar total de votos de la votación
    UPDATE votaciones 
    SET total_votos = total_votos + 1
    WHERE id = NEW.votacion_id;
    
    -- Actualizar total de votos de la propuesta
    UPDATE propuestas 
    SET total_votos = total_votos + 1
    WHERE id = NEW.propuesta_id;
END//

CREATE TRIGGER after_votacion_voto_delete
AFTER DELETE ON votacion_votos
FOR EACH ROW
BEGIN
    -- Actualizar votos de la propuesta en la votación
    UPDATE votacion_propuestas 
    SET votos_recibidos = votos_recibidos - 1
    WHERE votacion_id = OLD.votacion_id AND propuesta_id = OLD.propuesta_id;
    
    -- Actualizar total de votos de la votación
    UPDATE votaciones 
    SET total_votos = total_votos - 1
    WHERE id = OLD.votacion_id;
    
    -- Actualizar total de votos de la propuesta
    UPDATE propuestas 
    SET total_votos = total_votos - 1
    WHERE id = OLD.propuesta_id;
END//
DELIMITER ;
