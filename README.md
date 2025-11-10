# MuniOps - Plataforma de Participación Ciudadana Gamificada

![MuniOps](https://img.shields.io/badge/Version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange.svg)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple.svg)

## 📋 Descripción

**MuniOps** es una plataforma de participación ciudadana gamificada que permite a las municipalidades presentar propuestas y a los ciudadanos votar, comentar y debatir sobre las iniciativas de su comunidad. El sistema incluye un sistema de puntos y recompensas para incentivar la participación activa.

## ✨ Características Principales

### 🔐 Sistema de Autenticación
- Registro e inicio de sesión con DNI
- Integración con API de RENIEC para validación de identidad
- Un usuario por DNI (previene duplicados)
- Roles: Ciudadanos y Administradores

### 🗳️ Sistema de Propuestas y Votación
- Visualización de propuestas activas
- Sistema de votación (1 voto por usuario por propuesta)
- Categorización de propuestas (Infraestructura, Salud, Educación, etc.)
- Filtrado por categoría y estado
- Contador de votos en tiempo real
- Fecha de inicio y cierre de votaciones

### 💬 Módulo de Comentarios y Debates
- Comentarios en propuestas
- Sistema de respuestas (hilos de conversación)
- Me gusta en comentarios
- Visualización en tiempo real

### 🏆 Sistema de Puntos y Recompensas
- Puntos por votar (+10 pts)
- Puntos por comentar (+5 pts)
- Puntos por recibir likes (+2 pts)
- Ranking de usuarios más activos
- Medallas, niveles e insignias desbloqueables
- Visualización de progreso

### 👨‍💼 Panel de Administración
- Dashboard con estadísticas
- Gestión completa de propuestas (CRUD)
- Gestión de usuarios (activar/desactivar, cambiar roles)
- Reportes y analíticas
- Control de estados de propuestas

## 🛠️ Tecnologías Utilizadas

- **Backend:** PHP 7.4+ (Vanilla PHP, sin framework)
- **Base de Datos:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, Bootstrap 5.3
- **JavaScript:** jQuery + Vanilla JS
- **Iconos:** Bootstrap Icons
- **Fuentes:** Google Fonts (Poppins)

## 📦 Requisitos del Sistema

- PHP >= 7.4
- MySQL >= 5.7 o MariaDB >= 10.3
- Apache/Nginx con mod_rewrite
- XAMPP, WAMP, LAMP o similar
- cURL habilitado en PHP (para API DNI)

## 🚀 Instalación

### 1. Clonar o descargar el proyecto

```bash
# Si tienes Git instalado
git clone https://github.com/S3RN4K/MuniOps.git

# O descargar el ZIP y extraer en c:\xampp\htdocs\MuniOps
```

### 2. Crear la base de datos

1. Abre **phpMyAdmin** (http://localhost/phpmyadmin)
2. Crea una nueva base de datos llamada `muniops`
3. Importa el archivo SQL:
   - Ve a la pestaña "Importar"
   - Selecciona el archivo `database/muniops.sql`
   - Haz clic en "Continuar"

### 3. Configurar la conexión a la base de datos

Edita el archivo `config/database.php` si es necesario (por defecto ya está configurado para XAMPP):

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'muniops');
```

### 4. Configurar la API de DNI

Edita el archivo `config/config.php` y reemplaza el token de la API DNI:

```php
define('API_DNI_TOKEN', 'TU_TOKEN_AQUI');
```

**Obtener Token de API DNI:**
- Visita: https://apisperu.com/ o https://apis.net.pe/
- Regístrate y obtén tu token gratuito
- El sistema funcionará sin esto, pero no podrá autocompletar datos del DNI

### 5. Configurar permisos (Linux/Mac)

```bash
chmod -R 755 /ruta/a/MuniOps
chmod -R 777 /ruta/a/MuniOps/uploads
```

### 6. Acceder al sistema

- **URL Principal:** http://localhost/MuniOps/
- **Usuario Admin por defecto:**
  - DNI: `12345678`
  - Contraseña: `admin123`

⚠️ **IMPORTANTE:** Cambia la contraseña del administrador después del primer acceso.

## 📁 Estructura del Proyecto

```
MuniOps/
│
├── admin/                      # Panel de administración
│   ├── dashboard.php          # Dashboard principal
│   ├── propuestas.php         # Gestión de propuestas
│   ├── usuarios.php           # Gestión de usuarios
│   └── reportes.php           # Reportes y estadísticas
│
├── assets/                    # Recursos estáticos
│   ├── css/
│   │   └── style.css         # Estilos personalizados
│   └── js/
│       └── main.js           # JavaScript principal
│
├── config/                    # Configuración
│   ├── config.php            # Configuración general
│   └── database.php          # Conexión a BD
│
├── database/                  # Base de datos
│   └── muniops.sql           # Script SQL de instalación
│
├── includes/                  # Archivos incluibles
│   ├── functions.php         # Funciones del sistema
│   ├── header.php            # Encabezado
│   └── footer.php            # Pie de página
│
├── uploads/                   # Archivos subidos
│   └── propuestas/           # Imágenes de propuestas
│
├── index.php                  # Página principal
├── login.php                  # Inicio de sesión
├── registro.php               # Registro de usuarios
├── propuestas.php             # Lista de propuestas
├── propuesta-detalle.php      # Detalle de propuesta
├── ranking.php                # Ranking de usuarios
├── recompensas.php            # Logros y recompensas
├── perfil.php                 # Perfil de usuario
├── mis-votos.php              # Historial de votos
├── logout.php                 # Cerrar sesión
└── README.md                  # Este archivo
```

## 📖 Guía de Uso

### Para Ciudadanos

1. **Registrarse**
   - Ingresa tu DNI
   - Completa el formulario (la API autocompletará tus datos)
   - Crea una contraseña segura

2. **Ver Propuestas**
   - Explora las propuestas activas
   - Filtra por categoría
   - Lee descripciones completas

3. **Votar**
   - Selecciona una propuesta
   - Haz clic en "Votar Ahora"
   - Gana 10 puntos por tu voto

4. **Comentar**
   - Deja tu opinión en las propuestas
   - Responde a otros comentarios
   - Gana 5 puntos por comentario

5. **Ver tu Ranking**
   - Consulta tu posición en el ranking
   - Revisa tus puntos acumulados
   - Desbloquea logros y recompensas

### Para Administradores

1. **Acceder al Panel Admin**
   - Inicia sesión con cuenta de administrador
   - Ve a "Admin" en el menú

2. **Crear Propuestas**
   - Completa título y descripción
   - Selecciona categoría
   - Sube una imagen (opcional)
   - Define fechas de inicio y fin
   - Publica o guarda como borrador

3. **Gestionar Usuarios**
   - Visualiza todos los usuarios
   - Activa/desactiva/bloquea cuentas
   - Asigna roles de administrador

4. **Ver Reportes**
   - Consulta estadísticas generales
   - Revisa propuestas más votadas
   - Analiza participación por categoría

## 🎯 Sistema de Puntos

| Acción | Puntos |
|--------|--------|
| Votar en una propuesta | +10 pts |
| Comentar en una propuesta | +5 pts |
| Recibir un "Me gusta" | +2 pts |

## 🏅 Recompensas Disponibles

### Medallas
- **Nuevo Participante** - Completa tu primer acción (0 pts)
- **Votante Activo** - Vota en tu primera propuesta (10 pts)
- **Participación Perfecta** - Vota en todas las propuestas del mes (100 pts)

### Niveles
- **Ciudadano Bronce** - 50 puntos
- **Ciudadano Plata** - 150 puntos
- **Ciudadano Oro** - 300 puntos
- **Líder Comunitario** - 500 puntos

### Insignias
- **Comentarista** - Realiza tu primer comentario (5 pts)
- **Experto en Debate** - Recibe 50 likes (100 pts)

## 🔧 Configuración Avanzada

### Cambiar puntos por acción

Edita `config/config.php`:

```php
define('PUNTOS_VOTO', 10);
define('PUNTOS_COMENTARIO', 5);
define('PUNTOS_LIKE_RECIBIDO', 2);
```

### Cambiar límite de propuestas activas

Modifica en la tabla `configuracion`:

```sql
UPDATE configuracion 
SET valor = '5' 
WHERE clave = 'max_propuestas_activas';
```

### Personalizar estilos

Edita `assets/css/style.css` para cambiar colores, fuentes y diseño.

## 🐛 Solución de Problemas

### Error de conexión a la base de datos
- Verifica que MySQL esté corriendo
- Confirma credenciales en `config/database.php`
- Asegúrate de haber importado el archivo SQL

### Las imágenes no se suben
- Verifica permisos de la carpeta `uploads/`
- Aumenta `upload_max_filesize` en `php.ini`
- Verifica `MAX_FILE_SIZE` en `config/config.php`

### La API de DNI no funciona
- Verifica tu token en `config/config.php`
- Confirma que cURL esté habilitado
- El sistema funciona sin API, solo no autocompletará datos

### Los puntos no se otorgan
- Revisa la tabla `historial_puntos`
- Verifica que las transacciones no fallen
- Consulta logs de PHP para errores

## 📞 Soporte

Para reportar problemas o sugerencias, visita:
- Issues: https://github.com/S3RN4K/MuniOps/issues

## 📄 Licencia

Este proyecto fue desarrollado para fines educativos y de demostración.

## 🔄 Actualizaciones Futuras

- [ ] Sistema de notificaciones por email
- [ ] Integración con redes sociales
- [ ] App móvil
- [ ] Sistema de encuestas
- [ ] Chat en vivo entre usuarios
- [ ] Módulo de seguimiento de propuestas implementadas
- [ ] Dashboard con gráficos interactivos
- [ ] Exportación de reportes a PDF/Excel

---

**¡Gracias por usar MuniOps! 🎉**

*Construyendo comunidades más participativas y conectadas.*
