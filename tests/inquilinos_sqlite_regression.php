<?php
declare(strict_types=1);
// Ensayo de alta y pantallas sobre SQLite en memoria: no toca datos reales.
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
$_SESSION = ['usuario_id' => 1, 'usuario' => 'prueba', 'nombre' => 'Propietario'];
$_SERVER['REQUEST_METHOD'] = 'GET';
$app = require APP_ROOT . 'config/app.php';
$controller = new App\Controllers\InquilinoController();
$service = new App\Services\InquilinoService();
App\Support\Session::flash('error_inquilino', 'Mensaje de prueba', 'error');
ob_start();
$controller->nuevo($app);
$form = ob_get_clean();
if (!str_contains($form, 'Mensaje de prueba') || !str_contains($form, 'name="numero_documento"') || !str_contains($form, '</html>')) {
    throw new RuntimeException('El formulario no se renderizó completo');
}
$result = $service->crear(['tipo_documento' => 'Otro', 'numero_documento' => ' abc 123 ', 'nombre' => 'Persona', 'apellidos' => 'Prueba']);
if (!$result['exito'] || $db->query('SELECT numero_documento FROM inquilinos')->fetchColumn() !== 'ABC123') {
    throw new RuntimeException('El alta no conserva un documento normalizado');
}
App\Support\Session::flash('inquilino_creado', 'Alta correcta', 'exito');
ob_start();
$controller->listar($app);
$list = ob_get_clean();
if (!str_contains($list, 'Alta correcta') || !str_contains($list, 'Persona Prueba')) {
    throw new RuntimeException('El listado no muestra el alta');
}
$_GET['id'] = (string) $result['id'];
ob_start();
$controller->editar($app);
$edit = ob_get_clean();
if (!str_contains($edit, 'ABC123')) {
    throw new RuntimeException('No se puede abrir la edición');
}
echo "PASS: alta, listado, formularios y mensajes de inquilino\n";
