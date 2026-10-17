<?php
declare(strict_types=1);

/**
 * Repositorio MovimientoFianzaRepository — Acceso a datos de movimientos de fianza.
 *
 * Responsabilidad:
 *  - Consultar, insertar movimientos de fianza (recepción, devolución, aplicación, compensación).
 *  - Obtener historial de movimientos de una fianza.
 *
 * Todas las consultas usan PDO con consultas preparadas.
 */

namespace App\Repositories;

use App\Support\Database;

class MovimientoFianzaRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
    }

    /**
     * Obtiene todos los movimientos de una fianza.
     *
     * @param int $fianzaId
     * @return array
     */
    public function obtenerPorFianza(int $fianzaId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT m.*, u.nombre AS registrado_por_nombre
             FROM movimientos_fianza m
             LEFT JOIN usuarios u ON m.registrado_por = u.id
             WHERE m.fianza_id = :fianza_id
             ORDER BY m.fecha ASC, m.id ASC'
        );
        $stmt->execute([':fianza_id' => $fianzaId]);
        return $stmt->fetchAll();
    }

    /**
     * Crea un nuevo movimiento de fianza.
     *
     * @param array $datos fianza_id, tipo, importe, fecha, medio, cargo_alquiler_id, motivo, observaciones, registrado_por
     * @return int ID del movimiento creado
     */
    public function crear(array $datos): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO movimientos_fianza
             (fianza_id, tipo, importe, fecha, medio, cargo_alquiler_id, motivo, observaciones,
              fecha_registro, registrado_por)
             VALUES
             (:fianza_id, :tipo, :importe, :fecha, :medio, :cargo_alquiler_id, :motivo, :observaciones,
              NOW(), :registrado_por)'
        );
        $stmt->execute([
            ':fianza_id' => $datos['fianza_id'],
            ':tipo' => $datos['tipo'],
            ':importe' => $datos['importe'],
            ':fecha' => $datos['fecha'],
            ':medio' => $datos['medio'] ?? null,
            ':cargo_alquiler_id' => $datos['cargo_alquiler_id'] ?? null,
            ':motivo' => $datos['motivo'] ?? null,
            ':observaciones' => $datos['observaciones'] ?? null,
            ':registrado_por' => $datos['registrado_por'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }
}
