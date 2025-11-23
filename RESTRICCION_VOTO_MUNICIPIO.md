# Restricción de Voto por Municipio - MuniOps

## Cambios Implementados

### 1. **Base de Datos**
- ✅ Creada tabla `municipios` con lista de 40+ municipios del Perú
- ✅ Agregada columna `municipio_id` a tabla `usuarios`
- ✅ Agregada columna `municipio_id` a tabla `propuestas`
- ✅ Creadas relaciones (Foreign Keys) con tabla municipios

**Script ejecutado:** `database/upgrade-municipios.sql`

### 2. **Backend - Funciones Nuevas** (`includes/functions.php`)

```php
// Obtener todos los municipios
getAllMunicipios()

// Validar que usuario puede votar en una propuesta (mismo municipio)
canUserVotePropuesta($userId, $propuestaId)
// Retorna: ['canVote' => true/false, 'reason' => mensaje]

// Filtrar propuestas activas solo del municipio del usuario
getActivePropuestasByUserMunicipio($userId, $limit = null)

// Obtener estadísticas de municipios
getMunicipiosWithStats()
```

### 3. **Registro de Usuarios**
- ✅ Agregar campo `municipio_id` a función `createUser()`
- ✅ Campo select de municipio en formulario de registro
- ✅ Municipio es campo obligatorio
- ✅ Validación en backend

**Archivo:** `registro.php`

### 4. **Sistema de Votación**
- ✅ Nueva API: `api/votar.php`
- ✅ Valida que usuario y propuesta sean del MISMO municipio
- ✅ Si no son del mismo municipio → RECHAZA el voto
- ✅ Mensaje de error claro al usuario

**Validación:**
```
Usuario municipio_id = Propuesta municipio_id ✓ Puede votar
Usuario municipio_id ≠ Propuesta municipio_id ✗ No puede votar
```

### 5. **Flujo de Usuario**

```
1. Usuario se registra
   ↓
2. Selecciona su municipio
   ↓
3. Se guarda en tabla usuarios.municipio_id
   ↓
4. Usuario intenta votar propuesta
   ↓
5. Sistema valida: ¿Mismo municipio?
   ├─ SÍ → Voto registrado ✓
   └─ NO → Voto rechazado ✗
```

## Próximos Pasos (Opcional)

1. **Agregar municipio a propuestas existentes:**
   ```sql
   UPDATE propuestas SET municipio_id = 1 WHERE id > 0;
   ```

2. **Filtrar propuestas en lista:**
   - Usar `getActivePropuestasByUserMunicipio()` en lugar de `getActivePropuestas()`

3. **Panel de municipios:**
   - Vista de propuestas agrupadas por municipio
   - Estadísticas por municipio

## Ejemplos de Código

### Registrar usuario con municipio:
```php
$userData = [
    'dni' => '12345678',
    'nombres' => 'Juan',
    'apellido_paterno' => 'Pérez',
    'apellido_materno' => 'García',
    'email' => 'juan@email.com',
    'telefono' => '987654321',
    'municipio_id' => 1,  // ID del municipio
    'password' => 'password123'
];
createUser($userData);
```

### Validar voto:
```php
$validation = canUserVotePropuesta($userId, $propuestaId);

if ($validation['canVote']) {
    // Proceder con el voto
} else {
    // Mostrar error
    echo $validation['reason'];
}
```

### Obtener propuestas del municipio del usuario:
```php
$propuestas = getActivePropuestasByUserMunicipio($userId);
// Solo retorna propuestas del mismo municipio
```

## Municipios Disponibles

Se insertaron 40+ municipios principales:
- **Lima:** Lima, San Isidro, Miraflores, Surco, La Molina, Barranco, San Borja, Magdalena, Callao
- **Arequipa:** Arequipa, Yanahuara, Cayma
- **Cusco:** Cusco, Wanchaq
- **Trujillo, Piura, Chiclayo, Iquitos, Pucallpa, Puerto Maldonado, Juliaca, Tacna, Ayacucho, Huancayo, Cerro de Pasco, Huacho, Ica, Moquegua**

Puedes agregar más ejecutando:
```sql
INSERT INTO municipios (nombre, departamento, provincia) VALUES ('Nombre', 'Departamento', 'Provincia');
```

## Prueba

1. Accede a: `http://localhost:3000/MuniOps/registro.php`
2. Rellena el formulario y selecciona un municipio
3. Regístrate
4. Intenta votar una propuesta de otro municipio → **Será rechazado** ✓
5. Intenta votar una propuesta de tu municipio → **Será aceptado** ✓
