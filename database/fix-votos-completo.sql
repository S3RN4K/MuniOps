-- ============================================
-- SCRIPT DE CORRECCIÓN DE CONTEO DE VOTOS
-- ============================================
-- Este script actualiza las vistas para contar correctamente los votos
-- de ambas tablas: votos (directos antiguos) y votacion_votos (nuevos)
-- ============================================

-- 1. RECREAR VISTA propuestas_estadisticas
-- ============================================
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
    -- TOTAL_VOTOS: Contar votos directos (tabla votos) + votos en votaciones (votacion_votos)
    (
        (SELECT COUNT(*) FROM votos WHERE votos.propuesta_id = p.id) + 
        (SELECT COUNT(*) FROM votacion_votos WHERE votacion_votos.propuesta_id = p.id)
    ) AS total_votos,
    -- TOTAL_VOTOS_ACTUAL: Solo votos directos (mantener compatibilidad)
    (SELECT COUNT(*) FROM votos WHERE votos.propuesta_id = p.id) AS total_votos_actual,
    -- TOTAL_COMENTARIOS: Contar solo comentarios principales (no respuestas)
    (SELECT COUNT(*) FROM comentarios WHERE comentarios.propuesta_id = p.id AND comentarios.comentario_padre_id IS NULL) AS total_comentarios,
    -- DIAS_RESTANTES: Diferencia entre fecha fin y hoy
    DATEDIFF(p.fecha_fin, NOW()) AS dias_restantes,
    -- ESTADO_ACTUAL: Estado calculado según fecha
    CASE 
        WHEN p.estado = 'activa' AND p.fecha_fin >= NOW() THEN 'activa'
        WHEN p.estado = 'activa' AND p.fecha_fin < NOW() THEN 'finalizada'
        ELSE p.estado 
    END AS estado_actual
FROM propuestas p
LEFT JOIN usuarios u ON p.creado_por = u.id;

-- 2. RECREAR VISTA ranking_usuarios
-- ============================================
DROP VIEW IF EXISTS ranking_usuarios;

CREATE VIEW ranking_usuarios AS
SELECT 
    u.id,
    u.dni,
    CONCAT(u.nombres, ' ', u.apellido_paterno, ' ', u.apellido_materno) AS nombre_completo,
    u.puntos,
    -- TOTAL_VOTOS: Contar votos directos + votos en votaciones
    (
        (SELECT COUNT(DISTINCT v.id) FROM votos v WHERE v.usuario_id = u.id) +
        (SELECT COUNT(DISTINCT vv.id) FROM votacion_votos vv WHERE vv.usuario_id = u.id)
    ) AS total_votos,
    -- TOTAL_COMENTARIOS: Contar todos los comentarios del usuario
    COUNT(DISTINCT c.id) AS total_comentarios,
    -- TOTAL_RECOMPENSAS: Contar recompensas obtenidas
    COUNT(DISTINCT ur.id) AS total_recompensas,
    -- POSICION: Ranking según puntos
    RANK() OVER (ORDER BY u.puntos DESC) AS posicion
FROM usuarios u
LEFT JOIN comentarios c ON u.id = c.usuario_id
LEFT JOIN usuario_recompensas ur ON u.id = ur.usuario_id
WHERE u.rol = 'ciudadano' AND u.estado = 'activo'
GROUP BY u.id, u.dni, u.nombres, u.apellido_paterno, u.apellido_materno, u.puntos;

-- 3. ACTUALIZAR CONTADORES EXISTENTES (OPCIONAL)
-- ============================================
-- Actualizar los contadores en votacion_propuestas basándose en votos reales
UPDATE votacion_propuestas vp
SET votos_recibidos = (
    SELECT COUNT(*) 
    FROM votacion_votos vv 
    WHERE vv.votacion_id = vp.votacion_id 
    AND vv.propuesta_id = vp.propuesta_id
);

-- Actualizar contador total en votaciones
UPDATE votaciones v
SET total_votos = (
    SELECT COUNT(*) 
    FROM votacion_votos vv 
    WHERE vv.votacion_id = v.id
);

-- Actualizar porcentajes en votacion_propuestas
UPDATE votacion_propuestas vp
JOIN (
    SELECT 
        vp2.votacion_id,
        vp2.propuesta_id,
        CASE 
            WHEN v.total_votos > 0 
            THEN (vp2.votos_recibidos / v.total_votos * 100)
            ELSE 0 
        END as nuevo_porcentaje
    FROM votacion_propuestas vp2
    JOIN votaciones v ON vp2.votacion_id = v.id
) calc ON vp.votacion_id = calc.votacion_id AND vp.propuesta_id = calc.propuesta_id
SET vp.porcentaje = calc.nuevo_porcentaje;

-- ============================================
-- SCRIPT COMPLETADO
-- ============================================
-- Las vistas ahora cuentan correctamente:
-- - propuestas_estadisticas.total_votos: votos directos + votos en votaciones
-- - ranking_usuarios.total_votos: votos directos + votos en votaciones
-- Los contadores en las tablas también se actualizaron
-- ============================================
