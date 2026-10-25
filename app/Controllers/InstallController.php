<?php
declare(strict_types=1);

/**
 * Controlador InstallController — Asistente de instalación.
 *
 * Responsabilidad:
 *  - Detectar si existe un propietario registrado.
 *  - Si no existe, mostrar el formulario para crear la primera cuenta.
 *  - Crear el usuario propietario en la base de datos.
 *  - Redirigir al login tras la instalación.
 *
 * Seguridad:
 *  - Solo accesible si no hay usuarios en la tabla usuarios.
 *  - CSRF obligatorio en el POST de creación.
 *  - Contraseña hasheada con password_hash.
 */

namespace App\Controllers;

use App\Support\Session;

class InstallController
{
    /**
     * Ejecuta el asistente de instalación.
     *
     * @param array $config Configuración de la aplicación
     * @return void
     */
    public function instalar(array $config): void
    {
        $pdo = \App\Support\Database::obtenerConexion();

        // Verificar si ya existe un usuario propietario
        $stmt = $pdo->query('SELECT COUNT(*) AS total FROM usuarios');
        $total = (int) $stmt->fetch()['total'];

        if ($total > 0) {
            // Ya está instalado, redirigir al login
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->crearPropietario($config);
            exit;
        }

        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('install/instalar.php', [
            'titulo' => 'Instalación — Crear cuenta de propietario',
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Crea el primer usuario propietario.
     *
     * @param array $config Configuración de la aplicación
     * @return void
     */
    private function crearPropietario(array $config): void
    {
        // Verificar CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validarCsrf($token)) {
            Session::flash('csrf_error', 'Token de seguridad inválido.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=install');
            exit;
        }

        $usuario = trim($_POST['usuario'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirmar = $_POST['password_confirmar'] ?? '';

        // Validaciones
        $errores = [];

        if ($usuario === '') {
            $errores[] = 'El nombre de usuario es obligatorio.';
        } elseif (strlen($usuario) < 3) {
            $errores[] = 'El nombre de usuario debe tener al menos 3 caracteres.';
        }

        if ($nombre === '') {
            $errores[] = 'El nombre completo es obligatorio.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo electrónico no es válido.';
        }

        if ($password === '') {
            $errores[] = 'La contraseña es obligatoria.';
        } elseif (strlen($password) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        if ($password !== $passwordConfirmar) {
            $errores[] = 'La contraseña y su confirmación no coinciden.';
        }

        // Verificar que el usuario no exista
        $pdo = \App\Support\Database::obtenerConexion();
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = :usuario LIMIT 1');
        $stmt->execute([':usuario' => $usuario]);
        if ($stmt->fetch()) {
            $errores[] = 'El nombre de usuario ya está registrado.';
        }

        if (!empty($errores)) {
            Session::flash('error_instalacion', implode(' ', $errores), 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=install');
            exit;
        }

        // Crear el usuario
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO usuarios (usuario, password_hash, nombre, email, activo, fecha_creacion)
             VALUES (:usuario, :hash, :nombre, :email, 1, datetime('now'))"
        );

        if ($stmt->execute([
            ':usuario' => $usuario,
            ':hash' => $hash,
            ':nombre' => $nombre,
            ':email' => $email,
        ])) {
            Session::flash('instalacion_completada', 'Cuenta de propietario creada correctamente. Puede iniciar sesión.', 'exito');
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
        } else {
            Session::flash('error_instalacion', 'Error al crear la cuenta. Intente de nuevo.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=install');
        }

        exit;
    }

    /**
     * Renderiza una vista de instalación.
     *
     * @param string $vista Ruta relativa de la vista
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

        ob_start();
        require $rutaVista;
        $contenido = ob_get_clean();

        $titulo = $titulo ?? 'Instalación';
        $flash = $flash ?? [];
        $csrf = $csrf ?? '';
        $config = $config ?? '';

        require $rutaLayout;
    }
}