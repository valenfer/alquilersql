<?php
declare(strict_types=1);

/**
 * Servicio DashboardService — Lógica de negocio del dashboard.
 *
 * Responsabilidad:
 *  - Recopilar indicadores del dashboard para el mes consultado.
 *  - Tarjetas de las cuatro habitaciones con ocupante, renta, estado y cantidades pendientes.
 *  - Indicadores: ocupación, alquiler esperado/cobrado, pendientes, deuda vencida,
 *    facturas sin cargo bancario, facturas sin entregar, gastos pagados, fianzas, llaves pendientes.
 *  - Gráfico sencillo de ingresos y gastos mensuales (datos para tabla y SVG).
 *
 * Todas las consultas usan PDO con consultas preparadas.
 */

namespace App\Services;

use App\Support\Database;
use App\Services\ContratoService;

class DashboardService
{
    private \PDO $pdo;
    private ContratoService $contratoService;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
        $this->contratoService = new ContratoService();
    }

    /**
     * Obtiene todos los datos del dashboard para un año y mes dados.
     *
     * @param int $anio
     * @param int $mes
     * @return array
     */
    public function obtenerDatos(int $anio, int $mes): array
    {
        return [
            'habitaciones' => $this->obtenerHabitaciones(),
            'indicadores' => $this->obtenerIndicadores($anio, $mes),
            'grafico' => $this->obtenerDatosGrafico($anio),
            'alertas_contratos' => $this->contratoService->obtenerAlertasVencimiento(),
        ];
    }

    /**
     * Obtiene las cuatro habitaciones con su estado actual e inquilino.
     *
     * @return array
     */
    private function obtenerHabitaciones(): array
    {
        $stmt = $this->pdo->query(
            "SELECT h.id, h.numero, h.nombre, h.estado, h.renta_orientativa,
                    e.id AS estancia_id, i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos,
                    e.renta_mensual AS renta_actual, e.fecha_entrada
             FROM habitaciones h
             LEFT JOIN estancias e ON e.habitacion_id = h.id
                 AND e.estado = 'activa'
                 AND (e.fecha_salida_efectiva IS NULL OR e.fecha_salida_efectiva > CURDATE())
             LEFT JOIN inquilinos i ON e.inquilino_id = i.id
             ORDER BY h.numero ASC"
        );
        $habitaciones = $stmt->fetchAll();

        foreach ($habitaciones as &$h) {
            // Calcular cobros pendientes de esta habitación (alquiler + suministros)
            $h['pendiente_alquiler'] = $this->obtenerPendienteAlquilerHabitacion((int) $h['id']);
            $h['pendiente_suministros'] = $this->obtenerPendienteSuministrosHabitacion((int) $h['id']);
        }
        unset($h);

        return $habitaciones;
    }

    /**
     * Obtiene el importe pendiente de alquiler de una habitación.
     *
     * @param int $habitacionId
     * @return float
     */
    private function obtenerPendienteAlquilerHabitacion(int $habitacionId): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(ROUND(c.importe_esperado - c.importe_cobrado, 2)), 0) AS total
             FROM cargos_alquiler c
             INNER JOIN estancias e ON c.estancia_id = e.id
             WHERE e.habitacion_id = :habitacion_id
               AND c.estado IN ('pendiente', 'parcial')"
        );
        $stmt->execute([':habitacion_id' => $habitacionId]);
        return (float) $stmt->fetch()['total'];
    }

    /**
     * Obtiene el importe pendiente de suministros de una habitación.
     *
     * @param int $habitacionId
     * @return float
     */
    private function obtenerPendienteSuministrosHabitacion(int $habitacionId): float
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(c.importe), 0) AS total
             FROM cuotas_reparto c
             INNER JOIN estancias e ON c.estancia_id = e.id
             WHERE e.habitacion_id = :habitacion_id'
        );
        $stmt->execute([':habitacion_id' => $habitacionId]);
        $totalCuotas = (float) $stmt->fetch()['total'];

        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(a.importe), 0) AS cobrado
             FROM aplicaciones_cobro a
             INNER JOIN cuotas_reparto c ON a.cuota_reparto_id = c.id
             INNER JOIN estancias e ON c.estancia_id = e.id
             WHERE e.habitacion_id = :habitacion_id'
        );
        $stmt->execute([':habitacion_id' => $habitacionId]);
        $totalCobrado = (float) $stmt->fetch()['cobrado'];

        return round($totalCuotas - $totalCobrado, 2);
    }

    /**
     * Obtiene los indicadores principales del dashboard.
     *
     * @param int $anio
     * @param int $mes
     * @return array
     */
    private function obtenerIndicadores(int $anio, int $mes): array
    {
        $inicioMes = sprintf('%04d-%02d-01', $anio, $mes);
        $finMes = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $anio));

        // Habitaciones ocupadas y libres
        $stmt = $this->pdo->query(
            'SELECT COUNT(*) AS total FROM habitaciones'
        );
        $totalHabitaciones = (int) $stmt->fetch()['total'];

        $stmt = $this->pdo->query(
            "SELECT COUNT(DISTINCT h.id) AS ocupadas
             FROM habitaciones h
             INNER JOIN estancias e ON e.habitacion_id = h.id
             WHERE e.estado = 'activa'
               AND (e.fecha_salida_efectiva IS NULL OR e.fecha_salida_efectiva > CURDATE())"
        );
        $habitacionesOcupadas = (int) $stmt->fetch()['ocupadas'];
        $habitacionesLibres = $totalHabitaciones - $habitacionesOcupadas;

        // Alquiler esperado y cobrado del mes
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(c.importe_esperado), 0) AS esperado,
                    COALESCE(SUM(c.importe_cobrado), 0) AS cobrado
             FROM cargos_alquiler c
             WHERE c.anio = :anio AND c.mes = :mes'
        );
        $stmt->execute([':anio' => $anio, ':mes' => $mes]);
        $alquiler = $stmt->fetch();
        $alquilerEsperado = (float) $alquiler['esperado'];
        $alquilerCobrado = (float) $alquiler['cobrado'];

        // Pendiente de alquiler, luz y agua (cargos del mes no cobrados)
        $pendienteAlquiler = round($alquilerEsperado - $alquilerCobrado, 2);

        // Pendiente de suministros (cuotas - cobrados) para el mes
        $pendienteLuz = $this->obtenerPendienteSuministroMes('luz', $inicioMes, $finMes);
        $pendienteAgua = $this->obtenerPendienteSuministroMes('agua', $inicioMes, $finMes);

        // Deuda vencida (cargos con vencimiento pasado y saldo pendiente)
        $hoy = date('Y-m-d');
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(ROUND(c.importe_esperado - c.importe_cobrado, 2)), 0) AS total,
                    COUNT(DISTINCT e.inquilino_id) AS personas
             FROM cargos_alquiler c
             INNER JOIN estancias e ON c.estancia_id = e.id
             WHERE c.fecha_vencimiento < :hoy
               AND c.estado IN ('pendiente', 'parcial')"
        );
        $stmt->execute([':hoy' => $hoy]);
        $deuda = $stmt->fetch();
        $deudaVencida = (float) $deuda['total'];
        $personasConDeuda = (int) $deuda['personas'];

        // Facturas sin cargo bancario completo
        $stmt = $this->pdo->query(
            "SELECT COUNT(*) AS total
             FROM facturas_suministro f
             LEFT JOIN pagos_proveedor p ON f.id = p.factura_id
             WHERE f.estado = 'confirmada'
             GROUP BY f.id
             HAVING COALESCE(SUM(p.importe), 0) < f.importe_total OR SUM(p.importe) IS NULL"
        );
        $facturasSinCargo = $stmt->rowCount();

        // Facturas pendientes de entregar a algún destinatario
        $facturasSinEntregar = $this->contarFacturasSinEntregar();

        // Gastos efectivamente pagados del periodo
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(pg.importe), 0) AS total
             FROM gastos g
             INNER JOIN pagos_gasto pg ON g.id = pg.gasto_id
             WHERE pg.fecha_pago BETWEEN :inicio AND :fin'
        );
        $stmt->execute([':inicio' => $inicioMes, ':fin' => $finMes]);
        $gastosPagados = (float) $stmt->fetch()['total'];

        // Fianzas mantenidas (saldo pendiente de devolver, separado de ingresos)
        $stmt = $this->pdo->query(
            'SELECT COALESCE(SUM(saldo_pendiente_devolver), 0) AS total FROM fianzas'
        );
        $fianzasMantenidas = (float) $stmt->fetch()['total'];

        // Llaves pendientes de devolución en estancias finalizadas
        $stmt = $this->pdo->query(
            "SELECT COALESCE(SUM(
                CASE WHEN m.tipo = 'entrega' OR m.tipo = 'reposicion' THEN m.cantidad
                     WHEN m.tipo = 'devolucion' OR m.tipo = 'perdida' THEN -m.cantidad
                 END
             ), 0) AS pendiente
             FROM movimientos_llave m
             INNER JOIN estancias e ON m.estancia_id = e.id
             WHERE e.estado = 'finalizada'"
        );
        $llavesPendientes = (int) $stmt->fetch()['pendiente'];

        return [
            'habitaciones_ocupadas' => $habitacionesOcupadas,
            'habitaciones_libres' => $habitacionesLibres,
            'total_habitaciones' => $totalHabitaciones,
            'alquiler_esperado' => $alquilerEsperado,
            'alquiler_cobrado' => $alquilerCobrado,
            'pendiente_alquiler' => $pendienteAlquiler,
            'pendiente_luz' => $pendienteLuz,
            'pendiente_agua' => $pendienteAgua,
            'deuda_vencida' => $deudaVencida,
            'personas_con_deuda' => $personasConDeuda,
            'facturas_sin_cargo' => $facturasSinCargo,
            'facturas_sin_entregar' => $facturasSinEntregar,
            'gastos_pagados' => $gastosPagados,
            'fianzas_mantenidas' => $fianzasMantenidas,
            'llaves_pendientes' => $llavesPendientes,
            'mes' => $mes,
            'anio' => $anio,
        ];
    }

    /**
     * Obtiene el pendiente de un suministro (luz/agua) para un periodo.
     *
     * @param string $tipo
     * @param string $inicio
     * @param string $fin
     * @return float
     */
    private function obtenerPendienteSuministroMes(string $tipo, string $inicio, string $fin): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(c.importe), 0) AS total_cuotas,
                    COALESCE(SUM(a.importe), 0) AS total_cobrado
             FROM facturas_suministro f
             INNER JOIN repartos_factura r ON f.id = r.factura_id AND r.estado = 'confirmado'
             INNER JOIN cuotas_reparto c ON c.reparto_id = r.id
             LEFT JOIN aplicaciones_cobro a ON a.cuota_reparto_id = c.id
             WHERE f.tipo = :tipo
               AND f.periodo_desde BETWEEN :inicio AND :fin
               AND f.estado = 'confirmada'"
        );
        $stmt->execute([':tipo' => $tipo, ':inicio' => $inicio, ':fin' => $fin]);
        $fila = $stmt->fetch();
        return round((float) $fila['total_cuotas'] - (float) $fila['total_cobrado'], 2);
    }

    /**
     * Cuenta facturas confirmadas que no tienen reparto confirmado ni entregas a todos los destinatarios.
     *
     * @return int
     */
    private function contarFacturasSinEntregar(): int
    {
        $stmt = $this->pdo->query(
            "SELECT COUNT(DISTINCT f.id) AS total
             FROM facturas_suministro f
             LEFT JOIN repartos_factura r ON f.id = r.factura_id AND r.estado = 'confirmado'
             WHERE f.estado = 'confirmada'
               AND r.id IS NULL"
        );
        return (int) $stmt->fetch()['total'];
    }

    /**
     * Obtiene datos para el gráfico de ingresos y gastos mensuales del año.
     *
     * @param int $anio
     * @return array
     */
    private function obtenerDatosGrafico(int $anio): array
    {
        $meses = [];
        $ingresos = [];
        $gastos = [];

        for ($m = 1; $m <= 12; $m++) {
            $inicio = sprintf('%04d-%02d-01', $anio, $m);
            $fin = date('Y-m-t', mktime(0, 0, 0, $m, 1, $anio));
            $nombreMes = date('M', mktime(0, 0, 0, $m, 1, $anio));

            // Ingresos: cobros de alquiler y suministros en el mes
            $stmt = $this->pdo->prepare(
                'SELECT COALESCE(SUM(c.importe_total), 0) AS total
                 FROM cobros c
                 WHERE c.fecha_cobro BETWEEN :inicio AND :fin'
            );
            $stmt->execute([':inicio' => $inicio, ':fin' => $fin]);
            $ingreso = (float) $stmt->fetch()['total'];

            // Gastos: pagos de gastos y pagos a proveedores en el mes
            $stmt = $this->pdo->prepare(
                'SELECT (
                    COALESCE((SELECT SUM(pg.importe) FROM pagos_gasto pg
                              INNER JOIN gastos g ON pg.gasto_id = g.id
                              WHERE pg.fecha_pago BETWEEN :inicio AND :fin), 0)
                    + COALESCE((SELECT SUM(p.importe) FROM pagos_proveedor p
                                WHERE p.fecha_real BETWEEN :inicio AND :fin), 0)
                ) AS total'
            );
            $stmt->execute([':inicio' => $inicio, ':fin' => $fin]);
            $gasto = (float) $stmt->fetch()['total'];

            $meses[] = $nombreMes;
            $ingresos[] = round($ingreso, 2);
            $gastos[] = round($gasto, 2);
        }

        return [
            'meses' => $meses,
            'ingresos' => $ingresos,
            'gastos' => $gastos,
        ];
    }
}
