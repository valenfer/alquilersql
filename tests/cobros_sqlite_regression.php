<?php
declare(strict_types=1);
// Prueba de cobros en SQLite en memoria; no modifica la base real.
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
$db->exec("INSERT INTO usuarios (usuario, password_hash, nombre, email) VALUES ('test', 'test', 'Prueba', 'test@example.invalid')");
$db->exec("INSERT INTO habitaciones (numero, nombre) VALUES (1, 'Habitación 1')");
$db->exec("INSERT INTO inquilinos (tipo_documento, numero_documento, nombre, apellidos) VALUES ('Otro', 'TEST1', 'Persona', 'Prueba')");
$db->exec("INSERT INTO estancias (inquilino_id, habitacion_id, fecha_entrada, renta_mensual, estado) VALUES (1, 1, '2026-10-01', 350, 'activa')");
$db->exec("INSERT INTO cargos_alquiler (estancia_id, anio, mes, periodo_desde, periodo_hasta, importe_esperado, fecha_vencimiento) VALUES (1, 2026, 10, '2026-10-01', '2026-10-31', 350, '2026-10-05')");
$_SESSION = ['usuario_id' => 1, 'usuario' => 'test', 'nombre' => 'Prueba'];
$_SERVER['REQUEST_METHOD'] = 'GET';
$config = require APP_ROOT . 'config/app.php';
$controller = new App\Controllers\CobroController();
$service = new App\Services\CobroService();
App\Support\Session::flash('error_cobro', 'Mensaje de prueba', 'error');
ob_start();
$controller->nuevo($config);
$form1 = ob_get_clean();
ob_start();
$controller->nuevo($config);
$form2 = ob_get_clean();
if (!str_contains($form1, 'Mensaje de prueba') || !str_contains($form1, 'Persona Prueba') || !str_contains($form1, '</html>')) {
    throw new RuntimeException('El formulario de cobro no se renderizó: flash=' . (int) str_contains($form1, 'Mensaje de prueba') . ', inquilino=' . (int) str_contains($form1, 'Persona Prueba') . ', html=' . (int) str_contains($form1, '</html>'));
}
preg_match('/name="token_idempotencia"[^>]*value="([^"]+)"/', $form1, $m1);
preg_match('/name="token_idempotencia"[^>]*value="([^"]+)"/', $form2, $m2);
if (empty($m1[1]) || empty($m2[1]) || $m1[1] === $m2[1]) {
    throw new RuntimeException('Cada formulario debe tener un token de idempotencia distinto');
}
if (App\Support\Security::normalizarImporte('150.00') !== 150.0 ||
    App\Support\Security::normalizarImporte('1.234,56') !== 1234.56 ||
    App\Support\Security::normalizarImporte('150,001') !== null) {
    throw new RuntimeException('No se normalizan correctamente los importes admitidos por el formulario');
}
$datos = ['inquilino_id' => 1, 'fecha_cobro' => '2026-10-25', 'medio' => 'efectivo',
    'importe_total' => '150,00', 'token_idempotencia' => $m1[1],
    'aplicaciones' => [['cargo_alquiler_id' => 1, 'importe' => 150]]];
$resultado = $service->crearConAplicaciones($datos, 1);
if (!$resultado['exito']) {
    throw new RuntimeException('No se registró el cobro parcial: ' . implode('; ', $resultado['errores']));
}
$duplicado = $service->crearConAplicaciones($datos, 1);
if (!$duplicado['exito'] || $duplicado['id'] !== $resultado['id'] || (int) $db->query('SELECT count(*) FROM cobros')->fetchColumn() !== 1) {
    throw new RuntimeException('El reenvío del mismo formulario duplicó el cobro');
}
$cargo = $db->query('SELECT importe_cobrado, estado FROM cargos_alquiler WHERE id=1')->fetch();
if ((float) $cargo['importe_cobrado'] !== 150.0 || $cargo['estado'] !== 'parcial') {
    throw new RuntimeException('El primer pago no dejó el cargo parcial');
}
$datos['importe_total'] = '200,00';
$datos['aplicaciones'][0]['importe'] = 200;
$datos['token_idempotencia'] = $m2[1];
$segundo = $service->crearConAplicaciones($datos, 1);
if (!$segundo['exito']) {
    throw new RuntimeException('No se registró el segundo cobro: ' . implode('; ', $segundo['errores']));
}
$cargo = $db->query('SELECT importe_cobrado, estado FROM cargos_alquiler WHERE id=1')->fetch();
if ((float) $cargo['importe_cobrado'] !== 350.0 || $cargo['estado'] !== 'pagado' || (int) $db->query('SELECT count(*) FROM aplicaciones_cobro')->fetchColumn() !== 2) {
    throw new RuntimeException('Los cobros no liquidaron el cargo');
}
$_GET['id'] = (string) $segundo['id'];
App\Support\Session::flash('cobro_registrado', 'Cobro registrado correctamente', 'exito');
ob_start();
$controller->ver($config);
$detalle = ob_get_clean();
ob_start();
$controller->listar($config);
$listado = ob_get_clean();
if (!str_contains($detalle, '200,00') || !str_contains($detalle, 'Cobro registrado correctamente') || !str_contains($listado, 'Persona Prueba')) {
    throw new RuntimeException('Las pantallas de detalle y listado no reflejan los cobros');
}
if ($db->query('PRAGMA foreign_key_check')->fetchAll()) {
    throw new RuntimeException('Fallaron las claves foráneas');
}
echo "PASS: formulario, cobros parcial/completo, idempotencia, detalle y listado\n";
