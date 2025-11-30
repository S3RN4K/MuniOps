-- Crear tabla votacion_votos
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
