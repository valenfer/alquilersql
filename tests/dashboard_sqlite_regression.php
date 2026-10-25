<?php
declare(strict_types=1);
// Prueba del panel sobre una réplica, nunca sobre el archivo de producción.
if (!isset($argv[1])) { throw new InvalidArgumentException('Indique ruta SQLite de ensayo'); }
define('APP_ROOT', dirname(__DIR__) . '/');
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) === 0) {
        require APP_ROOT . 'app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    }
});
$config = require APP_ROOT . 'config/database.php';
$config['ruta'] = $argv[1];
App\Support\Database::configurar($config);
$datos = (new App\Services\DashboardService())->obtenerDatos(2026, 10);
if (count($datos['habitaciones']) !== 4 || !isset($datos['indicadores'], $datos['alertas_contratos'])) {
    throw new RuntimeException('Panel incompleto');
}
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario'] = 'dueno';
$_SESSION['nombre'] = 'Propietario';
$_SESSION['email'] = 'test@example.invalid';
$_SERVER['REQUEST_METHOD'] = 'GET';
ob_start();
try {
    (new App\Controllers\DashboardController())->index(require APP_ROOT . 'config/app.php');
    $html = ob_get_clean();
} catch (Throwable $error) {
    ob_end_clean();
    throw $error;
}
if (!str_contains($html, 'Habitación 1') || !str_contains($html, '</html>')) {
    throw new RuntimeException('La página HTML del panel está incompleta');
}
echo "PASS: panel y HTML de cuatro habitaciones y alertas consultables\n";
