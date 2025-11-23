-- Agregar tabla de municipios
CREATE TABLE IF NOT EXISTS municipios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL UNIQUE,
    departamento VARCHAR(100),
    provincia VARCHAR(100),
    codigo_inei VARCHAR(10),
    INDEX idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar columna municipio_id a usuarios si no existe
ALTER TABLE usuarios ADD COLUMN municipio_id INT DEFAULT NULL AFTER sexo;
ALTER TABLE usuarios ADD CONSTRAINT fk_usuario_municipio FOREIGN KEY (municipio_id) REFERENCES municipios(id) ON DELETE SET NULL;
ALTER TABLE usuarios ADD INDEX idx_municipio (municipio_id);

-- Agregar columna municipio_id a propuestas si no existe
ALTER TABLE propuestas ADD COLUMN municipio_id INT DEFAULT NULL AFTER creado_por;
ALTER TABLE propuestas ADD CONSTRAINT fk_propuesta_municipio FOREIGN KEY (municipio_id) REFERENCES municipios(id) ON DELETE SET NULL;
ALTER TABLE propuestas ADD INDEX idx_propuesta_municipio (municipio_id);

-- Insertar municipios principales del Perú
INSERT IGNORE INTO municipios (nombre, departamento, provincia) VALUES
-- Lima
('Lima', 'Lima', 'Lima'),
('San Isidro', 'Lima', 'Lima'),
('Miraflores', 'Lima', 'Lima'),
('Surco', 'Lima', 'Lima'),
('La Molina', 'Lima', 'Lima'),
('Barranco', 'Lima', 'Lima'),
('San Borja', 'Lima', 'Lima'),
('Magdalena', 'Lima', 'Lima'),
('Callao', 'Callao', 'Callao'),

-- Arequipa
('Arequipa', 'Arequipa', 'Arequipa'),
('Yanahuara', 'Arequipa', 'Arequipa'),
('Cayma', 'Arequipa', 'Arequipa'),

-- Cusco
('Cusco', 'Cusco', 'Cusco'),
('Wanchaq', 'Cusco', 'Cusco'),

-- Trujillo
('Trujillo', 'La Libertad', 'Trujillo'),
('Victor Larco Herrera', 'La Libertad', 'Trujillo'),

-- Piura
('Piura', 'Piura', 'Piura'),
('Castilla', 'Piura', 'Piura'),

-- Chiclayo
('Chiclayo', 'Lambayeque', 'Chiclayo'),
('La Victoria', 'Lambayeque', 'Chiclayo'),

-- Iquitos
('Iquitos', 'Loreto', 'Maynas'),
('San Juan Bautista', 'Loreto', 'Maynas'),

-- Pucallpa
('Pucallpa', 'Ucayali', 'Coronel Portillo'),

-- Puerto Maldonado
('Puerto Maldonado', 'Madre de Dios', 'Tambopata'),

-- Juliaca
('Juliaca', 'Puno', 'San Román'),

-- Tacna
('Tacna', 'Tacna', 'Tacna'),
('Gregorio Albarracín Lanchipa', 'Tacna', 'Tacna'),

-- Ayacucho
('Ayacucho', 'Ayacucho', 'Huamanga'),

-- Huancayo
('Huancayo', 'Junín', 'Huancayo'),

-- Cerro de Pasco
('Cerro de Pasco', 'Pasco', 'Pasco'),

-- Huacho
('Huacho', 'Lima', 'Huaura'),

-- Ica
('Ica', 'Ica', 'Ica'),

-- Moquegua
('Moquegua', 'Moquegua', 'Mariscal Nieto');
