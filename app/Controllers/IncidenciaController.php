<?php
declare(strict_types=1);

/**
 * Controlador IncidenciaController — Gestión de incidencias (averías/reparaciones).
 *
 * Responsabilidad:
 *  - Listar incidencias con filtros.
 *  - Crear nueva incidencia.
 *  - Actualizar estado (pendiente/en curso/resuelta).
 *
 * Rutas:
 *  - r=incidencias                    → listar
 *  - r=incidencias/nuevo              → formulario crear
 *  - r=incidencias/crear              → procesar crear (POST)
 *  - r=incidencias/estado&id=X        → formulario cambiar estado
 *  - r=incidencias/procesar-estado    → procesar cambio de estado (POST)
 */

namespace App\Controllers;

use App\Support\Session;
use App\Support\Security;
use App\Services\IncidenciaService;

class IncidenciaController
{
    private IncidenciaService $service;

    public function __construct()
    {
        $this->service = new IncidenciaService();
    }

    /**
     * Lista incidencias con filtros.
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
        if (!empty($_GET['estado'])) {
            $filtros['estado'] = $_GET['estado'];
        }
        if (!empty($_GET['habitacion_id'])) {
            $filtros['habitacion_id'] = (int) $_GET['habitacion_id'];
        }

        $incidencias = $this->service->listar($filtros);
        $habitaciones = $this->obtenerHabitaciones();
        $flash = Session::consumirTodos();
        $csrf = Session::tokenCsrf();

        $this->render('incidencias/listar.php', [
            'titulo' => 'Incidencias y averías',
            'incidencias' => $incidencias,
            'habitaciones' => $habitaciones,
            'filtros' => $filtros,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Muestra el formulario para crear una incidencia.
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

        $habitaciones = $this->obtenerHabitaciones();
        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('incidencias/nuevo.php', [
            'titulo' => 'Nueva incidencia',
            'habitaciones' => $habitaciones,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa la creación de una incidencia.
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
            header('Location: ' . $config['url_base'] . '/index.php?r=incidencias/nuevo');
            exit;
        }

        $resultado = $this->service->crear([
            'habitacion_id' => $_POST['habitacion_id'] ?? '',
            'descripcion' => $_POST['descripcion'] ?? '',
            'fecha_aviso' => $_POST['fecha_aviso'] ?? '',
            'estado' => $_POST['estado'] ?? 'pendiente',
            'fecha_resolucion' => $_POST['fecha_resolucion'] ?? '',
        ]);

        if ($resultado['exito']) {
            Session::flash('incidencia_creada', 'Incidencia registrada correctamente.', 'exito');
            header('Location: ' . $config['url_base'] . '/index.php?r=incidencias');
        } else {
            Session::flash('error_incidencia', implode(' ', $resultado['errores']), 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=incidencias/nuevo');
        }

        exit;
    }

    /**
     * Muestra el formulario para cambiar el estado de una incidencia.
     *
     * @param array $config
     * @return void
     */
    public function estado(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . $config['url_base'] . '/index.php?r=incidencias');
            exit;
        }

        $pdo = \App\Support\Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT i.*, h.numero AS habitacion_numero
             FROM incidencias i
             LEFT JOIN habitaciones h ON i.habitacion_id = h.id
             WHERE i.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $incidencia = $stmt->fetch();

        if (!$incidencia) {
            Session::flash('error_incidencia', 'La incidencia no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=incidencias');
            exit;
        }

        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('incidencias/estado.php', [
            'titulo' => 'Cambiar estado de incidencia',
            'incidencia' => $incidencia,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa el cambio de estado de una incidencia.
     *
     * @param array $config
     * @return void
     */
    public function procesarEstado(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validarCsrf($token)) {
            Session::flash('csrf_error', 'Token de seguridad inválido.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=incidencias/estado&id=' . $_POST['id']);
            exit;
        }

        $fechaResolucion = null;
        if ($_POST['estado'] === 'resuelta' && !empty($_POST['fecha_resolucion'])) {
            $fechaResolucion = $_POST['fecha_resolucion'];
        }

        $resultado = $this->service->actualizarEstado(
            (int) ($_POST['id'] ?? 0),
            $_POST['estado'] ?? 'pendiente',
            $fechaResolucion
        );

        if ($resultado['exito']) {
            Session::flash('incidencia_actualizada', 'Estado de la incidencia actualizado.', 'exito');
        } else {
            Session::flash('error_incidencia', implode(' ', $resultado['errores']), 'error');
        }

        header('Location: ' . $config['url_base'] . '/index.php?r=incidencias');
        exit;
    }

    /**
     * Obtiene lista de habitaciones.
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
     * Renderiza una vista de incidencias.
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

        $titulo = $titulo ?? 'Incidencias';
        $flash = $flash ?? [];
        $csrf = $csrf ?? '';
        $config = $config ?? [];
        $usuario = \App\Support\Auth::usuario();

        require $rutaLayout;
    }
}
