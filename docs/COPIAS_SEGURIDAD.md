# Copias de Seguridad y Restauración

Este documento describe cómo realizar copias de seguridad y restaurar la
aplicación, tanto en XAMPP como en un servidor habitual.

---

## 1. Qué incluye una copia de seguridad

Una copia completa contiene:

1. **Base de datos SQLite**: el archivo `storage/alquiler.sqlite` con todas las
   tablas y datos.
2. **Adjuntos**: archivos subidos (contratos, facturas, etc.) en
   `storage/attachments/`.
3. **Configuración**: `config/database.php` y `config/app.php` (sin secretos,
   usar los archivos `.example` como referencia).
4. **Logs**: `storage/logs/` (opcional, para depuración).

---

## 2. Copia de seguridad de la base de datos (SQLite)

### Usando el administrador de archivos o línea de comandos

```bash
# En Windows (copia el archivo)
copy storage\alquiler.sqlite "C:\copias\alquiler_$(date +%Y%m%d_%H%M%S).sqlite"

# En Linux/macOS
cp storage/alquiler.sqlite /ruta/de/copia/alquiler_$(date +%Y%m%d_%H%M%S).sqlite
```

### Características de SQLite

- La base de datos es un solo archivo, por lo que no requiere `mysqldump` ni
  herramientas externas.
- Para copiar la base de datos mientras la aplicación está en uso, es recomendable
  detener el servidor web o usar el modo WAL (`PRAGMA journal = WAL`) para
  consistencia.

---

## 3. Copia de seguridad de adjuntos

Los adjuntos se almacenan en `storage/attachments/` fuera de la raíz pública.
Copiar toda la carpeta:

```bash
# En XAMPP (Windows)
xcopy "C:\xampp\htdocs\alquiler\storage\attachments" "C:\copias\attachments\" /E /I

# En Linux
cp -r storage/attachments /ruta/de/copia/
```

---

## 4. Copias desde la aplicación

La aplicación no incluye actualmente una interfaz para crear, descargar o
restaurar copias de seguridad. Realice la copia del archivo `storage/alquiler.sqlite`
y una copia de la carpeta de adjuntos siguiendo los pasos anteriores. Guarde también
la configuración necesaria en un lugar seguro y separado del repositorio.

---

## 5. Restauración de una copia

### Paso 1: Restaurar la base de datos SQLite

```bash
# Copiar la copia de seguridad al lugar original
copy "C:\copias\alquiler_YYYYMMDD_HHMMSS.sqlite" storage\alquiler.sqlite
# O en Linux
cp /ruta/de/copia/alquiler_YYYYMMDD_HHMMSS.sqlite storage/alquiler.sqlite
```

### Paso 2: Restaurar los adjuntos

Copiar los archivos de la copia de seguridad a `storage/attachments/`:

```bash
# En XAMPP (Windows)
xcopy "C:\copias\attachments\*" "C:\xampp\htdocs\alquiler\storage\attachments\" /E /I

# En Linux
cp -r /ruta/de/copia/attachments/* storage/attachments/
```

### Paso 3: Verificar la configuración

1. Verificar que `config/database.php` apunte al archivo correcto (por defecto
   `storage/alquiler.sqlite`).
2. Copiar `config/app.example.php` a `config/app.php` si es necesario.
3. Verificar que las carpetas `storage/` tengan permisos de escritura.

### Paso 4: Probar la aplicación

Acceder a la aplicación y verificar que:
- Se puede iniciar sesión con el propietario.
- Los datos están completos.
- Los adjuntos son accesibles.

---

## 6. Seguridad en las copias

- Las copias se almacenan en `storage/backups/` (fuera de la raíz pública).
- No se dejen copias descargables en una carpeta pública.
- El acceso a las copias requiere autenticación de propietario.
- La restauración pide confirmación porque sustituye datos.
- Antes de restaurar, la aplicación ofrece crear una copia previa de seguridad.

---

## 7. Procedimiento recomendado en XAMPP

1. Detener Apache (opcional, para consistencia total con WAL).
2. Copiar `storage/alquiler.sqlite` a `storage/backups/alquiler_YYYYMMDD_HHMMSS.sqlite`.
3. Copiar la carpeta `storage/attachments`.
4. Guardar todo en una ubicación externa (USB, disco externo, nube).

---

## 8. Verificación de la copia

Después de restaurar en una base de prueba, verificar:

- [ ] Se puede iniciar sesión con el propietario.
- [ ] Las cuatro habitaciones existen.
- [ ] Los inquilinos y sus datos están completos.
- [ ] Las estancias, cargos y cobros están presentes.
- [ ] Las facturas y sus repartos están completos.
- [ ] Los adjuntos se pueden descargar.
- [ ] Los informes anuales se generan correctamente.

Registrar el resultado de la prueba en `docs/PRUEBAS.md`.
