<?php
declare(strict_types=1);

/**
 * Controlador CobroController — Gestión de cobros recibidos y sus aplicaciones.
 *
 * Responsabilidad:
 *  - Listar cobros con filtros.
 *  - Mostrar formulario para registrar un cobro (quién paga → qué paga → importe y fecha → revisar).
 *  - Procesar creación de cobro con aplicaciones (transaccional).
 *  - Ver detalle de un cobro con sus aplicaciones.
 *
 * Rutas:
 *  - r=cobros                    → listar
 *  - r=cobros/nuevo              → formulario crear cobro
 *  - r=cobros/crear              → procesar crear (POST)
 *  - r=cobros/ver&id=X           → detalle de un cobro
 *  - r=cobros/cargos-pendientes&inquilino_id=X → obtener cargos pendientes (AJAX)
 */

namespace App\Controllers;

use App\Support\Session;
use App\Support\Security;
use App\Services\CobroService;

class CobroController
{
    private CobroService $service;

    public function __construct()
    {
        $this->service = new CobroService();
    }

    /**
     * Lista cobros con filtros.
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
        if (!empty($_GET['inquilino_id'])) {
            $filtros['inquilino_id'] = (int) $_GET['inquilino_id'];
        }
        if (!empty($_GET['fecha_desde'])) {
            $filtros['fecha_desde'] = $_GET['fecha_desde'];
        }
        if (!empty($_GET['fecha_hasta'])) {
            $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
        }
        if (!empty($_GET['medio'])) {
            $filtros['medio'] = $_GET['medio'];
        }

        $cobros = $this->service->listar($filtros);
        $flash = Session::consumirTodos();
        $csrf = Session::tokenCsrf();

        // Obtener inquilinos para filtro
        $pdo = \App\Support\Database::obtenerConexion();
        $stmt = $pdo->query("SELECT id, nombre, apellidos FROM inquilinos WHERE estado = 'activo' ORDER BY apellidos, nombre");
        $inquilinos = $stmt->fetchAll();

        $medios = ['efectivo', 'transferencia', 'bizum', 'otro'];

        $this->render('cobros/listar.php', [
            'titulo' => 'Cobros recibidos',
            'cobros' => $cobros,
            'filtros' => $filtros,
            'inquilinos' => $inquilinos,
            'medios' => $medios,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Muestra el formulario para registrar un cobro.
     *
     * Paso 1: seleccionar inquilino.
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

        $pdo = \App\Support\Database::obtenerConexion();
        $stmt = $pdo->query("SELECT id, nombre, apellidos, numero_documento FROM inquilinos WHERE estado = 'activo' ORDER BY apellidos, nombre");
        $inquilinos = $stmt->fetchAll();

        $medios = ['efectivo', 'transferencia', 'bizum', 'otro'];
        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('cobros/nuevo.php', [
            'titulo' => 'Registrar un cobro',
            'inquilinos' => $inquilinos,
            'medios' => $medios,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa la creación de un cobro con sus aplicaciones (transaccional).
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
            header('Location: ' . $config['url_base'] . '/index.php?r=cobros/nuevo');
            exit;
        }

        $usuario = \App\Support\Auth::usuario();

        // Decodificar aplicaciones enviadas como JSON
        $aplicacionesJson = $_POST['aplicaciones_json'] ?? '[]';
        $aplicaciones = json_decode($aplicacionesJson, true);
        if (!is_array($aplicaciones)) {
            $aplicaciones = [];
        }

        $datos = [
            'inquilino_id' => $_POST['inquilino_id'] ?? '',
            'fecha_cobro' => $_POST['fecha_cobro'] ?? '',
            'medio' => $_POST['medio'] ?? '',
            'importe_total' => $_POST['importe_total'] ?? '',
            'referencia' => $_POST['referencia'] ?? '',
            'observaciones' => $_POST['observaciones'] ?? '',
            'token_idempotencia' => $_POST['token_idempotencia'] ?? null,
            'aplicaciones' => $aplicaciones,
        ];

        $resultado = $this->service->crearConAplicaciones($datos, $usuario['id']);

        if ($resultado['exito']) {
            Session::flash('cobro_registrado', 'Cobro registrado correctamente.', 'exito');
            header('Location: ' . $config['url_base'] . '/index.php?r=cobros/ver&id=' . $resultado['id']);
        } else {
            Session::flash('error_cobro', implode(' ', $resultado['errores']), 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=cobros/nuevo');
        }

        exit;
    }

    /**
     * Devuelve los cargos pendientes de un inquilino en formato JSON (para AJAX).
     *
     * @param array $config
     * @return void
     */
    public function cargosPendientes(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            http_response_code(401);
            echo json_encode(['error' => 'No autenticado']);
            exit;
        }

        $inquilinoId = (int) ($_GET['inquilino_id'] ?? 0);
        if ($inquilinoId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Inquilino no válido']);
            exit;
        }

        $cargos = $this->service->obtenerCargosPendientesPorInquilino($inquilinoId);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($cargos, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Muestra el detalle de un cobro con sus aplicaciones.
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
            header('Location: ' . $config['url_base'] . '/index.php?r=cobros');
            exit;
        }

        $cobro = $this->service->obtenerDetalle($id);
        if ($cobro === null) {
            Session::flash('error_cobro', 'El cobro no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=cobros');
            exit;
        }

        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('cobros/ver.php', [
            'titulo' => 'Detalle del cobro',
            'cobro' => $cobro,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Renderiza una vista de cobros.
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

        $titulo = $titulo ?? 'Cobros';
        $flash = $flash ?? [];
        $csrf = $csrf ?? '';
        $config = $config ?? [];
        $usuario = \App\Support\Auth::usuario();

        require $rutaLayout;
    }
}
