# Alquiler — Gestión de piso alquilado por habitaciones

Aplicación web completa para que un propietario gestione un único piso con
cuatro habitaciones alquiladas por habitaciones. Construida con **PHP puro**
(sin frameworks), **SQLite** (PDO), **HTML5/CSS/JS propios** y
**PDO** con consultas preparadas.

Diseñada para una persona mayor con pocos conocimientos de informática: letra
grande (18 px), botones amplios, mensajes en español de España, euros y fechas
en formato `dd/mm/aaaa`.

## Requisitos

- Servidor web con PHP 8.0+ con la extensión `pdo` y `pdo_sqlite` (probado con XAMPP).
- Extensiones PHP: `pdo`, `pdo_sqlite`, `mbstring`, `gd` (opcional, para adjuntos de imagen).
- No se requiere servidor de bases de datos separado: SQLite usa un archivo local.
- Navegador moderno.

## Instalación paso a paso

### 1. Desplegar los archivos

Copiar todo el contenido del proyecto en la carpeta pública del servidor web,
por ejemplo `C:\xampp\htdocs\alquiler\`.

El archivo `.htaccess` de la raíz restringe las peticiones web a `public/` y
desactiva el listado de directorios. En Apache, habilitar `mod_rewrite` y
permitir las directivas `Options` y `FileInfo` en `.htaccess` (por ejemplo,
`AllowOverride Options FileInfo`). Si el servidor no admite `.htaccess`,
configurar el `DocumentRoot` directamente a la carpeta `public/`; no exponer
la raíz del proyecto. Las carpetas
`config/`, `app/`, `database/`, `storage/` y `scripts/` deben permanecer fuera
de la raíz web efectiva.

### 2. Configurar la base de datos SQLite

1. Verificar que la carpeta `storage/` exista y sea escribible por el usuario del servidor web.
2. El archivo de base de datos se creará automáticamente en `storage/alquiler.sqlite`.
3. Copiar `config/database.example.php` a `config/database.php` (el archivo ya está preconfigurado con la ruta por defecto).

### 3. Ejecutar el esquema

Ejecutar el esquema SQLite desde la línea de comando o la interfaz web:

```bash
php database/001_schema.sql
```

O bien, la primera vez que se accede a `http://localhost/alquiler/public/index.php`,
la aplicación detectará que la base de datos no tiene las tablas necesarias y
lanzará el asistente de instalación que creará el esquema automáticamente.

### 4. Ejecutar la instalación web

Acceder a `http://localhost/alquiler/public/index.php`. Si no existe ningún
usuario propietario, se redirige automáticamente al asistente de instalación
para crear la cuenta del propietario.

### 5. Iniciar sesión

Con las credenciales creadas en la instalación, acceder al dashboard y comenzar
a gestionar el piso.

## Estructura del proyecto

```
public/            -> Raíz web (única expuesta al navegador)
  index.php        -> Punto de entrada único
  assets/          -> CSS, JS y vendor (jQuery local)
app/
  Controllers/     -> Controladores de petición
  Services/        -> Lógica de negocio
  Repositories/    -> Acceso a datos con PDO
  Views/           -> Plantillas HTML
  Support/         -> Base de datos, autenticación, sesiones, CSRF, seguridad
config/            -> Configuración (fuera de la raíz web)
database/          -> Esquema SQL y semillas
scripts/           -> Scripts de mantenimiento y CLI
storage/           -> Adjuntos, logs y copias de seguridad
tests/             -> Pruebas funcionales (PHP puro)
docs/              -> Documentación del proyecto
```

## Seguridad

- Contraseñas con `password_hash` / `password_verify`.
- Sesiones con regeneración de ID, cookies HttpOnly y SameSite.
- CSRF en todas las operaciones de cambio.
- Consultas preparadas con PDO.
- Adjuntos fuera de la raíz pública y descargados mediante acción autenticada.
- Sin credenciales en el repositorio.

## Licencia

Uso interno. Sin telemetría ni envío de datos a terceros.
