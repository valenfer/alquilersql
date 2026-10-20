<?php
declare(strict_types=1);

/**
 * Clase Auth — Autenticación del propietario.
 *
 * Responsabilidad:
 *  - Verificar credenciales de inicio de sesión (usuario + contraseña).
 *  - Crear y destruir sesiones de forma segura.
 *  - Cambiar contraseña del propietario.
 *  - Controlar intentos fallidos y bloqueo temporal.
 *
 * Seguridad:
 *  - Contraseñas con password_hash / password_verify (bcrypt).
 *  - Regeneración del ID de sesión al autenticar (session_regenerate_id(true)).
 *  - Cookies HttpOnly y SameSite=Lax; Secure si HTTPS.
 *  - Mensajes genéricos que no revelan si el usuario existe.
 *  - Bloqueo tras intentos fallidos para evitar fuerza bruta.
 */

namespace App\Support;

use App\Support\Database;

class Auth
{
    private const TOKEN_LOGIN = 'alquiler_intentos_login';
    private const TOKEN_BLOQUEO = 'alquiler_bloqueo_hasta';

    /**
     * Intenta autenticar al propietario.
     *
     * @param string $usuario Nombre de usuario
     * @param string $password Contraseña en texto plano
     * @param string $ip Dirección IP del cliente (para auditoría y bloqueo)
     * @return array{exito: bool, usuario: array|null, error: string|null}
     */
    public static function autenticar(string $usuario, string $password, string $ip): array
    {
        // Verificar bloqueo por intentos fallidos
        $bloqueo = self::obtenerBloqueo();
        if ($bloqueo !== null && time() < $bloqueo) {
            $restantes = (int) ceil(($bloqueo - time()) / 60);
            return [
                'exito' => false,
                'usuario' => null,
                'error' => 'Demasiados intentos fallidos. Intente de nuevo en ' . $restantes . ' minuto(s).',
            ];
        }

        // Buscar usuario
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT id, usuario, password_hash, nombre, email, activo FROM usuarios WHERE usuario = :usuario LIMIT 1'
        );
        $stmt->execute([':usuario' => $usuario]);
        $fila = $stmt->fetch();

        // Mensaje genérico si no existe o la contraseña no coincide
        if (!$fila || !password_verify($password, $fila['password_hash'])) {
            self::registrarIntentoFallido();
            return [
                'exito' => false,
                'usuario' => null,
                'error' => 'Usuario o contraseña incorrectos.',
            ];
        }

        if (!$fila['activo']) {
            return [
                'exito' => false,
                'usuario' => null,
                'error' => 'Cuenta desactivada. Contacte con el administrador.',
            ];
        }

        // Éxito: limpiar intentos fallidos y crear sesión
        self::limpiarIntentosFallidos();
        self::iniciarSesion((int) $fila['id'], $usuario, $fila['nombre'], $fila['email'], $ip);

        return [
            'exito' => true,
            'usuario' => [
                'id' => (int) $fila['id'],
                'usuario' => $fila['usuario'],
                'nombre' => $fila['nombre'],
                'email' => $fila['email'],
            ],
            'error' => null,
        ];
    }

    /**
     * Cierra la sesión del usuario actual.
     */
    public static function cerrarSesion(): void
    {
        $_SESSION = [];
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        session_destroy();
    }

    /**
     * Verifica si hay una sesión activa.
     *
     * @return bool
     */
    public static function estaAutenticado(): bool
    {
        return !empty($_SESSION['usuario_id']) && !empty($_SESSION['usuario']);
    }

    /**
     * Devuelve los datos del usuario en sesión.
     *
     * @return array|null
     */
    public static function usuario(): ?array
    {
        if (!self::estaAutenticado()) {
            return null;
        }

        return [
            'id' => (int) $_SESSION['usuario_id'],
            'usuario' => $_SESSION['usuario'],
            'nombre' => $_SESSION['nombre'] ?? '',
            'email' => $_SESSION['email'] ?? '',
        ];
    }

    /**
     * Cambia la contraseña del usuario autenticado.
     *
     * @param int $usuarioId ID del usuario
     * @param string $passwordNueva Contraseña en texto plano
     * @return bool
     */
    public static function cambiarContrasena(int $usuarioId, string $passwordNueva): bool
    {
        $hash = password_hash($passwordNueva, PASSWORD_DEFAULT);
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'UPDATE usuarios SET password_hash = :hash WHERE id = :id AND activo = 1'
        );
        return $stmt->execute([':hash' => $hash, ':id' => $usuarioId]);
    }

    /**
     * Verifica si el usuario está bloqueado por intentos fallidos.
     *
     * @return int|null Timestamp de fin del bloqueo o null si no está bloqueado
     */
    private static function obtenerBloqueo(): ?int
    {
        return $_SESSION[self::TOKEN_BLOQUEO] ?? null;
    }

    /**
     * Registra un intento fallido y bloquea si se supera el límite.
     */
    private static function registrarIntentoFallido(): void
    {
        $config = require __DIR__ . '/../../config/app.php';
        $maxIntentos = $config['max_intentos_login'] ?? 5;
        $tiempoBloqueo = $config['tiempo_bloqueo_login'] ?? 300;

        $intentos = ($_SESSION[self::TOKEN_LOGIN] ?? 0) + 1;
        $_SESSION[self::TOKEN_LOGIN] = $intentos;

        if ($intentos >= $maxIntentos) {
            $_SESSION[self::TOKEN_BLOQUEO] = time() + $tiempoBloqueo;
            $_SESSION[self::TOKEN_LOGIN] = 0;
        }
    }

    /**
     * Limpia los intentos fallidos tras un login exitoso.
     */
    private static function limpiarIntentosFallidos(): void
    {
        $_SESSION[self::TOKEN_LOGIN] = 0;
        $_SESSION[self::TOKEN_BLOQUEO] = null;
    }

    /**
     * Inicia la sesión de forma segura.
     *
     * @param int $id ID del usuario
     * @param string $usuario Nombre de usuario
     * @param string $nombre Nombre completo
     * @param string $email Correo electrónico
     * @param string $ip Dirección IP
     */
    private static function iniciarSesion(int $id, string $usuario, string $nombre, string $email, string $ip): void
    {
        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $id;
        $_SESSION['usuario'] = $usuario;
        $_SESSION['nombre'] = $nombre;
        $_SESSION['email'] = $email;
        $_SESSION['ip'] = $ip;
        $_SESSION['inicio_sesion'] = time();
        $_SESSION['ultima_actividad'] = time();

        // Session::iniciar configura las opciones de cookie antes de session_start().
        // session_regenerate_id() vuelve a emitir la cookie con esas opciones.
    }
}
