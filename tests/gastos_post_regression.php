<?php
declare(strict_types=1);
// Prueba POST real del controlador; SQLite en memoria sobrevive al exit en shutdown.
define('APP_ROOT', dirname(__DIR__) . '/');
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        require APP_ROOT . 'app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    }
});
$configDb = require APP_ROOT . 'config/database.php';
$configDb['ruta'] = ':memory:';
App\Support\Database::configurar($configDb);
$db = App\Support\Database::obtenerConexion();
$db->exec(file_get_contents(APP_ROOT . 'database/001_schema.sql'));
$db->exec("INSERT INTO usuarios (usuario,password_hash,nombre,email) VALUES ('test','hash','Propietario','test@example.invalid')");
$db->exec("INSERT INTO categorias_gasto (nombre) VALUES ('Prueba')");
$_SESSION = ['usuario_id' => 1, 'usuario' => 'test', 'nombre' => 'Propietario', 'csrf_token' => 'test-csrf'];
$_POST = ['csrf_token' => 'test-csrf', 'categoria_id' => '1', 'descripcion' => 'Gasto con céntimos',
    'importe' => '90,50', 'fecha_gasto' => '2026-10-25', 'relacion_habitacion' => '', 'es_general' => '1',
    'pago_importe' => ['0,50'], 'pago_fecha' => ['2026-10-25'], 'pago_medio' => ['efectivo']];
register_shutdown_function(static function () use ($db): void {
    $importe = $db->query('SELECT importe FROM gastos')->fetchColumn();
    $pago = $db->query('SELECT importe FROM pagos_gasto')->fetchColumn();
    if ((float) $importe !== 90.50 || (float) $pago !== 0.50) {
        throw new RuntimeException('El POST del formulario no conservó los céntimos: gasto=' . var_export($importe, true) . ', pago=' . var_export($pago, true));
    }
    echo "PASS: POST de gasto con pago de 0,50 €\n";
});
(new App\Controllers\GastoController())->crear(require APP_ROOT . 'config/app.php');
