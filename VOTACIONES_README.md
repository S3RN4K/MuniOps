# Sistema de Votaciones - MuniOps

## 📋 Descripción

Sistema de votaciones que permite a los administradores crear campañas de votación entre 2-3 propuestas, donde los ciudadanos pueden votar por su propuesta favorita. Las propuestas perdedoras se archivan automáticamente para poder ser reutilizadas en futuras votaciones.

## 🆕 Nuevas Funcionalidades

### Para Administradores

1. **Gestión de Votaciones** (`admin/votaciones.php`)
   - Crear nuevas votaciones con 2-3 propuestas
   - Seleccionar propuestas disponibles por municipio
   - Definir fechas de inicio y fin
   - Activar/Finalizar votaciones
   - Ver resultados en tiempo real

2. **Archivo de Propuestas**
   - Las propuestas perdedoras se archivan automáticamente
   - Pueden ser desarchivadas para reutilizarse
   - Historial de veces que una propuesta ha sido usada

3. **Finalización Automática**
   - Al finalizar una votación se determina la ganadora
   - La propuesta ganadora cambia a estado "implementada"
   - Las perdedoras se archivan
   - Se calculan porcentajes de votación

### Para Ciudadanos

1. **Votaciones Activas** (`votaciones.php`)
   - Ver votaciones activas de su municipio
   - Votar una sola vez por votación
   - Ver resultados después de votar
   - Ganar puntos por participar (10 puntos por voto)

2. **Visualización de Resultados**
   - Gráficos de progreso con porcentajes
   - Identificación de propuesta ganadora
   - Estadísticas de participación

## 🗄️ Estructura de Base de Datos

### Nuevas Tablas

#### `votaciones`
```sql
- id: Identificador único
- titulo: Título de la votación
- descripcion: Descripción opcional
- municipio_id: Municipio al que pertenece
- fecha_inicio/fin: Período de votación
- estado: borrador, activa, finalizada, cancelada
- propuesta_ganadora_id: Propuesta que ganó
- total_votos: Contador de votos
- creado_por: Usuario administrador
```

#### `votacion_propuestas`
```sql
- votacion_id: Referencia a votación
- propuesta_id: Referencia a propuesta
- orden: Orden de presentación
- votos_recibidos: Contador de votos
- porcentaje: Porcentaje de votos
- es_ganadora: Marca si ganó
```

#### `votacion_votos`
```sql
- votacion_id: Referencia a votación
- propuesta_id: Propuesta votada
- usuario_id: Usuario que votó
- fecha_voto: Fecha y hora del voto
- ip_address: IP del votante
```

### Modificaciones a Tablas Existentes

#### `propuestas`
```sql
- archivada: Boolean para marcar archivadas
- fecha_archivo: Fecha de archivo
- veces_usada_votacion: Contador de usos
```

## 🚀 Instalación

1. **Ejecutar Script SQL**
   ```bash
   mysql -u root muniops < database/upgrade-votaciones.sql
   ```

2. **Verificar Permisos**
   - El usuario debe tener un municipio asignado
   - Solo administradores pueden crear votaciones

3. **Configurar Puntos**
   - Por defecto: 10 puntos por voto en votación
   - Se puede modificar en la configuración

## 📖 Uso

### Crear una Votación (Admin)

1. Ir a **Admin > Votaciones**
2. Clic en "Nueva Votación"
3. Completar formulario:
   - Título descriptivo
   - Seleccionar municipio
   - Elegir 2-3 propuestas disponibles
   - Definir fechas
   - Estado inicial (borrador o activa)
4. Guardar votación

### Votar (Ciudadano)

1. Ir a **Votaciones** en el menú principal
2. Ver votaciones activas del municipio
3. Seleccionar una votación
4. Leer propuestas y detalles
5. Clic en "Votar por esta" en la propuesta elegida
6. Confirmar voto (irreversible)

### Finalizar Votación (Admin)

1. Ir a **Admin > Votaciones**
2. Localizar votación activa con fecha vencida
3. Clic en botón "Finalizar"
4. Sistema automáticamente:
   - Calcula ganadora
   - Actualiza porcentajes
   - Marca ganadora como "implementada"
   - Archiva perdedoras

### Reutilizar Propuestas (Admin)

1. Ir a **Admin > Votaciones > Archivo**
2. Ver propuestas archivadas
3. Clic en "Desarchivar" en la propuesta deseada
4. La propuesta vuelve a estar disponible
5. Puede incluirse en nueva votación

## 🔒 Restricciones

- **Un voto por votación**: Los usuarios solo pueden votar una vez
- **Municipio obligatorio**: Solo se muestran votaciones del municipio del usuario
- **Máximo 3 propuestas**: Las votaciones pueden tener 2 o 3 propuestas
- **Propuestas únicas**: Una propuesta no puede estar en múltiples votaciones activas simultáneamente
- **Voto irreversible**: No se puede cambiar el voto una vez emitido

## 📊 Reportes y Estadísticas

### Dashboard Admin
- Total de votaciones
- Votaciones activas
- Acceso rápido a crear votaciones

### Vista de Votación
- Total de votos
- Porcentaje por propuesta
- Días restantes
- Propuesta ganadora (si está finalizada)

## 🎯 Flujo de Trabajo

```
1. Admin crea votación → Borrador
2. Admin selecciona propuestas → Asocia 2-3 propuestas
3. Admin activa votación → Estado: Activa
4. Ciudadanos votan → Acumulan votos
5. Votación vence o admin finaliza → Determina ganadora
6. Ganadora → Estado: Implementada
7. Perdedoras → Archivadas (reutilizables)
```

## 🔧 Funciones Principales

### Backend (`includes/functions.php`)

- `createVotacion($data)` - Crear votación
- `addPropuestaToVotacion($votacionId, $propuestaId, $orden)` - Agregar propuesta
- `registerVotoVotacion($votacionId, $propuestaId, $usuarioId)` - Registrar voto
- `finalizarVotacion($votacionId)` - Finalizar y determinar ganadora
- `getPropuestasArchivadas($municipioId)` - Obtener archivadas
- `desarchivarPropuesta($propuestaId)` - Reutilizar propuesta

### Triggers de Base de Datos

- `after_votacion_voto_insert` - Actualiza contadores al votar
- `after_votacion_voto_delete` - Actualiza contadores al eliminar voto

## 🎨 Interfaz

- **Diseño responsive**: Compatible con móviles y tablets
- **Colores por categoría**: Identificación visual de propuestas
- **Animaciones**: Transiciones suaves en hover
- **Progreso visual**: Barras de progreso con porcentajes
- **Iconografía**: Bootstrap Icons para mejor UX

## 🔐 Seguridad

- Validación de municipio del usuario
- Verificación de una sola votación por usuario
- Protección contra votos duplicados
- Registro de IP por auditoría
- Permisos de administrador para gestión

## 📝 Notas Técnicas

- Las propuestas archivadas mantienen su historial
- Los votos se registran con timestamp e IP
- Las votaciones finalizadas no pueden editarse
- El sistema calcula porcentajes automáticamente
- Se otorgan puntos de gamificación por votar

## 🆘 Soporte

Para problemas o consultas:
1. Revisar logs de error de Apache/PHP
2. Verificar que se ejecutó el script SQL correctamente
3. Comprobar que el usuario tiene municipio asignado
4. Validar que las propuestas pertenecen al mismo municipio

## 📅 Actualizaciones Futuras

- [ ] Votaciones con múltiples rondas
- [ ] Votación por ranking (1°, 2°, 3°)
- [ ] Votaciones privadas/públicas
- [ ] Exportar resultados a PDF
- [ ] Notificaciones push para nuevas votaciones
- [ ] Integración con redes sociales para compartir
