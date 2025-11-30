-- Crear vista votaciones_estadisticas
CREATE OR REPLACE VIEW votaciones_estadisticas AS
SELECT 
    v.id,
    v.titulo,
    v.descripcion,
    v.municipio_id,
    COALESCE(m.nombre, 'Nacional') as municipio_nombre,
    v.fecha_inicio,
    v.fecha_fin,
    v.estado,
    v.fecha_creacion,
    CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellido_paterno, '')) as creado_por_nombre,
    DATEDIFF(v.fecha_fin, NOW()) as dias_restantes,
    COUNT(DISTINCT vp.propuesta_id) as total_propuestas
FROM votaciones v
LEFT JOIN municipios m ON v.municipio_id = m.id
LEFT JOIN usuarios u ON v.creado_por = u.id
LEFT JOIN votacion_propuestas vp ON v.id = vp.votacion_id
GROUP BY v.id, v.titulo, v.descripcion, v.municipio_id, m.nombre, v.fecha_inicio, 
         v.fecha_fin, v.estado, v.fecha_creacion, u.nombres, u.apellido_paterno;
