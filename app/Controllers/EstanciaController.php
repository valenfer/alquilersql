<?php
declare(strict_types=1);

/**
 * Controlador EstanciaController — Gestión de estancias (ocupaciones).
 *
 * Responsabilidad:
 *  - Listar estancias con filtros por habitación, inquilino y estado.
 *  - Mostrar formulario para crear/editar una estancia.
 *  - Prevenir solapamientos de ocupación (transaccional).
 *  - Finalizar una estancia (fecha de salida efectiva).
 *
 * Rutas:
 *  - r=estancias        → listar
 *  - r=estancias/nuevo  → formulario crear
 *  - r=estancias/crear  → procesar crear (POST)
 *  - r=estancias/editar&id=X  → formulario editar
 *  - r=estancias/actualizar&id=X  → procesar editar (POST)
 *  - r=estancias/finalizar&id=X  → finalizar (POST)
 */

namespace App\Controllers;

use App\Support\Session;
use App\Support\Security;
use App\Services\EstanciaService;

class EstanciaController
{
    private EstanciaService $service;

    public function __construct()
    {
        $this->service = new EstanciaService();
    }

    /**
     * Lista estancias con filtros opcionales.
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
        if (!empty($_GET['habitacion_id'])) {
            $filtros['habitacion_id'] = (int) $_GET['habitacion_id'];
        }
        if (!empty($_GET['inquilino_id'])) {
            $filtros['inquilino_id'] = (int) $_GET['inquilino_id'];
        }
        if (!empty($_GET['estado'])) {
            $filtros['estado'] = $_GET['estado'];
        }

        $estancias = $this->service->listar($filtros);
        $flash = Session::consumirTodos();
        $csrf = Session::tokenCsrf();

        // Listas para los filtros de habitaciones e inquilinos
        $pdo = \App\Support\Database::obtenerConexion();
        $stmtHab = $pdo->query('SELECT id, nombre FROM habitaciones ORDER BY numero');
        $habitaciones = $stmtHab->fetchAll();
        $stmtInq = $pdo->query('SELECT id, nombre, apellidos FROM inquilinos WHERE estado = 'activa' ORDER BY apellidos, nombre');
        $inquilinos = $stmtInq->fetchAll();

        $this->render('estancias/listar.php', [
            'titulo' => 'Estancias y ocupaciones',
            'estancias' => $estancias,
            'filtros' => $filtros,
            'habitaciones' => $habitaciones,
            'inquilinos' => $inquilinos,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Muestra el formulario para crear una estancia.
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

        $inquilinos = $this->obtenerInquilinos();
        $habitaciones = $this->obtenerHabitaciones();
        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('estancias/formulario.php', [
            'titulo' => 'Nueva estancia',
            'accion' => 'crear',
            'estancia' => null,
            'inquilinos' => $inquilinos,
            'habitaciones' => $habitaciones,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa la creación de una estancia (transaccional).
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
            header('Location: ' . $config['url_base'] . '/index.php?r=estancias/nuevo');
            exit;
        }

        $usuario = \App\Support\Auth::usuario();
        $datos = [
            'inquilino_id' => $_POST['inquilino_id'] ?? '',
            'habitacion_id' => $_POST['habitacion_id'] ?? '',
            'fecha_entrada' => $_POST['fecha_entrada'] ?? '',
            'fecha_salida_prevista' => $_POST['fecha_salida_prevista'] ?? '',
            'fecha_salida_efectiva' => $_POST['fecha_salida_efectiva'] ?? '',
            'renta_mensual' => $_POST['renta_mensual'] ?? '',
            'dia_vencimiento' => $_POST['dia_vencimiento'] ?? 1,
            'fianza_pactada' => $_POST['fianza_pactada'] ?? 0,
            'referencia_contrato' => $_POST['referencia_contrato'] ?? '',
        ];

        $resultado = $this->service->crear($datos, $usuario['id']);

        if ($resultado['exito']) {
            Session::flash('estancia_creada', 'Estancia creada correctamente.', 'exito');
            header('Location: ' . $config['url_base'] . '/index.php?r=estancias');
        } else {
            Session::flash('error_estancia', implode(' ', $resultado['errores']), 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=estancias/nuevo');
        }

        exit;
    }

    /**
     * Muestra el formulario para editar una estancia.
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
            header('Location: ' . $config['url_base'] . '/index.php?r=estancias');
            exit;
        }

        $estancia = $this->service->obtener($id);
        if ($estancia === null) {
            Session::flash('error_estancia', 'La estancia no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=estancias');
            exit;
        }

        $inquilinos = $this->obtenerInquilinos();
        $habitaciones = $this->obtenerHabitaciones();
        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('estancias/formulario.php', [
            'titulo' => 'Editar estancia',
            'accion' => 'actualizar',
            'id' => $id,
            'estancia' => $estancia,
            'inquilinos' => $inquilinos,
            'habitaciones' => $habitaciones,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa la actualización de una estancia (transaccional).
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
            header('Location: ' . $config['url_base'] . '/index.php?r=estancias');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . $config['url_base'] . '/index.php?r=estancias');
            exit;
        }

        $usuario = \App\Support\Auth::usuario();
        $datos = [
            'inquilino_id' => $_POST['inquilino_id'] ?? '',
            'habitacion_id' => $_POST['habitacion_id'] ?? '',
            'fecha_entrada' => $_POST['fecha_entrada'] ?? '',
            'fecha_salida_prevista' => $_POST['fecha_salida_prevista'] ?? '',
            'fecha_salida_efectiva' => $_POST['fecha_salida_efectiva'] ?? '',
            'renta_mensual' => $_POST['renta_mensual'] ?? '',
            'dia_vencimiento' => $_POST['dia_vencimiento'] ?? 1,
            'fianza_pactada' => $_POST['fianza_pactada'] ?? 0,
            'referencia_contrato' => $_POST['referencia_contrato'] ?? '',
        ];

        $resultado = $this->service->actualizar($id, $datos, $usuario['id']);

        if ($resultado['exito']) {
            Session::flash('estancia_actualizada', 'Estancia actualizada correctamente.', 'exito');
        } else {
            Session::flash('error_estancia', implode(' ', $resultado['errores']), 'error');
        }

        header('Location: ' . $config['url_base'] . '/index.php?r=estancias');
        exit;
    }

    /**
     * Finaliza una estancia estableciendo la fecha de salida efectiva.
     *
     * @param array $config
     * @return void
     */
    public function finalizar(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validarCsrf($token)) {
            Session::flash('csrf_error', 'Token de seguridad inválido.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=estancias');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $fechaSalida = $_POST['fecha_salida_efectiva'] ?? '';

        if ($id <= 0 || $fechaSalida === '') {
            Session::flash('error_estancia', 'Datos incompletos para finalizar la estancia.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=estancias');
            exit;
        }

        $resultado = $this->service->finalizar($id, $fechaSalida);

        if ($resultado['exito']) {
            Session::flash('estancia_finalizada', 'Estancia finalizada correctamente.', 'exito');
        } else {
            Session::flash('error_estancia', $resultado['error'], 'error');
        }

        header('Location: ' . $config['url_base'] . '/index.php?r=estancias');
        exit;
    }

    /**
     * Obtiene lista de inquilinos activos para selectores.
     *
     * @return array
     */
    private function obtenerInquilinos(): array
    {
        $pdo = \App\Support\Database::obtenerConexion();
        $stmt = $pdo->query(
            'SELECT id, nombre, apellidos, numero_documento FROM inquilinos WHERE estado = 'activa' ORDER BY apellidos, nombre'
        );
        return $stmt->fetchAll();
    }

    /**
     * Obtiene lista de habitaciones para selectores.
     *
     * @return array
     */
    private function obtenerHabitaciones(): array
    {
        $pdo = \App\Support\Database::obtenerConexion();
        $stmt = $pdo->query('SELECT id, numero, nombre, estado FROM habitaciones ORDER BY numero');
        return $stmt->fetchAll();
    }

    /**
     * Renderiza una vista de estancias.
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

        $titulo = $titulo ?? 'Estancias';
        $flash = $flash ?? [];
        $csrf = $csrf ?? '';
        $config = $config ?? [];
        $usuario = \App\Support\Auth::usuario();

        require $rutaLayout;
    }
}
