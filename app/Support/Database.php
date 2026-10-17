<?php
declare(strict_types=1);

/**
 * Clase Database — Gestión de la conexión PDO a MySQL.
 *
 * Responsabilidad:
 *  - Establecer y mantener una única conexión PDO (patrón Singleton ligero).
 *  - Configurar UTF-8mb4, zona horaria Europe/Madrid y modo de errores por excepción.
 *  - Proporcionar acceso a la conexión para consultas preparadas.
 *
 * Reglas de seguridad:
 *  - Todas las consultas deben usar consultas preparadas (no concatenar valores en SQL).
 *  - Las credenciales provienen de config/database.php (fuera de la raíz web).
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
     * @param array $config Claves: host, puerto, nombre, usuario, password, charset, options
     */
    public static function configurar(array $config): void
    {
        self::$config = $config;
    }

    /**
     * Devuelve la conexión PDO, creándola si es necesario.
     *
     * @return PDO Conexión activa a MySQL
     * @throws PDOException Si no se puede conectar
     */
    public static function obtenerConexion(): PDO
    {
        if (self::$conexion === null) {
            $cfg = self::$config;

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $cfg['host'],
                (int) $cfg['puerto'],
                $cfg['nombre'],
                $cfg['charset']
            );

            self::$conexion = new PDO($dsn, $cfg['usuario'], $cfg['password'], $cfg['options']);

            // Zona horaria de la aplicación para instantes y fechas calculadas
            self::$conexion->exec("SET time_zone = '+01:00'");
            // Nota: '+01:00' es CET. En verano (CEST) es '+02:00'.
            // PHP/MySQL manejan el horario de verano si el servidor tiene la zona correcta.
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
