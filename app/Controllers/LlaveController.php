<?php
declare(strict_types=1);

/**
 * Controlador LlaveController — Gestión de llaves entregadas y devueltas.
 *
 * Responsabilidad:
 *  - Listar tipos de llave y unidades.
 *  - Mostrar movimientos de llave por estancia con pendiente calculado.
 *  - Entregar, devolver, perder y reponer llaves.
 *  - Impedir devoluciones superiores a lo pendiente y unidades duplicadas.
 *
 * Rutas:
 *  - r=llaves                    → listar unidades
 *  - r=llaves/estancia&id=X      → movimientos de una estancia
 *  - r=llaves/entregar&estancia_id=X → formulario entregar
 *  - r=llaves/procesar-entregar  → procesar entrega (POST)
 *  - r=llaves/devolver&estancia_id=X → formulario devolver
 *  - r=llaves/procesar-devolver  → procesar devolución (POST)
 *  - r=llaves/perder&estancia_id=X → formulario pérdida
 *  - r=llaves/procesar-perder    → procesar pérdida (POST)
 *  - r=llaves/reponer&estancia_id=X → formulario reposición
 *  - r=llaves/procesar-reponer   → procesar reposición (POST)
 */

namespace App\Controllers;

use App\Support\Session;
use App\Support\Security;
use App\Services\LlaveService;

class LlaveController
{
    private LlaveService $service;

    public function __construct()
    {
        $this->service = new LlaveService();
    }

    /**
     * Lista unidades de llave y tipos.
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

        $llaves = $this->service->listarLlaves();
        $tipos = $this->service->listarTipos();
        $flash = Session::consumirTodos();
        $csrf = Session::tokenCsrf();

        $this->render('llaves/listar.php', [
            'titulo' => 'Llaves',
            'llaves' => $llaves,
            'tipos' => $tipos,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Muestra movimientos de llave de una estancia.
     *
     * @param array $config
     * @return void
     */
    public function estancia(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $estanciaId = (int) ($_GET['id'] ?? 0);
        if ($estanciaId <= 0) {
            header('Location: ' . $config['url_base'] . '/index.php?r=estancias');
            exit;
        }

        // Obtener datos de la estancia
        $pdo = \App\Support\Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT e.id, h.numero AS habitacion_numero,
                    i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos
             FROM estancias e
             INNER JOIN habitaciones h ON e.habitacion_id = h.id
             INNER JOIN inquilinos i ON e.inquilino_id = i.id
             WHERE e.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $estanciaId]);
        $estancia = $stmt->fetch();

        if (!$estancia) {
            Session::flash('error_llave', 'La estancia no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=estancias');
            exit;
        }

        $datos = $this->service->obtenerMovimientosEstancia($estanciaId);
        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('llaves/estancia.php', [
            'titulo' => 'Llaves — ' . Security::html($estancia['habitacion_numero']),
            'estancia' => $estancia,
            'movimientos' => $datos['movimientos'],
            'pendientes' => $datos['pendientes'],
            'tipos' => $this->service->listarTipos(),
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Muestra el formulario para entregar una llave.
     *
     * @param array $config
     * @return void
     */
    public function entregar(array $config): void
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

        $tipos = $this->service->listarTipos();
        $llavesDisponibles = $this->obtenerLlavesDisponibles();
        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('llaves/entregar.php', [
            'titulo' => 'Entregar llave',
            'estancia_id' => $estanciaId,
            'tipos' => $tipos,
            'llaves_disponibles' => $llavesDisponibles,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa la entrega de una llave.
     *
     * @param array $config
     * @return void
     */
    public function procesarEntregar(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validarCsrf($token)) {
            Session::flash('csrf_error', 'Token de seguridad inválido.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=llaves/entregar&estancia_id=' . $_POST['estancia_id']);
            exit;
        }

        $usuario = \App\Support\Auth::usuario();
        $llaveId = !empty($_POST['llave_id']) ? (int) $_POST['llave_id'] : null;

        $resultado = $this->service->entregar(
            (int) ($_POST['estancia_id'] ?? 0),
            $llaveId,
            (int) ($_POST['cantidad'] ?? 1),
            $_POST['fecha'] ?? '',
            $_POST['observaciones'] ?? null,
            $usuario['id']
        );

        if ($resultado['exito']) {
            Session::flash('llave_entregada', 'Llave entregada correctamente.', 'exito');
        } else {
            Session::flash('error_llave', implode(' ', $resultado['errores']), 'error');
        }

        header('Location: ' . $config['url_base'] . '/index.php?r=llaves/estancia&id=' . $_POST['estancia_id']);
        exit;
    }

    /**
     * Muestra el formulario para devolver una llave.
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

        $estanciaId = (int) ($_GET['estancia_id'] ?? 0);
        if ($estanciaId <= 0) {
            header('Location: ' . $config['url_base'] . '/index.php?r=estancias');
            exit;
        }

        $tipos = $this->service->listarTipos();
        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('llaves/devolver.php', [
            'titulo' => 'Devolver llave',
            'estancia_id' => $estanciaId,
            'tipos' => $tipos,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa la devolución de una llave.
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
            header('Location: ' . $config['url_base'] . '/index.php?r=llaves/devolver&estancia_id=' . $_POST['estancia_id']);
            exit;
        }

        $usuario = \App\Support\Auth::usuario();
        $llaveId = !empty($_POST['llave_id']) ? (int) $_POST['llave_id'] : null;

        $resultado = $this->service->devolver(
            (int) ($_POST['estancia_id'] ?? 0),
            $llaveId,
            (int) ($_POST['cantidad'] ?? 1),
            $_POST['fecha'] ?? '',
            $_POST['observaciones'] ?? null,
            $usuario['id']
        );

        if ($resultado['exito']) {
            Session::flash('llave_devuelta', 'Llave devuelta correctamente.', 'exito');
        } else {
            Session::flash('error_llave', implode(' ', $resultado['errores']), 'error');
        }

        header('Location: ' . $config['url_base'] . '/index.php?r=llaves/estancia&id=' . $_POST['estancia_id']);
        exit;
    }

    /**
     * Muestra el formulario para registrar una pérdida.
     *
     * @param array $config
     * @return void
     */
    public function perder(array $config): void
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

        $tipos = $this->service->listarTipos();
        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('llaves/perder.php', [
            'titulo' => 'Registrar pérdida de llave',
            'estancia_id' => $estanciaId,
            'tipos' => $tipos,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa el registro de una pérdida.
     *
     * @param array $config
     * @return void
     */
    public function procesarPerder(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validarCsrf($token)) {
            Session::flash('csrf_error', 'Token de seguridad inválido.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=llaves/perder&estancia_id=' . $_POST['estancia_id']);
            exit;
        }

        $usuario = \App\Support\Auth::usuario();
        $llaveId = !empty($_POST['llave_id']) ? (int) $_POST['llave_id'] : null;

        $resultado = $this->service->perder(
            (int) ($_POST['estancia_id'] ?? 0),
            $llaveId,
            (int) ($_POST['cantidad'] ?? 1),
            $_POST['fecha'] ?? '',
            $_POST['observaciones'] ?? null,
            $usuario['id']
        );

        if ($resultado['exito']) {
            Session::flash('llave_perdida', 'Pérdida de llave registrada.', 'exito');
        } else {
            Session::flash('error_llave', implode(' ', $resultado['errores']), 'error');
        }

        header('Location: ' . $config['url_base'] . '/index.php?r=llaves/estancia&id=' . $_POST['estancia_id']);
        exit;
    }

    /**
     * Muestra el formulario para reponer una llave.
     *
     * @param array $config
     * @return void
     */
    public function reponer(array $config): void
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

        $tipos = $this->service->listarTipos();
        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('llaves/reponer.php', [
            'titulo' => 'Reponer llave',
            'estancia_id' => $estanciaId,
            'tipos' => $tipos,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa la reposición de una llave.
     *
     * @param array $config
     * @return void
     */
    public function procesarReponer(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validarCsrf($token)) {
            Session::flash('csrf_error', 'Token de seguridad inválido.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=llaves/reponer&estancia_id=' . $_POST['estancia_id']);
            exit;
        }

        $usuario = \App\Support\Auth::usuario();
        $llaveId = !empty($_POST['llave_id']) ? (int) $_POST['llave_id'] : null;

        $resultado = $this->service->reponer(
            (int) ($_POST['estancia_id'] ?? 0),
            $llaveId,
            (int) ($_POST['cantidad'] ?? 1),
            $_POST['fecha'] ?? '',
            $_POST['observaciones'] ?? null,
            $usuario['id']
        );

        if ($resultado['exito']) {
            Session::flash('llave_repuesta', 'Llave repuesta correctamente.', 'exito');
        } else {
            Session::flash('error_llave', implode(' ', $resultado['errores']), 'error');
        }

        header('Location: ' . $config['url_base'] . '/index.php?r=llaves/estancia&id=' . $_POST['estancia_id']);
        exit;
    }

    /**
     * Obtiene unidades de llave disponibles para entregar.
     *
     * @return array
     */
    private function obtenerLlavesDisponibles(): array
    {
        $pdo = \App\Support\Database::obtenerConexion();
        $stmt = $pdo->query(
            'SELECT l.*, t.nombre AS tipo_nombre
             FROM llaves l
             INNER JOIN tipos_llave t ON l.tipo_llave_id = t.id
             WHERE l.estado = 'disponible'
             ORDER BY t.nombre, l.identificador'
        );
        return $stmt->fetchAll();
    }

    /**
     * Renderiza una vista de llaves.
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

        $titulo = $titulo ?? 'Llaves';
        $flash = $flash ?? [];
        $csrf = $csrf ?? '';
        $config = $config ?? [];
        $usuario = \App\Support\Auth::usuario();

        require $rutaLayout;
    }
}
