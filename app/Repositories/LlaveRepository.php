<?php
declare(strict_types=1);

/**
 * Repositorio LlaveRepository — Acceso a datos de llaves y sus movimientos.
 *
 * Responsabilidad:
 *  - Consultar unidades de llave con su estado.
 *  - Obtener movimientos de llave por estancia.
 *  - Calcular cantidad pendiente de devolver por estancia y tipo de llave.
 *
 * Todas las consultas usan PDO con consultas preparadas.
 */

namespace App\Repositories;

use App\Support\Database;

class LlaveRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
    }

    /**
     * Obtiene todas las unidades de llave con su tipo.
     *
     * @return array
     */
    public function obtenerTodas(): array
    {
        $stmt = $this->pdo->query(
            'SELECT l.*, t.nombre AS tipo_nombre
             FROM llaves l
             INNER JOIN tipos_llave t ON l.tipo_llave_id = t.id
             ORDER BY t.nombre, l.identificador'
        );
        return $stmt->fetchAll();
    }

    /**
     * Obtiene movimientos de llave de una estancia.
     *
     * @param int $estanciaId
     * @return array
     */
    public function obtenerMovimientosPorEstancia(int $estanciaId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT m.*, t.nombre AS tipo_llave_nombre, l.identificador
             FROM movimientos_llave m
             LEFT JOIN llaves l ON m.llave_id = l.id
             LEFT JOIN tipos_llave t ON l.tipo_llave_id = t.id
             WHERE m.estancia_id = :estancia_id
             ORDER BY m.fecha ASC, m.id ASC'
        );
        $stmt->execute([':estancia_id' => $estanciaId]);
        return $stmt->fetchAll();
    }

    /**
     * Calcula la cantidad pendiente de devolver para una estancia y tipo de llave.
     *
     * @param int $estanciaId
     * @param int|null $tipoLlaveId
     * @return int Cantidad pendiente
     */
    public function calcularPendienteDevolver(int $estanciaId, ?int $tipoLlaveId = null): int
    {
        $sql = "
            SELECT COALESCE(SUM(CASE WHEN m.tipo = 'entrega' THEN m.cantidad ELSE -m.cantidad END), 0) AS pendiente
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
        return (int) $stmt->fetch()['pendiente'];
    }

    /**
     * Crea una unidad de llave.
     *
     * @param int $tipoLlaveId
     * @param string|null $identificador
     * @param string $estado
     * @return int ID de la llave creada
     */
    public function crear(int $tipoLlaveId, ?string $identificador, string $estado): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO llaves (tipo_llave_id, identificador, estado)
             VALUES (:tipo_llave_id, :identificador, :estado)'
        );
        $stmt->execute([
            ':tipo_llave_id' => $tipoLlaveId,
            ':identificador' => $identificador,
            ':estado' => $estado,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza el estado de una llave.
     *
     * @param int $id
     * @param string $estado
     * @return bool
     */
    public function actualizarEstado(int $id, string $estado): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE llaves SET estado = :estado WHERE id = :id'
        );
        return $stmt->execute([':estado' => $estado, ':id' => $id]);
    }
}
