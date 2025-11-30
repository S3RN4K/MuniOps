# 🚀 Configuración de Base de Datos Remote con Railway

## ¿Qué es Railway?

Railway es una plataforma que permite hostear bases de datos MySQL de forma **GRATUITA** en internet. Perfecta para desarrollo en equipo.

## ✅ Ya está configurado

La BD remota **ya está sincronizada** con todos tus datos. No necesitas hacer nada más. Solo necesitas cargar el archivo `.env` con las credenciales.

## 📋 El archivo `.env`

Ya existe en tu proyecto un archivo `.env` que contiene las credenciales de Railway:

```env
MYSQLHOST=nozomi.proxy.rlwy.net
MYSQLPORT=50599
MYSQLUSER=root
MYSQLPASSWORD=SbPLDWRfjsRUtVHbxRBURYqktfpCQTlo
MYSQLDATABASE=railway
```

## 🔐 Importante: El `.env` NO se sube a GitHub

El archivo `.env` está en `.gitignore`, eso significa que:
- ✅ No se sube a GitHub (protege contraseñas)
- ✅ Cada dev puede tener su propia BD remota
- ✅ Pero tu `.env` compartido permite que todos accedan a la misma BD

## 🚀 Cómo usar la BD remota

Simplemente accede a `http://localhost:3000/MuniOps/` normalmente. El código automáticamente:

1. Lee el archivo `.env`
2. Detecta que tiene variables de Railway
3. Conecta a Railway en lugar de localhost

## 📊 Verificar que está funcionando

Ejecuta este comando para verificar:

```bash
C:\xampp\php\php.exe test-railway-connection.php
```

Deberías ver:
```
✅ Usuarios: 3
✅ Municipios: 33
✅ Propuestas: 2
🎉 ¡Conexión a Railway funciona correctamente!
```

## 💡 Ventajas de esta configuración

✅ **Compartida**: Todos los devs ven los mismos datos
✅ **Remota**: La BD está en internet (no en la computadora)
✅ **Segura**: Las credenciales no se suben a GitHub
✅ **Fácil**: Funciona automáticamente sin hacer nada
✅ **Gratis**: Railway es gratuito para desarrollo

## 🔄 ¿Qué pasa si clonas el proyecto en otra computadora?

1. Clona el repositorio normalmente
2. Copia el archivo `.env` que te pase el admin
3. Colócalo en la raíz del proyecto
4. ¡Listo! Ya tendrás acceso a la misma BD remota

## ❌ Si algo no funciona

Si vez este error:

```
Error de conexión a la base de datos: ...
```

Verifica:
1. ✅ El archivo `.env` está en la carpeta raíz de MuniOps
2. ✅ Las credenciales en `.env` son correctas
3. ✅ Tu internet funciona (Railway es remoto)
4. ✅ Ejecuta `test-railway-connection.php` para diagnosticar

## 📚 Archivos de configuración

- `config/load-env.php` - Carga variables desde `.env`
- `config/database.php` - Detecta si usar local o Railway
- `.env` - Credenciales de Railway (NO publicar)
- `.env.example` - Template para otros devs

## 🎯 Próximos pasos

Ahora todos los devs pueden:
- ✅ Clonar el proyecto
- ✅ Copiar `.env` del admin
- ✅ Acceder a la misma BD remota
- ✅ Trabajar en paralelo sin conflictos de datos
