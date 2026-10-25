<?php
declare(strict_types=1);

/**
 * Repositorio PagoGastoRepository — Acceso a datos de pagos de gastos.
 *
 * Responsabilidad:
 *  - Consultar, insertar pagos de gastos (admite pagos parciales).
 */

namespace App\Repositories;

use App\Support\Database;

class PagoGastoRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
    }

    /**
     * Crea un nuevo pago de gasto.
     *
     * @param array $datos gasto_id, importe, fecha_pago, medio, referencia, observaciones, registrado_por
     * @return int ID del pago creado
     */
    public function crear(array $datos): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO pagos_gasto
             (gasto_id, importe, fecha_pago, medio, referencia, observaciones,
              fecha_registro, registrado_por)
             VALUES
             (:gasto_id, :importe, :fecha_pago, :medio, :referencia, :observaciones,
              CURRENT_TIMESTAMP, :registrado_por)'
        );
        $stmt->execute([
            ':gasto_id' => $datos['gasto_id'],
            ':importe' => $datos['importe'],
            ':fecha_pago' => $datos['fecha_pago'],
            ':medio' => $datos['medio'],
            ':referencia' => $datos['referencia'] ?? null,
            ':observaciones' => $datos['observaciones'] ?? null,
            ':registrado_por' => $datos['registrado_por'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }
}
