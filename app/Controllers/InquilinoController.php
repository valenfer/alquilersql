<?php
declare(strict_types=1);

/**
 * Controlador InquilinoController — Gestión de inquilinos.
 *
 * Responsabilidad:
 *  - Listar inquilinos (activos y archivados).
 *  - Mostrar formulario para crear/editar un inquilino.
 *  - Validar DNI/NIE y normalizar número de documento.
 *  - Archivar un inquilino (no se elimina para conservar historial).
 *
 * Rutas:
 *  - r=inquilinos        → listar
 *  - r=inquilinos/nuevo  → formulario crear
 *  - r=inquilinos/crear  → procesar crear (POST)
 *  - r=inquilinos/editar&id=X  → formulario editar
 *  - r=inquilinos/actualizar&id=X  → procesar editar (POST)
 *  - r=inquilinos/archivar&id=X  → archivar (POST)
 */

namespace App\Controllers;

use App\Support\Session;
use App\Support\Security;
use App\Services\InquilinoService;

class InquilinoController
{
    private InquilinoService $service;

    public function __construct()
    {
        $this->service = new InquilinoService();
    }

    /**
     * Lista inquilinos.
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

        $estado = $_GET['estado'] ?? null;
        $inquilinos = $this->service->listar($estado);
        $flash = Session::consumirTodos();
        $csrf = Session::tokenCsrf();

        $this->render('inquilinos/listar.php', [
            'titulo' => 'Inquilinos',
            'inquilinos' => $inquilinos,
            'estado_actual' => $estado,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Muestra el formulario para crear un inquilino.
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

        $this->render('inquilinos/formulario.php', [
            'titulo' => 'Nuevo inquilino',
            'accion' => 'crear',
            'inquilino' => null,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa la creación de un inquilino.
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
            header('Location: ' . $config['url_base'] . '/index.php?r=inquilinos/nuevo');
            exit;
        }

        $datos = [
            'tipo_documento' => $_POST['tipo_documento'] ?? 'DNI',
            'numero_documento' => $_POST['numero_documento'] ?? '',
            'nombre' => $_POST['nombre'] ?? '',
            'apellidos' => $_POST['apellidos'] ?? '',
            'telefono' => $_POST['telefono'] ?? '',
            'email' => $_POST['email'] ?? '',
            'contacto_adicional' => $_POST['contacto_adicional'] ?? '',
            'observaciones' => $_POST['observaciones'] ?? '',
            'estado' => $_POST['estado'] ?? 'activo',
        ];

        $resultado = $this->service->crear($datos);

        if ($resultado['exito']) {
            Session::flash('inquilino_creado', 'Inquilino dado de alta correctamente.', 'exito');
            header('Location: ' . $config['url_base'] . '/index.php?r=inquilinos');
        } else {
            Session::flash('error_inquilino', implode(' ', $resultado['errores']), 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=inquilinos/nuevo');
        }

        exit;
    }

    /**
     * Muestra el formulario para editar un inquilino.
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
            header('Location: ' . $config['url_base'] . '/index.php?r=inquilinos');
            exit;
        }

        $inquilino = $this->service->obtener($id);
        if ($inquilino === null) {
            Session::flash('error_inquilino', 'El inquilino no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=inquilinos');
            exit;
        }

        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('inquilinos/formulario.php', [
            'titulo' => 'Editar inquilino',
            'accion' => 'actualizar',
            'id' => $id,
            'inquilino' => $inquilino,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa la actualización de un inquilino.
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
            header('Location: ' . $config['url_base'] . '/index.php?r=inquilinos');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . $config['url_base'] . '/index.php?r=inquilinos');
            exit;
        }

        $datos = [
            'tipo_documento' => $_POST['tipo_documento'] ?? 'DNI',
            'numero_documento' => $_POST['numero_documento'] ?? '',
            'nombre' => $_POST['nombre'] ?? '',
            'apellidos' => $_POST['apellidos'] ?? '',
            'telefono' => $_POST['telefono'] ?? '',
            'email' => $_POST['email'] ?? '',
            'contacto_adicional' => $_POST['contacto_adicional'] ?? '',
            'observaciones' => $_POST['observaciones'] ?? '',
            'estado' => $_POST['estado'] ?? 'activo',
        ];

        $resultado = $this->service->actualizar($id, $datos);

        if ($resultado['exito']) {
            Session::flash('inquilino_actualizado', 'Inquilino actualizado correctamente.', 'exito');
        } else {
            Session::flash('error_inquilino', implode(' ', $resultado['errores']), 'error');
        }

        header('Location: ' . $config['url_base'] . '/index.php?r=inquilinos');
        exit;
    }

    /**
     * Archiva un inquilino (cambia estado a 'archivado', no se elimina).
     *
     * @param array $config
     * @return void
     */
    public function archivar(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validarCsrf($token)) {
            Session::flash('csrf_error', 'Token de seguridad inválido.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=inquilinos');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . $config['url_base'] . '/index.php?r=inquilinos');
            exit;
        }

        // No se elimina físicamente, se archiva para conservar historial
        $resultado = $this->service->actualizar($id, ['estado' => 'archivado']);

        if ($resultado['exito']) {
            Session::flash('inquilino_archivado', 'Inquilino archivado. Su historial se conserva.', 'exito');
        } else {
            Session::flash('error_inquilino', 'No se pudo archivar el inquilino.', 'error');
        }

        header('Location: ' . $config['url_base'] . '/index.php?r=inquilinos');
        exit;
    }

    /**
     * Renderiza una vista de inquilinos.
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

        $titulo = $titulo ?? 'Inquilinos';
        $flash = $flash ?? [];
        $csrf = $csrf ?? '';
        $config = $config ?? [];
        $usuario = \App\Support\Auth::usuario();

        require $rutaLayout;
    }
}
