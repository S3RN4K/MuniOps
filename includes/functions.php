<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

// Funciones de usuarios
function getUserByDNI($dni) {
    return fetchOne("SELECT * FROM usuarios WHERE dni = ?", [$dni]);
}

function getUserById($id) {
    return fetchOne("SELECT * FROM usuarios WHERE id = ?", [$id]);
}

function createUser($data) {
    // Usar la misma conexión para insert y lastInsertId
    $pdo = getConnection();
    
    $sql = "INSERT INTO usuarios (dni, nombres, apellido_paterno, apellido_materno, fecha_nacimiento, 
            sexo, municipio_id, direccion, email, telefono, password, rol) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['dni'],
            $data['nombres'],
            $data['apellido_paterno'],
            $data['apellido_materno'],
            $data['fecha_nacimiento'] ?? null,
            $data['sexo'] ?? null,
            $data['municipio_id'] ?? null,
            $data['direccion'] ?? null,
            $data['email'],
            $data['telefono'] ?? null,
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['rol'] ?? 'ciudadano'
        ]);
        
        // Obtener el ID insertado de la misma conexión
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error en createUser: " . $e->getMessage());
        return false;
    }
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function updateLastAccess($userId) {
    execute("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?", [$userId]);
}

// Funciones de propuestas
function getActivePropuestas($limit = null) {
    $sql = "SELECT * FROM propuestas_estadisticas 
            WHERE estado = 'activa' AND fecha_fin >= NOW() 
            ORDER BY fecha_inicio DESC";
    
    if ($limit) {
        $sql .= " LIMIT " . (int)$limit;
    }
    
    return fetchAll($sql);
}

function getPropuestaById($id) {
    return fetchOne("SELECT * FROM propuestas WHERE id = ?", [$id]);
}

function getPropuestaWithStats($id) {
    return fetchOne("SELECT * FROM propuestas_estadisticas WHERE id = ?", [$id]);
}

function createPropuesta($data) {
    // Usar la misma conexión para insert y lastInsertId
    $pdo = getConnection();
    
    $sql = "INSERT INTO propuestas (titulo, descripcion, categoria, imagen, municipio_id, presupuesto_estimado, 
            fecha_inicio, fecha_fin, estado, creado_por) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['titulo'],
            $data['descripcion'],
            $data['categoria'],
            $data['imagen'] ?? null,
            $data['municipio_id'] ?? null,
            $data['presupuesto_estimado'] ?? null,
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['estado'] ?? 'borrador',
            $data['creado_por']
        ]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error en createPropuesta: " . $e->getMessage());
        return false;
    }
}

function updatePropuesta($id, $data) {
    $sql = "UPDATE propuestas SET titulo = ?, descripcion = ?, categoria = ?, municipio_id = ?,
            presupuesto_estimado = ?, fecha_inicio = ?, fecha_fin = ?, estado = ? 
            WHERE id = ?";
    
    return execute($sql, [
        $data['titulo'],
        $data['descripcion'],
        $data['categoria'],
        $data['municipio_id'] ?? null,
        $data['presupuesto_estimado'] ?? null,
        $data['fecha_inicio'],
        $data['fecha_fin'],
        $data['estado'],
        $id
    ]);
}

function deletePropuesta($id) {
    return execute("DELETE FROM propuestas WHERE id = ?", [$id]);
}

// Funciones de votos
function hasVoted($userId, $propuestaId) {
    $result = fetchOne("SELECT id FROM votos WHERE usuario_id = ? AND propuesta_id = ?", 
                      [$userId, $propuestaId]);
    return !empty($result);
}

function registerVote($userId, $propuestaId, $ipAddress = null) {
    try {
        $pdo = getConnection();
        $pdo->beginTransaction();
        
        // Insertar voto
        $sql = "INSERT INTO votos (usuario_id, propuesta_id, ip_address) VALUES (?, ?, ?)";
        execute($sql, [$userId, $propuestaId, $ipAddress]);
        
        // Actualizar contador de votos en propuesta
        execute("UPDATE propuestas SET total_votos = total_votos + 1 WHERE id = ?", [$propuestaId]);
        
        // Otorgar puntos al usuario
        addPoints($userId, PUNTOS_VOTO, 'voto', 'Voto en propuesta #' . $propuestaId, $propuestaId);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function getVotosCount($propuestaId) {
    $result = fetchOne("SELECT COUNT(*) as total FROM votos WHERE propuesta_id = ?", [$propuestaId]);
    return $result['total'] ?? 0;
}

// Funciones de comentarios
function getComentariosByPropuesta($propuestaId) {
    $sql = "SELECT c.*, 
            CONCAT(u.nombres, ' ', u.apellido_paterno) as autor_nombre,
            (SELECT COUNT(*) FROM comentario_likes WHERE comentario_id = c.id) as total_likes
            FROM comentarios c
            JOIN usuarios u ON c.usuario_id = u.id
            WHERE c.propuesta_id = ? AND c.estado = 'visible' AND c.comentario_padre_id IS NULL
            ORDER BY c.fecha_comentario DESC";
    
    return fetchAll($sql, [$propuestaId]);
}

function getRespuestas($comentarioPadreId) {
    $sql = "SELECT c.*, 
            CONCAT(u.nombres, ' ', u.apellido_paterno) as autor_nombre,
            (SELECT COUNT(*) FROM comentario_likes WHERE comentario_id = c.id) as total_likes
            FROM comentarios c
            JOIN usuarios u ON c.usuario_id = u.id
            WHERE c.comentario_padre_id = ? AND c.estado = 'visible'
            ORDER BY c.fecha_comentario ASC";
    
    return fetchAll($sql, [$comentarioPadreId]);
}

function createComentario($propuestaId, $userId, $comentario, $comentarioPadreId = null) {
    try {
        $pdo = getConnection();
        $pdo->beginTransaction();
        
        $sql = "INSERT INTO comentarios (propuesta_id, usuario_id, comentario, comentario_padre_id) 
                VALUES (?, ?, ?, ?)";
        execute($sql, [$propuestaId, $userId, $comentario, $comentarioPadreId]);
        
        $comentarioId = lastInsertId();
        
        // Otorgar puntos
        addPoints($userId, PUNTOS_COMENTARIO, 'comentario', 
                 'Comentario en propuesta #' . $propuestaId, $comentarioId);
        
        $pdo->commit();
        return $comentarioId;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function toggleLikeComentario($comentarioId, $userId) {
    // Verificar si ya existe el like
    $existingLike = fetchOne("SELECT id FROM comentario_likes WHERE comentario_id = ? AND usuario_id = ?", 
                            [$comentarioId, $userId]);
    
    if ($existingLike) {
        // Quitar like
        execute("DELETE FROM comentario_likes WHERE comentario_id = ? AND usuario_id = ?", 
               [$comentarioId, $userId]);
        execute("UPDATE comentarios SET likes = likes - 1 WHERE id = ?", [$comentarioId]);
        return 'removed';
    } else {
        // Agregar like
        execute("INSERT INTO comentario_likes (comentario_id, usuario_id) VALUES (?, ?)", 
               [$comentarioId, $userId]);
        execute("UPDATE comentarios SET likes = likes + 1 WHERE id = ?", [$comentarioId]);
        
        // Otorgar puntos al autor del comentario
        $comentario = fetchOne("SELECT usuario_id FROM comentarios WHERE id = ?", [$comentarioId]);
        if ($comentario) {
            addPoints($comentario['usuario_id'], PUNTOS_LIKE_RECIBIDO, 'like_recibido', 
                     'Like recibido en comentario', $comentarioId);
        }
        
        return 'added';
    }
}

// Funciones de puntos
function addPoints($userId, $points, $tipo, $descripcion = '', $referenciaId = null) {
    $sql = "INSERT INTO historial_puntos (usuario_id, puntos, tipo_accion, descripcion, referencia_id) 
            VALUES (?, ?, ?, ?, ?)";
    execute($sql, [$userId, $points, $tipo, $descripcion, $referenciaId]);
    
    execute("UPDATE usuarios SET puntos = puntos + ? WHERE id = ?", [$points, $userId]);
    
    // Verificar logros
    checkAchievements($userId);
}

function getUserPoints($userId) {
    $result = fetchOne("SELECT puntos FROM usuarios WHERE id = ?", [$userId]);
    return $result['puntos'] ?? 0;
}

function getHistorialPuntos($userId, $limit = 10) {
    $sql = "SELECT * FROM historial_puntos WHERE usuario_id = ? 
            ORDER BY fecha_accion DESC LIMIT ?";
    return fetchAll($sql, [$userId, $limit]);
}

function getRanking($limit = 10) {
    return fetchAll("SELECT * FROM ranking_usuarios LIMIT ?", [$limit]);
}

function getUserRank($userId) {
    $result = fetchOne("SELECT posicion FROM ranking_usuarios WHERE id = ?", [$userId]);
    return $result['posicion'] ?? null;
}

// Funciones de recompensas
function getRecompensas() {
    return fetchAll("SELECT * FROM recompensas WHERE estado = 'activa' ORDER BY puntos_requeridos ASC");
}

function getUserRecompensas($userId) {
    $sql = "SELECT r.* FROM recompensas r
            JOIN usuario_recompensas ur ON r.id = ur.recompensa_id
            WHERE ur.usuario_id = ?
            ORDER BY ur.fecha_obtencion DESC";
    return fetchAll($sql, [$userId]);
}

function checkAchievements($userId) {
    $userPoints = getUserPoints($userId);
    $recompensas = getRecompensas();
    
    foreach ($recompensas as $recompensa) {
        if ($userPoints >= $recompensa['puntos_requeridos']) {
            // Verificar si ya tiene la recompensa
            $hasReward = fetchOne("SELECT id FROM usuario_recompensas 
                                  WHERE usuario_id = ? AND recompensa_id = ?", 
                                 [$userId, $recompensa['id']]);
            
            if (!$hasReward) {
                execute("INSERT INTO usuario_recompensas (usuario_id, recompensa_id) VALUES (?, ?)", 
                       [$userId, $recompensa['id']]);
            }
        }
    }
}

// Función para consultar API DNI
// Función para validar DNI peruano
function validarDNI($dni) {
    return preg_match('/^\d{8}$/', $dni);
}

// Función para consultar API DNI
function consultarDNI($dni) {
    $url = API_DNI_URL . $dni;
    $token = API_DNI_TOKEN;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        return json_decode($response, true);
    }
    
    return false;
}

// Función para subir imagen
function uploadImage($file, $subfolder = 'propuestas') {
    // Debug: verificar que el archivo llegó
    if (!isset($file['error'])) {
        error_log("uploadImage: No hay archivo o error no definido");
        return false;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        error_log("uploadImage: Error en upload. Código: " . $file['error']);
        return false;
    }
    
    $fileSize = $file['size'];
    error_log("uploadImage: Tamaño archivo: " . $fileSize . " bytes, Max: " . MAX_FILE_SIZE);
    
    if ($fileSize > MAX_FILE_SIZE) {
        error_log("uploadImage: Archivo muy grande");
        return false;
    }
    
    $fileName = $file['name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    error_log("uploadImage: Extensión: " . $fileExt);
    
    if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
        error_log("uploadImage: Extensión no permitida");
        return false;
    }
    
    $uploadDir = UPLOAD_DIR . $subfolder . '/';
    error_log("uploadImage: Directorio destino: " . $uploadDir);
    
    if (!is_dir($uploadDir)) {
        error_log("uploadImage: Creando directorio");
        mkdir($uploadDir, 0777, true);
    }
    
    $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
    $targetPath = $uploadDir . $newFileName;
    
    error_log("uploadImage: Intentando mover a: " . $targetPath);
    error_log("uploadImage: Archivo temporal: " . $file['tmp_name']);
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        error_log("uploadImage: Éxito! Retornando: " . $subfolder . '/' . $newFileName);
        return $subfolder . '/' . $newFileName;
    }
    
    error_log("uploadImage: Falló move_uploaded_file");
    return false;
}

// ===== FUNCIONES DE MUNICIPIOS =====

// Obtener todos los municipios
function getAllMunicipios() {
    return fetchAll("SELECT * FROM municipios ORDER BY departamento, nombre");
}

// Obtener municipio por ID
function getMunicipioById($id) {
    return fetchOne("SELECT * FROM municipios WHERE id = ?", [$id]);
}

// Validar que el usuario pueda votar en una propuesta (mismo municipio)
function canUserVotePropuesta($userId, $propuestaId) {
    // Obtener municipio del usuario
    $user = getUserById($userId);
    if (!$user || !$user['municipio_id']) {
        return ['canVote' => false, 'reason' => 'Usuario sin municipio asignado'];
    }
    
    // Obtener municipio de la propuesta
    $propuesta = fetchOne("SELECT municipio_id FROM propuestas WHERE id = ?", [$propuestaId]);
    if (!$propuesta || !$propuesta['municipio_id']) {
        return ['canVote' => false, 'reason' => 'Propuesta sin municipio asignado'];
    }
    
    // Comparar municipios
    if ($user['municipio_id'] != $propuesta['municipio_id']) {
        $userMuni = getMunicipioById($user['municipio_id']);
        $propuestaMuni = getMunicipioById($propuesta['municipio_id']);
        return [
            'canVote' => false,
            'reason' => "Solo puedes votar propuestas de tu municipio ({$userMuni['nombre']}). Esta propuesta es de {$propuestaMuni['nombre']}"
        ];
    }
    
    return ['canVote' => true];
}

// Filtrar propuestas por municipio del usuario
function getActivePropuestasByUserMunicipio($userId, $limit = null) {
    $user = getUserById($userId);
    
    if (!$user || !$user['municipio_id']) {
        return [];
    }
    
    $sql = "SELECT * FROM propuestas_estadisticas 
            WHERE estado = 'activa' 
            AND fecha_fin >= NOW()
            AND municipio_id = ?
            ORDER BY fecha_inicio DESC";
    
    if ($limit) {
        $sql .= " LIMIT " . (int)$limit;
    }
    
    return fetchAll($sql, [$user['municipio_id']]);
}

// Obtener municipios con más propuestas activas
function getMunicipiosWithStats() {
    return fetchAll("
        SELECT 
            m.id,
            m.nombre,
            m.departamento,
            m.provincia,
            COUNT(p.id) as total_propuestas,
            SUM(CASE WHEN p.estado = 'activa' AND p.fecha_fin >= NOW() THEN 1 ELSE 0 END) as propuestas_activas,
            COUNT(DISTINCT v.usuario_id) as total_votantes
        FROM municipios m
        LEFT JOIN propuestas p ON p.municipio_id = m.id
        LEFT JOIN votos v ON v.propuesta_id = p.id
        GROUP BY m.id, m.nombre, m.departamento, m.provincia
        ORDER BY propuestas_activas DESC, m.nombre
    ");
}

// ===== FUNCIONES DE VOTACIONES =====

// Crear una votación
function createVotacion($data) {
    $pdo = getConnection();
    
    $sql = "INSERT INTO votaciones (titulo, descripcion, municipio_id, fecha_inicio, fecha_fin, estado, creado_por) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['titulo'],
            $data['descripcion'] ?? null,
            $data['municipio_id'],
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['estado'] ?? 'borrador',
            $data['creado_por']
        ]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error en createVotacion: " . $e->getMessage());
        return false;
    }
}

// Agregar propuesta a votación
function addPropuestaToVotacion($votacionId, $propuestaId, $orden = 1) {
    $pdo = getConnection();
    
    try {
        $stmt = $pdo->prepare("INSERT INTO votacion_propuestas (votacion_id, propuesta_id, orden) VALUES (?, ?, ?)");
        $stmt->execute([$votacionId, $propuestaId, $orden]);
        
        // Incrementar contador de veces usada
        execute("UPDATE propuestas SET veces_usada_votacion = veces_usada_votacion + 1 WHERE id = ?", [$propuestaId]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error en addPropuestaToVotacion: " . $e->getMessage());
        return false;
    }
}

// Remover propuesta de votación
function removePropuestaFromVotacion($votacionId, $propuestaId) {
    return execute("DELETE FROM votacion_propuestas WHERE votacion_id = ? AND propuesta_id = ?", [$votacionId, $propuestaId]) > 0;
}

// Obtener todas las votaciones
function getAllVotaciones($municipioId = null) {
    if ($municipioId) {
        return fetchAll("SELECT * FROM votaciones_estadisticas WHERE municipio_id = ? ORDER BY fecha_creacion DESC", [$municipioId]);
    }
    return fetchAll("SELECT * FROM votaciones_estadisticas ORDER BY fecha_creacion DESC");
}

// Obtener votación por ID
function getVotacionById($id) {
    return fetchOne("SELECT * FROM votaciones WHERE id = ?", [$id]);
}

// Obtener votación con estadísticas
function getVotacionWithStats($id) {
    return fetchOne("SELECT * FROM votaciones_estadisticas WHERE id = ?", [$id]);
}

// Obtener votaciones activas
function getActiveVotaciones($municipioId = null) {
    if ($municipioId) {
        return fetchAll("SELECT * FROM votaciones_estadisticas WHERE estado = 'activa' AND municipio_id = ? AND fecha_fin >= NOW() ORDER BY fecha_inicio DESC", [$municipioId]);
    }
    return fetchAll("SELECT * FROM votaciones_estadisticas WHERE estado = 'activa' AND fecha_fin >= NOW() ORDER BY fecha_inicio DESC");
}

// Obtener propuestas de una votación con sus votos
function getPropuestasDeVotacion($votacionId) {
    $sql = "SELECT 
                vp.*,
                p.titulo,
                p.descripcion,
                p.categoria,
                p.imagen,
                p.presupuesto_estimado,
                COALESCE((SELECT COUNT(*) FROM votacion_votos WHERE votacion_id = vp.votacion_id AND propuesta_id = vp.propuesta_id), 0) as votos_recibidos,
                (SELECT COUNT(*) FROM votacion_votos WHERE votacion_id = vp.votacion_id) as total_votos_votacion
            FROM votacion_propuestas vp
            JOIN propuestas p ON vp.propuesta_id = p.id
            WHERE vp.votacion_id = ?
            ORDER BY vp.orden ASC";
    
    $propuestas = fetchAll($sql, [$votacionId]);
    
    // Calcular porcentajes
    foreach ($propuestas as &$prop) {
        $totalVotos = (int)$prop['total_votos_votacion'];
        $prop['porcentaje'] = $totalVotos > 0 ? ($prop['votos_recibidos'] / $totalVotos * 100) : 0;
    }
    
    return $propuestas;
}

// Obtener votación activa que contiene una propuesta específica
function getVotacionActivaByPropuesta($propuestaId) {
    $sql = "SELECT v.* 
            FROM votaciones v
            JOIN votacion_propuestas vp ON v.id = vp.votacion_id
            WHERE vp.propuesta_id = ? 
            AND v.estado = 'activa' 
            AND v.fecha_fin >= NOW()
            LIMIT 1";
    
    return fetchOne($sql, [$propuestaId]);
}

// Verificar si usuario ya votó en una votación
function hasVotedInVotacion($usuarioId, $votacionId) {
    $voto = fetchOne("SELECT id FROM votacion_votos WHERE usuario_id = ? AND votacion_id = ?", [$usuarioId, $votacionId]);
    return $voto !== false;
}

// Registrar voto en votación
function registerVotoVotacion($votacionId, $propuestaId, $usuarioId) {
    $pdo = getConnection();
    
    try {
        // Verificar que no haya votado antes
        if (hasVotedInVotacion($usuarioId, $votacionId)) {
            return ['success' => false, 'message' => 'Ya has votado en esta votación'];
        }
        
        // Verificar que la votación esté activa
        $votacion = getVotacionById($votacionId);
        if (!$votacion || $votacion['estado'] !== 'activa') {
            return ['success' => false, 'message' => 'La votación no está activa'];
        }
        
        // Verificar que la propuesta pertenezca a la votación
        $propuestaEnVotacion = fetchOne("SELECT id FROM votacion_propuestas WHERE votacion_id = ? AND propuesta_id = ?", [$votacionId, $propuestaId]);
        if (!$propuestaEnVotacion) {
            return ['success' => false, 'message' => 'La propuesta no pertenece a esta votación'];
        }
        
        // Registrar voto
        $stmt = $pdo->prepare("INSERT INTO votacion_votos (votacion_id, propuesta_id, usuario_id, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$votacionId, $propuestaId, $usuarioId, $_SERVER['REMOTE_ADDR'] ?? null]);
        
        // Otorgar puntos
        addPoints($usuarioId, PUNTOS_VOTO, 'voto', 'Voto en votación: ' . $votacion['titulo'], $votacionId);
        
        return ['success' => true, 'message' => '¡Voto registrado exitosamente!'];
    } catch (PDOException $e) {
        error_log("Error en registerVotoVotacion: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al registrar el voto'];
    }
}

// Actualizar estado de votación
function updateVotacionEstado($votacionId, $estado) {
    return execute("UPDATE votaciones SET estado = ? WHERE id = ?", [$estado, $votacionId]) > 0;
}

// Finalizar votación y determinar ganadora
function finalizarVotacion($votacionId) {
    $pdo = getConnection();
    
    try {
        // Obtener propuesta con más votos
        $ganadora = fetchOne("
            SELECT propuesta_id, votos_recibidos 
            FROM votacion_propuestas 
            WHERE votacion_id = ? 
            ORDER BY votos_recibidos DESC 
            LIMIT 1
        ", [$votacionId]);
        
        if (!$ganadora) {
            return ['success' => false, 'message' => 'No hay votos registrados'];
        }
        
        // Calcular porcentajes
        $totalVotos = fetchOne("SELECT total_votos FROM votaciones WHERE id = ?", [$votacionId])['total_votos'];
        
        if ($totalVotos > 0) {
            $propuestas = fetchAll("SELECT propuesta_id, votos_recibidos FROM votacion_propuestas WHERE votacion_id = ?", [$votacionId]);
            
            foreach ($propuestas as $prop) {
                $porcentaje = ($prop['votos_recibidos'] / $totalVotos) * 100;
                execute("UPDATE votacion_propuestas SET porcentaje = ? WHERE votacion_id = ? AND propuesta_id = ?", 
                    [$porcentaje, $votacionId, $prop['propuesta_id']]);
            }
        }
        
        // Marcar ganadora en votacion_propuestas
        execute("UPDATE votacion_propuestas SET es_ganadora = TRUE WHERE votacion_id = ? AND propuesta_id = ?", 
            [$votacionId, $ganadora['propuesta_id']]);
        
        // Marcar ganadora en propuestas (nueva columna)
        execute("UPDATE propuestas SET es_ganadora = 1 WHERE id = ?", [$ganadora['propuesta_id']]);
        
        // Actualizar votación
        execute("UPDATE votaciones SET estado = 'finalizada', propuesta_ganadora_id = ? WHERE id = ?", 
            [$ganadora['propuesta_id'], $votacionId]);
        
        // Cambiar estado de propuesta ganadora a implementada
        execute("UPDATE propuestas SET estado = 'implementada' WHERE id = ?", [$ganadora['propuesta_id']]);
        
        // Archivar propuestas perdedoras
        $propuestas = fetchAll("SELECT propuesta_id FROM votacion_propuestas WHERE votacion_id = ? AND propuesta_id != ?", 
            [$votacionId, $ganadora['propuesta_id']]);
        
        foreach ($propuestas as $prop) {
            execute("UPDATE propuestas SET archivada = TRUE, fecha_archivo = NOW() WHERE id = ?", [$prop['propuesta_id']]);
        }
        
        return ['success' => true, 'message' => 'Votación finalizada correctamente', 'ganadora_id' => $ganadora['propuesta_id']];
    } catch (PDOException $e) {
        error_log("Error en finalizarVotacion: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al finalizar la votación'];
    }
}

// Obtener propuestas disponibles para votación (no archivadas, no en votaciones activas)
function getPropuestasDisponiblesParaVotacion($municipioId = null) {
    $sql = "SELECT p.* FROM propuestas p
            WHERE p.archivada = FALSE 
            AND p.estado IN ('activa', 'finalizada')
            AND p.id NOT IN (
                SELECT vp.propuesta_id 
                FROM votacion_propuestas vp
                JOIN votaciones v ON vp.votacion_id = v.id
                WHERE v.estado IN ('activa', 'borrador')
            )";
    
    if ($municipioId) {
        $sql .= " AND p.municipio_id = ?";
        return fetchAll($sql, [$municipioId]);
    }
    
    return fetchAll($sql);
}

// Obtener propuestas archivadas (para reutilizar)
function getPropuestasArchivadas($municipioId = null) {
    if ($municipioId) {
        return fetchAll("SELECT * FROM propuestas WHERE archivada = TRUE AND municipio_id = ? ORDER BY fecha_archivo DESC", [$municipioId]);
    }
    return fetchAll("SELECT * FROM propuestas WHERE archivada = TRUE ORDER BY fecha_archivo DESC");
}

// Desarchivar propuesta (para reutilizar)
function desarchivarPropuesta($propuestaId) {
    return execute("UPDATE propuestas SET archivada = FALSE, fecha_archivo = NULL WHERE id = ?", [$propuestaId]) > 0;
}

// Eliminar votación
function deleteVotacion($id) {
    // Las propuestas asociadas se eliminan automáticamente por CASCADE
    return execute("DELETE FROM votaciones WHERE id = ?", [$id]) > 0;
}

// Actualizar votación
function updateVotacion($id, $data) {
    $sql = "UPDATE votaciones SET titulo = ?, descripcion = ?, municipio_id = ?, 
            fecha_inicio = ?, fecha_fin = ?, estado = ? WHERE id = ?";
    
    return execute($sql, [
        $data['titulo'],
        $data['descripcion'] ?? null,
        $data['municipio_id'],
        $data['fecha_inicio'],
        $data['fecha_fin'],
        $data['estado'] ?? 'borrador',
        $id
    ]) > 0;
}

// ==================== FUNCIONES PARA CIERRE AUTOMÁTICO DE VOTACIONES ====================

// Verificar y finalizar automáticamente votaciones vencidas
function checkAndFinalizarVotaciones() {
    try {
        // Obtener votaciones activas que ya pasaron su fecha de fin
        $votacionesVencidas = fetchAll("
            SELECT id FROM votaciones 
            WHERE estado = 'activa' 
            AND fecha_fin < NOW()
        ");
        
        foreach ($votacionesVencidas as $votacion) {
            finalizarVotacion($votacion['id']);
        }
        
        return count($votacionesVencidas);
    } catch (Exception $e) {
        error_log("Error en checkAndFinalizarVotaciones: " . $e->getMessage());
        return 0;
    }
}

// ==================== FUNCIONES PARA PROPUESTAS GANADORAS ====================

// Obtener propuestas ganadoras
function getPropuestasGanadoras($municipioId = null) {
    if ($municipioId) {
        return fetchAll("SELECT * FROM propuestas WHERE es_ganadora = 1 AND municipio_id = ? ORDER BY fecha_creacion DESC", [$municipioId]);
    }
    return fetchAll("SELECT * FROM propuestas WHERE es_ganadora = 1 ORDER BY fecha_creacion DESC");
}

// ==================== FUNCIONES PARA SEGUIMIENTO DE PROPUESTAS ====================

// Crear seguimiento
function createSeguimiento($propuestaId, $titulo, $descripcion, $imagen, $creadoPor) {
    $sql = "INSERT INTO seguimiento_propuestas 
            (propuesta_id, titulo, descripcion, imagen, fecha_actualizacion, creado_por) 
            VALUES (?, ?, ?, ?, NOW(), ?)";
    
    return execute($sql, [$propuestaId, $titulo, $descripcion, $imagen, $creadoPor]) > 0;
}

// Obtener seguimientos de una propuesta
function getSeguimientosByPropuesta($propuestaId) {
    return fetchAll("
        SELECT s.*, CONCAT(u.nombres, ' ', COALESCE(u.apellido_paterno, '')) as autor_nombre 
        FROM seguimiento_propuestas s
        JOIN usuarios u ON s.creado_por = u.id
        WHERE s.propuesta_id = ?
        ORDER BY s.fecha_actualizacion DESC
    ", [$propuestaId]);
}

// Obtener un seguimiento por ID
function getSeguimientoById($id) {
    return fetchOne("SELECT * FROM seguimiento_propuestas WHERE id = ?", [$id]);
}

// Actualizar seguimiento
function updateSeguimiento($id, $titulo, $descripcion, $imagen = null) {
    if ($imagen) {
        $sql = "UPDATE seguimiento_propuestas SET titulo = ?, descripcion = ?, imagen = ?, fecha_actualizacion = NOW() WHERE id = ?";
        return execute($sql, [$titulo, $descripcion, $imagen, $id]) > 0;
    } else {
        $sql = "UPDATE seguimiento_propuestas SET titulo = ?, descripcion = ?, fecha_actualizacion = NOW() WHERE id = ?";
        return execute($sql, [$titulo, $descripcion, $id]) > 0;
    }
}

// Eliminar seguimiento
function deleteSeguimiento($id) {
    return execute("DELETE FROM seguimiento_propuestas WHERE id = ?", [$id]) > 0;
}
?>
