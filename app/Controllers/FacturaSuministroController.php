<?php
declare(strict_types=1);

/**
 * Controlador FacturaSuministroController — Gestión de facturas de luz y agua.
 *
 * Responsabilidad:
 *  - Listar facturas con filtros.
 *  - Crear facturas (borrador o confirmada).
 *  - Editar facturas existentes.
 *  - Anular facturas.
 *  - Ver detalle de factura con estado de pago al proveedor.
 *
 * Rutas:
 *  - r=facturas                    → listar
 *  - r=facturas/nuevo              → formulario crear
 *  - r=facturas/crear              → procesar crear (POST)
 *  - r=facturas/editar&id=X        → formulario editar
 *  - r=facturas/actualizar&id=X    → procesar editar (POST)
 *  - r=facturas/anular&id=X        → anular (POST)
 *  - r=facturas/ver&id=X           → detalle de factura
 */

namespace App\Controllers;

use App\Support\Session;
use App\Support\Security;
use App\Services\FacturaSuministroService;

class FacturaSuministroController
{
    private FacturaSuministroService $service;

    public function __construct()
    {
        $this->service = new FacturaSuministroService();
    }

    /**
     * Lista facturas con filtros.
     *
     * @param array $config
     * @return void
     */
    public function listar(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $filtros = [];
        if (!empty($_GET['tipo'])) {
            $filtros['tipo'] = $_GET['tipo'];
        }
        if (!empty($_GET['proveedor'])) {
            $filtros['proveedor'] = $_GET['proveedor'];
        }
        if (!empty($_GET['estado'])) {
            $filtros['estado'] = $_GET['estado'];
        }

        $facturas = $this->service->listar($filtros);
        $flash = Session::consumirTodos();
        $csrf = Session::tokenCsrf();

        $this->render('facturas/listar.php', [
            'titulo' => 'Facturas de luz y agua',
            'facturas' => $facturas,
            'filtros' => $filtros,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Muestra el formulario para crear una factura.
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

        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('facturas/formulario.php', [
            'titulo' => 'Nueva factura de suministro',
            'accion' => 'crear',
            'factura' => null,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa la creación de una factura.
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
            header('Location: ' . $config['url_base'] . '/index.php?r=facturas/nuevo');
            exit;
        }

        $usuario = \App\Support\Auth::usuario();
        $esBorrador = !empty($_POST['guardar_borrador']);

        $datos = [
            'tipo' => $_POST['tipo'] ?? '',
            'proveedor' => $_POST['proveedor'] ?? '',
            'numero_factura' => $_POST['numero_factura'] ?? '',
            'fecha_emision' => $_POST['fecha_emision'] ?? '',
            'periodo_desde' => $_POST['periodo_desde'] ?? '',
            'periodo_hasta' => $_POST['periodo_hasta'] ?? '',
            'importe_total' => $_POST['importe_total'] ?? '',
            'fecha_vencimiento' => $_POST['fecha_vencimiento'] ?? '',
            'estado' => $esBorrador ? 'borrador' : 'confirmada',
            'observaciones' => $_POST['observaciones'] ?? '',
            'motivo_solapamiento' => $_POST['motivo_solapamiento'] ?? '',
        ];

        $resultado = $this->service->crear($datos, $usuario['id'], $esBorrador);

        if ($resultado['exito']) {
            Session::flash('factura_creada', 'Factura creada correctamente.', 'exito');
            header('Location: ' . $config['url_base'] . '/index.php?r=facturas/ver&id=' . $resultado['id']);
        } else {
            Session::flash('error_factura', implode(' ', $resultado['errores']), 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=facturas/nuevo');
        }

        exit;
    }

    /**
     * Muestra el formulario para editar una factura.
     *
     * @param array $config
     * @return void
     */
    public function editar(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . $config['url_base'] . '/index.php?r=facturas');
            exit;
        }

        $factura = $this->service->obtener($id);
        if ($factura === null) {
            Session::flash('error_factura', 'La factura no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=facturas');
            exit;
        }

        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('facturas/formulario.php', [
            'titulo' => 'Editar factura',
            'accion' => 'actualizar',
            'id' => $id,
            'factura' => $factura,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa la actualización de una factura.
     *
     * @param array $config
     * @return void
     */
    public function actualizar(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validarCsrf($token)) {
            Session::flash('csrf_error', 'Token de seguridad inválido.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=facturas');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . $config['url_base'] . '/index.php?r=facturas');
            exit;
        }

        $usuario = \App\Support\Auth::usuario();
        $datos = [
            'tipo' => $_POST['tipo'] ?? '',
            'proveedor' => $_POST['proveedor'] ?? '',
            'numero_factura' => $_POST['numero_factura'] ?? '',
            'fecha_emision' => $_POST['fecha_emision'] ?? '',
            'periodo_desde' => $_POST['periodo_desde'] ?? '',
            'periodo_hasta' => $_POST['periodo_hasta'] ?? '',
            'importe_total' => $_POST['importe_total'] ?? '',
            'fecha_vencimiento' => $_POST['fecha_vencimiento'] ?? '',
            'estado' => $_POST['estado'] ?? 'borrador',
            'observaciones' => $_POST['observaciones'] ?? '',
            'motivo_solapamiento' => $_POST['motivo_solapamiento'] ?? '',
        ];

        $resultado = $this->service->actualizar($id, $datos, $usuario['id']);

        if ($resultado['exito']) {
            Session::flash('factura_actualizada', 'Factura actualizada correctamente.', 'exito');
        } else {
            Session::flash('error_factura', implode(' ', $resultado['errores']), 'error');
        }

        header('Location: ' . $config['url_base'] . '/index.php?r=facturas');
        exit;
    }

    /**
     * Anula una factura.
     *
     * @param array $config
     * @return void
     */
    public function anular(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validarCsrf($token)) {
            Session::flash('csrf_error', 'Token de seguridad inválido.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=facturas');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $motivo = trim($_POST['motivo_anulacion'] ?? '');

        if ($id <= 0) {
            header('Location: ' . $config['url_base'] . '/index.php?r=facturas');
            exit;
        }

        $resultado = $this->service->anular($id, $motivo);

        if ($resultado['exito']) {
            Session::flash('factura_anulada', 'Factura anulada correctamente.', 'exito');
        } else {
            Session::flash('error_factura', $resultado['error'], 'error');
        }

        header('Location: ' . $config['url_base'] . '/index.php?r=facturas');
        exit;
    }

    /**
     * Muestra el detalle de una factura con su estado de pago al proveedor.
     *
     * @param array $config
     * @return void
     */
    public function ver(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . $config['url_base'] . '/index.php?r=facturas');
            exit;
        }

        $factura = $this->service->obtener($id);
        if ($factura === null) {
            Session::flash('error_factura', 'La factura no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=facturas');
            exit;
        }

        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        // Pagos al proveedor
        $pdo = \App\Support\Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT * FROM pagos_proveedor WHERE factura_id = :factura_id ORDER BY fecha_real ASC'
        );
        $stmt->execute([':factura_id' => $id]);
        $pagos = $stmt->fetchAll();

        $this->render('facturas/ver.php', [
            'titulo' => 'Detalle de la factura',
            'factura' => $factura,
            'pagos' => $pagos,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Renderiza una vista de facturas.
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

        $titulo = $titulo ?? 'Facturas';
        $flash = $flash ?? [];
        $csrf = $csrf ?? '';
        $config = $config ?? [];
        $usuario = \App\Support\Auth::usuario();

        require $rutaLayout;
    }
}
