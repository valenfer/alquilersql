<?php
declare(strict_types=1);

/**
 * Repositorio PagoProveedorRepository — Acceso a datos de pagos al proveedor (cargo bancario).
 *
 * Responsabilidad:
 *  - Consultar, insertar pagos al proveedor por factura de suministro.
 *  - Permitir varios pagos parciales para una factura.
 *  - Obtener el historial de pagos de una factura.
 *
 * Todas las consultas usan PDO con consultas preparadas.
 */

namespace App\Repositories;

use App\Support\Database;

class PagoProveedorRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
    }

    /**
     * Obtiene todos los pagos al proveedor con filtros opcionales.
     *
     * @param array $filtros factura_id, fecha_desde, fecha_hasta
     * @return array
     */
    public function obtenerTodos(array $filtros = []): array
    {
        $sql = '
            SELECT p.*, f.numero_factura, f.tipo, f.proveedor, f.importe_total AS factura_total
            FROM pagos_proveedor p
            INNER JOIN facturas_suministro f ON p.factura_id = f.id
            WHERE 1=1';
        $params = [];

        if (!empty($filtros['factura_id'])) {
            $sql .= ' AND p.factura_id = :factura_id';
            $params[':factura_id'] = $filtros['factura_id'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $sql .= ' AND p.fecha_real >= :fecha_desde';
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= ' AND p.fecha_real <= :fecha_hasta';
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }

        $sql .= ' ORDER BY p.fecha_real DESC, p.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un pago al proveedor por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, f.numero_factura, f.tipo, f.proveedor
            FROM pagos_proveedor p
            INNER JOIN facturas_suministro f ON p.factura_id = f.id
            WHERE p.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }

    /**
     * Obtiene todos los pagos de una factura ordenados por fecha.
     *
     * @param int $facturaId
     * @return array
     */
    public function obtenerPorFactura(int $facturaId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM pagos_proveedor
             WHERE factura_id = :factura_id
             ORDER BY fecha_real ASC, id ASC'
        );
        $stmt->execute([':factura_id' => $facturaId]);
        return $stmt->fetchAll();
    }

    /**
     * Crea un nuevo pago al proveedor.
     *
     * @param array $datos factura_id, importe, fecha_real, medio, referencia, observaciones, registrado_por
     * @return int ID del pago creado
     */
    public function crear(array $datos): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO pagos_proveedor
             (factura_id, importe, fecha_real, medio, referencia, observaciones,
              fecha_registro, registrado_por)
             VALUES
             (:factura_id, :importe, :fecha_real, :medio, :referencia, :observaciones,
              NOW(), :registrado_por)'
        );
        $stmt->execute([
            ':factura_id' => $datos['factura_id'],
            ':importe' => $datos['importe'],
            ':fecha_real' => $datos['fecha_real'],
            ':medio' => $datos['medio'],
            ':referencia' => $datos['referencia'] ?? null,
            ':observaciones' => $datos['observaciones'] ?? null,
            ':registrado_por' => $datos['registrado_por'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }
}
