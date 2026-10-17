<?php
declare(strict_types=1);

/**
 * Controlador ComprobacionFacturaController — Comprobación de pagos por factura.
 *
 * Responsabilidad:
 *  - Mostrar pantalla de comprobación por factura (cuadre de reparto y cuadre del dinero).
 *  - Permitir filtrar y ver detalle de cobros por destinatario.
 *
 * Rutas:
 *  - r=comprobacion/ver&factura_id=X  → pantalla de comprobación
 */

namespace App\Controllers;

use App\Support\Session;
use App\Support\Security;
use App\Services\ComprobacionFacturaService;

class ComprobacionFacturaController
{
    private ComprobacionFacturaService $service;

    public function __construct()
    {
        $this->service = new ComprobacionFacturaService();
    }

    /**
     * Muestra la pantalla de comprobación de una factura.
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

        $facturaId = (int) ($_GET['factura_id'] ?? 0);
        if ($facturaId <= 0) {
            header('Location: ' . $config['url_base'] . '/index.php?r=facturas');
            exit;
        }

        $detalle = $this->service->obtenerDetalle($facturaId);
        if ($detalle === null) {
            Session::flash('error_comprobacion', 'La factura no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=facturas');
            exit;
        }

        $flash = Session::consumirTodos();
        $csrf = Session::tokenCsrf();

        $this->render('comprobacion/ver.php', [
            'titulo' => 'Comprobar pagos de esta factura',
            'detalle' => $detalle,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Renderiza una vista de comprobación.
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

        $titulo = $titulo ?? 'Comprobación';
        $flash = $flash ?? [];
        $csrf = $csrf ?? '';
        $config = $config ?? [];
        $usuario = \App\Support\Auth::usuario();

        require $rutaLayout;
    }
}
