<?php
declare(strict_types=1);

/**
 * Controlador HabitacionController — Gestión de habitaciones.
 *
 * Responsabilidad:
 *  - Listar las cuatro habitaciones del piso.
 *  - Mostrar formulario para crear/editar una habitación.
 *  - Procesar creación y actualización con validación del servicio.
 *  - Eliminar una habitación solo si no tiene historial.
 *
 * Rutas:
 *  - r=habitaciones        → listar
 *  - r=habitaciones/nuevo  → formulario crear
 *  - r=habitaciones/crear  → procesar crear (POST)
 *  - r=habitaciones/editar&id=X  → formulario editar
 *  - r=habitaciones/actualizar&id=X  → procesar editar (POST)
 *  - r=habitaciones/eliminar&id=X  → eliminar (POST)
 */

namespace App\Controllers;

use App\Support\Session;
use App\Support\Security;
use App\Services\HabitacionService;

class HabitacionController
{
    private HabitacionService $service;

    public function __construct()
    {
        $this->service = new HabitacionService();
    }

    /**
     * Lista todas las habitaciones.
     *
     * @param array $config Configuración de la aplicación
     * @return void
     */
    public function listar(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $habitaciones = $this->service->listar();
        $flash = Session::consumirTodos();
        $csrf = Session::tokenCsrf();

        $this->render('habitaciones/listar.php', [
            'titulo' => 'Habitaciones del piso',
            'habitaciones' => $habitaciones,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Muestra el formulario para crear una habitación.
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

        $total = $this->contarHabitaciones();
        $puedeCrear = $total < 4;
        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('habitaciones/formulario.php', [
            'titulo' => 'Nueva habitación',
            'accion' => 'crear',
            'habitacion' => null,
            'puedeCrear' => $puedeCrear,
            'total' => $total,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa la creación de una habitación.
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

        // Validar CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validarCsrf($token)) {
            Session::flash('csrf_error', 'Token de seguridad inválido.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=habitaciones/nuevo');
            exit;
        }

        $datos = [
            'numero' => $_POST['numero'] ?? '',
            'nombre' => $_POST['nombre'] ?? '',
            'descripcion' => $_POST['descripcion'] ?? '',
            'renta_orientativa' => $_POST['renta_orientativa'] ?? '',
            'estado' => $_POST['estado'] ?? 'libre',
        ];

        $resultado = $this->service->crear($datos);

        if ($resultado['exito']) {
            Session::flash('habitacion_creada', 'Habitación creada correctamente.', 'exito');
            header('Location: ' . $config['url_base'] . '/index.php?r=habitaciones');
        } else {
            Session::flash('error_habitacion', implode(' ', $resultado['errores']), 'error');
            // Volver al formulario con los datos (se perderán, pero el mensaje de error guiará)
            header('Location: ' . $config['url_base'] . '/index.php?r=habitaciones/nuevo');
        }

        exit;
    }

    /**
     * Muestra el formulario para editar una habitación.
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
            header('Location: ' . $config['url_base'] . '/index.php?r=habitaciones');
            exit;
        }

        $habitacion = $this->service->obtener($id);
        if ($habitacion === null) {
            Session::flash('error_habitacion', 'La habitación no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=habitaciones');
            exit;
        }

        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('habitaciones/formulario.php', [
            'titulo' => 'Editar habitación',
            'accion' => 'actualizar',
            'id' => $id,
            'habitacion' => $habitacion,
            'puedeCrear' => true,
            'total' => 4,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa la actualización de una habitación.
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
            header('Location: ' . $config['url_base'] . '/index.php?r=habitaciones');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . $config['url_base'] . '/index.php?r=habitaciones');
            exit;
        }

        $datos = [
            'numero' => $_POST['numero'] ?? '',
            'nombre' => $_POST['nombre'] ?? '',
            'descripcion' => $_POST['descripcion'] ?? '',
            'renta_orientativa' => $_POST['renta_orientativa'] ?? '',
            'estado' => $_POST['estado'] ?? 'libre',
        ];

        $resultado = $this->service->actualizar($id, $datos);

        if ($resultado['exito']) {
            Session::flash('habitacion_actualizada', 'Habitación actualizada correctamente.', 'exito');
        } else {
            Session::flash('error_habitacion', implode(' ', $resultado['errores']), 'error');
        }

        header('Location: ' . $config['url_base'] . '/index.php?r=habitaciones');
        exit;
    }

    /**
     * Elimina una habitación si no tiene historial.
     *
     * @param array $config
     * @return void
     */
    public function eliminar(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validarCsrf($token)) {
            Session::flash('csrf_error', 'Token de seguridad inválido.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=habitaciones');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . $config['url_base'] . '/index.php?r=habitaciones');
            exit;
        }

        $resultado = $this->service->eliminar($id);

        if ($resultado['exito']) {
            Session::flash('habitacion_eliminada', 'Habitación eliminada correctamente.', 'exito');
        } else {
            Session::flash('error_habitacion', $resultado['error'], 'error');
        }

        header('Location: ' . $config['url_base'] . '/index.php?r=habitaciones');
        exit;
    }

    /**
     * Cuenta el número total de habitaciones.
     *
     * @return int
     */
    private function contarHabitaciones(): int
    {
        $pdo = \App\Support\Database::obtenerConexion();
        $stmt = $pdo->query('SELECT COUNT(*) AS total FROM habitaciones');
        return (int) $stmt->fetch()['total'];
    }

    /**
     * Renderiza una vista de habitaciones.
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

        $titulo = $titulo ?? 'Habitaciones';
        $flash = $flash ?? [];
        $csrf = $csrf ?? '';
        $config = $config ?? [];
        $usuario = \App\Support\Auth::usuario();

        require $rutaLayout;
    }
}
