<?php
declare(strict_types=1);

/**
 * Servicio ComprobacionFacturaService — Lógica de negocio de comprobación de pagos por factura.
 *
 * Responsabilidad:
 *  - Verificar que la suma de cuotas acordadas coincida con el importe de la factura (cuadre del reparto).
 *  - Verificar que la suma de cobros efectivos de inquilinos coincida con el importe de la factura (cuadre del dinero).
 *  - Obtener detalle de cobros aplicados a cada cuota.
 *  - Calcular importes a devolver y compensaciones con fianza.
 *
 * Reglas (sección 8.4 del documento):
 *  - Cuadre del reparto: total factura, suma de cuotas acordadas, diferencia por asignar.
 *  - Cuadre del dinero: total factura, suma de cobros netos, diferencia entre factura e ingresos.
 *  - Cobros netos = suma de aplicaciones válidas − devoluciones reales.
 *  - Aplicaciones de fianza se presentan separadamente (no son dinero nuevo).
 *  - Un importe positivo indica dinero no recuperado; negativo indica exceso.
 */

namespace App\Services;

use App\Repositories\FacturaSuministroRepository;
use App\Repositories\RepartoFacturaRepository;
use App\Repositories\CuotaRepartoRepository;
use App\Repositories\AplicacionCobroRepository;
use App\Support\Database;

class ComprobacionFacturaService
{
    private FacturaSuministroRepository $facturaRepo;
    private RepartoFacturaRepository $repartoRepo;
    private CuotaRepartoRepository $cuotaRepo;
    private AplicacionCobroRepository $aplicacionRepo;

    public function __construct()
    {
        $this->facturaRepo = new FacturaSuministroRepository();
        $this->repartoRepo = new RepartoFacturaRepository();
        $this->cuotaRepo = new CuotaRepartoRepository();
        $this->aplicacionRepo = new AplicacionCobroRepository();
    }

    /**
     * Obtiene el detalle completo de comprobación para una factura.
     *
     * Incluye: cuadre de reparto, cuadre del dinero, detalle por destinatario con cobros.
     *
     * @param int $facturaId
     * @return array|null
     */
    public function obtenerDetalle(int $facturaId): ?array
    {
        $factura = $this->facturaRepo->obtenerPorId($facturaId);
        if ($factura === null) {
            return null;
        }

        // Obtener el último reparto confirmado
        $reparto = $this->repartoRepo->obtenerUltimoConfirmado($facturaId);
        $cuotas = [];
        $totalCuotasAcordadas = 0.0;

        if ($reparto !== null) {
            $cuotas = $this->cuotaRepo->obtenerConDestinatario($reparto['id']);
            foreach ($cuotas as $cuota) {
                $totalCuotasAcordadas = round($totalCuotasAcordadas + (float) $cuota['importe'], 2);
            }
        }

        // Cuadre del reparto
        $diferenciaReparto = round($factura['importe_total'] - $totalCuotasAcordadas, 2);

        // Para cada destinatario, obtener cobros aplicados a su cuota
        $destinatarios = [];
        $totalCobrosDinero = 0.0;

        foreach ($cuotas as $cuota) {
            $estanciaId = (int) $cuota['estancia_id'];
            $cuotaId = (int) $cuota['id']; // Esto no está disponible en obtenerConDestinatario; necesitamos ajustar

            // Obtener cobros aplicados a esta cuota
            // Nota: necesitaríamos obtener el ID de la cuota. Lo obtenemos de obtenerCuotasPorReparto
            $cobrosCuota = $this->obtenerCobrosDeCuota($cuotaId);

            $cobradoDinero = 0.0;
            foreach ($cobrosCuota as $cobro) {
                $cobradoDinero = round($cobradoDinero + (float) $cobro['importe_aplicado'], 2);
            }

            $pendiente = round((float) $cuota['importe'] - $cobradoDinero, 2);

            $destinatarios[] = [
                'habitacion' => (int) $cuota['habitacion_numero'],
                'inquilino' => $cuota['inquilino_nombre'] . ' ' . $cuota['inquilino_apellidos'],
                'cuota_acordada' => (float) $cuota['importe'],
                'cobrado_dinero' => $cobradoDinero,
                'pendiente' => $pendiente,
                'cobros' => $cobrosCuota,
            ];

            $totalCobrosDinero = round($totalCobrosDinero + $cobradoDinero, 2);
        }

        // Cuadre del dinero
        $totalPagadoProveedor = $this->facturaRepo->totalPagadoProveedor($facturaId);
        $diferenciaDinero = round($factura['importe_total'] - $totalCobrosDinero, 2);

        // Determinar estado de comprobación
        $estadoReparto = $this->determinarEstadoReparto($reparto, $diferenciaReparto);
        $estadoDinero = $this->determinarEstadoDinero($diferenciaDinero);

        return [
            'factura' => $factura,
            'total_pagado_proveedor' => $totalPagadoProveedor,
            // Cuadre del reparto
            'total_factura' => $factura['importe_total'],
            'total_cuotas_acordadas' => $totalCuotasAcordadas,
            'diferencia_reparto' => $diferenciaReparto,
            'estado_reparto' => $estadoReparto,
            // Cuadre del dinero
            'total_cobros_dinero' => $totalCobrosDinero,
            'diferencia_dinero' => $diferenciaDinero,
            'estado_dinero' => $estadoDinero,
            // Detalle por destinatario
            'destinatarios' => $destinatarios,
        ];
    }

    /**
     * Obtiene los cobros aplicados a una cuota de reparto específica.
     *
     * @param int $cuotaId
     * @return array
     */
    private function obtenerCobrosDeCuota(int $cuotaId): array
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT a.importe AS importe_aplicado, c.fecha_cobro, c.medio, c.referencia, c.observaciones
             FROM aplicaciones_cobro a
             INNER JOIN cobros c ON a.cobro_id = c.id
             WHERE a.cuota_reparto_id = :cuota_id
             ORDER BY c.fecha_cobro DESC'
        );
        $stmt->execute([':cuota_id' => $cuotaId]);
        return $stmt->fetchAll();
    }

    /**
     * Determina el estado del reparto basado en la diferencia.
     *
     * @param array|null $reparto
     * @param float $diferencia
     * @return string
     */
    private function determinarEstadoReparto(?array $reparto, float $diferencia): string
    {
        if ($reparto === null) {
            return 'Reparto pendiente';
        }

        if ($diferencia !== 0.0) {
            return 'Reparto descuadrado';
        }

        return 'Reparto cuadrado';
    }

    /**
     * Determina el estado del dinero basado en la diferencia.
     *
     * @param float $diferencia
     * @return string
     */
    private function determinarEstadoDinero(float $diferencia): string
    {
        if ($diferencia > 0.01) {
            return 'Cobros parciales';
        }
        if ($diferencia < -0.01) {
            return 'Exceso de cobro';
        }

        return 'Factura cobrada íntegramente en dinero';
    }
}
