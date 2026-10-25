<?php
declare(strict_types=1);
// Facturas de luz/agua en SQLite en memoria: no toca los datos reales.
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
$db->exec("INSERT INTO usuarios (usuario, password_hash, nombre, email) VALUES ('test', 'test', 'Propietario', 'test@example.invalid')");
$_SESSION = ['usuario_id' => 1, 'usuario' => 'test', 'nombre' => 'Propietario'];
$_SERVER['REQUEST_METHOD'] = 'GET';
$config = require APP_ROOT . 'config/app.php';
$controller = new App\Controllers\FacturaSuministroController();
$service = new App\Services\FacturaSuministroService();
App\Support\Session::flash('error_factura', 'Mensaje de prueba', 'error');
ob_start();
$controller->nuevo($config);
$form = ob_get_clean();
if (!str_contains($form, 'Mensaje de prueba') || !str_contains($form, 'name="importe_total"') ||
    !str_contains($form, 'name="guardar_borrador"') || !str_contains($form, '</html>')) {
    throw new RuntimeException('El formulario nuevo no se renderiza completo con sus dos acciones');
}
$datos = ['tipo' => 'luz', 'proveedor' => 'Compañía prueba', 'numero_factura' => 'FAC-1',
    'fecha_emision' => '2026-10-25', 'periodo_desde' => '2026-09-01', 'periodo_hasta' => '2026-09-30',
    'importe_total' => '120,50', 'fecha_vencimiento' => '', 'estado' => 'confirmada', 'observaciones' => 'Luz'];
$resultado = $service->crear($datos, 1);
if (!$resultado['exito']) {
    throw new RuntimeException('No se creó la factura confirmada: ' . implode('; ', $resultado['errores']));
}
$factura = $db->query('SELECT importe_total, estado, fecha_confirmacion, fecha_creacion FROM facturas_suministro WHERE id=1')->fetch();
if ((float) $factura['importe_total'] !== 120.50 || $factura['estado'] !== 'confirmada' || !$factura['fecha_confirmacion'] || !$factura['fecha_creacion']) {
    throw new RuntimeException('La factura confirmada no guardó importe, estado o fechas válidos: ' . var_export($factura, true));
}
$duplicado = $service->crear($datos, 1);
if ($duplicado['exito'] || (int) $db->query('SELECT count(*) FROM facturas_suministro')->fetchColumn() !== 1) {
    throw new RuntimeException('Se permitió una factura duplicada');
}
$_GET['id'] = (string) $resultado['id'];
App\Support\Session::flash('factura_creada', 'Alta de prueba', 'exito');
ob_start();
$controller->ver($config);
$detalle = ob_get_clean();
ob_start();
$controller->listar($config);
$listado = ob_get_clean();
ob_start();
$controller->editar($config);
$edicion = ob_get_clean();
if (!str_contains($detalle, 'Alta de prueba') || !str_contains($detalle, '120,50') ||
    !str_contains($listado, 'Compañía prueba') || !str_contains($edicion, 'FAC-1')) {
    throw new RuntimeException('Detalle, listado o formulario de edición no muestran la factura');
}
$datos['numero_factura'] = 'FAC-1-EDIT';
$datos['importe_total'] = '125,00';
$editado = $service->actualizar((int) $resultado['id'], $datos, 1);
if (!$editado['exito']) {
    throw new RuntimeException('No se pudo editar: ' . implode('; ', $editado['errores']));
}
$actual = $db->query('SELECT numero_factura, importe_total, fecha_confirmacion FROM facturas_suministro WHERE id=1')->fetch();
if ($actual['numero_factura'] !== 'FAC-1-EDIT' || (float) $actual['importe_total'] !== 125.0 || $actual['fecha_confirmacion'] !== $factura['fecha_confirmacion']) {
    throw new RuntimeException('La edición perdió datos o cambió la fecha original de confirmación');
}
$agua = $datos;
$agua['tipo'] = 'agua';
$agua['numero_factura'] = 'AGUA-1';
$agua['estado'] = 'borrador';
$borrador = $service->crear($agua, 1, true);
if (!$borrador['exito'] || $db->query('SELECT fecha_confirmacion FROM facturas_suministro WHERE id=2')->fetchColumn() !== null) {
    throw new RuntimeException('El borrador no se guardó sin confirmar');
}
$anulada = $service->anular((int) $resultado['id'], 'Duplicada por la compañía');
if (!$anulada['exito']) {
    throw new RuntimeException('No se pudo anular la factura confirmada: ' . ($anulada['error'] ?? 'sin detalle'));
}
$trasAnular = $db->query('SELECT estado, fecha_confirmacion, observaciones FROM facturas_suministro WHERE id=1')->fetch();
if ($trasAnular['estado'] !== 'anulada' || $trasAnular['fecha_confirmacion'] !== $factura['fecha_confirmacion'] ||
    !str_contains($trasAnular['observaciones'], 'Duplicada por la compañía')) {
    throw new RuntimeException('La anulación no conservó la confirmación o el motivo');
}
if ($db->query('PRAGMA foreign_key_check')->fetchAll()) {
    throw new RuntimeException('Fallaron las claves foráneas');
}
echo "PASS: factura confirmada, borrador, edición, duplicados y pantallas\n";
