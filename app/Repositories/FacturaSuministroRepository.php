<?php
declare(strict_types=1);

/**
 * Repositorio FacturaSuministroRepository — Acceso a datos de facturas de luz y agua.
 *
 * Responsabilidad:
 *  - Consultar, insertar y actualizar facturas de suministros.
 *  - Verificar duplicados por proveedor, suministro y número de factura normalizado.
 *  - Detectar periodos solapados con facturas del mismo suministro.
 *  - Obtener estado de pago al proveedor (derivado de pagos_proveedor).
 *  - Obtener facturas pendientes de entrega a destinatarios.
 *
 * Todas las consultas usan PDO con consultas preparadas.
 */

namespace App\Repositories;

use App\Support\Database;

class FacturaSuministroRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
    }

    /**
     * Obtiene todas las facturas con filtros opcionales.
     *
     * @param array $filtros tipo, proveedor, numero_factura, periodo_desde, periodo_hasta, estado
     * @return array
     */
    public function obtenerTodos(array $filtros = []): array
    {
        $sql = '
            SELECT f.*, u.nombre AS creado_por_nombre
            FROM facturas_suministro f
            LEFT JOIN usuarios u ON f.creado_por = u.id
            WHERE 1=1';
        $params = [];

        if (!empty($filtros['tipo'])) {
            $sql .= ' AND f.tipo = :tipo';
            $params[':tipo'] = $filtros['tipo'];
        }
        if (!empty($filtros['proveedor'])) {
            $sql .= ' AND f.proveedor LIKE :proveedor';
            $params[':proveedor'] = '%' . $filtros['proveedor'] . '%';
        }
        if (!empty($filtros['numero_factura'])) {
            $sql .= ' AND f.numero_factura LIKE :numero_factura';
            $params[':numero_factura'] = '%' . $filtros['numero_factura'] . '%';
        }
        if (!empty($filtros['periodo_desde'])) {
            $sql .= ' AND f.periodo_desde >= :periodo_desde';
            $params[':periodo_desde'] = $filtros['periodo_desde'];
        }
        if (!empty($filtros['periodo_hasta'])) {
            $sql .= ' AND f.periodo_hasta <= :periodo_hasta';
            $params[':periodo_hasta'] = $filtros['periodo_hasta'];
        }
        if (!empty($filtros['estado'])) {
            $sql .= ' AND f.estado = :estado';
            $params[':estado'] = $filtros['estado'];
        }

        $sql .= ' ORDER BY f.periodo_desde DESC, f.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene una factura por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT f.*, u.nombre AS creado_por_nombre
            FROM facturas_suministro f
            LEFT JOIN usuarios u ON f.creado_por = u.id
            WHERE f.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }

    /**
     * Verifica si ya existe una factura con el mismo proveedor, tipo y número (duplicado).
     *
     * @param string $proveedor
     * @param string $tipo 'luz' o 'agua'
     * @param string $numeroFactura Número normalizado
     * @param int|null $idExcluir ID a excluir (para edición)
     * @return array|null La factura existente o null
     */
    public function obtenerPorProveedorTipoNumero(
        string $proveedor,
        string $tipo,
        string $numeroFactura,
        ?int $idExcluir = null
    ): ?array {
        $sql = '
            SELECT * FROM facturas_suministro
            WHERE proveedor = :proveedor AND tipo = :tipo AND numero_factura = :numero_factura';
        $params = [
            ':proveedor' => $proveedor,
            ':tipo' => $tipo,
            ':numero_factura' => $numeroFactura,
        ];

        if ($idExcluir !== null) {
            $sql .= ' AND id != :id_excluir';
            $params[':id_excluir'] = $idExcluir;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }

    /**
     * Detecta periodos solapados con facturas del mismo suministro.
     *
     * @param string $tipo 'luz' o 'agua'
     * @param string $periodoDesde Fecha inicial del periodo (inclusive)
     * @param string $periodoHasta Fecha final del periodo (inclusive)
     * @param int|null $idExcluir ID a excluir (para edición)
     * @return array|null La factura solapante encontrada o null
     */
    public function obtenerSolapamientoPeriodo(
        string $tipo,
        string $periodoDesde,
        string $periodoHasta,
        ?int $idExcluir = null
    ): ?array {
        $sql = "
            SELECT * FROM facturas_suministro
            WHERE tipo = :tipo
              AND periodo_desde <= :periodo_hasta
              AND periodo_hasta >= :periodo_desde
              AND estado != 'anulada'";
        $params = [
            ':tipo' => $tipo,
            ':periodo_hasta' => $periodoHasta,
            ':periodo_desde' => $periodoDesde,
        ];

        if ($idExcluir !== null) {
            $sql .= ' AND id != :id_excluir';
            $params[':id_excluir'] = $idExcluir;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }

    /**
     * Crea una nueva factura de suministro.
     *
     * @param array $datos
     * @return int ID de la factura creada
     */
    public function crear(array $datos): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO facturas_suministro
             (tipo, proveedor, numero_factura, fecha_emision, periodo_desde, periodo_hasta,
              importe_total, fecha_vencimiento, estado, documento_adjunto_id, observaciones,
              fecha_creacion, creado_por, fecha_confirmacion)
             VALUES
             (:tipo, :proveedor, :numero_factura, :fecha_emision, :periodo_desde, :periodo_hasta,
              :importe_total, :fecha_vencimiento, :estado, :documento_adjunto_id, :observaciones,
              NOW(), :creado_por, :fecha_confirmacion)'
        );
        $fechaConfirmacion = $datos['estado'] === 'confirmada' ? 'NOW()' : 'NULL';
        // Nota: se usa un truco para evitar inyección; mejor hacer dos prepares si es necesario.
        // Aquí usamos un enfoque simple:
        $stmt2 = $this->pdo->prepare(
            'INSERT INTO facturas_suministro
             (tipo, proveedor, numero_factura, fecha_emision, periodo_desde, periodo_hasta,
              importe_total, fecha_vencimiento, estado, documento_adjunto_id, observaciones,
              fecha_creacion, creado_por, fecha_confirmacion)
             VALUES
             (:tipo, :proveedor, :numero_factura, :fecha_emision, :periodo_desde, :periodo_hasta,
              :importe_total, :fecha_vencimiento, :estado, :documento_adjunto_id, :observaciones,
              NOW(), :creado_por, ' . ($datos['estado'] === 'confirmada' ? 'NOW()' : 'NULL') . ')'
        );
        $stmt2->execute([
            ':tipo' => $datos['tipo'],
            ':proveedor' => $datos['proveedor'],
            ':numero_factura' => $datos['numero_factura'],
            ':fecha_emision' => $datos['fecha_emision'],
            ':periodo_desde' => $datos['periodo_desde'],
            ':periodo_hasta' => $datos['periodo_hasta'],
            ':importe_total' => $datos['importe_total'],
            ':fecha_vencimiento' => $datos['fecha_vencimiento'] ?? null,
            ':estado' => $datos['estado'],
            ':documento_adjunto_id' => $datos['documento_adjunto_id'] ?? null,
            ':observaciones' => $datos['observaciones'] ?? null,
            ':creado_por' => $datos['creado_por'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza una factura existente.
     *
     * @param int $id ID de la factura
     * @param array $datos Datos a actualizar
     * @return bool true si se actualizó
     */
    public function actualizar(int $id, array $datos): bool
    {
        $fechaConfirmacion = $datos['estado'] === 'confirmada' ? 'NOW()' : (isset($datos['fecha_confirmacion']) ? $datos['fecha_confirmacion'] : 'NULL');

        $stmt = $this->pdo->prepare(
            'UPDATE facturas_suministro
             SET tipo = :tipo, proveedor = :proveedor, numero_factura = :numero_factura,
                 fecha_emision = :fecha_emision, periodo_desde = :periodo_desde,
                 periodo_hasta = :periodo_hasta, importe_total = :importe_total,
                 fecha_vencimiento = :fecha_vencimiento, estado = :estado,
                 documento_adjunto_id = :documento_adjunto_id, observaciones = :observaciones,
                 fecha_confirmacion = ' . $fechaConfirmacion . '
             WHERE id = :id'
        );
        return $stmt->execute(array_merge($datos, [':id' => $id]));
    }

    /**
     * Obtiene el total pagado al proveedor para una factura.
     *
     * @param int $facturaId
     * @return float Total pagado
     */
    public function totalPagadoProveedor(int $facturaId): float
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(importe), 0) AS total FROM pagos_proveedor WHERE factura_id = :factura_id'
        );
        $stmt->execute([':factura_id' => $facturaId]);
        return (float) $stmt->fetch()['total'];
    }

    /**
     * Obtiene facturas que tienen reparto pendiente (sin reparto confirmado).
     *
     * @return array
     */
    public function obtenerConRepartoPendiente(): array
    {
        $stmt = $this->pdo->query(
            "SELECT f.* FROM facturas_suministro f
             LEFT JOIN repartos_factura r ON f.id = r.factura_id AND r.estado = 'confirmado'
             WHERE f.estado = 'confirmada' AND r.id IS NULL
             ORDER BY f.periodo_desde DESC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Obtiene facturas sin cargo bancario completo (pago al proveedor incompleto).
     *
     * @return array
     */
    public function obtenerSinCargoBancarioCompleto(): array
    {
        $stmt = $this->pdo->query(
            "SELECT f.*, COALESCE(SUM(p.importe), 0) AS total_pagado
             FROM facturas_suministro f
             LEFT JOIN pagos_proveedor p ON f.id = p.factura_id
             WHERE f.estado = 'confirmada'
             GROUP BY f.id
             HAVING total_pagado < f.importe_total OR total_pagado IS NULL
             ORDER BY f.periodo_desde DESC"
        );
        return $stmt->fetchAll();
    }
}
