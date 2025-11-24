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
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $fileSize = $file['size'];
    if ($fileSize > MAX_FILE_SIZE) {
        return false;
    }
    
    $fileName = $file['name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
        return false;
    }
    
    $uploadDir = UPLOAD_DIR . $subfolder . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
    $targetPath = $uploadDir . $newFileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $subfolder . '/' . $newFileName;
    }
    
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
?>
