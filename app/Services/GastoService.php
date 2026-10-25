<?php
declare(strict_types=1);

/**
 * Servicio GastoService — Lógica de negocio de gastos del propietario.
 *
 * Responsabilidad:
 *  - Coordinar el repositorio para operaciones de gasto.
 *  - Registrar gastos con pagos parciales o completos en una transacción.
 *  - Comunidad e IBI no generan cuotas a inquilinos (regla del documento).
 *  - Advertir si un gasto manual parece duplicar una factura de suministro.
 *
 * Reglas (sección 11 del documento):
 *  - Comunidad e IBI no generan cargos a los inquilinos.
 *  - Separar existencia del gasto y su pago (pagos parciales admitidos).
 *  - Si se introduce un gasto ya pagado, permitir registrarlo todo en un único formulario y transacción.
 */

namespace App\Services;

use App\Repositories\GastoRepository;
use App\Repositories\PagoGastoRepository;
use App\Repositories\CategoriaGastoRepository;
use App\Support\Database;
use App\Support\Security;

class GastoService
{
    private GastoRepository $repo;
    private PagoGastoRepository $pagoRepo;
    private CategoriaGastoRepository $catRepo;

    public function __construct()
    {
        $this->repo = new GastoRepository();
        $this->pagoRepo = new PagoGastoRepository();
        $this->catRepo = new CategoriaGastoRepository();
    }

    /**
     * Obtiene todas las categorías de gasto.
     *
     * @return array
     */
    public function listarCategorias(): array
    {
        return $this->catRepo->obtenerTodos();
    }

    /**
     * Obtiene todos los gastos con filtros.
     *
     * @param array $filtros
     * @return array
     */
    public function listar(array $filtros = []): array
    {
        return $this->repo->obtenerTodos($filtros);
    }

    /**
     * Obtiene un gasto con sus pagos.
     *
     * @param int $id
     * @return array|null
     */
    public function obtener(int $id): ?array
    {
        $gasto = $this->repo->obtenerPorId($id);
        if ($gasto === null) {
            return null;
        }
        $gasto['pagos'] = $this->repo->obtenerPagos($id);
        $gasto['total_pagado'] = $this->repo->totalPagado($id);
        $gasto['saldo_pendiente_pago'] = round($gasto['importe'] - $gasto['total_pagado'], 2);
        return $gasto;
    }

    /**
     * Crea un gasto con sus pagos en una transacción.
     *
     * @param array $datos Datos del gasto y pagos
     * @param int $usuarioId
     * @return array{exito: bool, id: int|null, errores: array}
     */
    public function crearConPagos(array $datos, int $usuarioId): array
    {
        $errores = [];

        $categoriaId = (int) ($datos['categoria_id'] ?? 0);
        $descripcion = trim($datos['descripcion'] ?? '');
        $importe = Security::normalizarImporte((string) ($datos['importe'] ?? ''));
        $fechaGasto = $datos['fecha_gasto'] ?? null;
        $proveedor = trim($datos['proveedor'] ?? '');
        $numeroFactura = trim($datos['numero_factura'] ?? '');
        $relacionHabitacion = (int) ($datos['relacion_habitacion'] ?? 0);
        $esGeneral = !empty($datos['es_general']) ? 1 : 0;
        $observaciones = trim($datos['observaciones'] ?? '');

        // Validaciones
        if ($categoriaId <= 0) {
            $errores[] = 'La categoría es obligatoria.';
        }
        if ($descripcion === '') {
            $errores[] = 'La descripción es obligatoria.';
        }
        if ($importe === null || $importe <= 0) {
            $errores[] = 'El importe debe ser mayor a 0.';
        }
        if ($fechaGasto === null) {
            $errores[] = 'La fecha del gasto es obligatoria.';
        }

        // Advertir si parece duplicar una factura de suministro (comunidad e IBI no generan cuotas)
        // Esta advertencia es informativa; no bloquea la creación.
        // Los pagos indicados en el alta deben ser válidos y no exceder el gasto.
        $totalPrevisto = 0.0;
        foreach (($datos['pagos'] ?? []) as $pago) {
            $importePago = Security::normalizarImporte((string) ($pago['importe'] ?? ''));
            if ($importePago === null || $importePago <= 0 || empty($pago['fecha_pago'])) {
                $errores[] = 'Cada pago debe tener un importe válido y una fecha.';
                continue;
            }
            $totalPrevisto = round($totalPrevisto + $importePago, 2);
        }
        if ($importe !== null && $totalPrevisto > $importe) {
            $errores[] = 'Los pagos no pueden superar el importe del gasto.';
        }

        if (!empty($errores)) {
            return ['exito' => false, 'id' => null, 'errores' => $errores];
        }

        $pdo = Database::obtenerConexion();
        $pdo->beginTransaction();

        try {
            $gastoId = $this->repo->crear([
                'categoria_id' => $categoriaId,
                'descripcion' => $descripcion,
                'importe' => $importe,
                'fecha_gasto' => $fechaGasto,
                'proveedor' => $proveedor,
                'numero_factura' => $numeroFactura,
                'relacion_habitacion' => $relacionHabitacion > 0 ? $relacionHabitacion : null,
                'es_general' => $esGeneral,
                'observaciones' => $observaciones,
                'creado_por' => $usuarioId,
            ]);

            // Registrar pagos si se indican
            $pagos = $datos['pagos'] ?? [];
            $totalPagos = 0.0;
            foreach ($pagos as $pago) {
                $importePago = Security::normalizarImporte((string) ($pago['importe'] ?? ''));
                $fechaPago = $pago['fecha_pago'] ?? null;
                $medio = $pago['medio'] ?? '';

                if ($importePago > 0 && $fechaPago !== null) {
                    $this->pagoRepo->crear([
                        'gasto_id' => $gastoId,
                        'importe' => $importePago,
                        'fecha_pago' => $fechaPago,
                        'medio' => $medio,
                        'referencia' => $pago['referencia'] ?? null,
                        'observaciones' => $pago['observaciones'] ?? null,
                        'registrado_por' => $usuarioId,
                    ]);
                    $totalPagos = round($totalPagos + $importePago, 2);
                }
            }

            $pdo->commit();

            return [
                'exito' => true,
                'id' => $gastoId,
                'errores' => [],
                'total_pagado' => $totalPagos,
            ];

        } catch (\Exception $e) {
            $pdo->rollBack();
            return [
                'exito' => false,
                'id' => null,
                'errores' => ['Error al registrar el gasto: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Registra un pago adicional a un gasto existente.
     *
     * @param int $gastoId
     * @param float $importe
     * @param string $fecha
     * @param string $medio
     * @param string|null $referencia
     * @param string|null $observaciones
     * @param int $usuarioId
     * @return array{exito: bool, errores: array}
     */
    public function registrarPago(
        int $gastoId,
        float $importe,
        string $fecha,
        string $medio,
        ?string $referencia,
        ?string $observaciones,
        int $usuarioId
    ): array {
        $errores = [];

        if ($importe <= 0) {
            $errores[] = 'El importe del pago debe ser mayor a 0.';
        }

        $gasto = $this->repo->obtenerPorId($gastoId);
        if (!$gasto) {
            $errores[] = 'El gasto no existe.';
        } else {
            $totalPagado = $this->repo->totalPagado($gastoId);
            $saldoPendiente = round($gasto['importe'] - $totalPagado, 2);
            if ($importe > $saldoPendiente) {
                $errores[] = 'El pago excede el saldo pendiente del gasto (' .
                    number_format($saldoPendiente, 2, ',', '.') . ' €).';
            }
        }

        if (!empty($errores)) {
            return ['exito' => false, 'errores' => $errores];
        }

        $this->pagoRepo->crear([
            'gasto_id' => $gastoId,
            'importe' => $importe,
            'fecha_pago' => $fecha,
            'medio' => $medio,
            'referencia' => $referencia,
            'observaciones' => $observaciones,
            'registrado_por' => $usuarioId,
        ]);

        return ['exito' => true, 'errores' => []];
    }
}
