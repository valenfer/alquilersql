# Alquiler — Gestión de piso alquilado por habitaciones

Aplicación web completa para que un propietario gestione un único piso con
cuatro habitaciones alquiladas por habitaciones. Construida con **PHP puro**
(sin frameworks), **MySQL** (InnoDB, `utf8mb4`), **HTML5/CSS/JS propios** y
**PDO** con consultas preparadas.

Diseñada para una persona mayor con pocos conocimientos de informática: letra
grande (18 px), botones amplios, mensajes en español de España, euros y fechas
en formato `dd/mm/aaaa`.

## Requisitos

- Servidor web con PHP 8.0+ y MySQL/MariaDB (probado con XAMPP).
- Extensiones PHP: `pdo`, `pdo_mysql`, `mbstring`, `gd` (opcional, para adjuntos de imagen).
- MySQL con motor InnoDB.
- Navegador moderno.

## Instalación paso a paso

### 1. Desplegar los archivos

Copiar todo el contenido del proyecto en la carpeta pública del servidor web,
por ejemplo `C:\xampp\htdocs\alquiler\`.

Solo `public/` debe ser accesible desde la web. Las carpetas `config/`,
`app/`, `database/`, `storage/` y `scripts/` no deben estar expuestas.

### 2. Configurar la base de datos

1. Abrir phpMyAdmin o la consola de MySQL.
2. Crear una base de datos, por ejemplo `alquiler`, con codificación
   `utf8mb4` y cotejamiento `utf8mb4_spanish_ci`.
3. Crear un usuario de ejecución con permisos sobre esa base de datos:

```sql
CREATE DATABASE IF NOT EXISTS alquiler CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
CREATE USER IF NOT EXISTS 'alquiler_app'@'localhost' IDENTIFIED BY 'cambiar_esta_contraseña';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER, DROP, REFERENCES ON alquiler.* TO 'alquiler_app'@'localhost';
FLUSH PRIVILEGES;
```

4. Copiar `config/database.example.php` a `config/database.php` y editar los
   datos de conexión (host, nombre de base de datos, usuario, contraseña).

### 3. Ejecutar el esquema

Importar el esquema completo desde la consola de MySQL o phpMyAdmin:

```bash
mysql -u alquiler_app -p alquiler < database/001_schema.sql
mysql -u alquiler_app -p alquiler < database/002_seed_base.sql
```

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
