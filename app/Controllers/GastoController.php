<?php
declare(strict_types=1);

/**
 * Controlador GastoController — Gestión de gastos del propietario.
 *
 * Responsabilidad:
 *  - Listar gastos con filtros.
 *  - Registrar gasto con pagos (partiales o completos) en una transacción.
 *  - Comunidad e IBI no generan cuotas a inquilinos.
 *  - Ver detalle de gasto con saldo pendiente de pago.
 *
 * Rutas:
 *  - r=gastos                    → listar
 *  - r=gastos/nuevo              → formulario crear
 *  - r=gastos/crear              → procesar crear (POST)
 *  - r=gastos/ver&id=X           → detalle
 *  - r=gastos/pagar&id=X         → formulario pagar
 *  - r=gastos/procesar-pagar     → procesar pago (POST)
 */

namespace App\Controllers;

use App\Support\Session;
use App\Support\Security;
use App\Services\GastoService;

class GastoController
{
    private GastoService $service;

    public function __construct()
    {
        $this->service = new GastoService();
    }

    /**
     * Lista gastos con filtros.
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
        if (!empty($_GET['categoria_id'])) {
            $filtros['categoria_id'] = (int) $_GET['categoria_id'];
        }
        if (!empty($_GET['fecha_desde'])) {
            $filtros['fecha_desde'] = $_GET['fecha_desde'];
        }
        if (!empty($_GET['fecha_hasta'])) {
            $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
        }

        $gastos = $this->service->listar($filtros);
        $categorias = $this->service->listarCategorias();
        $flash = Session::consumirTodos();
        $csrf = Session::tokenCsrf();

        $this->render('gastos/listar.php', [
            'titulo' => 'Gastos del propietario',
            'gastos' => $gastos,
            'categorias' => $categorias,
            'filtros' => $filtros,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Muestra el formulario para registrar un gasto.
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

        $categorias = $this->service->listarCategorias();
        $habitaciones = $this->obtenerHabitaciones();
        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('gastos/nuevo.php', [
            'titulo' => 'Registrar gasto',
            'categorias' => $categorias,
            'habitaciones' => $habitaciones,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa la creación de un gasto con pagos.
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
            header('Location: ' . $config['url_base'] . '/index.php?r=gastos/nuevo');
            exit;
        }

        $usuario = \App\Support\Auth::usuario();

        // Procesar pagos (pueden ser varios)
        $pagos = [];
        if (!empty($_POST['pago_importe'])) {
            foreach ($_POST['pago_importe'] as $idx => $importe) {
                if (Security::normalizarImporte((string) $importe) > 0) {
                    $pagos[] = [
                        'importe' => $importe,
                        'fecha_pago' => $_POST['pago_fecha'][$idx] ?? '',
                        'medio' => $_POST['pago_medio'][$idx] ?? '',
                        'referencia' => $_POST['pago_referencia'][$idx] ?? null,
                        'observaciones' => $_POST['pago_observaciones'][$idx] ?? null,
                    ];
                }
            }
        }

        $datos = [
            'categoria_id' => $_POST['categoria_id'] ?? '',
            'descripcion' => $_POST['descripcion'] ?? '',
            'importe' => $_POST['importe'] ?? '',
            'fecha_gasto' => $_POST['fecha_gasto'] ?? '',
            'proveedor' => $_POST['proveedor'] ?? '',
            'numero_factura' => $_POST['numero_factura'] ?? '',
            'relacion_habitacion' => $_POST['relacion_habitacion'] ?? '',
            'es_general' => $_POST['es_general'] ?? 1,
            'observaciones' => $_POST['observaciones'] ?? '',
            'pagos' => $pagos,
        ];

        $resultado = $this->service->crearConPagos($datos, $usuario['id']);

        if ($resultado['exito']) {
            Session::flash('gasto_creado', 'Gasto registrado correctamente.', 'exito');
            header('Location: ' . $config['url_base'] . '/index.php?r=gastos/ver&id=' . $resultado['id']);
        } else {
            Session::flash('error_gasto', implode(' ', $resultado['errores']), 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=gastos/nuevo');
        }

        exit;
    }

    /**
     * Muestra el detalle de un gasto.
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
            header('Location: ' . $config['url_base'] . '/index.php?r=gastos');
            exit;
        }

        $gasto = $this->service->obtener($id);
        if ($gasto === null) {
            Session::flash('error_gasto', 'El gasto no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=gastos');
            exit;
        }

        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('gastos/ver.php', [
            'titulo' => 'Detalle del gasto',
            'gasto' => $gasto,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Muestra el formulario para pagar un gasto.
     *
     * @param array $config
     * @return void
     */
    public function pagar(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . $config['url_base'] . '/index.php?r=gastos');
            exit;
        }

        $gasto = $this->service->obtener($id);
        if ($gasto === null) {
            Session::flash('error_gasto', 'El gasto no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=gastos');
            exit;
        }

        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('gastos/pagar.php', [
            'titulo' => 'Pagar gasto',
            'gasto' => $gasto,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa el pago de un gasto.
     *
     * @param array $config
     * @return void
     */
    public function procesarPagar(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validarCsrf($token)) {
            Session::flash('csrf_error', 'Token de seguridad inválido.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=gastos/pagar&id=' . $_POST['gasto_id']);
            exit;
        }

        $usuario = \App\Support\Auth::usuario();
        $resultado = $this->service->registrarPago(
            (int) ($_POST['gasto_id'] ?? 0),
            Security::normalizarImporte((string) ($_POST['importe'] ?? '')) ?? 0.0,
            $_POST['fecha_pago'] ?? '',
            $_POST['medio'] ?? '',
            $_POST['referencia'] ?? null,
            $_POST['observaciones'] ?? null,
            $usuario['id']
        );

        if ($resultado['exito']) {
            Session::flash('gasto_pagado', 'Pago registrado correctamente.', 'exito');
        } else {
            Session::flash('error_gasto', implode(' ', $resultado['errores']), 'error');
        }

        header('Location: ' . $config['url_base'] . '/index.php?r=gastos/ver&id=' . $_POST['gasto_id']);
        exit;
    }

    /**
     * Obtiene lista de habitaciones para selectores.
     *
     * @return array
     */
    private function obtenerHabitaciones(): array
    {
        $pdo = \App\Support\Database::obtenerConexion();
        $stmt = $pdo->query('SELECT id, numero, nombre FROM habitaciones ORDER BY numero');
        return $stmt->fetchAll();
    }

    /**
     * Renderiza una vista de gastos.
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

        $titulo = $titulo ?? 'Gastos';
        $flash = $flash ?? [];
        $csrf = $csrf ?? '';
        $config = $config ?? [];
        $usuario = \App\Support\Auth::usuario();

        require $rutaLayout;
    }
}
