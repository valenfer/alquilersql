<?php
declare(strict_types=1);

/**
 * Repositorio CuotaRepartoRepository — Acceso a datos de cuotas de reparto por destinatario.
 *
 * Responsabilidad:
 *  - Consultar, insertar y actualizar cuotas manuales de reparto.
 *  - Calcular la suma total de cuotas de un reparto.
 *  - Obtener cuotas con datos del inquilino y estancia para listados.
 *
 * Todas las consultas usan PDO con consultas preparadas.
 */

namespace App\Repositories;

use App\Support\Database;

class CuotaRepartoRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
    }

    /**
     * Crea una cuota de reparto.
     *
     * @param array $datos reparto_id, estancia_id, importe, asignado_explicitamente, observaciones
     * @return int ID de la cuota creada
     */
    public function crear(array $datos): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO cuotas_reparto
             (reparto_id, estancia_id, importe, asignado_explicitamente, observaciones)
             VALUES
             (:reparto_id, :estancia_id, :importe, :asignado_explicitamente, :observaciones)'
        );
        $stmt->execute([
            ':reparto_id' => $datos['reparto_id'],
            ':estancia_id' => $datos['estancia_id'],
            ':importe' => $datos['importe'],
            ':asignado_explicitamente' => $datos['asignado_explicitamente'] ?? 1,
            ':observaciones' => $datos['observaciones'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza el importe de una cuota de reparto.
     *
     * @param int $id ID de la cuota
     * @param float $importe Nuevo importe
     * @param int $asignadoExplicitamente 1 si es explícito, 0 si pendiente
     * @return bool
     */
    public function actualizar(int $id, float $importe, int $asignadoExplicitamente): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE cuotas_reparto SET importe = :importe, asignado_explicitamente = :asignado_explicitamente WHERE id = :id'
        );
        return $stmt->execute([
            ':importe' => $importe,
            ':asignado_explicitamente' => $asignadoExplicitamente,
            ':id' => $id,
        ]);
    }

    /**
     * Elimina todas las cuotas de un reparto (para reconstruir en rectificación).
     *
     * @param int $repartoId
     * @return bool
     */
    public function eliminarPorReparto(int $repartoId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM cuotas_reparto WHERE reparto_id = :reparto_id'
        );
        return $stmt->execute([':reparto_id' => $repartoId]);
    }

    /**
     * Calcula la suma total de cuotas asignadas de un reparto.
     *
     * @param int $repartoId
     * @return float Suma total
     */
    public function totalCuotas(int $repartoId): float
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(importe), 0) AS total FROM cuotas_reparto WHERE reparto_id = :reparto_id'
        );
        $stmt->execute([':reparto_id' => $repartoId]);
        return (float) $stmt->fetch()['total'];
    }

    /**
     * Obtiene cuotas de un reparto con datos del destinatario para comprobación.
     *
     * @param int $repartoId
     * @return array
     */
    public function obtenerConDestinatario(int $repartoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, e.inquilino_id, h.numero AS habitacion_numero,
                    i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos
             FROM cuotas_reparto c
             INNER JOIN estancias e ON c.estancia_id = e.id
             INNER JOIN habitaciones h ON e.habitacion_id = h.id
             INNER JOIN inquilinos i ON e.inquilino_id = i.id
             WHERE c.reparto_id = :reparto_id
             ORDER BY h.numero ASC'
        );
        $stmt->execute([':reparto_id' => $repartoId]);
        return $stmt->fetchAll();
    }
}
