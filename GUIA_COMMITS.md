# Guía de Commits para MuniOps

## ✅ ARCHIVOS QUE SÍ SE DEBEN PUBLICAR (Cambios de Municipios)

Estos archivos contienen la funcionalidad core de restricción de votos por municipio:

1. **includes/functions.php**
   - Nuevas funciones: `getAllMunicipios()`, `getMunicipioById()`, `canUserVotePropuesta()`, `getActivePropuestasByUserMunicipio()`, `getMunicipiosWithStats()`
   - Cambios en `createUser()` y `updateUser()` para incluir `municipio_id`
   - Cambios en `createPropuesta()` y `updatePropuesta()` para incluir `municipio_id`

2. **database/upgrade-municipios.sql**
   - Creación de tabla `municipios`
   - Adición de columna `municipio_id` a `usuarios` y `propuestas`
   - Inserción de 33 municipios del Perú

3. **database/recrear-vista.sql**
   - Recreación de vista `propuestas_estadisticas` con soporte para `municipio_id`

4. **registro.php**
   - Agregación de campo select de municipio obligatorio
   - Validación de `municipio_id` en registro

5. **propuestas.php**
   - Filtrado automático de propuestas por municipio del usuario
   - Indicador visual del municipio actual

6. **propuesta-detalle.php**
   - Validación de municipio antes de votar
   - Mensaje de error si intenta votar propuesta de otro municipio

7. **admin/propuestas.php**
   - Campo select de municipio en creación/edición de propuestas
   - Guardado de `municipio_id` con cada propuesta

8. **RESTRICCION_VOTO_MUNICIPIO.md**
   - Documentación de la funcionalidad

9. **api/votar.php**
   - API con validación de municipio para votación

## ❌ ARCHIVOS QUE NO SE DEBEN PUBLICAR (Cambios Personales)

Estos archivos están en `.gitignore` porque contienen configuraciones personales del servidor:

1. **config/config.php**
   - Contiene: `BASE_URL` con puerto 3000
   - Contiene: Token de API DNI personal
   - Tu compañero tiene sus propias configuraciones

2. **config/database.php**
   - Contiene: Host, usuario, contraseña de BD
   - Cambios de conexión (socket Unix vs TCP)

3. **Archivos de Prueba Personales**
   - test-api-dni.php
   - diagnostico-admin.php
   - verificar-municipios.php
   - import-db.php

4. **database/muniops.sql**
   - El archivo SQL completo con datos (admin insertado manualmente)
   - Solo comparte el script `upgrade-municipios.sql`

5. **Configuración de Apache**
   - Cambios de puerto 80 → 3000 en `httpd.conf`

## 📋 Checklist para Publicar

Antes de hacer `git push`:

```bash
# 1. Ver qué va a subirse
git status

# 2. Agregar solo los archivos de municipios
git add includes/functions.php
git add database/upgrade-municipios.sql
git add database/recrear-vista.sql
git add registro.php
git add propuestas.php
git add propuesta-detalle.php
git add admin/propuestas.php
git add api/votar.php
git add RESTRICCION_VOTO_MUNICIPIO.md
git add .gitignore

# 3. Verificar que NO estén:
# - config/config.php
# - config/database.php
# - test-api-dni.php
# - diagnostico-admin.php

# 4. Commit
git commit -m "Feat: Agregar restricción de votos por municipio"

# 5. Push
git push origin main
```

## 🔄 Instrucciones para tu Compañero

Después de hacer pull de los cambios:

1. **Ejecutar upgrade de BD:**
   ```bash
   mysql -u root muniops < database/upgrade-municipios.sql
   mysql -u root muniops < database/recrear-vista.sql
   ```

2. **Configurar su propio `config/config.php`:**
   - Usa el puerto que él usa (probablemente 80)
   - Su token de API DNI
   - Sus datos de BD

3. **Crear usuario admin (si no existe):**
   ```bash
   mysql -u root muniops -e "INSERT INTO usuarios (dni, nombres, apellido_paterno, apellido_materno, email, municipio_id, password, rol, estado) VALUES ('12345678', 'Administrador', 'Municipal', 'Sistema', 'admin@muniops.gob.pe', 1, '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'activo');"
   ```

4. **Asignar municipios a propuestas existentes:**
   ```bash
   mysql -u root muniops -e "UPDATE propuestas SET municipio_id = 1 WHERE municipio_id IS NULL;"
   ```

## 📝 Notas Importantes

- El archivo `.gitignore` evita que se publiquen cambios personales
- Solo los cambios de municipios se van a subir
- Tu compañero no tendrá conflictos de configuración
- Cada persona mantiene su propia `config/` local
