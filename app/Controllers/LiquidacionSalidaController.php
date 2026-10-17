<?php
declare(strict_types=1);

/**
 * Controlador LiquidacionSalidaController — Liquidación individual de salida.
 *
 * Responsabilidad:
 *  - Mostrar liquidación imprimible con deudas por concepto, fianza recibida,
 *    devoluciones previas, compensaciones, saldo y llaves pendientes.
 *  - Permitir dejar la liquidación abierta si faltan facturas de suministros.
 *
 * Rutas:
 *  - r=liquidacion/estancia&id=X  → liquidación de una estancia
 */

namespace App\Controllers;

use App\Support\Session;
use App\Support\Security;
use App\Support\Database;

class LiquidacionSalidaController
{
    /**
     * Muestra la liquidación de salida de una estancia.
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

        $pdo = Database::obtenerConexion();

        // Obtener datos de la estancia
        $stmt = $pdo->prepare(
            'SELECT e.*, h.numero AS habitacion_numero, h.nombre AS habitacion_nombre,
                    i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos,
                    i.numero_documento AS inquilino_documento
             FROM estancias e
             INNER JOIN habitaciones h ON e.habitacion_id = h.id
             INNER JOIN inquilinos i ON e.inquilino_id = i.id
             WHERE e.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $estanciaId]);
        $estancia = $stmt->fetch();

        if (!$estancia) {
            Session::flash('error_liquidacion', 'La estancia no existe.', 'error');
            header('Location: ' . $config['url_base'] . '/index.php?r=estancias');
            exit;
        }

        // Deudas de alquiler pendientes
        $stmt = $pdo->prepare(
            "SELECT c.id, c.anio, c.mes, c.importe_esperado, c.importe_cobrado,
                    ROUND(c.importe_esperado - c.importe_cobrado, 2) AS saldo,
                    c.fecha_vencimiento, c.estado
             FROM cargos_alquiler c
             WHERE c.estancia_id = :estancia_id
               AND c.estado IN ('pendiente', 'parcial')
             ORDER BY c.anio, c.mes"
        );
        $stmt->execute([':estancia_id' => $estanciaId]);
        $deudasAlquiler = $stmt->fetchAll();
        $totalDeudaAlquiler = 0.0;
        foreach ($deudasAlquiler as $d) {
            $totalDeudaAlquiler = round($totalDeudaAlquiler + (float) $d['saldo'], 2);
        }

        // Deudas de suministros (cuotas de reparto pendientes)
        $stmt = $pdo->prepare(
            "SELECT cu.id, f.tipo, f.proveedor, f.numero_factura, f.periodo_desde, f.periodo_hasta,
                    cu.importe AS cuota,
                    COALESCE(SUM(a.importe), 0) AS cobrado,
                    ROUND(cu.importe - COALESCE(SUM(a.importe), 0), 2) AS saldo
             FROM cuotas_reparto cu
             INNER JOIN repartos_factura r ON cu.reparto_id = r.id AND r.estado = 'confirmado'
             INNER JOIN facturas_suministro f ON r.factura_id = f.id
             LEFT JOIN aplicaciones_cobro a ON a.cuota_reparto_id = cu.id
             WHERE cu.estancia_id = :estancia_id
             GROUP BY cu.id
             HAVING saldo > 0
             ORDER BY f.periodo_desde"
        );
        $stmt->execute([':estancia_id' => $estanciaId]);
        $deudasSuministros = $stmt->fetchAll();
        $totalDeudaSuministros = 0.0;
        foreach ($deudasSuministros as $d) {
            $totalDeudaSuministros = round($totalDeudaSuministros + (float) $d['saldo'], 2);
        }

        // Fianza
        $stmt = $pdo->prepare(
            "SELECT f.*, COALESCE(SUM(m.importe), 0) AS recibidas,
                    COALESCE(SUM(CASE WHEN m.tipo = 'devolucion' THEN m.importe ELSE 0 END), 0) AS devueltas,
                    COALESCE(SUM(CASE WHEN m.tipo = 'aplicacion_deuda' THEN m.importe ELSE 0 END), 0) AS aplicadas,
                    COALESCE(SUM(CASE WHEN m.tipo = 'compensacion_danos' THEN m.importe ELSE 0 END), 0) AS compensaciones
             FROM fianzas f
             LEFT JOIN movimientos_fianza m ON f.id = m.fianza_id
             WHERE f.estancia_id = :estancia_id
             GROUP BY f.id"
        );
        $stmt->execute([':estancia_id' => $estanciaId]);
        $fianza = $stmt->fetch();
        $saldoFianza = $fianza ? round((float) $fianza['importe_recibido'] - (float) $fianza['importe_devuelto'] - (float) $fianza['importe_aplicado_deuda'], 2) : 0.0;

        // Llaves pendientes
        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(
                CASE WHEN m.tipo = 'entrega' OR m.tipo = 'reposicion' THEN m.cantidad
                     WHEN m.tipo = 'devolucion' OR m.tipo = 'perdida' THEN -m.cantidad
                 END
             ), 0) AS pendiente
             FROM movimientos_llave m
             WHERE m.estancia_id = :estancia_id"
        );
        $stmt->execute([':estancia_id' => $estanciaId]);
        $llavesPendientes = (int) $stmt->fetch()['pendiente'];

        // Verificar si hay facturas sin reparto confirmado para el periodo de la estancia
        $facturasPendientesReparto = false;
        if ($estancia['fecha_entrada'] && $estancia['fecha_salida_efectiva']) {
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) AS total
                 FROM facturas_suministro f
                 WHERE f.estado = 'confirmada'
                   AND f.periodo_desde >= :inicio
                   AND f.periodo_hasta <= :fin
                   AND NOT EXISTS (
                       SELECT 1 FROM repartos_factura r
                       WHERE r.factura_id = f.id AND r.estado = 'confirmado'
                   )"
            );
            $stmt->execute([
                ':inicio' => $estancia['fecha_entrada'],
                ':fin' => $estancia['fecha_salida_efectiva'],
            ]);
            $facturasPendientesReparto = (int) $stmt->fetch()['total'] > 0;
        }

        $totalDeuda = round($totalDeudaAlquiler + $totalDeudaSuministros, 2);
        $saldoLiquidacion = round($saldoFianza - $totalDeuda, 2);

        $csrf = Session::tokenCsrf();
        $flash = Session::consumirTodos();

        $this->render('liquidacion/ver.php', [
            'titulo' => 'Liquidación de salida',
            'estancia' => $estancia,
            'deudas_alquiler' => $deudasAlquiler,
            'total_deuda_alquiler' => $totalDeudaAlquiler,
            'deudas_suministros' => $deudasSuministros,
            'total_deuda_suministros' => $totalDeudaSuministros,
            'total_deuda' => $totalDeuda,
            'fianza' => $fianza,
            'saldo_fianza' => $saldoFianza,
            'total_liquidacion' => $saldoLiquidacion,
            'llaves_pendientes' => $llavesPendientes,
            'facturas_pendientes_reparto' => $facturasPendientesReparto,
            'csrf' => $csrf,
            'flash' => $flash,
            'config' => $config,
        ]);
    }

    /**
     * Renderiza una vista de liquidación.
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

        $titulo = $titulo ?? 'Liquidación';
        $flash = $flash ?? [];
        $csrf = $csrf ?? '';
        $config = $config ?? [];
        $usuario = \App\Support\Auth::usuario();

        require $rutaLayout;
    }
}
