<?php
declare(strict_types=1);

/**
 * Controlador InformeAnualController — Informes anuales y resumen anual.
 *
 * Responsabilidad:
 *  - Mostrar el resumen anual con tres bloques separados (caja, cargos/deudas, fianzas).
 *  - Mostrar estadísticas adicionales (ocupación, ingresos por habitación, gastos por concepto, evolución mensual).
 *  - Permitir seleccionar el año del informe.
 *
 * Rutas:
 *  - r=informes/anual           → formulario selección de año
 *  - r=informes/anual/generar   → generar informe para un año
 */

namespace App\Controllers;

use App\Support\Session;
use App\Support\Security;
use App\Services\InformeAnualService;

class InformeAnualController
{
    private InformeAnualService $service;

    public function __construct()
    {
        $this->service = new InformeAnualService();
    }

    /**
     * Muestra el formulario para seleccionar el año del informe.
     *
     * @param array $config
     * @return void
     */
    public function seleccionarAnio(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $anioActual = (int) date('Y');
        $anios = range($anioActual - 5, $anioActual);
        $flash = Session::consumirTodos();
        $csrf = Session::tokenCsrf();

        $this->render('informes/anual_seleccion.php', [
            'titulo' => 'Informe anual',
            'anios' => $anios,
            'anio_actual' => $anioActual,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Genera y muestra el informe anual para un año.
     *
     * @param array $config
     * @return void
     */
    public function generar(array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $anio = (int) ($_GET['anio'] ?? date('Y'));
        if ($anio < 2020 || $anio > 2030) {
            $anio = (int) date('Y');
        }

        $informe = $this->service->generar($anio);
        $flash = Session::consumirTodos();
        $csrf = Session::tokenCsrf();

        $anioActual = (int) date('Y');
        $anios = range($anioActual - 5, $anioActual);

        $this->render('informes/anual_ver.php', [
            'titulo' => 'Informe anual ' . $anio,
            'anio' => $anio,
            'anios' => $anios,
            'informe' => $informe,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Renderiza una vista de informes.
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

        $titulo = $titulo ?? 'Informes';
        $flash = $flash ?? [];
        $csrf = $csrf ?? '';
        $config = $config ?? [];
        $usuario = \App\Support\Auth::usuario();

        require $rutaLayout;
    }
}
