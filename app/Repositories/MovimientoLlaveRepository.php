<?php
declare(strict_types=1);

/**
 * Repositorio MovimientoLlaveRepository — Acceso a datos de movimientos de llave.
 *
 * Responsabilidad:
 *  - Consultar, insertar movimientos de llave (entrega, devolución, pérdida, reposición).
 *  - Verificar que no se devuelva más de lo entregado pendiente.
 *  - Verificar que no se asigne una misma unidad identificada dos veces.
 *
 * Todas las consultas usan PDO con consultas preparadas.
 */

namespace App\Repositories;

use App\Support\Database;

class MovimientoLlaveRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
    }

    /**
     * Obtiene todos los movimientos de llave con datos de estancia.
     *
     * @return array
     */
    public function obtenerTodos(): array
    {
        $stmt = $this->pdo->query(
            'SELECT m.*, e.id AS estancia_id, h.numero AS habitacion_numero,
                   i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos,
                   t.nombre AS tipo_llave_nombre, l.identificador
             FROM movimientos_llave m
             INNER JOIN estancias e ON m.estancia_id = e.id
             INNER JOIN habitaciones h ON e.habitacion_id = h.id
             INNER JOIN inquilinos i ON e.inquilino_id = i.id
             LEFT JOIN llaves l ON m.llave_id = l.id
             LEFT JOIN tipos_llave t ON l.tipo_llave_id = t.id
             ORDER BY m.fecha DESC, m.id DESC'
        );
        return $stmt->fetchAll();
    }

    /**
     * Crea un movimiento de llave.
     *
     * @param array $datos estancia_id, llave_id, tipo, cantidad, fecha, observaciones, registrado_por
     * @return int ID del movimiento creado
     */
    public function crear(array $datos): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO movimientos_llave
             (estancia_id, llave_id, tipo, cantidad, fecha, observaciones, fecha_registro, registrado_por)
             VALUES
             (:estancia_id, :llave_id, :tipo, :cantidad, :fecha, :observaciones, datetime('datetime('now')'), :registrado_por)'
        );
        $stmt->execute([
            ':estancia_id' => $datos['estancia_id'],
            ':llave_id' => $datos['llave_id'] ?? null,
            ':tipo' => $datos['tipo'],
            ':cantidad' => $datos['cantidad'],
            ':fecha' => $datos['fecha'],
            ':observaciones' => $datos['observaciones'] ?? null,
            ':registrado_por' => $datos['registrado_por'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Verifica si una unidad identificada ya está entregada (no disponible).
     *
     * @param int $llaveId
     * @return bool
     */
    public function unidadEntregada(int $llaveId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT estado FROM llaves WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $llaveId]);
        $fila = $stmt->fetch();
        return $fila && $fila['estado'] === 'entregada';
    }

    /**
     * Verifica que no se devuelva más de lo pendiente para una estancia y tipo de llave.
     *
     * @param int $estanciaId
     * @param int|null $tipoLlaveId
     * @param int $cantidadADevolver
     * @return array{valido: bool, pendiente: int}
     */
    public function verificarDevolucion(int $estanciaId, ?int $tipoLlaveId, int $cantidadADevolver): array
    {
        $sql = "
            SELECT COALESCE(SUM(CASE WHEN m.tipo = 'entrega' THEN m.cantidad
                                     WHEN m.tipo = 'reposicion' THEN m.cantidad
                                     ELSE -m.cantidad END), 0) AS pendiente
             FROM movimientos_llave m
             LEFT JOIN llaves l ON m.llave_id = l.id
             WHERE m.estancia_id = :estancia_id";
        $params = [':estancia_id' => $estanciaId];

        if ($tipoLlaveId !== null) {
            $sql .= ' AND l.tipo_llave_id = :tipo_llave_id';
            $params[':tipo_llave_id'] = $tipoLlaveId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $pendiente = (int) $stmt->fetch()['pendiente'];

        return [
            'valido' => $cantidadADevolver <= $pendiente,
            'pendiente' => $pendiente,
        ];
    }
}
