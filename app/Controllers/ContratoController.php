<?php
declare(strict_types=1);

/**
 * Controlador ContratoController — Gestión de contratos de alquiler.
 *
 * Responsabilidad:
 *  - Listar contratos con sus estados.
 *  - Crear nuevos contratos para una estancia.
 *  - Renovar contratos existentes.
 *  - Ver detalle de un contrato.
 *  - Marcar contratos como vencidos.
 *  - Mostrar alertas de vencimiento de contratos.
 *
 * Rutas:
 *  - r=contratos                    → listar
 *  - r=contratos/nuevo&estancia_id=X → formulario crear
 *  - r=contratos/crear              → procesar crear (POST)
 *  - r=contratos/ver&id=X           → detalle de contrato
 *  - r=contratos/renovar&id=X       → formulario renovar
 *  - r=contratos/procesar-renovar   → procesar renovar (POST)
 *  - r=contratos/marcar-vencido&id=X → marcar como vencido (POST)
 *  - r=contratos/alertas            → alertas de vencimiento
 */

namespace App\Controllers;

use App\Support\Session;
use App\Support\Security;
use App\Services\ContratoService;

class ContratoController
{
    private ContratoService $service;

    public function __construct()
    {
        $this->service = new ContratoService();
    }

    /**
     * Lista todos los contratos.
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

        $contratos = $this->service->listar();
        $flash = Session::consumirTodos();
        $csrf = Session::tokenCsrf();

        $this->render('contratos/listar.php', [
            'titulo' => 'Contratos de alquiler',
            'contratos' => $contratos,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Muestra el formulario para crear un contrato.
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

        $estanciaId = (int) ($_GET['estancia_id'] ?? 0);
        if ($estanciaId <= 0) {
            header('Location: ' . $config['url_base'] . '/index.php?r=estancias');
            exit;
        }

        // Obtener datos de la estancia
        $pdo = \App\Support\Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT e.id, h.numero AS habitacion_numero, h.nombre AS habitacion_nombre,
                    i.id AS inquilino_id, i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos,
                    e.renta_mensual AS renta_actual, e.fecha_entrada
             FROM estancias e
             INNER JOIN habitaciones h ON e.habitacion_id = h.id
             INNER JOIN inquilinos i ON e.inquilino_id = i.id
             WHERE e.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $estanciaId]);
        $estancia = $stmt->fetch();

        if (!$estancia) {
            Session::flash('error_contrato', 'La estancia no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=estancias');
            exit;
        }

        // Verificar si ya tiene contrato vigente
        $vigente = $this->service->obtenerVigentePorEstancia($estanciaId);
        if ($vigente !== null) {
            Session::flash('error_contrato', 'Esta estancia ya tiene un contrato vigente.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=contratos/ver&id=' . $vigente['id']);
            exit;
        }

        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('contratos/nuevo.php', [
            'titulo' => 'Nuevo contrato de alquiler',
            'estancia' => $estancia,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa la creación de un contrato.
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
            header('Location: ' . $config['url_base'] . '/index.php?r=contratos/nuevo&estancia_id=' . $_POST['estancia_id']);
            exit;
        }

        $usuario = \App\Support\Auth::usuario();
        $datos = [
            'estancia_id' => $_POST['estancia_id'] ?? '',
            'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
            'fecha_fin' => $_POST['fecha_fin'] ?? '',
            'renta_mensual' => $_POST['renta_mensual'] ?? '',
            'condiciones' => $_POST['condiciones'] ?? '',
            'clausulas_adicionales' => $_POST['clausulas_adicionales'] ?? '',
        ];

        $resultado = $this->service->crear($datos, $usuario['id']);

        if ($resultado['exito']) {
            Session::flash('contrato_creado', 'Contrato creado correctamente.', 'exito');
            header('Location: ' . $config['url_base'] . '/index.php?r=contratos/ver&id=' . $resultado['id']);
        } else {
            Session::flash('error_contrato', implode(' ', $resultado['errores']), 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=contratos/nuevo&estancia_id=' . $_POST['estancia_id']);
        }

        exit;
    }

    /**
     * Muestra el detalle de un contrato.
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
            header('Location: ' . $config['url_base'] . '/index.php?r=contratos');
            exit;
        }

        $contrato = $this->service->obtener($id);
        if ($contrato === null) {
            Session::flash('error_contrato', 'El contrato no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=contratos');
            exit;
        }

        // Verificar si tiene un contrato de renovación
        $contratoRenovado = null;
        if (isset($contrato['contrato_renovado_id'])) {
            $contratoRenovado = $this->service->obtener($contrato['contrato_renovado_id']);
        }

        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        // Calcular días restantes
        $diasRestantes = null;
        if ($contrato['fecha_fin'] !== null) {
            $diasRestantes = (int) floor((strtotime($contrato['fecha_fin']) - time()) / 86400);
        }

        $this->render('contratos/ver.php', [
            'titulo' => 'Detalle del contrato',
            'contrato' => $contrato,
            'contratoRenovado' => $contratoRenovado,
            'diasRestantes' => $diasRestantes,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Muestra el formulario para renovar un contrato.
     *
     * @param array $config
     * @return void
     */
    public function renovar(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . $config['url_base'] . '/index.php?r=contratos');
            exit;
        }

        $contrato = $this->service->obtener($id);
        if ($contrato === null) {
            Session::flash('error_contrato', 'El contrato no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=contratos');
            exit;
        }

        if ($contrato['estado'] !== 'vigente') {
            Session::flash('error_contrato', 'Solo se pueden renovar contratos vigentes.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=contratos/ver&id=' . $id);
            exit;
        }

        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('contratos/renovar.php', [
            'titulo' => 'Renovar contrato',
            'contrato' => $contrato,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa la renovación de un contrato.
     *
     * @param array $config
     * @return void
     */
    public function procesarRenovar(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validarCsrf($token)) {
            Session::flash('csrf_error', 'Token de seguridad inválido.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=contratos/renovar&id=' . $_POST['contrato_id']);
            exit;
        }

        $usuario = \App\Support\Auth::usuario();
        $datos = [
            'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
            'fecha_fin' => $_POST['fecha_fin'] ?? '',
            'renta_mensual' => $_POST['renta_mensual'] ?? '',
            'condiciones' => $_POST['condiciones'] ?? '',
            'clausulas_adicionales' => $_POST['clausulas_adicionales'] ?? '',
            'documento_adjunto_id' => $_POST['documento_adjunto_id'] ?? null,
        ];

        $resultado = $this->service->renovar((int) ($_POST['contrato_id'] ?? 0), $datos, $usuario['id']);

        if ($resultado['exito']) {
            Session::flash('contrato_renovado', 'Contrato renovado correctamente. El contrato anterior se marcó como renovado.', 'exito');
            header('Location: ' . $config['url_base'] . '/index.php?r=contratos/ver&id=' . $resultado['id']);
        } else {
            Session::flash('error_contrato', implode(' ', $resultado['errores']), 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=contratos/renovar&id=' . $_POST['contrato_id']);
        }

        exit;
    }

    /**
     * Marca un contrato como vencido.
     *
     * @param array $config
     * @return void
     */
    public function marcarVencido(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validarCsrf($token)) {
            Session::flash('csrf_error', 'Token de seguridad inválido.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=contratos/ver&id=' . $_POST['contrato_id']);
            exit;
        }

        $resultado = $this->service->marcarVencido((int) ($_POST['contrato_id'] ?? 0));

        if ($resultado['exito']) {
            Session::flash('contrato_vencido', 'Contrato marcado como vencido.', 'exito');
        } else {
            Session::flash('error_contrato', implode(' ', $resultado['errores']), 'error');
        }

        header('Location: ' . $config['url_base'] . '/index.php?r=contratos/ver&id=' . $_POST['contrato_id']);
        exit;
    }

    /**
     * Muestra las alertas de vencimiento de contratos.
     *
     * @param array $config
     * @return void
     */
    public function alertas(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $alertas = $this->service->obtenerAlertasVencimiento();
        $flash = Session::consumirTodos();
        $csrf = Session::tokenCsrf();

        $this->render('contratos/alertas.php', [
            'titulo' => 'Alertas de vencimiento de contratos',
            'alertas' => $alertas,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Renderiza una vista de contratos.
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

        $titulo = $titulo ?? 'Contratos';
        $flash = $flash ?? [];
        $csrf = $csrf ?? '';
        $config = $config ?? [];
        $usuario = \App\Support\Auth::usuario();

        require $rutaLayout;
    }
}
