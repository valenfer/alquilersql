<?php
declare(strict_types=1);

/**
 * Controlador AuthController — Autenticación del propietario.
 *
 * Responsabilidad:
 *  - Mostrar formulario de inicio de sesión.
 *  - Procesar login y logout.
 *  - Mostrar y procesar cambio de contraseña.
 *
 * Flujo:
 *  - GET /login → formulario de login
 *  - POST /login → validar CSRF, autenticar, redirigir al dashboard
 *  - GET /logout → cerrar sesión, redirigir al login
 *  - GET /password → formulario de cambio de contraseña
 *  - POST /password → validar CSRF, cambiar contraseña, redirigir al dashboard
 *
 * Seguridad:
 *  - CSRF obligatorio en POST.
 *  - Solo accesible si no está autenticado (login) o si está autenticado (password).
 *  - Mensajes genéricos de error.
 */

namespace App\Controllers;

use App\Support\Auth;
use App\Support\Session;
use App\Support\Security;

class AuthController
{
    /**
     * Muestra el formulario de inicio de sesión.
     *
     * @param array $config Configuración de la aplicación
     * @return void
     */
    public function login(array $config): void
    {
        // Si ya está autenticado, redirigir al dashboard
        if (Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=dashboard');
            exit;
        }

        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('auth/login.php', [
            'titulo' => 'Iniciar sesión',
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa el inicio de sesión.
     *
     * @param array $config Configuración de la aplicación
     * @return void
     */
    public function doLogin(array $config): void
    {
        // Verificar CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validarCsrf($token)) {
            Session::flash('csrf_error', 'Token de seguridad inválido. Vuelva a intentar.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $usuario = trim($_POST['usuario'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $resultado = Auth::autenticar($usuario, $password, $ip);

        if ($resultado['exito']) {
            Session::flash('bienvenido', 'Bienvenido, ' . Security::html($resultado['usuario']['nombre']) . '.', 'exito');
            header('Location: ' . $config['url_base'] . '/index.php?r=dashboard');
        } else {
            Session::flash('error_login', $resultado['error'], 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
        }

        exit;
    }

    /**
     * Cierra la sesión y redirige al login.
     *
     * @param array $config Configuración de la aplicación
     * @return void
     */
    public function logout(array $config): void
    {
        Auth::cerrarSesion();
        Session::flash('sesion_cerrada', 'Sesión cerrada correctamente.', 'info');
        header('Location: ' . $config['url_base'] . '/index.php?r=login');
        exit;
    }

    /**
     * Muestra el formulario de cambio de contraseña.
     *
     * @param array $config Configuración de la aplicación
     * @return void
     */
    public function password(array $config): void
    {
        if (!Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('auth/password.php', [
            'titulo' => 'Cambiar contraseña',
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa el cambio de contraseña.
     *
     * @param array $config Configuración de la aplicación
     * @return void
     */
    public function doPassword(array $config): void
    {
        if (!Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        // Verificar CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validarCsrf($token)) {
            Session::flash('csrf_error', 'Token de seguridad inválido.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=password');
            exit;
        }

        $usuario = Auth::usuario();
        $passwordActual = $_POST['password_actual'] ?? '';
        $passwordNueva = $_POST['password_nueva'] ?? '';
        $passwordConfirmar = $_POST['password_confirmar'] ?? '';

        // Validar contraseña actual
        $pdo = \App\Support\Database::obtenerConexion();
        $stmt = $pdo->prepare('SELECT password_hash FROM usuarios WHERE id = :id');
        $stmt->execute([':id' => $usuario['id']]);
        $fila = $stmt->fetch();

        if (!$fila || !password_verify($passwordActual, $fila['password_hash'])) {
            Session::flash('error_password', 'La contraseña actual es incorrecta.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=password');
            exit;
        }

        // Validar nueva contraseña
        if ($passwordNueva !== $passwordConfirmar) {
            Session::flash('error_password', 'La nueva contraseña y su confirmación no coinciden.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=password');
            exit;
        }

        if (strlen($passwordNueva) < 8) {
            Session::flash('error_password', 'La contraseña debe tener al menos 8 caracteres.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=password');
            exit;
        }

        // Cambiar contraseña
        if (Auth::cambiarContrasena($usuario['id'], $passwordNueva)) {
            Session::flash('password_cambiada', 'Contraseña cambiada correctamente.', 'exito');
        } else {
            Session::flash('error_password', 'No se pudo cambiar la contraseña. Intente de nuevo.', 'error');
        }

        header('Location: ' . $config['url_base'] . '/index.php?r=dashboard');
        exit;
    }

    /**
     * Renderiza una vista con los datos proporcionados.
     *
     * @param string $vista Ruta relativa de la vista dentro de app/Views
     * @param array $datos Datos para la vista
     * @return void
     */
    private function render(string $vista, array $datos): void
    {
        extract($datos);

        $rutaVista = __DIR__ . '/../Views/' . $vista;
        $rutaLayout = __DIR__ . '/../Views/layout.php';

        if (!file_exists($rutaVista)) {
            http_response_code(404);
            echo 'Vista no encontrada: ' . Security::html($vista);
            return;
        }

        // Incluir el layout que a su vez incluirá la vista
        $contenido = '';
        ob_start();
        require $rutaVista;
        $contenido = ob_get_clean();

        $titulo = $titulo ?? 'Alquiler';
        $flash = $flash ?? [];
        $csrf = $csrf ?? '';
        $config = $config ?? [];

        require $rutaLayout;
    }
}
