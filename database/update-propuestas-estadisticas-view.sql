-- Actualizar vista propuestas_estadisticas para incluir columna archivada
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
    p.archivada,
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
