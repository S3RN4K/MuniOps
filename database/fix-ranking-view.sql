-- Recrear la vista ranking_usuarios para contar votos directos + votos en votaciones
DROP VIEW IF EXISTS ranking_usuarios;

CREATE VIEW ranking_usuarios AS
SELECT 
    u.id,
    u.dni,
    CONCAT(u.nombres, ' ', u.apellido_paterno, ' ', u.apellido_materno) AS nombre_completo,
    u.puntos,
    -- Contar votos directos + votos en votaciones
    (
        (SELECT COUNT(DISTINCT v.id) FROM votos v WHERE v.usuario_id = u.id) +
        (SELECT COUNT(DISTINCT vv.id) FROM votacion_votos vv WHERE vv.usuario_id = u.id)
    ) AS total_votos,
    COUNT(DISTINCT c.id) AS total_comentarios,
    COUNT(DISTINCT ur.id) AS total_recompensas,
    RANK() OVER (ORDER BY u.puntos DESC) AS posicion
FROM usuarios u
LEFT JOIN comentarios c ON u.id = c.usuario_id
LEFT JOIN usuario_recompensas ur ON u.id = ur.usuario_id
WHERE u.rol = 'ciudadano' AND u.estado = 'activo'
GROUP BY u.id, u.dni, u.nombres, u.apellido_paterno, u.apellido_materno, u.puntos;
