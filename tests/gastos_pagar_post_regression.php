<?php
declare(strict_types=1);
// POST de pago adicional, aislado en SQLite en memoria.
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
$db->exec("INSERT INTO gastos (categoria_id,descripcion,importe,fecha_gasto) VALUES (1,'Gasto prueba',90.50,'2026-10-25')");
$_SESSION = ['usuario_id' => 1, 'usuario' => 'test', 'nombre' => 'Propietario', 'csrf_token' => 'test-csrf'];
$_POST = ['csrf_token' => 'test-csrf', 'gasto_id' => '1', 'importe' => '0,50', 'fecha_pago' => '2026-10-25', 'medio' => 'bizum'];
register_shutdown_function(static function () use ($db): void {
    $pago = $db->query('SELECT importe FROM pagos_gasto')->fetchColumn();
    if ((float) $pago !== 0.50) {
        throw new RuntimeException('El POST de pago adicional perdió los céntimos: ' . var_export($pago, true));
    }
    echo "PASS: POST de pago adicional de 0,50 €\n";
});
(new App\Controllers\GastoController())->procesarPagar(require APP_ROOT . 'config/app.php');
