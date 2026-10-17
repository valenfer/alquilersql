<?php
declare(strict_types=1);

/**
 * Punto de entrada único de la aplicación (Front Controller).
 *
 * Responsabilidad:
 *  - Cargar configuración (config/app.php y config/database.php).
 *  - Iniciar sesión y base de datos.
 *  - Enrutar la petición al controlador y acción adecuados según el parámetro 'r'.
 *  - Manejo de errores y excepciones.
 *
 * Rutas soportadas en esta fase:
 *  - r=login       → AuthController::login
 *  - r=doLogin     → AuthController::doLogin
 *  - r=logout      → AuthController::logout
 *  - r=password    → AuthController::password
 *  - r=doPassword  → AuthController::doPassword
 *  - r=install     → InstallController::instalar
 *  - r=doInstall   → InstallController::instalar (POST)
 *  - r=dashboard   → Dashboard (pendiente de implementar en fases futuras)
 *
 * Seguridad:
 *  - Solo public/ es accesible desde la web.
 *  - Configuración fuera de la raíz pública.
 *  - CSRF en operaciones de cambio.
 */

// Evitar acceso directo si se copia fuera de public/
if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__ . '/../');
}

// Cargar configuración de la aplicación
$configAppPath = APP_ROOT . 'config/app.php';
$configDbPath = APP_ROOT . 'config/database.php';

if (!file_exists($configAppPath)) {
    $configAppPath = APP_ROOT . 'config/app.example.php';
}
if (!file_exists($configDbPath)) {
    $configDbPath = APP_ROOT . 'config/database.example.php';
}

$config = require $configAppPath;
$dbConfig = require $configDbPath;

// Aplicar zona horaria
date_default_timezone_set($config['zona_horaria'] ?? 'Europe/Madrid');

// Iniciar sesión
require APP_ROOT . 'vendor/autoload.php'; // No hay autoload aún, cargamos manualmente

// Cargar clases de soporte manualmente (sin Composer)
spl_autoload_register(function ($clase) {
    $prefijo = 'App\\';
    if (strpos($clase, $prefijo) === 0) {
        $rutaClase = APP_ROOT . str_replace('\\', DIRECTORY_SEPARATOR, substr($clase, strlen($prefijo))) . '.php';
        if (file_exists($rutaClase)) {
            require $rutaClase;
        }
    }
});

use App\Support\Database;
use App\Support\Session;
use App\Support\Security;
use App\Controllers\AuthController;
use App\Controllers\InstallController;

// Configurar base de datos
Database::configurar($dbConfig);

// Iniciar sesión
Session::iniciar($config);

// Determinar ruta
$ruta = $_GET['r'] ?? 'login';
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Si no hay usuarios, redirigir a instalación (excepto rutas de instalación y assets)
$pdo = Database::obtenerConexion();
$stmt = $pdo->query('SELECT COUNT(*) AS total FROM usuarios');
$totalUsuarios = (int) $stmt->fetch()['total'];

$esRutaInstalacion = in_array($ruta, ['install', 'doInstall'], true);

if ($totalUsuarios === 0 && !$esRutaInstalacion) {
    header('Location: ' . $config['url_base'] . '/index.php?r=install');
    exit;
}

// Enrutar
$controlador = null;
$accion = null;

switch ($ruta) {
    case 'login':
        $controlador = new AuthController();
        $accion = 'login';
        break;
    case 'doLogin':
        $controlador = new AuthController();
        $accion = 'doLogin';
        break;
    case 'logout':
        $controlador = new AuthController();
        $accion = 'logout';
        break;
    case 'password':
        $controlador = new AuthController();
        $accion = 'password';
        break;
    case 'doPassword':
        $controlador = new AuthController();
        $accion = 'doPassword';
        break;
    case 'install':
    case 'doInstall':
        $controlador = new InstallController();
        $accion = 'instalar';
        break;
    case 'dashboard':
    default:
        // Dashboard temporal (fase futura)
        if (!Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }
        $contenido = '<div class="tarjeta-dashboard"><h2>Dashboard</h2><p>El dashboard se implementará en la fase 6.</p><a href="?r=logout" class="btn-salir">Cerrar sesión</a></div>';
        require APP_ROOT . 'app/Views/layout.php';
        exit;
}

if ($controlador && $accion) {
    $controlador->$accion($config);
}
