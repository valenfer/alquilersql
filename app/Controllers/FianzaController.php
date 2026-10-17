<?php
declare(strict_types=1);

/**
 * Controlador FianzaController — Gestión de fianzas.
 *
 * Responsabilidad:
 *  - Listar fianzas con saldo disponible.
 *  - Recibir fianza (dinero del inquilino, no es ingreso de alquiler).
 *  - Devolver fianza (no es gasto ordinario).
 *  - Aplicar fianza a deuda de alquiler (compensación sin movimiento de caja).
 *  - Ver detalle de fianza con movimientos.
 *
 * Rutas:
 *  - r=fianzas                    → listar
 *  - r=fianzas/recibir&estancia_id=X → formulario recibir
 *  - r=fianzas/crear              → procesar recibir (POST)
 *  - r=fianzas/devolver&id=X      → formulario devolver
 *  - r=fianzas/procesar-devolver  → procesar devolver (POST)
 *  - r=fianzas/aplicar&id=X       → formulario aplicar a deuda
 *  - r=fianzas/procesar-aplicar   → procesar aplicar (POST)
 *  - r=fianzas/ver&id=X           → detalle
 */

namespace App\Controllers;

use App\Support\Session;
use App\Support\Security;
use App\Services\FianzaService;

class FianzaController
{
    private FianzaService $service;

    public function __construct()
    {
        $this->service = new FianzaService();
    }

    /**
     * Lista todas las fianzas.
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

        $fianzas = $this->service->listar();
        $flash = Session::consumirTodos();
        $csrf = Session::tokenCsrf();

        $this->render('fianzas/listar.php', [
            'titulo' => 'Fianzas',
            'fianzas' => $fianzas,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Muestra el formulario para recibir una fianza.
     *
     * @param array $config
     * @return void
     */
    public function recibir(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $estanciaId = (int) ($_GET['estancia_id'] ?? 0);
        if ($estanciaId <= 0) {
            header('Location: ' . $config['url_base'] . '/index.php?r=estancias');
            exit;
        }

        // Obtener datos de la estancia para mostrar
        $pdo = \App\Support\Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT e.id, e.renta_mensual, e.fianza_pactada, h.numero AS habitacion_numero,
                    i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos
             FROM estancias e
             INNER JOIN habitaciones h ON e.habitacion_id = h.id
             INNER JOIN inquilinos i ON e.inquilino_id = i.id
             WHERE e.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $estanciaId]);
        $estancia = $stmt->fetch();

        if (!$estancia) {
            Session::flash('error_fianza', 'La estancia no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=estancias');
            exit;
        }

        // Verificar si ya tiene fianza
        $fianzaExistente = $this->service->obtenerPorEstancia($estanciaId);
        if ($fianzaExistente !== null) {
            Session::flash('error_fianza', 'Esta estancia ya tiene una fianza registrada.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=fianzas/ver&id=' . $fianzaExistente['id']);
            exit;
        }

        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('fianzas/recibir.php', [
            'titulo' => 'Recibir fianza',
            'estancia' => $estancia,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa la recepción de una fianza.
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
            header('Location: ' . $config['url_base'] . '/index.php?r=fianzas/recibir&estancia_id=' . $_POST['estancia_id']);
            exit;
        }

        $usuario = \App\Support\Auth::usuario();
        $resultado = $this->service->recibir(
            (int) ($_POST['estancia_id'] ?? 0),
            (float) ($_POST['importe'] ?? 0),
            $_POST['fecha'] ?? '',
            $_POST['medio'] ?? '',
            $_POST['referencia'] ?? null,
            $_POST['observaciones'] ?? null,
            $usuario['id']
        );

        if ($resultado['exito']) {
            Session::flash('fianza_recibida', 'Fianza recibida correctamente. No es ingreso de alquiler.', 'exito');
            header('Location: ' . $config['url_base'] . '/index.php?r=fianzas/ver&id=' . $resultado['id']);
        } else {
            Session::flash('error_fianza', implode(' ', $resultado['errores']), 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=fianzas/recibir&estancia_id=' . $_POST['estancia_id']);
        }

        exit;
    }

    /**
     * Muestra el formulario para devolver una fianza.
     *
     * @param array $config
     * @return void
     */
    public function devolver(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . $config['url_base'] . '/index.php?r=fianzas');
            exit;
        }

        $fianza = $this->service->obtener($id);
        if ($fianza === null) {
            Session::flash('error_fianza', 'La fianza no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=fianzas');
            exit;
        }

        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('fianzas/devolver.php', [
            'titulo' => 'Devolver fianza',
            'fianza' => $fianza,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa la devolución de una fianza.
     *
     * @param array $config
     * @return void
     */
    public function procesarDevolver(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validarCsrf($token)) {
            Session::flash('csrf_error', 'Token de seguridad inválido.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=fianzas/devolver&id=' . $_POST['fianza_id']);
            exit;
        }

        $usuario = \App\Support\Auth::usuario();
        $resultado = $this->service->devolver(
            (int) ($_POST['fianza_id'] ?? 0),
            (float) ($_POST['importe'] ?? 0),
            $_POST['fecha'] ?? '',
            $_POST['medio'] ?? '',
            $_POST['observaciones'] ?? null,
            $usuario['id']
        );

        if ($resultado['exito']) {
            Session::flash('fianza_devuelta', 'Fianza devuelta correctamente.', 'exito');
        } else {
            Session::flash('error_fianza', implode(' ', $resultado['errores']), 'error');
        }

        header('Location: ' . $config['url_base'] . '/index.php?r=fianzas/ver&id=' . $_POST['fianza_id']);
        exit;
    }

    /**
     * Muestra el formulario para aplicar fianza a deuda.
     *
     * @param array $config
     * @return void
     */
    public function aplicar(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . $config['url_base'] . '/index.php?r=fianzas');
            exit;
        }

        $fianza = $this->service->obtener($id);
        if ($fianza === null) {
            Session::flash('error_fianza', 'La fianza no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=fianzas');
            exit;
        }

        // Obtener cargos pendientes de la estancia
        $pdo = \App\Support\Database::obtenerConexion();
        $stmt = $pdo->prepare(
            "SELECT c.id, c.anio, c.mes, ROUND(c.importe_esperado - c.importe_cobrado, 2) AS saldo_pendiente
             FROM cargos_alquiler c
             INNER JOIN estancias e ON c.estancia_id = e.id
             WHERE e.id = :estancia_id AND c.estado IN ('pendiente', 'parcial')
             ORDER BY c.anio, c.mes"
        );
        $stmt->execute([':estancia_id' => $fianza['estancia_id']]);
        $cargosPendientes = $stmt->fetchAll();

        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('fianzas/aplicar.php', [
            'titulo' => 'Aplicar fianza a deuda',
            'fianza' => $fianza,
            'cargosPendientes' => $cargosPendientes,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa la aplicación de fianza a deuda.
     *
     * @param array $config
     * @return void
     */
    public function procesarAplicar(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validarCsrf($token)) {
            Session::flash('csrf_error', 'Token de seguridad inválido.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=fianzas/aplicar&id=' . $_POST['fianza_id']);
            exit;
        }

        $usuario = \App\Support\Auth::usuario();
        $resultado = $this->service->aplicarADeuda(
            (int) ($_POST['fianza_id'] ?? 0),
            (int) ($_POST['cargo_alquiler_id'] ?? 0),
            (float) ($_POST['importe'] ?? 0),
            $_POST['fecha'] ?? '',
            $_POST['motivo'] ?? null,
            $usuario['id']
        );

        if ($resultado['exito']) {
            Session::flash('fianza_aplicada', 'Fianza aplicada a la deuda (compensación sin movimiento de caja).', 'exito');
        } else {
            Session::flash('error_fianza', implode(' ', $resultado['errores']), 'error');
        }

        header('Location: ' . $config['url_base'] . '/index.php?r=fianzas/ver&id=' . $_POST['fianza_id']);
        exit;
    }

    /**
     * Muestra el detalle de una fianza con sus movimientos.
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
            header('Location: ' . $config['url_base'] . '/index.php?r=fianzas');
            exit;
        }

        $fianza = $this->service->obtener($id);
        if ($fianza === null) {
            Session::flash('error_fianza', 'La fianza no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=fianzas');
            exit;
        }

        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('fianzas/ver.php', [
            'titulo' => 'Detalle de la fianza',
            'fianza' => $fianza,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Renderiza una vista de fianzas.
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

        $titulo = $titulo ?? 'Fianzas';
        $flash = $flash ?? [];
        $csrf = $csrf ?? '';
        $config = $config ?? [];
        $usuario = \App\Support\Auth::usuario();

        require $rutaLayout;
    }
}
