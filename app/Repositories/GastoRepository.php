<?php
declare(strict_types=1);

/**
 * Repositorio GastoRepository — Acceso a datos de gastos del propietario.
 *
 * Responsabilidad:
 *  - Consultar, insertar y actualizar gastos.
 *  - Obtener gastos con filtros por concepto, fecha, habitación.
 *  - Obtener pagos de un gasto.
 *
 * Todas las consultas usan PDO con consultas preparadas.
 */

namespace App\Repositories;

use App\Support\Database;

class GastoRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
    }

    /**
     * Obtiene todos los gastos con filtros opcionales.
     *
     * @param array $filtros categoria_id, fecha_desde, fecha_hasta, habitacion_id
     * @return array
     */
    public function obtenerTodos(array $filtros = []): array
    {
        $sql = '
            SELECT g.*, c.nombre AS categoria_nombre, h.numero AS habitacion_numero,
                   u.nombre AS creado_por_nombre
             FROM gastos g
             INNER JOIN categorias_gasto c ON g.categoria_id = c.id
             LEFT JOIN habitaciones h ON g.relacion_habitacion = h.id
             LEFT JOIN usuarios u ON g.creado_por = u.id
             WHERE 1=1';
        $params = [];

        if (!empty($filtros['categoria_id'])) {
            $sql .= ' AND g.categoria_id = :categoria_id';
            $params[':categoria_id'] = $filtros['categoria_id'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $sql .= ' AND g.fecha_gasto >= :fecha_desde';
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= ' AND g.fecha_gasto <= :fecha_hasta';
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['habitacion_id'])) {
            $sql .= ' AND g.relacion_habitacion = :habitacion_id';
            $params[':habitacion_id'] = $filtros['habitacion_id'];
        }

        $sql .= ' ORDER BY g.fecha_gasto DESC, g.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un gasto por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT g.*, c.nombre AS categoria_nombre
             FROM gastos g
             INNER JOIN categorias_gasto c ON g.categoria_id = c.id
             WHERE g.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }

    /**
     * Crea un nuevo gasto.
     *
     * @param array $datos categoria_id, descripcion, importe, fecha_gasto, proveedor, ...
     * @return int ID del gasto creado
     */
    public function crear(array $datos): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO gastos
             (categoria_id, descripcion, importe, fecha_gasto, proveedor, numero_factura,
              relacion_habitacion, es_general, observaciones, documento_adjunto_id,
              fecha_creacion, creado_por)
             VALUES
             (:categoria_id, :descripcion, :importe, :fecha_gasto, :proveedor, :numero_factura,
              :relacion_habitacion, :es_general, :observaciones, :documento_adjunto_id,
              datetime('datetime('now')'), :creado_por)'
        );
        $stmt->execute([
            ':categoria_id' => $datos['categoria_id'],
            ':descripcion' => $datos['descripcion'],
            ':importe' => $datos['importe'],
            ':fecha_gasto' => $datos['fecha_gasto'],
            ':proveedor' => $datos['proveedor'] ?? null,
            ':numero_factura' => $datos['numero_factura'] ?? null,
            ':relacion_habitacion' => $datos['relacion_habitacion'] ?? null,
            ':es_general' => $datos['es_general'] ?? 1,
            ':observaciones' => $datos['observaciones'] ?? null,
            ':documento_adjunto_id' => $datos['documento_adjunto_id'] ?? null,
            ':creado_por' => $datos['creado_por'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Obtiene los pagos de un gasto.
     *
     * @param int $gastoId
     * @return array
     */
    public function obtenerPagos(int $gastoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM pagos_gasto WHERE gasto_id = :gasto_id ORDER BY fecha_pago ASC'
        );
        $stmt->execute([':gasto_id' => $gastoId]);
        return $stmt->fetchAll();
    }

    /**
     * Calcula el total pagado de un gasto.
     *
     * @param int $gastoId
     * @return float Total pagado
     */
    public function totalPagado(int $gastoId): float
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(importe), 0) AS total FROM pagos_gasto WHERE gasto_id = :gasto_id'
        );
        $stmt->execute([':gasto_id' => $gastoId]);
        return (float) $stmt->fetch()['total'];
    }
}
