<?php
declare(strict_types=1);

/**
 * Controlador RepartoController — Gestión del reparto manual de consumos.
 *
 * Responsabilidad:
 *  - Mostrar pantalla de reparto de una factura (factura, estancias, cuotas, ausencias).
 *  - Procesar creación de reparto (borrador o confirmado).
 *  - Validar diferencia de reparto = 0 para confirmar.
 *
 * Rutas:
 *  - r=repartos/nuevo&factura_id=X  → pantalla de reparto
 *  - r=repartos/crear                → procesar crear (POST)
 */

namespace App\Controllers;

use App\Support\Session;
use App\Support\Security;
use App\Services\RepartoService;

class RepartoController
{
    private RepartoService $service;

    public function __construct()
    {
        $this->service = new RepartoService();
    }

    /**
     * Muestra la pantalla de reparto de una factura.
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

        $facturaId = (int) ($_GET['factura_id'] ?? 0);
        if ($facturaId <= 0) {
            header('Location: ' . $config['url_base'] . '/index.php?r=facturas');
            exit;
        }

        $datos = $this->service->obtenerDatosReparto($facturaId);
        if ($datos === null) {
            Session::flash('error_reparto', 'La factura no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=facturas');
            exit;
        }

        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('repartos/nuevo.php', [
            'titulo' => 'Reparto manual de consumos',
            'factura' => $datos['factura'],
            'estancias' => $datos['estancias'],
            'reparto' => $datos['reparto'],
            'ausencias' => $datos['ausencias'],
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Procesa la creación de un reparto (borrador o confirmado).
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
            header('Location: ' . $config['url_base'] . '/index.php?r=repartos/nuevo&factura_id=' . $_POST['factura_id']);
            exit;
        }

        $usuario = \App\Support\Auth::usuario();
        $esBorrador = !empty($_POST['guardar_borrador']);

        // Recopilar cuotas desde el POST (claves: cuota_[estancia_id])
        $cuotas = [];
        foreach ($_POST as $key => $valor) {
            if (strpos($key, 'cuota_') === 0) {
                $estanciaId = substr($key, 6);
                $cuotas[$estanciaId] = $valor;
            }
        }

        $datos = [
            'factura_id' => $_POST['factura_id'] ?? '',
            'fecha_acuerdo' => $_POST['fecha_acuerdo'] ?? '',
            'autor' => $_POST['autor'] ?? '',
            'observaciones' => $_POST['observaciones'] ?? '',
            'estado' => $esBorrador ? 'borrador' : 'confirmado',
            'cuotas' => $cuotas,
        ];

        $resultado = $this->service->crear($datos, $usuario['id']);

        if ($resultado['exito']) {
            Session::flash('reparto_creado', 'Reparto guardado correctamente.', 'exito');
            header('Location: ' . $config['url_base'] . '/index.php?r=facturas/ver&id=' . $datos['factura_id']);
        } else {
            Session::flash('error_reparto', implode(' ', $resultado['errores']), 'error');
            // También pasar total_asignado y diferencia para mostrar en el formulario
            Session::flash('total_asignado', $resultado['total_asignado'] ?? 0);
            Session::flash('diferencia_pendiente', $resultado['diferencia_pendiente'] ?? 0);
            header('Location: ' . $config['url_base'] . '/index.php?r=repartos/nuevo&factura_id=' . $datos['factura_id']);
        }

        exit;
    }

    /**
     * Renderiza una vista de reparto.
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

        $titulo = $titulo ?? 'Reparto';
        $flash = $flash ?? [];
        $csrf = $csrf ?? '';
        $config = $config ?? [];
        $usuario = \App\Support\Auth::usuario();

        require $rutaLayout;
    }
}
