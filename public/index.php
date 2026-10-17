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
use App\Controllers\HabitacionController;
use App\Controllers\InquilinoController;
use App\Controllers\EstanciaController;
use App\Controllers\CargoAlquilerController;
use App\Controllers\CobroController;
use App\Controllers\FacturaSuministroController;
use App\Controllers\RepartoController;
use App\Controllers\EntregaFacturaController;
use App\Controllers\ComprobacionFacturaController;
use App\Controllers\FianzaController;
use App\Controllers\LlaveController;
use App\Controllers\GastoController;
use App\Controllers\IncidenciaController;
use App\Controllers\DashboardController;
use App\Controllers\ExportController;
use App\Controllers\InformeAnualController;
use App\Controllers\LiquidacionSalidaController;
use App\Controllers\HabitacionController;
use App\Controllers\InquilinoController;
use App\Controllers\EstanciaController;

// Configurar base de datos
Database::configurar($dbConfig);

// Iniciar sesión
Session::iniciar($config);

// Determinar ruta (primer segmento antes de '/' o '?')
$rutaCompleta = $_GET['r'] ?? 'login';
// Separar ruta base de parámetros adicionales (ej. "habitaciones/editar" o "habitaciones")
$partesRuta = explode('/', $rutaCompleta);
$ruta = $partesRuta[0];
$subRuta = $partesRuta[1] ?? null;
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
    case 'habitaciones':
        $controlador = new HabitacionController();
        switch ($subRuta) {
            case null:
            case '':
                $accion = 'listar';
                break;
            case 'nuevo':
                $accion = 'nuevo';
                break;
            case 'crear':
                $accion = 'crear';
                break;
            case 'editar':
                $accion = 'editar';
                break;
            case 'actualizar':
                $accion = 'actualizar';
                break;
            case 'eliminar':
                $accion = 'eliminar';
                break;
            default:
                $accion = 'listar';
        }
        break;
    case 'inquilinos':
        $controlador = new InquilinoController();
        switch ($subRuta) {
            case null:
            case '':
                $accion = 'listar';
                break;
            case 'nuevo':
                $accion = 'nuevo';
                break;
            case 'crear':
                $accion = 'crear';
                break;
            case 'editar':
                $accion = 'editar';
                break;
            case 'actualizar':
                $accion = 'actualizar';
                break;
            case 'archivar':
                $accion = 'archivar';
                break;
            default:
                $accion = 'listar';
        }
        break;
    case 'estancias':
        $controlador = new EstanciaController();
        switch ($subRuta) {
            case null:
            case '':
                $accion = 'listar';
                break;
            case 'nuevo':
                $accion = 'nuevo';
                break;
            case 'crear':
                $accion = 'crear';
                break;
            case 'editar':
                $accion = 'editar';
                break;
            case 'actualizar':
                $accion = 'actualizar';
                break;
            case 'finalizar':
                $accion = 'finalizar';
                break;
            default:
                $accion = 'listar';
        }
        break;
    case 'cargos':
        $controlador = new CargoAlquilerController();
        switch ($subRuta) {
            case null:
            case '':
                $accion = 'listar';
                break;
            case 'preparar':
                if (isset($_GET['preview']) || $_SERVER['REQUEST_METHOD'] === 'POST') {
                    $accion = 'preview';
                } else {
                    $accion = 'preparar';
                }
                break;
            case 'preview':
                $accion = 'preview';
                break;
            case 'guardar':
                $accion = 'guardar';
                break;
            case 'ver':
                $accion = 'ver';
                break;
            default:
                $accion = 'listar';
        }
        break;
    case 'cobros':
        $controlador = new CobroController();
        switch ($subRuta) {
            case null:
            case '':
                $accion = 'listar';
                break;
            case 'nuevo':
                $accion = 'nuevo';
                break;
            case 'crear':
                $accion = 'crear';
                break;
            case 'ver':
                $accion = 'ver';
                break;
            case 'cargos-pendientes':
                $accion = 'cargosPendientes';
                break;
            default:
                $accion = 'listar';
        }
        break;
    case 'facturas':
        $controlador = new FacturaSuministroController();
        switch ($subRuta) {
            case null:
            case '':
                $accion = 'listar';
                break;
            case 'nuevo':
                $accion = 'nuevo';
                break;
            case 'crear':
                $accion = 'crear';
                break;
            case 'editar':
                $accion = 'editar';
                break;
            case 'actualizar':
                $accion = 'actualizar';
                break;
            case 'anular':
                $accion = 'anular';
                break;
            case 'ver':
                $accion = 'ver';
                break;
            default:
                $accion = 'listar';
        }
        break;
    case 'repartos':
        $controlador = new RepartoController();
        switch ($subRuta) {
            case null:
            case '':
                $accion = 'nuevo';
                break;
            case 'nuevo':
                $accion = 'nuevo';
                break;
            case 'crear':
                $accion = 'crear';
                break;
            default:
                $accion = 'nuevo';
        }
        break;
    case 'entregas':
        $controlador = new EntregaFacturaController();
        switch ($subRuta) {
            case null:
            case '':
                $accion = 'nuevo';
                break;
            case 'nuevo':
                $accion = 'nuevo';
                break;
            case 'crear':
                $accion = 'crear';
                break;
            case 'todos':
                $accion = 'todos';
                break;
            case 'crear-todos':
                $accion = 'crearTodos';
                break;
            default:
                $accion = 'nuevo';
        }
        break;
    case 'comprobacion':
        $controlador = new ComprobacionFacturaController();
        switch ($subRuta) {
            case null:
            case '':
                $accion = 'ver';
                break;
            case 'ver':
                $accion = 'ver';
                break;
            default:
                $accion = 'ver';
        }
        break;
    case 'fianzas':
        $controlador = new FianzaController();
        switch ($subRuta) {
            case null:
            case '':
                $accion = 'listar';
                break;
            case 'listar':
                $accion = 'listar';
                break;
            case 'recibir':
                $accion = 'recibir';
                break;
            case 'crear':
                $accion = 'crear';
                break;
            case 'devolver':
                $accion = 'devolver';
                break;
            case 'procesar-devolver':
                $accion = 'procesarDevolver';
                break;
            case 'aplicar':
                $accion = 'aplicar';
                break;
            case 'procesar-aplicar':
                $accion = 'procesarAplicar';
                break;
            case 'ver':
                $accion = 'ver';
                break;
            default:
                $accion = 'listar';
        }
        break;
    case 'llaves':
        $controlador = new LlaveController();
        switch ($subRuta) {
            case null:
            case '':
                $accion = 'listar';
                break;
            case 'listar':
                $accion = 'listar';
                break;
            case 'estancia':
                $accion = 'estancia';
                break;
            case 'entregar':
                $accion = 'entregar';
                break;
            case 'procesar-entregar':
                $accion = 'procesarEntregar';
                break;
            case 'devolver':
                $accion = 'devolver';
                break;
            case 'procesar-devolver':
                $accion = 'procesarDevolver';
                break;
            case 'perder':
                $accion = 'perder';
                break;
            case 'procesar-perder':
                $accion = 'procesarPerder';
                break;
            case 'reponer':
                $accion = 'reponer';
                break;
            case 'procesar-reponer':
                $accion = 'procesarReponer';
                break;
            default:
                $accion = 'listar';
        }
        break;
    case 'gastos':
        $controlador = new GastoController();
        switch ($subRuta) {
            case null:
            case '':
                $accion = 'listar';
                break;
            case 'listar':
                $accion = 'listar';
                break;
            case 'nuevo':
                $accion = 'nuevo';
                break;
            case 'crear':
                $accion = 'crear';
                break;
            case 'ver':
                $accion = 'ver';
                break;
            case 'pagar':
                $accion = 'pagar';
                break;
            case 'procesar-pagar':
                $accion = 'procesarPagar';
                break;
            default:
                $accion = 'listar';
        }
        break;
    case 'incidencias':
        $controlador = new IncidenciaController();
        switch ($subRuta) {
            case null:
            case '':
                $accion = 'listar';
                break;
            case 'listar':
                $accion = 'listar';
                break;
            case 'nuevo':
                $accion = 'nuevo';
                break;
            case 'crear':
                $accion = 'crear';
                break;
            case 'estado':
                $accion = 'estado';
                break;
            case 'procesar-estado':
                $accion = 'procesarEstado';
                break;
            default:
                $accion = 'listar';
        }
        break;
    case 'dashboard':
        $controlador = new DashboardController();
        $accion = 'index';
        break;
    case 'exportar':
        $controlador = new ExportController();
        $tipo = $subRuta ?? '';
        $controlador->exportar($tipo, $config);
        exit;
    case 'informes':
        $controlador = new InformeAnualController();
        switch ($subRuta) {
            case null:
            case '':
                $accion = 'seleccionarAnio';
                break;
            case 'anual':
                if (isset($_GET['generar']) || !empty($_GET['anio'])) {
                    $accion = 'generar';
                } else {
                    $accion = 'seleccionarAnio';
                }
                break;
            default:
                $accion = 'seleccionarAnio';
        }
        break;
    case 'liquidacion':
        $controlador = new LiquidacionSalidaController();
        switch ($subRuta) {
            case null:
            case '':
                $accion = 'estancia';
                break;
            case 'estancia':
                $accion = 'estancia';
                break;
            default:
                $accion = 'estancia';
        }
        break;
    default:
        // Redirigir al dashboard
        header('Location: ' . $config['url_base'] . '/index.php?r=dashboard');
        exit;
}

if ($controlador && $accion) {
    $controlador->$accion($config);
}
