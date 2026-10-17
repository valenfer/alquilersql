<?php
declare(strict_types=1);

/**
 * Repositorio RepartoFacturaRepository — Acceso a datos de repartos manuales de facturas.
 *
 * Responsabilidad:
 *  - Consultar, insertar y actualizar repartos (instantánea del acuerdo de inquilinos).
 *  - Obtener cuotas de reparto por reparto.
 *  - Verificar si una factura tiene reparto confirmado.
 *  - Obtener el reparto más reciente de una factura.
 *
 * Todas las consultas usan PDO con consultas preparadas.
 */

namespace App\Repositories;

use App\Support\Database;

class RepartoFacturaRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
    }

    /**
     * Obtiene todos los repartos de una factura.
     *
     * @param int $facturaId
     * @return array
     */
    public function obtenerPorFactura(int $facturaId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM repartos_factura
             WHERE factura_id = :factura_id
             ORDER BY fecha_creacion DESC'
        );
        $stmt->execute([':factura_id' => $facturaId]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un reparto por su ID con sus cuotas.
     *
     * @param int $id
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM repartos_factura WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        if (!$fila) {
            return null;
        }

        $fila['cuotas'] = $this->obtenerCuotasPorReparto($id);
        return $fila;
    }

    /**
     * Obtiene el reparto más reciente confirmado de una factura.
     *
     * @param int $facturaId
     * @return array|null
     */
    public function obtenerUltimoConfirmado(int $facturaId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM repartos_factura
             WHERE factura_id = :factura_id AND estado = 'confirmado'
             ORDER BY fecha_creacion DESC
             LIMIT 1'
        );
        $stmt->execute([':factura_id' => $facturaId]);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }

    /**
     * Verifica si una factura tiene algún reparto confirmado.
     *
     * @param int $facturaId
     * @return bool
     */
    public function tieneRepartoConfirmado(int $facturaId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS total FROM repartos_factura
             WHERE factura_id = :factura_id AND estado = 'confirmado''
        );
        $stmt->execute([':factura_id' => $facturaId]);
        return (int) $stmt->fetch()['total'] > 0;
    }

    /**
     * Crea un nuevo reparto de factura.
     *
     * @param array $datos factura_id, fecha_acuerdo, autor, observaciones, total_asignado, diferencia_pendiente, estado, reparto_anterior_id
     * @return int ID del reparto creado
     */
    public function crear(array $datos): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO repartos_factura
             (factura_id, fecha_acuerdo, autor, observaciones, total_asignado,
              diferencia_pendiente, estado, reparto_anterior_id, fecha_creacion)
             VALUES
             (:factura_id, :fecha_acuerdo, :autor, :observaciones, :total_asignado,
              :diferencia_pendiente, :estado, :reparto_anterior_id, NOW())'
        );
        $stmt->execute([
            ':factura_id' => $datos['factura_id'],
            ':fecha_acuerdo' => $datos['fecha_acuerdo'],
            ':autor' => $datos['autor'],
            ':observaciones' => $datos['observaciones'] ?? null,
            ':total_asignado' => $datos['total_asignado'],
            ':diferencia_pendiente' => $datos['diferencia_pendiente'],
            ':estado' => $datos['estado'],
            ':reparto_anterior_id' => $datos['reparto_anterior_id'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza el estado de un reparto (ej. de borrador a confirmado o rectificado).
     *
     * @param int $id ID del reparto
     * @param string $estado Nuevo estado
     * @return bool
     */
    public function actualizarEstado(int $id, string $estado): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE repartos_factura SET estado = :estado WHERE id = :id'
        );
        return $stmt->execute([':estado' => $estado, ':id' => $id]);
    }

    /**
     * Obtiene las cuotas de un reparto.
     *
     * @param int $repartoId
     * @return array
     */
    public function obtenerCuotasPorReparto(int $repartoId): array
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
