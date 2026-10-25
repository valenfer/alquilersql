<?php
declare(strict_types=1);

/**
 * Servicio CobroService — Lógica de negocio de cobros y aplicaciones.
 *
 * Responsabilidad:
 *  - Crear cobros recibidos (movimientos de dinero) con sus aplicaciones a cargos.
 *  - Operar dentro de una transacción para integridad monetaria.
 *  - Validar que la suma de aplicaciones coincida con el importe recibido.
 *  - No permitir aplicar más del saldo pendiente de un cargo.
 *  - Token de idempotencia para prevenir doble envío de formularios.
 *  - Soportar cobros parciales y varios cobros para una misma mensualidad.
 *  - Soportar adelantar una mensualidad futura creada expresamente.
 *
 * Reglas (sección 6.3 y 8.2 del documento):
 *  - Un movimiento de cobro puede tener varias aplicaciones a distintos cargos.
 *  - La suma de aplicaciones debe coincidir con el importe recibido.
 *  - No crear créditos sin destino automáticamente.
 *  - No mezclar inquilinos en un mismo cobro.
 *  - No mezclar fianzas con cobros ordinarios (módulo específico en Fase 5).
 */

namespace App\Services;

use App\Repositories\CobroRepository;
use App\Repositories\AplicacionCobroRepository;
use App\Repositories\CargoAlquilerRepository;
use App\Support\Database;
use App\Support\Security;

class CobroService
{
    private CobroRepository $cobroRepo;
    private AplicacionCobroRepository $aplicacionRepo;
    private CargoAlquilerRepository $cargoRepo;

    public function __construct()
    {
        $this->cobroRepo = new CobroRepository();
        $this->aplicacionRepo = new AplicacionCobroRepository();
        $this->cargoRepo = new CargoAlquilerRepository();
    }

    /**
     * Obtiene todos los cobros con filtros.
     *
     * @param array $filtros
     * @return array
     */
    public function listar(array $filtros = []): array
    {
        return $this->cobroRepo->obtenerTodos($filtros);
    }

    /**
     * Obtiene un cobro por ID con sus aplicaciones.
     *
     * @param int $id
     * @return array|null
     */
    public function obtener(int $id): ?array
    {
        $cobro = $this->cobroRepo->obtenerPorId($id);
        if ($cobro === null) {
            return null;
        }
        $cobro['aplicaciones'] = $this->cobroRepo->obtenerAplicaciones($id);
        return $cobro;
    }

    /**
     * Obtiene los cargos pendientes de un inquilino para mostrar en el formulario de cobro.
     *
     * @param int $inquilinoId
     * @return array Lista de cargos pendientes con saldo
     */
    public function obtenerCargosPendientesPorInquilino(int $inquilinoId): array
    {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            "SELECT c.id, c.anio, c.mes, c.importe_esperado, c.importe_cobrado,
                    c.fecha_vencimiento, c.estado, h.numero AS habitacion_numero,
                    ROUND(c.importe_esperado - c.importe_cobrado, 2) AS saldo_pendiente
             FROM cargos_alquiler c
             INNER JOIN estancias e ON c.estancia_id = e.id
             INNER JOIN habitaciones h ON e.habitacion_id = h.id
             WHERE e.inquilino_id = :inquilino_id
               AND c.estado IN ('pendiente', 'parcial')
             ORDER BY c.anio ASC, c.mes ASC"
        );
        $stmt->execute([':inquilino_id' => $inquilinoId]);
        return $stmt->fetchAll();
    }

    /**
     * Crea un cobro con sus aplicaciones dentro de una transacción.
     *
     * @param array $datos Datos del cobro y aplicaciones
     * @param int $usuarioId ID del usuario que registra
     * @return array{exito: bool, id: int|null, errores: array}
     */
    public function crearConAplicaciones(array $datos, int $usuarioId): array
    {
        $errores = [];

        $inquilinoId = (int) ($datos['inquilino_id'] ?? 0);
        $fechaCobro = $datos['fecha_cobro'] ?? null;
        $medio = $datos['medio'] ?? '';
        $importeTotal = Security::normalizarImporte((string) ($datos['importe_total'] ?? ''));
        $referencia = trim($datos['referencia'] ?? '');
        $observaciones = trim($datos['observaciones'] ?? '');
        $tokenIdempotencia = $datos['token_idempotencia'] ?? Security::tokenIdempotencia();

        // Validaciones básicas
        if ($inquilinoId <= 0) {
            $errores[] = 'Debe seleccionar un inquilino.';
        }
        if ($fechaCobro === null) {
            $errores[] = 'La fecha del cobro es obligatoria.';
        }
        if ($medio === '') {
            $errores[] = 'El medio de pago es obligatorio.';
        }
        if ($importeTotal === null || $importeTotal <= 0) {
            $errores[] = 'El importe recibido debe ser mayor a 0.';
        }

        // Verificar idempotencia: si ya existe un cobro con este token, no duplicar
        if ($tokenIdempotencia !== null) {
            $existente = $this->cobroRepo->obtenerPorToken($tokenIdempotencia);
            if ($existente !== null) {
                return [
                    'exito' => true,
                    'id' => (int) $existente['id'],
                    'errores' => [],
                ];
            }
        }

        // Procesar aplicaciones
        $aplicaciones = $datos['aplicaciones'] ?? [];
        $totalAplicaciones = 0.0;
        $aplicacionesValidas = [];

        foreach ($aplicaciones as $idx => $app) {
            $cargoId = (int) ($app['cargo_alquiler_id'] ?? 0);
            $importeApp = (float) ($app['importe'] ?? 0);

            if ($cargoId <= 0) {
                $errores[] = "Aplicación #" . ($idx + 1) . ": cargo de alquiler no válido.";
                continue;
            }
            if ($importeApp <= 0) {
                $errores[] = "Aplicación #" . ($idx + 1) . ": importe debe ser mayor a 0.";
                continue;
            }

            // Verificar que no exceda el saldo pendiente del cargo
            $saldoCheck = $this->aplicacionRepo->excedeSaldoPendiente($cargoId, $importeApp);
            if ($saldoCheck['excede']) {
                $errores[] = "Aplicación #" . ($idx + 1) . ": el importe excede el saldo pendiente del cargo (máximo " .
                    number_format($saldoCheck['saldo_pendiente'], 2, ',', '.') . " €).";
                continue;
            }

            $aplicacionesValidas[] = [
                'cargo_alquiler_id' => $cargoId,
                'importe' => $importeApp,
            ];
            $totalAplicaciones = round($totalAplicaciones + $importeApp, 2);
        }

        // Validar que la suma de aplicaciones coincida con el importe total
        if ($importeTotal !== null && round($totalAplicaciones, 2) !== round($importeTotal, 2)) {
            $errores[] = 'La suma de las aplicaciones (' . number_format($totalAplicaciones, 2, ',', '.') .
                ' €) no coincide con el importe recibido (' .
                number_format($importeTotal, 2, ',', '.') . ' €).';
        }

        if (!empty($errores)) {
            return ['exito' => false, 'id' => null, 'errores' => $errores];
        }

        // Ejecutar dentro de una transacción
        $pdo = Database::obtenerConexion();
        $pdo->beginTransaction();

        try {
            // 1. Crear el cobro
            $cobroId = $this->cobroRepo->crear([
                'inquilino_id' => $inquilinoId,
                'fecha_cobro' => $fechaCobro,
                'medio' => $medio,
                'importe_total' => $importeTotal,
                'referencia' => $referencia,
                'observaciones' => $observaciones,
                'token_idempotencia' => $tokenIdempotencia,
                'registrado_por' => $usuarioId,
            ]);

            // 2. Crear las aplicaciones y actualizar los cargos
            foreach ($aplicacionesValidas as $app) {
                $this->aplicacionRepo->crear([
                    'cobro_id' => $cobroId,
                    'cargo_alquiler_id' => $app['cargo_alquiler_id'],
                    'importe' => $app['importe'],
                ]);

                // Actualizar importe cobrado del cargo
                $nuevoCobrado = $this->aplicacionRepo->totalAplicadoACargo($app['cargo_alquiler_id']);
                $this->cargoRepo->actualizarCobrado($app['cargo_alquiler_id'], $nuevoCobrado);
            }

            $pdo->commit();

            return [
                'exito' => true,
                'id' => $cobroId,
                'errores' => [],
            ];

        } catch (\Exception $e) {
            $pdo->rollBack();
            return [
                'exito' => false,
                'id' => null,
                'errores' => ['Error al guardar el cobro: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Verifica si un token de idempotencia ya fue usado.
     *
     * @param string $token
     * @return bool
     */
    public function tokenYaUsado(string $token): bool
    {
        return $this->cobroRepo->obtenerPorToken($token) !== null;
    }

    /**
     * Obtiene el detalle de un cobro con sus aplicaciones para conciliación.
     *
     * @param int $id
     * @return array|null
     */
    public function obtenerDetalle(int $id): ?array
    {
        return $this->obtener($id);
    }
}
