<?php
declare(strict_types=1);

/**
 * Repositorio AplicacionCobroRepository — Acceso a datos de aplicaciones de cobro.
 *
 * Responsabilidad:
 *  - Consultar, insertar aplicaciones (líneas de desglose de un cobro a deudas concretas).
 *  - Calcular el total aplicado a un cargo de alquiler.
 *  - Verificar que no se aplique más del saldo pendiente.
 *
 * Todas las consultas usan PDO con consultas preparadas.
 */

namespace App\Repositories;

use App\Support\Database;

class AplicacionCobroRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
    }

    /**
     * Obtiene todas las aplicaciones de un cobro.
     *
     * @param int $cobroId
     * @return array
     */
    public function obtenerPorCobro(int $cobroId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM aplicaciones_cobro WHERE cobro_id = :cobro_id'
        );
        $stmt->execute([':cobro_id' => $cobroId]);
        return $stmt->fetchAll();
    }

    /**
     * Crea una aplicación de cobro a un cargo de alquiler.
     *
     * @param array $datos cobro_id, cargo_alquiler_id, importe
     * @return int ID de la aplicación creada
     */
    public function crear(array $datos): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO aplicaciones_cobro
             (cobro_id, tipo_destino, cargo_alquiler_id, cuota_reparto_id, fianza_id, importe, fecha_aplicacion)
             VALUES
             (:cobro_id, 'alquiler', :cargo_alquiler_id, NULL, NULL, :importe, NOW())"
        );
        $stmt->execute([
            ':cobro_id' => $datos['cobro_id'],
            ':cargo_alquiler_id' => $datos['cargo_alquiler_id'],
            ':importe' => $datos['importe'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Calcula el total ya aplicado a un cargo de alquiler (suma de aplicaciones válidas).
     *
     * @param int $cargoAlquilerId
     * @return float Total aplicado
     */
    public function totalAplicadoACargo(int $cargoAlquilerId): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(importe), 0) AS total FROM aplicaciones_cobro
             WHERE cargo_alquiler_id = :cargo_alquiler_id AND tipo_destino = 'alquiler'"
        );
        $stmt->execute([':cargo_alquiler_id' => $cargoAlquilerId]);
        return (float) $stmt->fetch()['total'];
    }

    /**
     * Verifica si una aplicación excedería el saldo pendiente del cargo.
     *
     * @param int $cargoAlquilerId
     * @param float $importeNuevo
     * @return array{excede: bool, saldo_pendiente: float}
     */
    public function excedeSaldoPendiente(int $cargoAlquilerId, float $importeNuevo): array
    {
        $totalAplicado = $this->totalAplicadoACargo($cargoAlquilerId);

        $stmt = $this->pdo->prepare(
            'SELECT importe_esperado FROM cargos_alquiler WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $cargoAlquilerId]);
        $fila = $stmt->fetch();
        if (!$fila) {
            return ['excede' => true, 'saldo_pendiente' => 0.0];
        }

        $importeEsperado = (float) $fila['importe_esperado'];
        $saldoPendiente = round($importeEsperado - $totalAplicado, 2);
        $nuevoTotal = round($totalAplicado + $importeNuevo, 2);

        return [
            'excede' => $nuevoTotal > $importeEsperado,
            'saldo_pendiente' => $saldoPendiente,
        ];
    }
}
