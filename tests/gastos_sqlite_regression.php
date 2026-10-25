<?php
declare(strict_types=1);
// Gasto y pago completos en SQLite en memoria; no se toca la base real.
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
$db->exec("INSERT INTO categorias_gasto (nombre) VALUES ('Reparación')");
$db->exec("INSERT INTO habitaciones (numero,nombre) VALUES (1,'Habitación 1')");
$_SESSION = ['usuario_id' => 1, 'usuario' => 'test', 'nombre' => 'Propietario'];
$_SERVER['REQUEST_METHOD'] = 'GET';
$config = require APP_ROOT . 'config/app.php';
$service = new App\Services\GastoService();
$controller = new App\Controllers\GastoController();
App\Support\Session::flash('error_gasto', 'Fallo simulado', 'error');
ob_start();
$controller->nuevo($config);
$form = ob_get_clean();
if (!str_contains($form, 'Fallo simulado') || !str_contains($form, 'Reparación') || !str_contains($form, 'pago_importe[]') || !str_contains($form, '</html>')) {
    throw new RuntimeException('No se muestra completo el formulario nuevo con aviso y pagos');
}
$base = ['categoria_id' => 1, 'descripcion' => 'Caldera prueba', 'importe' => '90,50',
    'fecha_gasto' => '2026-10-25', 'relacion_habitacion' => '', 'es_general' => '1', 'pagos' => []];
$sinPago = $service->crearConPagos($base, 1);
if (!$sinPago['exito']) {
    throw new RuntimeException('Alta sin pago: ' . implode('; ', $sinPago['errores']));
}
$gasto = $service->obtener((int) $sinPago['id']);
if ((float) $gasto['importe'] !== 90.50 || (float) $gasto['total_pagado'] !== 0.0 ||
    (float) $gasto['saldo_pendiente_pago'] !== 90.50 || $gasto['relacion_habitacion'] !== null || !$gasto['fecha_creacion']) {
    throw new RuntimeException('Gasto general sin pago: importe, saldo, fecha o habitación incorrectos');
}
$pagado = $base;
$pagado['descripcion'] = 'Gasto ya pagado';
$pagado['importe'] = '120,50';
$pagado['pagos'] = [
    ['importe' => '20,25', 'fecha_pago' => '2026-10-25', 'medio' => 'efectivo'],
    ['importe' => '100,25', 'fecha_pago' => '2026-10-25', 'medio' => 'transferencia'],
];
$conPago = $service->crearConPagos($pagado, 1);
if (!$conPago['exito']) {
    throw new RuntimeException('Alta con pagos: ' . implode('; ', $conPago['errores']));
}
$otro = $service->obtener((int) $conPago['id']);
if ((float) $otro['importe'] !== 120.50 || (float) $otro['total_pagado'] !== 120.50 ||
    (float) $otro['saldo_pendiente_pago'] !== 0.0 || count($otro['pagos']) !== 2 ||
    !$otro['pagos'][0]['fecha_registro']) {
    throw new RuntimeException('El gasto ya pagado no refleja ambos pagos e importes reales');
}
$exceso = $pagado;
$exceso['importe'] = '10,00';
$exceso['pagos'] = [['importe' => '20,00', 'fecha_pago' => '2026-10-25', 'medio' => 'efectivo']];
$rechazado = $service->crearConPagos($exceso, 1);
if ($rechazado['exito'] || (int) $db->query('SELECT count(*) FROM gastos')->fetchColumn() !== 2) {
    throw new RuntimeException('Un pago superior al gasto creó datos incoherentes');
}
$listadoDatos = $service->listar();
if (count($listadoDatos) !== 2 || (float) $listadoDatos[0]['total_pagado'] !== 120.50 ||
    (float) $listadoDatos[0]['saldo_pendiente_pago'] !== 0.0) {
    throw new RuntimeException('El listado no calcula correctamente pagos y pendiente');
}
$_GET['id'] = (string) $conPago['id'];
App\Support\Session::flash('gasto_creado', 'Gasto de prueba registrado', 'exito');
ob_start();
$controller->ver($config);
$detalle = ob_get_clean();
App\Support\Session::flash('gasto_creado', 'Listado de prueba', 'exito');
ob_start();
$controller->listar($config);
$listado = ob_get_clean();
$_GET['id'] = (string) $sinPago['id'];
App\Support\Session::flash('error_gasto', 'Pago inválido', 'error');
ob_start();
$controller->pagar($config);
$pagoForm = ob_get_clean();
if (!str_contains($detalle, 'Gasto de prueba registrado') || !str_contains($detalle, '120,50') ||
    !str_contains($listado, 'Gasto ya pagado') || !str_contains($listado, 'Listado de prueba') ||
    !str_contains($pagoForm, '90,50') || !str_contains($pagoForm, 'Pago inválido') ||
    !str_contains($pagoForm, '</html>')) {
    throw new RuntimeException('Detalle, listado o formulario de pago incompletos');
}
$extra = $service->registrarPago((int) $sinPago['id'], 90.50, '2026-10-25', 'bizum', null, null, 1);
if (!$extra['exito'] || (float) $service->obtener((int) $sinPago['id'])['saldo_pendiente_pago'] !== 0.0) {
    throw new RuntimeException('El pago posterior no liquida el gasto');
}
if ($db->query('PRAGMA foreign_key_check')->fetchAll() || $db->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
    throw new RuntimeException('Fallo de integridad de SQLite');
}
echo "PASS: gasto sin pago, gasto ya pagado, listado, detalle y pago posterior\n";
