<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar que esté logueado
if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'No estás autenticado']);
    exit;
}

// Procesar votación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['propuesta_id'])) {
    $userId = $_SESSION['user_id'];
    $propuestaId = (int)$_POST['propuesta_id'];
    
    header('Content-Type: application/json');
    
    // Validar que el usuario puede votar esta propuesta
    $validation = canUserVotePropuesta($userId, $propuestaId);
    
    if (!$validation['canVote']) {
        echo json_encode([
            'success' => false,
            'error' => $validation['reason']
        ]);
        http_response_code(403);
        exit;
    }
    
    // Verificar que ya no haya votado
    $existingVote = fetchOne(
        "SELECT id FROM votos WHERE usuario_id = ? AND propuesta_id = ?", 
        [$userId, $propuestaId]
    );
    
    if ($existingVote) {
        echo json_encode([
            'success' => false,
            'error' => 'Ya has votado en esta propuesta'
        ]);
        http_response_code(400);
        exit;
    }
    
    // Registrar voto
    try {
        execute(
            "INSERT INTO votos (usuario_id, propuesta_id, fecha_voto) VALUES (?, ?, NOW())",
            [$userId, $propuestaId]
        );
        
        // Aumentar puntos al usuario
        addPoints($userId, PUNTOS_VOTO, 'voto', 'Votaste en una propuesta', $propuestaId);
        
        // Actualizar contador de votos
        execute("UPDATE propuestas SET total_votos = total_votos + 1 WHERE id = ?", [$propuestaId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Voto registrado exitosamente. Has ganado ' . PUNTOS_VOTO . ' puntos'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error al registrar el voto'
        ]);
        http_response_code(500);
    }
    exit;
}

echo json_encode(['error' => 'Solicitud inválida']);
http_response_code(400);
?>
