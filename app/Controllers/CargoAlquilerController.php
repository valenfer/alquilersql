<?php
declare(strict_types=1);

/**
 * Controlador CargoAlquilerController — Gestión de mensualidades y cargos de alquiler.
 *
 * Responsabilidad:
 *  - Listar cargos de alquiler con filtros.
 *  - Preparar mensualidades para un mes (vista previa, idempotente).
 *  - Guardar los cargos preparados.
 *  - Ver detalle de un cargo con su estado y saldo.
 *
 * Rutas:
 *  - r=cargos                    → listar
 *  - r=cargos/preparar           → formulario de preparación (selección de mes)
 *  - r=cargos/preparar/preview   → vista previa de cargos a generar (POST)
 *  - r=cargos/preparar/guardar   → guardar cargos (POST)
 *  - r=cargos/ver&id=X           → detalle de un cargo
 */

namespace App\Controllers;

use App\Support\Session;
use App\Support\Security;
use App\Services\CargoAlquilerService;

class CargoAlquilerController
{
    private CargoAlquilerService $service;

    public function __construct()
    {
        $this->service = new CargoAlquilerService();
    }

    /**
     * Lista cargos de alquiler con filtros.
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
        if (!empty($_GET['anio'])) {
            $filtros['anio'] = (int) $_GET['anio'];
        }
        if (!empty($_GET['mes'])) {
            $filtros['mes'] = (int) $_GET['mes'];
        }
        if (!empty($_GET['estado'])) {
            $filtros['estado'] = $_GET['estado'];
        }
        if (!empty($_GET['inquilino_id'])) {
            $filtros['inquilino_id'] = (int) $_GET['inquilino_id'];
        }

        $cargos = $this->service->listar($filtros);
        $flash = Session::consumirTodos();
        $csrf = Session::tokenCsrf();

        // Obtener años y meses disponibles para filtros
        $anios = $this->obtenerAniosDisponibles();
        $meses = $this->obtenerMeses();

        $this->render('cargos/listar.php', [
            'titulo' => 'Mensualidades y cargos de alquiler',
            'cargos' => $cargos,
            'filtros' => $filtros,
            'anios' => $anios,
            'meses' => $meses,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Muestra el formulario para preparar mensualidades (selección de mes y año).
     *
     * @param array $config
     * @return void
     */
    public function preparar(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $anioActual = (int) date('Y');
        $mesActual = (int) date('m');
        $anios = range($anioActual - 2, $anioActual + 1);
        $meses = $this->obtenerMeses();
        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('cargos/preparar.php', [
            'titulo' => 'Preparar mensualidades',
            'anio_actual' => $anioActual,
            'mes_actual' => $mesActual,
            'anios' => $anios,
            'meses' => $meses,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Genera la vista previa de cargos para un mes (no guarda todavía).
     *
     * @param array $config
     * @return void
     */
    public function preview(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $config['url_base'] . '/index.php?r=cargos/preparar');
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validarCsrf($token)) {
            Session::flash('csrf_error', 'Token de seguridad inválido.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=cargos/preparar');
            exit;
        }

        $anio = (int) ($_POST['anio'] ?? 0);
        $mes = (int) ($_POST['mes'] ?? 0);

        if ($anio < 2020 || $anio > 2030 || $mes < 1 || $mes > 12) {
            Session::flash('error_cargo', 'Año o mes no válidos.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=cargos/preparar');
            exit;
        }

        $usuario = \App\Support\Auth::usuario();
        $preparacion = $this->service->prepararMensualidades($anio, $mes, $usuario['id']);

        $meses = $this->obtenerMeses();
        $mesNombre = $meses[$mes - 1] ?? '';

        $this->render('cargos/preview.php', [
            'titulo' => 'Vista previa — Mensualidades ' . $mesNombre . ' ' . $anio,
            'anio' => $anio,
            'mes' => $mes,
            'mes_nombre' => $mesNombre,
            'cargos' => $preparacion['cargos'],
            'total_esperado' => $preparacion['total_esperado'],
            'errores' => $preparacion['errores'],
            'csrf' => Session::tokenCsrf(),
            'flash' => Session::consumirTodos(),
            'config' => $config,
        ]);
    }

    /**
     * Guarda los cargos preparados en la base de datos (idempotente).
     *
     * @param array $config
     * @return void
     */
    public function guardar(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validarCsrf($token)) {
            Session::flash('csrf_error', 'Token de seguridad inválido.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=cargos/preparar');
            exit;
        }

        $anio = (int) ($_POST['anio'] ?? 0);
        $mes = (int) ($_POST['mes'] ?? 0);
        $cargosJson = $_POST['cargos_json'] ?? '[]';

        $cargos = json_decode($cargosJson, true);
        if (!is_array($cargos)) {
            $cargos = [];
        }

        $usuario = \App\Support\Auth::usuario();
        $resultado = $this->service->guardarCargos($cargos, $usuario['id']);

        if ($resultado['exito']) {
            Session::flash('cargos_creados', 'Se crearon ' . $resultado['creados'] . ' mensualidad(es) correctamente.', 'exito');
        } else {
            Session::flash('error_cargo', implode(' ', $resultado['errores']), 'error');
        }

        header('Location: ' . $config['url_base'] . '/index.php?r=cargos');
        exit;
    }

    /**
     * Muestra el detalle de un cargo con su estado y saldo.
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
            header('Location: ' . $config['url_base'] . '/index.php?r=cargos');
            exit;
        }

        $cargo = $this->service->obtener($id);
        if ($cargo === null) {
            Session::flash('error_cargo', 'El cargo no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=cargos');
            exit;
        }

        // Recuperar estado actualizado desde cobros
        $estado = $this->service->recuperarEstado($id);
        $cargo['estado_calculado'] = $estado['estado'];
        $cargo['saldo_pendiente'] = $estado['saldo_pendiente'];
        $cargo['importe_cobrado'] = $estado['importe_cobrado'];

        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('cargos/ver.php', [
            'titulo' => 'Detalle del cargo',
            'cargo' => $cargo,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Obtiene años disponibles para filtros.
     *
     * @return array
     */
    private function obtenerAniosDisponibles(): array
    {
        $pdo = \App\Support\Database::obtenerConexion();
        $stmt = $pdo->query(
            'SELECT DISTINCT anio FROM cargos_alquiler ORDER BY anio DESC'
        );
        $anios = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        if (empty($anios)) {
            $anios = [(int) date('Y')];
        }
        return $anios;
    }

    /**
     * Obtiene nombres de meses en español.
     *
     * @return array
     */
    private function obtenerMeses(): array
    {
        return [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
    }

    /**
     * Renderiza una vista de cargos.
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

        $titulo = $titulo ?? 'Cargos';
        $flash = $flash ?? [];
        $csrf = $csrf ?? '';
        $config = $config ?? [];
        $usuario = \App\Support\Auth::usuario();

        require $rutaLayout;
    }
}
