-- Recrear la vista propuestas_estadisticas con todas las columnas necesarias
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
    p.archivada,
    p.fecha_archivo,
    p.veces_usada_votacion,
    p.es_ganadora,
    u.nombres AS creado_por_nombre,
    COALESCE(u.apellido_paterno, '') AS creado_por_apellido,
    -- Contar votos directos (tabla votos) + votos en votaciones (votacion_votos)
    (
        (SELECT COUNT(*) FROM votos WHERE votos.propuesta_id = p.id) + 
        (SELECT COUNT(*) FROM votacion_votos WHERE votacion_votos.propuesta_id = p.id)
    ) AS total_votos,
    (SELECT COUNT(*) FROM votos WHERE votos.propuesta_id = p.id) AS total_votos_actual,
    (SELECT COUNT(*) FROM comentarios WHERE comentarios.propuesta_id = p.id AND comentarios.comentario_padre_id IS NULL) AS total_comentarios,
    DATEDIFF(p.fecha_fin, NOW()) AS dias_restantes,
    CASE 
        WHEN p.estado = 'activa' AND p.fecha_fin >= NOW() THEN 'activa'
        WHEN p.estado = 'activa' AND p.fecha_fin < NOW() THEN 'finalizada'
        ELSE p.estado 
    END AS estado_actual
FROM propuestas p
LEFT JOIN usuarios u ON p.creado_por = u.id;
