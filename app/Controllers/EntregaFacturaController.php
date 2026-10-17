<?php
declare(strict_types=1);

/**
 * Controlador EntregaFacturaController — Gestión de entregas de facturas por destinatario.
 *
 * Responsabilidad:
 *  - Mostrar historial de entregas de una factura.
 *  - Registrar entrega a un destinatario individual.
 *  - Registrar entrega a todos los destinatarios seleccionados.
 *  - Mostrar destinatarios pendientes.
 *
 * Rutas:
 *  - r=entregas/nuevo&factura_id=X     → formulario entrega individual
 *  - r=entregas/crear                  → procesar entrega (POST)
 *  - r=entregas/todos&factura_id=X     → entrega a todos
 *  - r=entregas/crear-todos            → procesar entrega a todos (POST)
 */

namespace App\Controllers;

use App\Support\Session;
use App\Support\Security;
use App\Services\EntregaFacturaService;

class EntregaFacturaController
{
    private EntregaFacturaService $service;

    public function __construct()
    {
        $this->service = new EntregaFacturaService();
    }

    /**
     * Muestra el formulario para registrar una entrega individual.
     *
     * @param array $config
     * @return void
     */
    public function nuevo(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $facturaId = (int) ($_GET['factura_id'] ?? 0);
        if ($facturaId <= 0) {
            header('Location: ' . $config['url_base'] . '/index.php?r=facturas');
            exit;
        }

        $factura = $this->service->listar($facturaId); // Esto no devuelve la factura, ajuste necesario
        // Obtener factura directamente
        $pdo = \App\Support\Database::obtenerConexion();
        $stmt = $pdo->prepare('SELECT * FROM facturas_suministro WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $facturaId]);
        $factura = $stmt->fetch();

        if ($factura === null) {
            Session::flash('error_entrega', 'La factura no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=facturas');
            exit;
        }

        $pendientes = $this->service->obtenerPendientes($facturaId);
        $entregas = $this->service->listar($facturaId);
        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('entregas/nuevo.php', [
            'titulo' => 'Registrar entrega de factura',
            'factura' => $factura,
            'pendientes' => $pendientes,
            'entregas' => $entregas,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa el registro de una entrega individual.
     *
     * @param array $config
     * @return void
     */
    public function crear(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validarCsrf($token)) {
            Session::flash('csrf_error', 'Token de seguridad inválido.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=entregas/nuevo&factura_id=' . $_POST['factura_id']);
            exit;
        }

        $usuario = \App\Support\Auth::usuario();
        $datos = [
            'factura_id' => $_POST['factura_id'] ?? '',
            'estancia_id' => $_POST['estancia_id'] ?? '',
            'fecha_entrega' => $_POST['fecha_entrega'] ?? '',
            'medio' => $_POST['medio'] ?? '',
            'confirmacion_recepcion' => $_POST['confirmacion_recepcion'] ?? 0,
            'observaciones' => $_POST['observaciones'] ?? '',
        ];

        $resultado = $this->service->registrar($datos, $usuario['id']);

        if ($resultado['exito']) {
            Session::flash('entrega_registrada', 'Entrega registrada correctamente.', 'exito');
        } else {
            Session::flash('error_entrega', implode(' ', $resultado['errores']), 'error');
        }

        header('Location: ' . $config['url_base'] . '/index.php?r=entregas/nuevo&factura_id=' . $datos['factura_id']);
        exit;
    }

    /**
     * Muestra el formulario para entregar a todos los destinatarios.
     *
     * @param array $config
     * @return void
     */
    public function todos(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $facturaId = (int) ($_GET['factura_id'] ?? 0);
        if ($facturaId <= 0) {
            header('Location: ' . $config['url_base'] . '/index.php?r=facturas');
            exit;
        }

        $pdo = \App\Support\Database::obtenerConexion();
        $stmt = $pdo->prepare('SELECT * FROM facturas_suministro WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $facturaId]);
        $factura = $stmt->fetch();

        if ($factura === null) {
            Session::flash('error_entrega', 'La factura no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=facturas');
            exit;
        }

        $pendientes = $this->service->obtenerPendientes($facturaId);
        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('entregas/todos.php', [
            'titulo' => 'Registrar entrega a todos los destinatarios',
            'factura' => $factura,
            'pendientes' => $pendientes,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa el registro de entrega a todos los destinatarios seleccionados.
     *
     * @param array $config
     * @return void
     */
    public function crearTodos(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validarCsrf($token)) {
            Session::flash('csrf_error', 'Token de seguridad inválido.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=entregas/todos&factura_id=' . $_POST['factura_id']);
            exit;
        }

        $usuario = \App\Support\Auth::usuario();
        $facturaId = (int) ($_POST['factura_id'] ?? 0);
        $estanciaIds = $_POST['estancia_ids'] ?? [];
        $fechaEntrega = $_POST['fecha_entrega'] ?? '';
        $medio = $_POST['medio'] ?? '';

        if (!is_array($estanciaIds)) {
            $estanciaIds = [];
        }

        $resultado = $this->service->registrarATodos($facturaId, $estanciaIds, $fechaEntrega, $medio, $usuario['id']);

        if ($resultado['exito']) {
            Session::flash('entregas_registradas', 'Se registraron ' . $resultado['creados'] . ' entrega(s) correctamente.', 'exito');
        } else {
            Session::flash('error_entrega', implode(' ', $resultado['errores']), 'error');
        }

        header('Location: ' . $config['url_base'] . '/index.php?r=entregas/nuevo&factura_id=' . $facturaId);
        exit;
    }

    /**
     * Renderiza una vista de entregas.
     *
     * @param string $vista
     * @param array $datos
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

        $titulo = $titulo ?? 'Entregas';
        $flash = $flash ?? [];
        $csrf = $csrf ?? '';
        $config = $config ?? [];
        $usuario = \App\Support\Auth::usuario();

        require $rutaLayout;
    }
}
