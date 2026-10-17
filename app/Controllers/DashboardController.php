<?php
declare(strict_types=1);

/**
 * Controlador DashboardController — Pantalla de inicio del dashboard.
 *
 * Responsabilidad:
 *  - Mostrar el mes consultado y botones grandes de acciones principales.
 *  - Mostrar cuatro tarjetas de habitaciones con ocupante, renta, situación y cantidades pendientes.
 *  - Mostrar indicadores con periodo visible.
 *  - Mostrar gráfico sencillo de ingresos y gastos mensuales y tabla equivalente.
 *
 * Rutas:
 *  - r=dashboard → panel principal
 */

namespace App\Controllers;

use App\Support\Session;
use App\Support\Security;
use App\Services\DashboardService;

class DashboardController
{
    private DashboardService $service;

    public function __construct()
    {
        $this->service = new DashboardService();
    }

    /**
     * Muestra el dashboard principal.
     *
     * @param array $config
     * @return void
     */
    public function index(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $anio = (int) ($_GET['anio'] ?? date('Y'));
        $mes = (int) ($_GET['mes'] ?? date('m'));

        if ($mes < 1 || $mes > 12) {
            $mes = (int) date('m');
        }
        if ($anio < 2020 || $anio > 2030) {
            $anio = (int) date('Y');
        }

        $datos = $this->service->obtenerDatos($anio, $mes);
        $flash = Session::consumirTodos();
        $csrf = Session::tokenCsrf();

        $meses = $this->obtenerMeses();
        $anios = range($anio - 2, $anio + 2);

        $this->render('dashboard/index.php', [
            'titulo' => 'Dashboard',
            'anio' => $anio,
            'mes' => $mes,
            'mes_nombre' => $meses[$mes] ?? '',
            'meses' => $meses,
            'anios' => $anios,
            'habitaciones' => $datos['habitaciones'],
            'indicadores' => $datos['indicadores'],
            'grafico' => $datos['grafico'],
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
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
     * Renderiza una vista del dashboard.
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

        $titulo = $titulo ?? 'Dashboard';
        $flash = $flash ?? [];
        $csrf = $csrf ?? '';
        $config = $config ?? [];
        $usuario = \App\Support\Auth::usuario();

        require $rutaLayout;
    }
}
