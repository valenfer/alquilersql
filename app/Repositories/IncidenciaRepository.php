<?php
declare(strict_types=1);

/**
 * Repositorio IncidenciaRepository — Acceso a datos de incidencias.
 *
 * Responsabilidad:
 *  - Consultar, insertar y actualizar incidencias (averías, reparaciones).
 *  - Obtener incidencias con filtros por estado y habitación.
 */

namespace App\Repositories;

use App\Support\Database;

class IncidenciaRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
    }

    /**
     * Obtiene todas las incidencias con filtros opcionales.
     *
     * @param array $filtros estado, habitacion_id
     * @return array
     */
    public function obtenerTodos(array $filtros = []): array
    {
        $sql = '
            SELECT i.*, h.numero AS habitacion_numero
             FROM incidencias i
             LEFT JOIN habitaciones h ON i.habitacion_id = h.id
             WHERE 1=1';
        $params = [];

        if (!empty($filtros['estado'])) {
            $sql .= ' AND i.estado = :estado';
            $params[':estado'] = $filtros['estado'];
        }
        if (!empty($filtros['habitacion_id'])) {
            $sql .= ' AND i.habitacion_id = :habitacion_id';
            $params[':habitacion_id'] = $filtros['habitacion_id'];
        }

        $sql .= ' ORDER BY i.fecha_aviso DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Crea una nueva incidencia.
     *
     * @param array $datos habitacion_id, descripcion, fecha_aviso, estado, fecha_resolucion
     * @return int ID de la incidencia creada
     */
    public function crear(array $datos): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO incidencias
             (habitacion_id, descripcion, fecha_aviso, estado, fecha_resolucion, fecha_creacion)
             VALUES
             (:habitacion_id, :descripcion, :fecha_aviso, :estado, :fecha_resolucion, datetime('datetime('now')'))'
        );
        $stmt->execute([
            ':habitacion_id' => $datos['habitacion_id'] ?? null,
            ':descripcion' => $datos['descripcion'],
            ':fecha_aviso' => $datos['fecha_aviso'],
            ':estado' => $datos['estado'] ?? 'pendiente',
            ':fecha_resolucion' => $datos['fecha_resolucion'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza el estado de una incidencia.
     *
     * @param int $id
     * @param string $estado
     * @param string|null $fechaResolucion
     * @return bool
     */
    public function actualizarEstado(int $id, string $estado, ?string $fechaResolucion = null): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE incidencias SET estado = :estado, fecha_resolucion = :fecha_resolucion WHERE id = :id'
        );
        return $stmt->execute([
            ':estado' => $estado,
            ':fecha_resolucion' => $fechaResolucion,
            ':id' => $id,
        ]);
    }
}
