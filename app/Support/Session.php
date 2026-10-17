<?php
declare(strict_types=1);

/**
 * Clase Session — Gestión de sesiones seguras.
 *
 * Responsabilidad:
 *  - Iniciar sesión con nombre personalizado y cookies seguras.
 *  - Verificar caducidad y actividad.
 *  - Proporcionar utilidades para mensajes flash (confirmaciones, errores).
 */

namespace App\Support;

class Session
{
    /**
     * Inicia la sesión si no está iniciada.
     *
     * @param array $config Configuración de la aplicación
     */
    public static function iniciar(array $config): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $nombre = $config['sesion_nombre'] ?? 'alquiler_sesion';
            $ttl = (int) ($config['sesion_ttl'] ?? 14400);

            session_name($nombre);
            ini_set('session.gc_maxlifetime', (string) $ttl);
            ini_set('session.cookie_lifetime', (string) $ttl);
            ini_set('session.use_strict_mode', '1');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Lax');

            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', '1');
            }

            session_start();
        }

        self::verificarCaducidad($ttl);
    }

    /**
     * Verifica si la sesión ha caducado por inactividad.
     *
     * @param int $ttl Tiempo de vida en segundos
     */
    private static function verificarCaducidad(int $ttl): void
    {
        if (empty($_SESSION['ultima_actividad'])) {
            $_SESSION['ultima_actividad'] = time();
            return;
        }

        if (time() - $_SESSION['ultima_actividad'] > $ttl) {
            session_destroy();
            session_start();
            $_SESSION['ultima_actividad'] = time();
        }

        $_SESSION['ultima_actividad'] = time();
    }

    /**
     * Establece un mensaje flash (se muestra una vez y se borra).
     *
     * @param string $clave Clave del mensaje
     * @param string $valor Texto del mensaje
     * @param string $tipo Tipo: 'exito', 'error', 'advertencia', 'info'
     */
    public static function flash(string $clave, string $valor, string $tipo = 'info'): void
    {
        $_SESSION['flash'][$clave] = ['valor' => $valor, 'tipo' => $tipo];
    }

    /**
     * Obtiene y elimina un mensaje flash.
     *
     * @param string $clave Clave del mensaje
     * @return array|null ['valor' => string, 'tipo' => string]
     */
    public static function consumirFlash(string $clave): ?array
    {
        if (!isset($_SESSION['flash'][$clave])) {
            return null;
        }

        $valor = $_SESSION['flash'][$clave];
        unset($_SESSION['flash'][$clave]);
        return $valor;
    }

    /**
     * Devuelve todos los mensajes flash pendientes y los limpia.
     *
     * @return array
     */
    public static function consumirTodos(): array
    {
        $flashes = $_SESSION['flash'] ?? [];
        $_SESSION['flash'] = [];
        return $flashes;
    }

    /**
     * Verifica si existe un token CSRF válido en sesión.
     *
     * @return bool
     */
    public static function tieneCsrf(): bool
    {
        return !empty($_SESSION['csrf_token']);
    }

    /**
     * Genera o devuelve el token CSRF de la sesión.
     *
     * @return string
     */
    public static function tokenCsrf(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Valida un token CSRF enviado desde el formulario.
     *
     * @param string $tokenEnviado Token recibido del cliente
     * @return bool
     */
    public static function validarCsrf(string $tokenEnviado): bool
    {
        $tokenSesion = $_SESSION['csrf_token'] ?? '';
        if (empty($tokenSesion) || empty($tokenEnviado)) {
            return false;
        }

        return hash_equals($tokenSesion, $tokenEnviado);
    }

    /**
     * Renueva el token CSRF (tras operaciones sensibles).
     */
    public static function renovarCsrf(): void
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
