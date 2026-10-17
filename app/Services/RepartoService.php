<?php
declare(strict_types=1);

/**
 * Servicio RepartoService — Lógica de negocio de reparto manual de consumos.
 *
 * Responsabilidad:
 *  - Crear repartos manuales de facturas (acuerdo de inquilinos).
 *  - Validar que la suma de cuotas coincida exactamente con el importe de la factura.
 *  - Distinguir cero explícito (0,00 €) de campo vacío (pendiente).
 *  - Gestionar estados: borrador, confirmado, rectificado.
 *  - Preservar historial en rectificaciones (vínculo al reparto anterior).
 *
 * Reglas (sección 7.2 y 7.3 del documento):
 *  - El único modo de reparto es introducción manual del acuerdo.
 *  - No calcular ni precargar importes automáticamente.
 *  - Diferencia de reparto = total factura − suma cuotas. Debe ser 0,00 € para confirmar.
 *  - No redondear silenciosamente; máxima dos decimales.
 *  - Habitación vacía no genera cuota ni parte del propietario.
 *  - Una cuota de 0,00 € es válida si se asigna explícitamente.
 *  - Al confirmar, guardar como instantánea; no modificar automáticamente después.
 */

namespace App\Services;

use App\Repositories\RepartoFacturaRepository;
use App\Repositories\CuotaRepartoRepository;
use App\Repositories\FacturaSuministroRepository;
use App\Support\Database;
use App\Support\Security;

class RepartoService
{
    private RepartoFacturaRepository $repartoRepo;
    private CuotaRepartoRepository $cuotaRepo;
    private FacturaSuministroRepository $facturaRepo;

    public function __construct()
    {
        $this->repartoRepo = new RepartoFacturaRepository();
        $this->cuotaRepo = new CuotaRepartoRepository();
        $this->facturaRepo = new FacturaSuministroRepository();
    }

    /**
     * Obtiene los datos necesarios para mostrar la pantalla de reparto de una factura.
     *
     * Incluye: factura, estancias del periodo, cuotas existentes (si hay reparto), ausencias.
     *
     * @param int $facturaId
     * @return array|null
     */
    public function obtenerDatosReparto(int $facturaId): ?array
    {
        $factura = $this->facturaRepo->obtenerPorId($facturaId);
        if ($factura === null) {
            return null;
        }

        // Obtener estancias que ocuparon habitaciones durante el periodo de la factura
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT e.id AS estancia_id, e.inquilino_id, e.fecha_entrada, e.fecha_salida_efectiva,
                    h.numero AS habitacion_numero, h.nombre AS habitacion_nombre,
                    i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos
             FROM estancias e
             INNER JOIN habitaciones h ON e.habitacion_id = h.id
             INNER JOIN inquilinos i ON e.inquilino_id = i.id
             WHERE e.fecha_entrada <= :periodo_hasta
               AND (e.fecha_salida_efectiva IS NULL OR e.fecha_salida_efectiva >= :periodo_desde)
             ORDER BY h.numero ASC'
        );
        $stmt->execute([
            ':periodo_desde' => $factura['periodo_desde'],
            ':periodo_hasta' => $factura['periodo_hasta'],
        ]);
        $estancias = $stmt->fetchAll();

        // Obtener reparto existente (último borrador o confirmado)
        $repartos = $this->repartoRepo->obtenerPorFactura($facturaId);
        $repartoActual = null;
        if (!empty($repartos)) {
            $repartoActual = $repartos[0]; // El más reciente
            $repartoActual['cuotas'] = $this->cuotaRepo->obtenerConDestinatario($repartoActual['id']);
        }

        // Obtener ausencias que coincidan con el periodo
        $stmt = $pdo->prepare(
            'SELECT a.*, e.id AS estancia_id, h.numero AS habitacion_numero,
                    i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos
             FROM ausencias_estancia a
             INNER JOIN estancias e ON a.estancia_id = e.id
             INNER JOIN habitaciones h ON e.habitacion_id = h.id
             INNER JOIN inquilinos i ON e.inquilino_id = i.id
             WHERE a.fecha_inicio <= :periodo_hasta
               AND a.fecha_fin >= :periodo_desde
             ORDER BY h.numero ASC, a.fecha_inicio ASC'
        );
        $stmt->execute([
            ':periodo_desde' => $factura['periodo_desde'],
            ':periodo_hasta' => $factura['periodo_hasta'],
        ]);
        $ausencias = $stmt->fetchAll();

        return [
            'factura' => $factura,
            'estancias' => $estancias,
            'reparto' => $repartoActual,
            'ausencias' => $ausencias,
        ];
    }

    /**
     * Valida y crea un reparto de factura con sus cuotas.
     *
     * @param array $datos Datos del reparto y cuotas
     * @param int $usuarioId ID del usuario que registra
     * @return array{exito: bool, id: int|null, errores: array}
     */
    public function crear(array $datos, int $usuarioId): array
    {
        $errores = [];

        $facturaId = (int) ($datos['factura_id'] ?? 0);
        $fechaAcuerdo = $datos['fecha_acuerdo'] ?? null;
        $autor = trim($datos['autor'] ?? '');
        $observaciones = trim($datos['observaciones'] ?? '');
        $estado = $datos['estado'] ?? 'borrador'; // borrador o confirmado
        $cuotas = $datos['cuotas'] ?? []; // [estancia_id => importe]

        // Validaciones básicas
        if ($facturaId <= 0) {
            $errores[] = 'Factura no válida.';
        }
        if ($fechaAcuerdo === null) {
            $errores[] = 'La fecha del acuerdo es obligatoria.';
        }
        if ($autor === '') {
            $errores[] = 'El autor es obligatorio.';
        }

        // Obtener factura para validar importe total
        $factura = $this->facturaRepo->obtenerPorId($facturaId);
        if ($factura === null) {
            $errores[] = 'La factura no existe.';
        }

        // Procesar cuotas
        $totalAsignado = 0.0;
        $cuotasValidas = [];
        $tieneVacias = false;

        if ($factura !== null) {
            foreach ($cuotas as $estanciaId => $importeStr) {
                $estanciaId = (int) $estanciaId;
                $importe = Security::normalizarImporte($importeStr ?? '');

                if ($importe === null) {
                    // Campo vacío o inválido: pendiente de completar
                    $tieneVacias = true;
                    $cuotasValidas[] = [
                        'estancia_id' => $estanciaId,
                        'importe' => 0.00,
                        'asignado_explicitamente' => 0,
                    ];
                } else {
                    $cuotasValidas[] = [
                        'estancia_id' => $estanciaId,
                        'importe' => $importe,
                        'asignado_explicitamente' => 1,
                    ];
                    $totalAsignado = round($totalAsignado + $importe, 2);
                }
            }
        }

        // Validar diferencia si se confirma
        $diferenciaPendiente = 0.0;
        if ($factura !== null) {
            $diferenciaPendiente = round($factura['importe_total'] - $totalAsignado, 2);
        }

        if ($estado === 'confirmado') {
            if ($tieneVacias) {
                $errores[] = 'Todas las cuotas deben tener un importe explícito antes de confirmar.';
            }
            if ($diferenciaPendiente !== 0.0) {
                $direccion = $diferenciaPendiente > 0 ? 'falta asignar' : 'sobra asignado';
                $errores[] = "$direccion " . number_format(abs($diferenciaPendiente), 2, ',', '.') . ' €. '
                    . 'La suma de cuotas debe coincidir exactamente con el importe de la factura.';
            }
        }

        if (!empty($errores)) {
            return [
                'exito' => false,
                'id' => null,
                'errores' => $errores,
                'total_asignado' => $totalAsignado,
                'diferencia_pendiente' => $diferenciaPendiente,
            ];
        }

        // Crear el reparto dentro de una transacción
        $pdo = Database::obtenerConexion();
        $pdo->beginTransaction();

        try {
            // Verificar si existe un reparto confirmado previo (para rectificación)
            $repartoAnterior = null;
            if ($estado === 'confirmado') {
                $repartoAnterior = $this->repartoRepo->obtenerUltimoConfirmado($facturaId);
            }

            // Crear el reparto
            $repartoId = $this->repartoRepo->crear([
                'factura_id' => $facturaId,
                'fecha_acuerdo' => $fechaAcuerdo,
                'autor' => $autor,
                'observaciones' => $observaciones,
                'total_asignado' => $totalAsignado,
                'diferencia_pendiente' => $diferenciaPendiente,
                'estado' => $estado,
                'reparto_anterior_id' => $repartoAnterior['id'] ?? null,
            ]);

            // Crear las cuotas
            foreach ($cuotasValidas as $cuota) {
                $this->cuotaRepo->crear(array_merge($cuota, ['reparto_id' => $repartoId]));
            }

            $pdo->commit();

            return [
                'exito' => true,
                'id' => $repartoId,
                'errores' => [],
                'total_asignado' => $totalAsignado,
                'diferencia_pendiente' => $diferenciaPendiente,
            ];

        } catch (\Exception $e) {
            $pdo->rollBack();
            return [
                'exito' => false,
                'id' => null,
                'errores' => ['Error al guardar el reparto: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Calcula la diferencia de reparto para una factura y un conjunto de cuotas.
     *
     * @param int $facturaId
     * @param array $cuotas [estancia_id => importe]
     * @return array{total_asignado: float, diferencia_pendiente: float, importe_factura: float}
     */
    public function calcularDiferencia(int $facturaId, array $cuotas): array
    {
        $factura = $this->facturaRepo->obtenerPorId($facturaId);
        if ($factura === null) {
            return ['total_asignado' => 0.0, 'diferencia_pendiente' => 0.0, 'importe_factura' => 0.0];
        }

        $totalAsignado = 0.0;
        foreach ($cuotas as $importeStr) {
            $importe = Security::normalizarImporte($importeStr ?? '');
            if ($importe !== null) {
                $totalAsignado = round($totalAsignado + $importe, 2);
            }
        }

        $diferenciaPendiente = round($factura['importe_total'] - $totalAsignado, 2);

        return [
            'total_asignado' => $totalAsignado,
            'diferencia_pendiente' => $diferenciaPendiente,
            'importe_factura' => $factura['importe_total'],
        ];
    }

    /**
     * Obtiene el reparto confirmado de una factura con sus cuotas.
     *
     * @param int $facturaId
     * @return array|null
     */
    public function obtenerRepartoConfirmado(int $facturaId): ?array
    {
        $reparto = $this->repartoRepo->obtenerUltimoConfirmado($facturaId);
        if ($reparto === null) {
            return null;
        }

        $reparto['cuotas'] = $this->cuotaRepo->obtenerConDestinatario($reparto['id']);
        return $reparto;
    }
}
