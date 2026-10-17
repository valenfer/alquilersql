<?php
declare(strict_types=1);

/**
 * Repositorio EstanciaRepository — Acceso a datos de estancias (ocupaciones).
 *
 * Responsabilidad:
 *  - Consultar, insertar y actualizar estancias.
 *  - Detectar solapamientos de ocupación en una misma habitación.
 *  - Detectar estancias simultáneas de una misma persona en distintas habitaciones.
 *  - Obtener estancias con datos del inquilino y la habitación para listados.
 *
 * Todas las consultas usan PDO con consultas preparadas.
 */

namespace App\Repositories;

use App\Support\Database;

class EstanciaRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
    }

    /**
     * Obtiene todas las estancias con datos del inquilino y la habitación.
     *
     * @param array $filtros Filtros opcionales: habitacion_id, inquilino_id, estado
     * @return array Lista de estancias
     */
    public function obtenerTodos(array $filtros = []): array
    {
        $sql = '
            SELECT e.*, i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos,
                   i.numero_documento AS inquilino_documento, h.numero AS habitacion_numero,
                   h.nombre AS habitacion_nombre
            FROM estancias e
            INNER JOIN inquilinos i ON e.inquilino_id = i.id
            INNER JOIN habitaciones h ON e.habitacion_id = h.id
            WHERE 1=1';
        $params = [];

        if (!empty($filtros['habitacion_id'])) {
            $sql .= ' AND e.habitacion_id = :habitacion_id';
            $params[':habitacion_id'] = $filtros['habitacion_id'];
        }
        if (!empty($filtros['inquilino_id'])) {
            $sql .= ' AND e.inquilino_id = :inquilino_id';
            $params[':inquilino_id'] = $filtros['inquilino_id'];
        }
        if (!empty($filtros['estado'])) {
            $sql .= ' AND e.estado = :estado';
            $params[':estado'] = $filtros['estado'];
        }

        $sql .= ' ORDER BY e.fecha_entrada DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene una estancia por su ID con datos relacionados.
     *
     * @param int $id ID de la estancia
     * @return array|null Datos de la estancia o null si no existe
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.*, i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos,
                   i.numero_documento AS inquilino_documento, i.telefono, i.email,
                   h.numero AS habitacion_numero, h.nombre AS habitacion_nombre
            FROM estancias e
            INNER JOIN inquilinos i ON e.inquilino_id = i.id
            INNER JOIN habitaciones h ON e.habitacion_id = h.id
            WHERE e.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }

    /**
     * Detecta si existe una ocupación solapada en una habitación dada.
     *
     * Intervalo [entrada, salida): el día de entrada cuenta, el día de salida no cuenta.
     * Dos estancias se solapan si: entrada_a < salida_b Y entrada_b < salida_a.
     *
     * @param int $habitacionId ID de la habitación
     * @param string $fechaEntrada Fecha de entrada (Y-m-d)
     * @param string|null $fechaSalidaEfectiva Fecha de salida efectiva (null si sigue ocupada)
     * @param int|null $estanciaIdExcluir ID de la estancia a excluir (para edición)
     * @return array|null La estancia solapante encontrada o null
     */
    public function existeSolapamientoHabitacion(
        int $habitacionId,
        string $fechaEntrada,
        ?string $fechaSalidaEfectiva,
        ?int $estanciaIdExcluir = null
    ): ?array {
        // Si no hay fecha de salida, la estancia sigue abierta (salida infinita)
        $fechaSalida = $fechaSalidaEfectiva ?? '9999-12-31';

        $sql = '
            SELECT e.*, i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos
            FROM estancias e
            INNER JOIN inquilinos i ON e.inquilino_id = i.id
            WHERE e.habitacion_id = :habitacion_id
              AND e.fecha_entrada < :fecha_salida
              AND COALESCE(e.fecha_salida_efectiva, e.fecha_salida_prevista, :fecha_salida) > :fecha_entrada';
        $params = [
            ':habitacion_id' => $habitacionId,
            ':fecha_salida' => $fechaSalida,
            ':fecha_entrada' => $fechaEntrada,
        ];

        if ($estanciaIdExcluir !== null) {
            $sql .= ' AND e.id != :estancia_id';
            $params[':estancia_id'] = $estanciaIdExcluir;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }

    /**
     * Detecta si una persona ya tiene una estancia activa o prevista que se solape
     * en fechas con otra habitación (no puede ocupar dos habitaciones a la vez).
     *
     * @param int $inquilinoId ID del inquilino
     * @param string $fechaEntrada Fecha de entrada (Y-m-d)
     * @param string|null $fechaSalidaEfectiva Fecha de salida efectiva
     * @param int|null $estanciaIdExcluir ID de la estancia a excluir
     * @return array|null La estancia solapante encontrada o null
     */
    public function existeSolapamientoPersona(
        int $inquilinoId,
        string $fechaEntrada,
        ?string $fechaSalidaEfectiva,
        ?int $estanciaIdExcluir = null
    ): ?array {
        $fechaSalida = $fechaSalidaEfectiva ?? '9999-12-31';

        $sql = '
            SELECT e.*, h.numero AS habitacion_numero, h.nombre AS habitacion_nombre
            FROM estancias e
            INNER JOIN habitaciones h ON e.habitacion_id = h.id
            WHERE e.inquilino_id = :inquilino_id
              AND e.fecha_entrada < :fecha_salida
              AND COALESCE(e.fecha_salida_efectiva, e.fecha_salida_prevista, :fecha_salida) > :fecha_entrada';
        $params = [
            ':inquilino_id' => $inquilinoId,
            ':fecha_salida' => $fechaSalida,
            ':fecha_entrada' => $fechaEntrada,
        ];

        if ($estanciaIdExcluir !== null) {
            $sql .= ' AND e.id != :estancia_id';
            $params[':estancia_id'] = $estanciaIdExcluir;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }

    /**
     * Verifica si existe una estancia en una habitación para una fecha concreta
     * (para permitir salida y entrada el mismo día sin solapamiento).
     *
     * @param int $habitacionId ID de la habitación
     * @param string $fecha Fecha a comprobar (Y-m-d)
     * @return bool true si la habitación está ocupada en esa fecha
     */
    public function estaOcupadaEnFecha(int $habitacionId, string $fecha): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS total FROM estancias
             WHERE habitacion_id = :habitacion_id
               AND fecha_entrada <= :fecha
               AND COALESCE(fecha_salida_efectiva, fecha_salida_prevista, '9999-12-31') > :fecha'
        );
        $stmt->execute([
            ':habitacion_id' => $habitacionId,
            ':fecha' => $fecha,
        ]);
        return (int) $stmt->fetch()['total'] > 0;
    }

    /**
     * Crea una nueva estancia dentro de una transacción.
     *
     * @param array $datos Datos de la estancia
     * @return int ID de la estancia creada
     */
    public function crear(array $datos): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO estancias
             (inquilino_id, habitacion_id, fecha_entrada, fecha_salida_prevista,
              fecha_salida_efectiva, renta_mensual, dia_vencimiento, fianza_pactada,
              referencia_contrato, contrato_adjunto_id, estado, fecha_creacion, fecha_actualizacion)
             VALUES
             (:inquilino_id, :habitacion_id, :fecha_entrada, :fecha_salida_prevista,
              :fecha_salida_efectiva, :renta_mensual, :dia_vencimiento, :fianza_pactada,
              :referencia_contrato, :contrato_adjunto_id, :estado, NOW(), NOW())'
        );
        $stmt->execute([
            ':inquilino_id' => $datos['inquilino_id'],
            ':habitacion_id' => $datos['habitacion_id'],
            ':fecha_entrada' => $datos['fecha_entrada'],
            ':fecha_salida_prevista' => $datos['fecha_salida_prevista'] ?? null,
            ':fecha_salida_efectiva' => $datos['fecha_salida_efectiva'] ?? null,
            ':renta_mensual' => $datos['renta_mensual'],
            ':dia_vencimiento' => $datos['dia_vencimiento'] ?? 1,
            ':fianza_pactada' => $datos['fianza_pactada'] ?? 0.00,
            ':referencia_contrato' => $datos['referencia_contrato'] ?? null,
            ':contrato_adjunto_id' => $datos['contrato_adjunto_id'] ?? null,
            ':estado' => $datos['estado'] ?? 'prevista',
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza una estancia existente.
     *
     * @param int $id ID de la estancia
     * @param array $datos Datos a actualizar
     * @return bool true si se actualizó
     */
    public function actualizar(int $id, array $datos): bool
    {
        $campos = [];
        $params = [':id' => $id];

        $mapeo = [
            'inquilino_id' => 'inquilino_id',
            'habitacion_id' => 'habitacion_id',
            'fecha_entrada' => 'fecha_entrada',
            'fecha_salida_prevista' => 'fecha_salida_prevista',
            'fecha_salida_efectiva' => 'fecha_salida_efectiva',
            'renta_mensual' => 'renta_mensual',
            'dia_vencimiento' => 'dia_vencimiento',
            'fianza_pactada' => 'fianza_pactada',
            'referencia_contrato' => 'referencia_contrato',
            'contrato_adjunto_id' => 'contrato_adjunto_id',
            'estado' => 'estado',
        ];

        foreach ($mapeo as $claveOrigen => $columna) {
            if (array_key_exists($claveOrigen, $datos)) {
                $campos[] = "$columna = :$claveOrigen";
                $params[':' . $claveOrigen] = $datos[$claveOrigen];
            }
        }

        if (empty($campos)) {
            return false;
        }

        $campos[] = 'fecha_actualizacion = NOW()';
        $sql = 'UPDATE estancias SET ' . implode(', ', $campos) . ' WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Finaliza una estancia estableciendo la fecha de salida efectiva y estado finalizada.
     *
     * @param int $id ID de la estancia
     * @param string $fechaSalidaEfectiva Fecha de salida efectiva (Y-m-d)
     * @return bool true si se actualizó
     */
    public function finalizar(int $id, string $fechaSalidaEfectiva): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE estancias
             SET fecha_salida_efectiva = :fecha_salida_efectiva,
                 estado = 'finalizada',
                 fecha_actualizacion = NOW()
             WHERE id = :id'
        );
        return $stmt->execute([
            ':id' => $id,
            ':fecha_salida_efectiva' => $fechaSalidaEfectiva,
        ]);
    }

    /**
     * Obtiene la estancia activa actual de una habitación (si la hay).
     *
     * @param int $habitacionId ID de la habitación
     * @return array|null La estancia activa o null
     */
    public function obtenerActivaPorHabitacion(int $habitacionId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.*, i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos
            FROM estancias e
            INNER JOIN inquilinos i ON e.inquilino_id = i.id
            WHERE e.habitacion_id = :habitacion_id
              AND e.estado = 'activa'
              AND (e.fecha_salida_efectiva IS NULL OR e.fecha_salida_efectiva > CURDATE())
            LIMIT 1'
        );
        $stmt->execute([':habitacion_id' => $habitacionId]);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }
}
