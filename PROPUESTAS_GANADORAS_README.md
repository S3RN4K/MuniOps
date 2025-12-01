# Sistema de Propuestas Ganadoras y Seguimiento

## Cambios Implementados

### 1. Nueva Columna en Base de Datos
Se agregó la columna `es_ganadora` a la tabla `propuestas` para marcar las propuestas que ganaron votaciones.

### 2. Sistema de Seguimiento
Se creó una nueva tabla `seguimiento_propuestas` para registrar actualizaciones y progreso de las propuestas ganadoras.

### 3. Cierre Automático de Votaciones
Las votaciones ahora se finalizan automáticamente cuando pasa su fecha de fin. Esto ocurre al cargar cualquier página del sistema.

### 4. Panel de Administración Mejorado
- **Nueva pestaña "Propuestas Ganadoras"** en admin/propuestas.php
- Botón "Ver Seguimiento" para propuestas ganadoras
- Enlace "Votaciones" agregado en todos los sidebars de admin

### 5. Nueva Página de Seguimiento
**admin/seguimiento-propuesta.php**: Permite a los administradores:
- Ver todas las actualizaciones de una propuesta ganadora
- Agregar nuevas actualizaciones con título, descripción e imagen
- Editar y eliminar actualizaciones existentes
- Mantener informados a los ciudadanos sobre el progreso

### 6. Indicadores Visuales
- Badge "Ganadora" (amarillo con trofeo) en el listado público de propuestas
- Badge "Ganadora" (verde con trofeo) en el panel de administración

## Instrucciones de Instalación

### Paso 1: Ejecutar Script SQL
Abre phpMyAdmin y ejecuta el archivo:
```
database/upgrade-propuestas-ganadoras.sql
```

Este script:
- Agrega la columna `es_ganadora` a la tabla `propuestas`
- Crea la tabla `seguimiento_propuestas`
- Marca como ganadoras las propuestas que ya ganaron votaciones finalizadas

### Paso 2: Verificar Carpeta de Imágenes
La carpeta `uploads/seguimientos/` ya fue creada automáticamente.
Asegúrate de que tenga permisos de escritura (777 en Linux/Mac).

### Paso 3: Probar el Sistema

1. **Probar cierre automático:**
   - Crea una votación con fecha de fin en el pasado
   - Recarga cualquier página
   - Verifica que la votación se finalizó automáticamente

2. **Ver propuestas ganadoras:**
   - Ve a Admin → Propuestas
   - Haz clic en la pestaña "Propuestas Ganadoras"
   - Deberías ver las propuestas que ganaron votaciones

3. **Agregar seguimiento:**
   - En la lista de propuestas ganadoras, haz clic en "Ver Seguimiento"
   - Haz clic en "Nueva Actualización"
   - Completa el formulario con título, descripción e imagen
   - Guarda y verifica que aparezca en la lista

4. **Verificar badge público:**
   - Ve a la página pública de Propuestas
   - Las propuestas ganadoras deben mostrar el badge amarillo "Ganadora"

## Funciones Nuevas en includes/functions.php

### Votaciones
- `checkAndFinalizarVotaciones()` - Finaliza automáticamente votaciones vencidas
- `getPropuestasGanadoras($municipioId = null)` - Obtiene todas las propuestas ganadoras

### Seguimiento
- `createSeguimiento($propuestaId, $titulo, $descripcion, $imagen, $creadoPor)` - Crea actualización
- `getSeguimientosByPropuesta($propuestaId)` - Obtiene seguimientos de una propuesta
- `getSeguimientoById($id)` - Obtiene un seguimiento específico
- `updateSeguimiento($id, $titulo, $descripcion, $imagen = null)` - Actualiza seguimiento
- `deleteSeguimiento($id)` - Elimina seguimiento

## Modificaciones en finalizarVotacion()

La función ahora:
1. Marca `es_ganadora = 1` en la tabla `propuestas` para la propuesta ganadora
2. Mantiene toda la funcionalidad anterior (archivado de perdedoras, cambio de estado, etc.)

## Flujo Completo

1. **Admin crea votación** con 2-3 propuestas
2. **Ciudadanos votan** en la votación
3. **Sistema finaliza automáticamente** la votación al llegar la fecha fin
4. **Propuesta ganadora** se marca con `es_ganadora = 1` y estado `implementada`
5. **Admin puede ver** la propuesta en "Propuestas Ganadoras"
6. **Admin agrega seguimientos** con fotos y actualizaciones del progreso
7. **Ciudadanos pueden ver** el badge "Ganadora" en el listado público

## Archivos Modificados

- `database/upgrade-propuestas-ganadoras.sql` (NUEVO)
- `admin/seguimiento-propuesta.php` (NUEVO)
- `includes/functions.php` (modificado - agregadas funciones)
- `includes/header.php` (modificado - check automático de votaciones)
- `admin/propuestas.php` (modificado - tabs y sidebar)
- `admin/usuarios.php` (modificado - sidebar)
- `admin/reportes.php` (modificado - sidebar)
- `propuestas.php` (modificado - badge ganadora)
- `uploads/seguimientos/` (NUEVO directorio)

## Notas Importantes

- El cierre automático se ejecuta en cada carga de página, pero solo procesa votaciones vencidas
- Las propuestas perdedoras se archivan automáticamente y pueden reutilizarse
- Los seguimientos permiten subir imágenes en formato JPG, PNG, GIF (máx. 5MB)
- Solo propuestas con `es_ganadora = 1` pueden tener seguimientos
