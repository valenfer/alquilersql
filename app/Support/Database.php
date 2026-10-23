<?php
declare(strict_types=1);

/**
 * Clase Database — Gestión de la conexión PDO a SQLite.
 *
 * Responsabilidad:
 *  - Establecer y mantener una única conexión PDO (patrón Singleton ligero).
 *  - Configurar UTF-8, modo de errores por excepción y claves foráneas.
 *  - Proporcionar acceso a la conexión para consultas preparadas.
 *
 * Reglas de seguridad:
 *  - Todas las consultas deben usar consultas preparadas (no concatenar valores en SQL).
 *  - Las credenciales no requieren host/usuario/contraseña (SQLite local).
 *  - No guarda secretos en auditoría ni en logs.
 */

namespace App\Support;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $conexion = null;
    private static array $config = [];

    /**
     * Establece la configuración de conexión.
     *
     * @param array $config Claves: driver, ruta, options
     */
    public static function configurar(array $config): void
    {
        self::$config = $config;
    }

    /**
     * Devuelve la conexión PDO, creándola si es necesario.
     *
     * @return PDO Conexión activa a SQLite
     * @throws PDOException Si no se puede conectar
     */
    public static function obtenerConexion(): PDO
    {
        if (self::$conexion === null) {
            $cfg = self::$config;

            // DSN para SQLite: ruta absoluta al archivo de base de datos
            $dsn = sprintf(
                'sqlite:%s',
                $cfg['ruta']
            );

            // Crear el directorio si no existe
            $directorio = dirname($cfg['ruta']);
            if (!is_dir($directorio)) {
                mkdir($directorio, 0755, true);
            }

            self::$conexion = new PDO($dsn, null, null, $cfg['options']);

            // Activar claves foráneas en SQLite
            self::$conexion->exec('PRAGMA foreign_keys = ON');

            // Configurar zona horaria para fechas de negocio (Europe/Madrid)
            // SQLite no tiene funciones de fecha built-in con zona horaria,
            // así que usamos PHP date_default_timezone_set
            date_default_timezone_set('Europe/Madrid');

            // Configurar modo de resultados de fecha/hora
            self::$conexion->exec('PRAGMA date_format = "%Y-%m-%d %H:%M:%S"');
        }

        return self::$conexion;
    }

    /**
     * Cierra la conexión (útil en pruebas).
     */
    public static function cerrar(): void
    {
        self::$conexion = null;
    }
}
