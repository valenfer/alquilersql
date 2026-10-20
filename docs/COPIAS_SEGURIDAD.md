# Copias de Seguridad y Restauración

Este documento describe cómo realizar copias de seguridad y restaurar la
aplicación, tanto en XAMPP como en un servidor habitual.

---

## 1. Qué incluye una copia de seguridad

Una copia completa contiene:

1. **Base de datos MySQL**: todas las tablas, datos y esquema.
2. **Adjuntos**: archivos subidos (contratos, facturas, etc.) en
   `storage/attachments/`.
3. **Configuración**: `config/database.php` y `config/app.php` (sin secretos,
   usar los archivos `.example` como referencia).
4. **Logs**: `storage/logs/` (opcional, para depuración).

---

## 2. Copia de seguridad de la base de datos (MySQL)

### Usando mysqldump (línea de comandos)

```bash
mysqldump -u alquiler_app -p alquiler > copia_alquiler_$(date +%Y%m%d_%H%M%S).sql
```

### Usando phpMyAdmin

1. Abrir phpMyAdmin.
2. Seleccionar la base de datos `alquiler`.
3. Ir a "Exportar".
4. Seleccionar formato SQL y ejecutar.

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
restaurar copias de seguridad. Realice las copias con `mysqldump` y una copia
de la carpeta de adjuntos siguiendo los pasos anteriores. Guarde también la
configuración necesaria en un lugar seguro y separado del repositorio.

---

## 5. Restauración de una copia

### Paso 1: Restaurar la base de datos

```bash
mysql -u alquiler_app -p alquiler < copia_alquiler_YYYYMMDD_HHMMSS.sql
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

1. Copiar `config/database.example.php` a `config/database.php` y editar con
   los datos de la nueva base de datos.
2. Copiar `config/app.example.php` a `config/app.php` si es necesario.
3. Verificar que las carpetas `storage/` tengan permisos de escritura.

### Paso 4: Probar la aplicación

Acceder a la aplicación y verificar que:
- Se puede iniciar sesión.
- Los datos están completos.
- Los adjuntos son accesibles.

---

## 6. Seguridad en las copias

- Las copias se almacenan en `storage/backups/` (fuera de la raíz pública).
- No se dejan copias descargables en una carpeta pública.
- El acceso a las copias requiere autenticación de propietario.
- La restauración pide confirmación porque sustituye datos.
- Antes de restaurar, la aplicación ofrece crear una copia previa de seguridad.

---

## 7. Procedimiento recomendado en XAMPP

1. Detener Apache y MySQL (opcional, para consistencia).
2. Ejecutar `mysqldump` para exportar la base de datos.
3. Copiar la carpeta `storage/attachments`.
4. Copiar `config/` (los archivos `.php` con credenciales).
5. Guardar todo en una ubicación externa (USB, disco externo, nube).
6. Reiniciar los servicios.

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
