# 📚 Documentación Técnica de MuniOps

## 🎯 Descripción General del Proyecto

**MuniOps** es una plataforma de participación ciudadana gamificada desarrollada en PHP puro (vanilla PHP) que permite a las municipalidades gestionar propuestas y a los ciudadanos votar, comentar y participar activamente en las decisiones de su comunidad. El sistema incluye un sistema de puntos y recompensas para incentivar la participación.

---

## 🏗️ Arquitectura del Sistema

### Stack Tecnológico

| Capa | Tecnología | Versión |
|------|------------|---------|
| **Backend** | PHP | 7.4+ |
| **Base de Datos** | MySQL (Aiven Cloud) | 5.7+ |
| **Frontend** | Bootstrap + jQuery | 5.3 |
| **Servidor Web** | Apache/Nginx | - |

### Estructura de Directorios

```
MuniOps/
├── admin/                      # Panel de administración
│   ├── dashboard.php          # Dashboard con estadísticas
│   ├── propuestas.php         # CRUD de propuestas
│   ├── votaciones.php         # Gestión de votaciones
│   ├── usuarios.php           # Gestión de usuarios
│   ├── reportes.php           # Reportes y analíticas
│   └── seguimiento-propuesta.php
│
├── api/                       # Endpoints API
│   └── votar.php              # API para registro de votos
│
├── assets/                    # Recursos estáticos
│   ├── css/                   # Estilos CSS
│   └── js/                    # JavaScript
│
├── config/                    # Configuración del sistema
│   ├── config.php             # Configuración general
│   └── database.php           # Conexión a base de datos
│
├── database/                  # Scripts SQL
│   ├── muniops.sql            # Esquema principal
│   ├── upgrade-votaciones.sql # Sistema de votaciones
│   ├── upgrade-municipios.sql # Sistema de municipios
│   └── upgrade-propuestas-ganadoras.sql
│
├── includes/                  # Componentes reutilizables
│   ├── functions.php          # Funciones del sistema
│   ├── header.php             # Encabezado HTML
│   └── footer.php             # Pie de página
│
├── Certs/                     # Certificados SSL
│   └── ca.pem                 # Certificado CA para Aiven
│
├── uploads/                   # Archivos subidos
│   └── propuestas/            # Imágenes de propuestas
│
└── [Páginas públicas]         # Interfaces de usuario
    ├── index.php              # Página principal
    ├── login.php              # Inicio de sesión
    ├── registro.php           # Registro de usuarios
    ├── propuestas.php         # Lista de propuestas
    ├── propuesta-detalle.php  # Detalle de propuesta
    ├── votaciones.php         # Sistema de votaciones
    ├── ranking.php            # Ranking de usuarios
    ├── recompensas.php        # Sistema de logros
    ├── perfil.php             # Perfil de usuario
    └── mis-votos.php          # Historial de votos
```

---

## 🗄️ Base de Datos Remota (Aiven Cloud)

### Configuración de Conexión

El sistema utiliza una base de datos MySQL hospedada en **Aiven Cloud**, un servicio de base de datos gestionado en la nube. La configuración se encuentra en `config/database.php`:

```php
<?php
// Configuración de la base de datos remota (Aiven Cloud)
// NOTA: Las credenciales deben configurarse como variables de entorno en producción
define('DB_HOST', 'tu-servidor.aivencloud.com');     // Host de Aiven Cloud
define('DB_PORT', '19400');                           // Puerto MySQL SSL
define('DB_USER', 'tu_usuario');                      // Usuario de base de datos
define('DB_PASS', 'tu_password_seguro');              // Contraseña (usar variables de entorno)
define('DB_NAME', 'nombre_base_datos');               // Nombre de la base de datos
define('DB_CHARSET', 'utf8mb4');                      // Charset UTF8 completo
define('DB_SSL_CA', __DIR__ . '/../Certs/ca.pem');    // Certificado CA para SSL
```

### Conexión Segura con SSL

La conexión a la base de datos remota utiliza **SSL/TLS** para garantizar la seguridad de los datos en tránsito:

```php
function getConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_SSL_CA => DB_SSL_CA,           // Certificado CA
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true, // Verificación SSL
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}
```

### Características de Seguridad

1. **Conexión SSL obligatoria**: Todos los datos se transmiten encriptados
2. **Verificación de certificado**: Se valida el certificado del servidor
3. **Prepared Statements**: Prevención de inyección SQL
4. **Charset UTF8MB4**: Soporte completo para caracteres Unicode

### Funciones Auxiliares de Base de Datos

```php
// Ejecutar consulta con parámetros
function query($sql, $params = []) {
    $pdo = getConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

// Obtener un registro
function fetchOne($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetch();
}

// Obtener múltiples registros
function fetchAll($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetchAll();
}

// Ejecutar INSERT/UPDATE/DELETE
function execute($sql, $params = []) {
    $pdo = getConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}
```

---

## 📊 Esquema de Base de Datos

### Tablas Principales

#### 1. `usuarios` - Gestión de usuarios
```sql
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dni VARCHAR(8) UNIQUE NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellido_paterno VARCHAR(100) NOT NULL,
    apellido_materno VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE,
    sexo ENUM('M', 'F') DEFAULT NULL,
    municipio_id INT,                    -- Relación con municipio
    direccion VARCHAR(255),
    email VARCHAR(100) UNIQUE,
    telefono VARCHAR(15),
    password VARCHAR(255) NOT NULL,      -- Hash bcrypt
    rol ENUM('ciudadano', 'admin') DEFAULT 'ciudadano',
    puntos INT DEFAULT 0,                -- Sistema de gamificación
    estado ENUM('activo', 'inactivo', 'bloqueado') DEFAULT 'activo',
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso DATETIME DEFAULT NULL
);
```

#### 2. `propuestas` - Propuestas municipales
```sql
CREATE TABLE propuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT NOT NULL,
    categoria ENUM('infraestructura', 'salud', 'educacion', 'seguridad', 
                   'medio_ambiente', 'deporte', 'cultura', 'otros'),
    imagen VARCHAR(255) DEFAULT NULL,
    municipio_id INT,                    -- Propuesta por municipio
    presupuesto_estimado DECIMAL(10,2),
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    estado ENUM('borrador', 'activa', 'finalizada', 'implementada', 'cancelada'),
    archivada BOOLEAN DEFAULT FALSE,
    es_ganadora BOOLEAN DEFAULT FALSE,
    total_votos INT DEFAULT 0,
    creado_por INT NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
);
```

#### 3. `votaciones` - Sistema de votación entre propuestas
```sql
CREATE TABLE votaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    municipio_id INT NOT NULL,           -- Votación por municipio
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    estado ENUM('borrador', 'activa', 'finalizada', 'cancelada'),
    propuesta_ganadora_id INT DEFAULT NULL,
    total_votos INT DEFAULT 0,
    creado_por INT NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (municipio_id) REFERENCES municipios(id),
    FOREIGN KEY (propuesta_ganadora_id) REFERENCES propuestas(id)
);
```

#### 4. `votos` - Registro de votos
```sql
CREATE TABLE votos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    propuesta_id INT NOT NULL,
    fecha_voto DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    UNIQUE KEY unique_voto (usuario_id, propuesta_id),  -- Un voto por propuesta
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (propuesta_id) REFERENCES propuestas(id)
);
```

#### 5. `comentarios` - Sistema de comentarios
```sql
CREATE TABLE comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    propuesta_id INT NOT NULL,
    usuario_id INT NOT NULL,
    comentario TEXT NOT NULL,
    comentario_padre_id INT DEFAULT NULL,  -- Para respuestas anidadas
    fecha_comentario DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('visible', 'oculto', 'reportado'),
    likes INT DEFAULT 0,
    FOREIGN KEY (propuesta_id) REFERENCES propuestas(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (comentario_padre_id) REFERENCES comentarios(id)
);
```

#### 6. `historial_puntos` - Registro de puntos obtenidos
```sql
CREATE TABLE historial_puntos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    puntos INT NOT NULL,
    tipo_accion ENUM('voto', 'comentario', 'like_recibido', 
                     'propuesta_implementada', 'bono', 'penalizacion'),
    descripcion VARCHAR(255),
    referencia_id INT DEFAULT NULL,
    fecha_accion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
```

#### 7. `votacion_propuestas` - Propuestas dentro de una votación
```sql
CREATE TABLE votacion_propuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    votacion_id INT NOT NULL,
    propuesta_id INT NOT NULL,
    orden INT NOT NULL DEFAULT 1,
    votos_recibidos INT DEFAULT 0,
    porcentaje DECIMAL(5,2) DEFAULT 0.00,
    es_ganadora BOOLEAN DEFAULT FALSE,
    UNIQUE KEY unique_votacion_propuesta (votacion_id, propuesta_id),
    FOREIGN KEY (votacion_id) REFERENCES votaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (propuesta_id) REFERENCES propuestas(id) ON DELETE CASCADE
);
```

#### 8. `votacion_votos` - Votos en votaciones grupales
```sql
CREATE TABLE votacion_votos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    votacion_id INT NOT NULL,
    propuesta_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_voto DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) DEFAULT NULL,
    UNIQUE KEY unique_voto_votacion (votacion_id, usuario_id),  -- Un voto por votación
    FOREIGN KEY (votacion_id) REFERENCES votaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (propuesta_id) REFERENCES propuestas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
```

### Vistas SQL

```sql
-- Vista para ranking de usuarios
CREATE VIEW ranking_usuarios AS
SELECT 
    u.id,
    CONCAT(u.nombres, ' ', u.apellido_paterno, ' ', u.apellido_materno) as nombre_completo,
    u.puntos,
    COUNT(DISTINCT v.id) as total_votos,
    COUNT(DISTINCT c.id) as total_comentarios,
    RANK() OVER (ORDER BY u.puntos DESC) as posicion
FROM usuarios u
LEFT JOIN votos v ON u.id = v.usuario_id
LEFT JOIN comentarios c ON u.id = c.usuario_id
WHERE u.rol = 'ciudadano' AND u.estado = 'activo'
GROUP BY u.id;

-- Vista para propuestas con estadísticas
CREATE VIEW propuestas_estadisticas AS
SELECT 
    p.*,
    COUNT(DISTINCT c.id) as total_comentarios,
    DATEDIFF(p.fecha_fin, NOW()) as dias_restantes
FROM propuestas p
LEFT JOIN comentarios c ON p.id = c.propuesta_id
GROUP BY p.id;
```

### Triggers

```sql
-- Actualizar contadores automáticamente al registrar votos en votaciones
-- Esta tabla se usa para el sistema de votaciones grupales entre propuestas
CREATE TRIGGER after_votacion_voto_insert
AFTER INSERT ON votacion_votos
FOR EACH ROW
BEGIN
    -- Actualizar votos de la propuesta en la votación
    UPDATE votacion_propuestas 
    SET votos_recibidos = votos_recibidos + 1
    WHERE votacion_id = NEW.votacion_id AND propuesta_id = NEW.propuesta_id;
    
    -- Actualizar total de votos de la votación
    UPDATE votaciones 
    SET total_votos = total_votos + 1
    WHERE id = NEW.votacion_id;
END;
```

> **Nota**: La tabla `votacion_votos` es parte del sistema de votaciones grupales (definida en `upgrade-votaciones.sql`), donde múltiples propuestas compiten entre sí. La tabla `votos` estándar se usa para votos individuales en propuestas.

---

## ⚙️ Programación PHP

### Patrón de Diseño

El proyecto utiliza un **patrón procedural estructurado** con:
- Separación de configuración (`config/`)
- Funciones reutilizables (`includes/functions.php`)
- Componentes de vista (`includes/header.php`, `includes/footer.php`)

### Sistema de Autenticación

```php
// config/config.php - Manejo de sesiones

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        redirect('index.php');
    }
}
```

### Registro de Usuarios

```php
function createUser($data) {
    $pdo = getConnection();
    
    $sql = "INSERT INTO usuarios (dni, nombres, apellido_paterno, apellido_materno, 
            fecha_nacimiento, sexo, municipio_id, direccion, email, telefono, password, rol) 
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
            password_hash($data['password'], PASSWORD_DEFAULT),  // Hash seguro
            $data['rol'] ?? 'ciudadano'
        ]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error en createUser: " . $e->getMessage());
        return false;
    }
}
```

### Sistema de Votación con Restricción por Municipio

```php
// Validar que el usuario pueda votar (mismo municipio)
function canUserVotePropuesta($userId, $propuestaId) {
    $user = getUserById($userId);
    if (!$user || !$user['municipio_id']) {
        return ['canVote' => false, 'reason' => 'Usuario sin municipio asignado'];
    }
    
    $propuesta = fetchOne("SELECT municipio_id FROM propuestas WHERE id = ?", [$propuestaId]);
    if (!$propuesta || !$propuesta['municipio_id']) {
        return ['canVote' => false, 'reason' => 'Propuesta sin municipio asignado'];
    }
    
    if ($user['municipio_id'] != $propuesta['municipio_id']) {
        return [
            'canVote' => false,
            'reason' => "Solo puedes votar propuestas de tu municipio"
        ];
    }
    
    return ['canVote' => true];
}
```

### Sistema de Puntos (Gamificación)

```php
// Constantes de puntos
define('PUNTOS_VOTO', 10);
define('PUNTOS_COMENTARIO', 5);
define('PUNTOS_LIKE_RECIBIDO', 2);

// Agregar puntos al usuario
function addPoints($userId, $points, $tipo, $descripcion = '', $referenciaId = null) {
    // Registrar en historial
    $sql = "INSERT INTO historial_puntos (usuario_id, puntos, tipo_accion, descripcion, referencia_id) 
            VALUES (?, ?, ?, ?, ?)";
    execute($sql, [$userId, $points, $tipo, $descripcion, $referenciaId]);
    
    // Actualizar puntos totales
    execute("UPDATE usuarios SET puntos = puntos + ? WHERE id = ?", [$points, $userId]);
    
    // Verificar logros desbloqueados
    checkAchievements($userId);
}

// Verificar y otorgar recompensas
function checkAchievements($userId) {
    $userPoints = getUserPoints($userId);
    $recompensas = getRecompensas();
    
    foreach ($recompensas as $recompensa) {
        if ($userPoints >= $recompensa['puntos_requeridos']) {
            $hasReward = fetchOne(
                "SELECT id FROM usuario_recompensas WHERE usuario_id = ? AND recompensa_id = ?", 
                [$userId, $recompensa['id']]
            );
            
            if (!$hasReward) {
                execute(
                    "INSERT INTO usuario_recompensas (usuario_id, recompensa_id) VALUES (?, ?)", 
                    [$userId, $recompensa['id']]
                );
            }
        }
    }
}
```

### API de Votación (AJAX)

```php
// api/votar.php - Endpoint para votar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['propuesta_id'])) {
    $userId = $_SESSION['user_id'];
    $propuestaId = (int)$_POST['propuesta_id'];
    
    header('Content-Type: application/json');
    
    // Validar permisos
    $validation = canUserVotePropuesta($userId, $propuestaId);
    if (!$validation['canVote']) {
        echo json_encode(['success' => false, 'error' => $validation['reason']]);
        http_response_code(403);
        exit;
    }
    
    // Verificar voto previo
    $existingVote = fetchOne(
        "SELECT id FROM votos WHERE usuario_id = ? AND propuesta_id = ?", 
        [$userId, $propuestaId]
    );
    
    if ($existingVote) {
        echo json_encode(['success' => false, 'error' => 'Ya has votado']);
        http_response_code(400);
        exit;
    }
    
    // Registrar voto
    execute("INSERT INTO votos (usuario_id, propuesta_id) VALUES (?, ?)", 
            [$userId, $propuestaId]);
    
    // Otorgar puntos
    addPoints($userId, PUNTOS_VOTO, 'voto', 'Votaste en una propuesta', $propuestaId);
    
    // Actualizar contador
    execute("UPDATE propuestas SET total_votos = total_votos + 1 WHERE id = ?", [$propuestaId]);
    
    echo json_encode(['success' => true, 'message' => 'Voto registrado exitosamente']);
}
```

### Integración con API RENIEC

```php
// Consultar datos de DNI peruano
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
```

### Manejo de Archivos (Upload de Imágenes)

```php
function uploadImage($file, $subfolder = 'propuestas') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {  // 50MB máximo
        return false;
    }
    
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {  // jpg, jpeg, png, gif
        return false;
    }
    
    $uploadDir = UPLOAD_DIR . $subfolder . '/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);  // Permisos más restrictivos por seguridad
    }
    
    $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
    $targetPath = $uploadDir . $newFileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $subfolder . '/' . $newFileName;
    }
    
    return false;
}
```

---

## 🔒 Seguridad Implementada

### 1. Sanitización de Entrada
```php
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}
```

### 2. Prepared Statements (Prevención SQL Injection)
```php
// ✅ Correcto - Usando prepared statements
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE dni = ?");
$stmt->execute([$dni]);

// ❌ Incorrecto - Vulnerable a SQL injection
// $result = query("SELECT * FROM usuarios WHERE dni = '$dni'");
```

### 3. Hash de Contraseñas
```php
// Guardar contraseña
password_hash($password, PASSWORD_DEFAULT);

// Verificar contraseña
password_verify($password, $hash);
```

### 4. Configuración de Sesiones Seguras
```php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);  // Cambiar a 1 con HTTPS
```

---

## 🎮 Sistema de Gamificación

### Puntos por Acción

| Acción | Puntos |
|--------|--------|
| Votar en propuesta | +10 pts |
| Comentar | +5 pts |
| Recibir like | +2 pts |

### Niveles

| Nivel | Puntos Requeridos |
|-------|-------------------|
| Ciudadano Bronce | 50 pts |
| Ciudadano Plata | 150 pts |
| Ciudadano Oro | 300 pts |
| Líder Comunitario | 500 pts |

### Medallas e Insignias

- **Nuevo Participante**: Primera acción
- **Votante Activo**: Primer voto
- **Comentarista**: Primer comentario
- **Experto en Debate**: 50 likes recibidos

---

## 🔄 Flujo de la Aplicación

### Flujo de Registro
```
1. Usuario ingresa DNI
2. Sistema consulta API RENIEC (opcional)
3. Autocompletado de datos personales
4. Usuario selecciona municipio
5. Usuario crea contraseña
6. Sistema crea cuenta con rol 'ciudadano'
7. Se otorga medalla "Nuevo Participante"
```

### Flujo de Votación
```
1. Usuario autenticado ve propuestas de su municipio
2. Selecciona propuesta para votar
3. Sistema valida:
   - Usuario autenticado
   - Mismo municipio que la propuesta
   - No ha votado antes en esa propuesta
   - Propuesta está activa
4. Se registra el voto
5. Se otorgan 10 puntos
6. Se actualiza contador de votos
7. Se verifican logros desbloqueados
```

### Flujo de Votaciones Grupales
```
1. Admin crea votación con múltiples propuestas
2. Ciudadanos votan por UNA propuesta de la votación
3. Al finalizar fecha:
   - Sistema determina propuesta ganadora
   - Ganadora se marca como 'implementada'
   - Perdedoras se archivan para reutilización
```

---

## 📝 Conclusión

MuniOps es un sistema completo de participación ciudadana que combina:

1. **Backend robusto** en PHP con funciones modulares
2. **Base de datos remota** en Aiven Cloud con conexión SSL segura
3. **Sistema de gamificación** con puntos, niveles y recompensas
4. **Restricciones por municipio** para votaciones localizadas
5. **Panel administrativo** completo para gestión
6. **Integración con API RENIEC** para validación de identidad

El proyecto demuestra buenas prácticas como prepared statements, hash de contraseñas, y separación de responsabilidades, aunque al ser vanilla PHP no utiliza un framework MVC tradicional.
