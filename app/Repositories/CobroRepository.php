<?php
declare(strict_types=1);

/**
 * Repositorio CobroRepository — Acceso a datos de cobros recibidos.
 *
 * Responsabilidad:
 *  - Consultar, insertar y actualizar cobros (movimientos de dinero recibido).
 *  - Verificar idempotencia mediante token (prevenir reenvíos duplicados).
 *  - Obtener cobros de un inquilino para listados y conciliación.
 *  - Nunca mezcla inquilinos ni fianzas en el mismo cobro.
 *
 * Todas las consultas usan PDO con consultas preparadas.
 */

namespace App\Repositories;

use App\Support\Database;

class CobroRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
    }

    /**
     * Obtiene todos los cobros con filtros opcionales.
     *
     * @param array $filtros inquilino_id, fecha_desde, fecha_hasta, medio
     * @return array
     */
    public function obtenerTodos(array $filtros = []): array
    {
        $sql = '
            SELECT c.*, i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos
            FROM cobros c
            INNER JOIN inquilinos i ON c.inquilino_id = i.id
            WHERE 1=1';
        $params = [];

        if (!empty($filtros['inquilino_id'])) {
            $sql .= ' AND c.inquilino_id = :inquilino_id';
            $params[':inquilino_id'] = $filtros['inquilino_id'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $sql .= ' AND c.fecha_cobro >= :fecha_desde';
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= ' AND c.fecha_cobro <= :fecha_hasta';
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['medio'])) {
            $sql .= ' AND c.medio = :medio';
            $params[':medio'] = $filtros['medio'];
        }

        $sql .= ' ORDER BY c.fecha_cobro DESC, c.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un cobro por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos
            FROM cobros c
            INNER JOIN inquilinos i ON c.inquilino_id = i.id
            WHERE c.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }

    /**
     * Verifica si ya existe un cobro con el mismo token de idempotencia.
     *
     * @param string $token
     * @return array|null
     */
    public function obtenerPorToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM cobros WHERE token_idempotencia = :token LIMIT 1'
        );
        $stmt->execute([':token' => $token]);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }

    /**
     * Crea un nuevo cobro.
     *
     * @param array $datos
     * @return int ID del cobro creado
     */
    public function crear(array $datos): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO cobros
             (inquilino_id, fecha_cobro, medio, importe_total, referencia, observaciones,
              token_idempotencia, fecha_registro, registrado_por)
             VALUES
             (:inquilino_id, :fecha_cobro, :medio, :importe_total, :referencia, :observaciones,
              :token_idempotencia, datetime('datetime('now')'), :registrado_por)'
        );
        $stmt->execute([
            ':inquilino_id' => $datos['inquilino_id'],
            ':fecha_cobro' => $datos['fecha_cobro'],
            ':medio' => $datos['medio'],
            ':importe_total' => $datos['importe_total'],
            ':referencia' => $datos['referencia'] ?? null,
            ':observaciones' => $datos['observaciones'] ?? null,
            ':token_idempotencia' => $datos['token_idempotencia'] ?? null,
            ':registrado_por' => $datos['registrado_por'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Obtiene las aplicaciones de un cobro con detalles del destino.
     *
     * @param int $cobroId
     * @return array
     */
    public function obtenerAplicaciones(int $cobroId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, ca.anio, ca.mes, ca.periodo_desde, ca.periodo_hasta,
                    h.numero AS habitacion_numero, i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos
            FROM aplicaciones_cobro a
            LEFT JOIN cargos_alquiler ca ON a.cargo_alquiler_id = ca.id
            LEFT JOIN estancias e ON ca.estancia_id = e.id
            LEFT JOIN habitaciones h ON e.habitacion_id = h.id
            LEFT JOIN inquilinos i ON e.inquilino_id = i.id
            WHERE a.cobro_id = :cobro_id'
        );
        $stmt->execute([':cobro_id' => $cobroId]);
        return $stmt->fetchAll();
    }
}
