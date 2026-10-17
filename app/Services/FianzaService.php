<?php
declare(strict_types=1);

/**
 * Servicio FianzaService — Lógica de negocio de fianzas.
 *
 * Responsabilidad:
 *  - Coordinar el repositorio para operaciones de fianza.
 *  - Aplicar reglas de negocio críticas:
 *    * Una fianza recibida no es ingreso de alquiler ni beneficio ordinario.
 *    * No permitir devolver o aplicar más fianza de la realmente disponible.
 *    * Aplicar a deuda reduce la deuda una sola vez, sin movimiento de caja.
 *    * Mostrar aplicación como compensación separada de cobros en dinero.
 *  - Usar transacciones para recibir, devolver, aplicar y compensar.
 *
 * Reglas (sección 9 del documento):
 *  - Fianza recibida ≠ ingreso de alquiler.
 *  - Devolución de fianza ≠ gasto ordinario del piso.
 *  - No devolver/aplicar más de lo disponible.
 *  - Si se aplica a deuda existente, enlazar al cargo y reducir deuda una sola vez.
 *  - No asumir que la fianza cubre automáticamente el último mes.
 */

namespace App\Services;

use App\Repositories\FianzaRepository;
use App\Repositories\MovimientoFianzaRepository;
use App\Repositories\CargoAlquilerRepository;
use App\Support\Database;

class FianzaService
{
    private FianzaRepository $repo;
    private MovimientoFianzaRepository $movRepo;
    private CargoAlquilerRepository $cargoRepo;

    public function __construct()
    {
        $this->repo = new FianzaRepository();
        $this->movRepo = new MovimientoFianzaRepository();
        $this->cargoRepo = new CargoAlquilerRepository();
    }

    /**
     * Obtiene todas las fianzas.
     *
     * @return array
     */
    public function listar(): array
    {
        return $this->repo->obtenerTodas();
    }

    /**
     * Obtiene una fianza por ID con sus movimientos.
     *
     * @param int $id
     * @return array|null
     */
    public function obtener(int $id): ?array
    {
        $fianza = $this->repo->obtenerPorId($id);
        if ($fianza === null) {
            return null;
        }
        $fianza['movimientos'] = $this->movRepo->obtenerPorFianza($id);
        $fianza['saldo_disponible'] = $this->repo->calcularSaldoDisponible($id);
        return $fianza;
    }

    /**
     * Obtiene la fianza de una estancia.
     *
     * @param int $estanciaId
     * @return array|null
     */
    public function obtenerPorEstancia(int $estanciaId): ?array
    {
        return $this->repo->obtenerPorEstancia($estanciaId);
    }

    /**
     * Registra la recepción de una fianza (dinero recibido del inquilino).
     *
     * @param int $estanciaId
     * @param float $importe
     * @param string $fecha
     * @param string $medio
     * @param string|null $referencia
     * @param string|null $observaciones
     * @param int $usuarioId
     * @return array{exito: bool, id: int|null, errores: array}
     */
    public function recibir(
        int $estanciaId,
        float $importe,
        string $fecha,
        string $medio,
        ?string $referencia,
        ?string $observaciones,
        int $usuarioId
    ): array {
        $errores = [];

        if ($importe <= 0) {
            $errores[] = 'El importe de la fianza debe ser mayor a 0.';
        }

        if (!empty($errores)) {
            return ['exito' => false, 'id' => null, 'errores' => $errores];
        }

        $pdo = Database::obtenerConexion();
        $pdo->beginTransaction();

        try {
            // Obtener importe pactado de la estancia
            $stmt = $pdo->prepare(
                'SELECT fianza_pactada FROM estancias WHERE id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $estanciaId]);
            $estancia = $stmt->fetch();
            $importePactado = $estancia ? (float) $estancia['fianza_pactada'] : 0.00;

            // Crear la fianza
            $fianzaId = $this->repo->crear([
                'estancia_id' => $estanciaId,
                'importe_pactado' => $importePactado,
                'importe_recibido' => $importe,
            ]);

            // Registrar movimiento de recepción
            $this->movRepo->crear([
                'fianza_id' => $fianzaId,
                'tipo' => 'recepcion',
                'importe' => $importe,
                'fecha' => $fecha,
                'medio' => $medio,
                'referencia' => $referencia,
                'motivo' => 'Recepción de fianza del inquilino',
                'observaciones' => $observaciones,
                'registrado_por' => $usuarioId,
            ]);

            $pdo->commit();

            return ['exito' => true, 'id' => $fianzaId, 'errores' => []];

        } catch (\Exception $e) {
            $pdo->rollBack();
            return [
                'exito' => false,
                'id' => null,
                'errores' => ['Error al registrar la fianza: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Registra una devolución de fianza en dinero.
     *
     * @param int $fianzaId
     * @param float $importe
     * @param string $fecha
     * @param string $medio
     * @param string|null $observaciones
     * @param int $usuarioId
     * @return array{exito: bool, errores: array}
     */
    public function devolver(
        int $fianzaId,
        float $importe,
        string $fecha,
        string $medio,
        ?string $observaciones,
        int $usuarioId
    ): array {
        $errores = [];

        if ($importe <= 0) {
            $errores[] = 'El importe de la devolución debe ser mayor a 0.';
        }

        $saldoDisponible = $this->repo->calcularSaldoDisponible($fianzaId);
        if ($importe > $saldoDisponible) {
            $errores[] = 'No se puede devolver ' . number_format($importe, 2, ',', '.') .
                ' €. Saldo disponible: ' . number_format($saldoDisponible, 2, ',', '.') . ' €.';
        }

        if (!empty($errores)) {
            return ['exito' => false, 'errores' => $errores];
        }

        $pdo = Database::obtenerConexion();
        $pdo->beginTransaction();

        try {
            $nuevoSaldo = round($saldoDisponible - $importe, 2);
            $nuevoDevuelto = round(
                $this->obtenerImporteDevuelto($fianzaId) + $importe,
                2
            );

            $this->repo->actualizarImportes($fianzaId, [
                'importe_devuelto' => $nuevoDevuelto,
                'saldo_pendiente_devolver' => $nuevoSaldo,
            ]);

            $this->movRepo->crear([
                'fianza_id' => $fianzaId,
                'tipo' => 'devolucion',
                'importe' => $importe,
                'fecha' => $fecha,
                'medio' => $medio,
                'motivo' => 'Devolución de fianza al inquilino',
                'observaciones' => $observaciones,
                'registrado_por' => $usuarioId,
            ]);

            $pdo->commit();

            return ['exito' => true, 'errores' => []];

        } catch (\Exception $e) {
            $pdo->rollBack();
            return [
                'exito' => false,
                'errores' => ['Error al devolver la fianza: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Aplica una parte de la fianza a una deuda de alquiler existente.
     * No crea movimiento de caja; es una compensación.
     *
     * @param int $fianzaId
     * @param int $cargoAlquilerId
     * @param float $importe
     * @param string $fecha
     * @param string|null $motivo
     * @param int $usuarioId
     * @return array{exito: bool, errores: array}
     */
    public function aplicarADeuda(
        int $fianzaId,
        int $cargoAlquilerId,
        float $importe,
        string $fecha,
        ?string $motivo,
        int $usuarioId
    ): array {
        $errores = [];

        if ($importe <= 0) {
            $errores[] = 'El importe a aplicar debe ser mayor a 0.';
        }

        $saldoDisponible = $this->repo->calcularSaldoDisponible($fianzaId);
        if ($importe > $saldoDisponible) {
            $errores[] = 'No se puede aplicar ' . number_format($importe, 2, ',', '.') .
                ' €. Saldo disponible: ' . number_format($saldoDisponible, 2, ',', '.') . ' €.';
        }

        // Verificar que el cargo existe y tiene saldo pendiente
        $cargo = $this->cargoRepo->obtenerPorId($cargoAlquilerId);
        if (!$cargo) {
            $errores[] = 'El cargo de alquiler no existe.';
        } else {
            $saldoCargo = round($cargo['importe_esperado'] - $cargo['importe_cobrado'], 2);
            if ($importe > $saldoCargo) {
                $errores[] = 'El importe a aplicar excede el saldo pendiente del cargo (' .
                    number_format($saldoCargo, 2, ',', '.') . ' €).';
            }
        }

        if (!empty($errores)) {
            return ['exito' => false, 'errores' => $errores];
        }

        $pdo = Database::obtenerConexion();
        $pdo->beginTransaction();

        try {
            $nuevoSaldo = round($saldoDisponible - $importe, 2);
            $nuevoAplicado = round(
                $this->obtenerImporteAplicado($fianzaId) + $importe,
                2
            );

            $this->repo->actualizarImportes($fianzaId, [
                'importe_aplicado_deuda' => $nuevoAplicado,
                'saldo_pendiente_devolver' => $nuevoSaldo,
            ]);

            // Registrar movimiento de aplicación (sin movimiento de caja)
            $this->movRepo->crear([
                'fianza_id' => $fianzaId,
                'tipo' => 'aplicacion_deuda',
                'importe' => $importe,
                'fecha' => $fecha,
                'cargo_alquiler_id' => $cargoAlquilerId,
                'motivo' => $motivo ?? 'Aplicación de fianza a deuda de alquiler',
                'registrado_por' => $usuarioId,
            ]);

            // Actualizar el cargo de alquiler (reducir deuda)
            $nuevoCobrado = round($cargo['importe_cobrado'] + $importe, 2);
            $this->cargoRepo->actualizarCobrado($cargoAlquilerId, $nuevoCobrado);

            $pdo->commit();

            return ['exito' => true, 'errores' => []];

        } catch (\Exception $e) {
            $pdo->rollBack();
            return [
                'exito' => false,
                'errores' => ['Error al aplicar la fianza: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Registra una compensación por daños documentados sin cargo previo.
     *
     * @param int $fianzaId
     * @param float $importe
     * @param string $fecha
     * @param string|null $motivo
     * @param int $usuarioId
     * @return array{exito: bool, errores: array}
     */
    public function compensarDanos(
        int $fianzaId,
        float $importe,
        string $fecha,
        ?string $motivo,
        int $usuarioId
    ): array {
        $errores = [];

        if ($importe <= 0) {
            $errores[] = 'El importe de la compensación debe ser mayor a 0.';
        }

        $saldoDisponible = $this->repo->calcularSaldoDisponible($fianzaId);
        if ($importe > $saldoDisponible) {
            $errores[] = 'No se puede compensar ' . number_format($importe, 2, ',', '.') .
                ' €. Saldo disponible: ' . number_format($saldoDisponible, 2, ',', '.') . ' €.';
        }

        if (!empty($errores)) {
            return ['exito' => false, 'errores' => $errores];
        }

        $pdo = Database::obtenerConexion();
        $pdo->beginTransaction();

        try {
            $nuevoSaldo = round($saldoDisponible - $importe, 2);
            $nuevoAplicado = round(
                $this->obtenerImporteAplicado($fianzaId) + $importe,
                2
            );

            $this->repo->actualizarImportes($fianzaId, [
                'importe_aplicado_deuda' => $nuevoAplicado,
                'saldo_pendiente_devolver' => $nuevoSaldo,
            ]);

            $this->movRepo->crear([
                'fianza_id' => $fianzaId,
                'tipo' => 'compensacion_danos',
                'importe' => $importe,
                'fecha' => $fecha,
                'motivo' => $motivo ?? 'Compensación por daños documentados',
                'registrado_por' => $usuarioId,
            ]);

            $pdo->commit();

            return ['exito' => true, 'errores' => []];

        } catch (\Exception $e) {
            $pdo->rollBack();
            return [
                'exito' => false,
                'errores' => ['Error al registrar la compensación: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Obtiene el importe ya devuelto de una fianza.
     *
     * @param int $fianzaId
     * @return float
     */
    private function obtenerImporteDevuelto(int $fianzaId): float
    {
        $stmt = $this->repo instanceof FianzaRepository ? null : null;
        // Usar consulta directa para evitar acoplamiento
        $pdo = \App\Support\Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT importe_devuelto FROM fianzas WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $fianzaId]);
        $fila = $stmt->fetch();
        return $fila ? (float) $fila['importe_devuelto'] : 0.0;
    }

    /**
     * Obtiene el importe ya aplicado a deuda de una fianza.
     *
     * @param int $fianzaId
     * @return float
     */
    private function obtenerImporteAplicado(int $fianzaId): float
    {
        $pdo = \App\Support\Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT importe_aplicado_deuda FROM fianzas WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $fianzaId]);
        $fila = $stmt->fetch();
        return $fila ? (float) $fila['importe_aplicado_deuda'] : 0.0;
    }
}
