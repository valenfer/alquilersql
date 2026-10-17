<?php
declare(strict_types=1);

/**
 * Repositorio ContratoRepository — Acceso a datos de contratos de alquiler.
 *
 * Responsabilidad:
 *  - Consultar, insertar y actualizar contratos de alquiler.
 *  - Obtener contratos por estancia, por estado, y por fechas de vencimiento.
 *  - Gestionar la relación entre contratos y estancias.
 *
 * Todas las consultas usan PDO con consultas preparadas.
 */

namespace App\Repositories;

use App\Support\Database;

class ContratoRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
    }

    /**
     * Obtiene todos los contratos con datos de la estancia, inquilino y habitación.
     *
     * @return array
     */
    public function obtenerTodos(): array
    {
        $stmt = $this->pdo->query(
            'SELECT c.*, h.numero AS habitacion_numero, h.nombre AS habitacion_nombre,
                    i.id AS inquilino_id, i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos,
                    i.numero_documento AS inquilino_documento,
                    e.id AS estancia_id
             FROM contratos c
             INNER JOIN estancias e ON c.estancia_id = e.id
             INNER JOIN habitaciones h ON e.habitacion_id = h.id
             INNER JOIN inquilinos i ON e.inquilino_id = i.id
             ORDER BY c.fecha_inicio DESC'
        );
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un contrato por su ID con datos relacionados.
     *
     * @param int $id
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, h.numero AS habitacion_numero, h.nombre AS habitacion_nombre,
                    i.id AS inquilino_id, i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos,
                    i.numero_documento AS inquilino_documento,
                    e.id AS estancia_id, r.id AS contrato_renovado_id
             FROM contratos c
             INNER JOIN estancias e ON c.estancia_id = e.id
             INNER JOIN habitaciones h ON e.habitacion_id = h.id
             INNER JOIN inquilinos i ON e.inquilino_id = i.id
             LEFT JOIN contratos r ON r.renovado_de_id = c.id
             WHERE c.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }

    /**
     * Obtiene el contrato vigente de una estancia.
     *
     * @param int $estanciaId
     * @return array|null
     */
    public function obtenerVigentePorEstancia(int $estanciaId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.*, h.numero AS habitacion_numero,
                    i.id AS inquilino_id, i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos
             FROM contratos c
             INNER JOIN estancias e ON c.estancia_id = e.id
             INNER JOIN habitaciones h ON e.habitacion_id = h.id
             INNER JOIN inquilinos i ON e.inquilino_id = i.id
             WHERE c.estancia_id = :estancia_id
               AND c.estado = 'vigente'
               AND c.fecha_inicio <= CURDATE()
               AND (c.fecha_fin IS NULL OR c.fecha_fin >= CURDATE())
             ORDER BY c.fecha_inicio DESC
             LIMIT 1"
        );
        $stmt->execute([':estancia_id' => $estanciaId]);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }

    /**
     * Obtiene contratos que vencen dentro de un número de días dado.
     * Útil para generar alertas de vencimiento.
     *
     * @param int $dias Número de días de antelación
     * @return array Lista de contratos por vencer
     */
    public function obtenerPorVencer(int $dias): array
    {
        $fechaLimite = date('Y-m-d', strtotime("+{$dias} days"));
        $stmt = $this->pdo->prepare(
            "SELECT c.*, h.numero AS habitacion_numero, h.nombre AS habitacion_nombre,
                    i.id AS inquilino_id, i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos,
                    i.numero_documento AS inquilino_documento,
                    DATEDIFF(c.fecha_fin, CURDATE()) AS dias_restantes
             FROM contratos c
             INNER JOIN estancias e ON c.estancia_id = e.id
             INNER JOIN habitaciones h ON e.habitacion_id = h.id
             INNER JOIN inquilinos i ON e.inquilino_id = i.id
             WHERE c.estado = 'vigente'
               AND c.fecha_fin IS NOT NULL
               AND c.fecha_fin <= :fecha_limite
               AND c.fecha_fin >= CURDATE()
             ORDER BY c.fecha_fin ASC"
        );
        $stmt->execute([':fecha_limite' => $fechaLimite]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene contratos ya vencidos que aún están marcados como vigentes.
     *
     * @return array
     */
    public function obtenerVencidos(): array
    {
        $stmt = $this->pdo->query(
            "SELECT c.*, h.numero AS habitacion_numero, h.nombre AS habitacion_nombre,
                    i.id AS inquilino_id, i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos,
                    i.numero_documento AS inquilino_documento,
                    DATEDIFF(CURDATE(), c.fecha_fin) AS dias_vencido
             FROM contratos c
             INNER JOIN estancias e ON c.estancia_id = e.id
             INNER JOIN habitaciones h ON e.habitacion_id = h.id
             INNER JOIN inquilinos i ON e.inquilino_id = i.id
             WHERE c.estado = 'vigente'
               AND c.fecha_fin IS NOT NULL
               AND c.fecha_fin < CURDATE()
             ORDER BY c.fecha_fin ASC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Crea un nuevo contrato.
     *
     * @param array $datos
     * @return int ID del contrato creado
     */
    public function crear(array $datos): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO contratos
             (estancia_id, fecha_inicio, fecha_fin, renta_mensual, condiciones,
              clausulas_adicionales, estado, documento_adjunto_id, renovado_de_id,
              fecha_creacion, fecha_actualizacion)
             VALUES
             (:estancia_id, :fecha_inicio, :fecha_fin, :renta_mensual, :condiciones,
              :clausulas_adicionales, :estado, :documento_adjunto_id, :renovado_de_id,
              NOW(), NOW())'
        );
        $stmt->execute([
            ':estancia_id' => $datos['estancia_id'],
            ':fecha_inicio' => $datos['fecha_inicio'],
            ':fecha_fin' => $datos['fecha_fin'],
            ':renta_mensual' => $datos['renta_mensual'],
            ':condiciones' => $datos['condiciones'] ?? null,
            ':clausulas_adicionales' => $datos['clausulas_adicionales'] ?? null,
            ':estado' => $datos['estado'] ?? 'vigente',
            ':documento_adjunto_id' => $datos['documento_adjunto_id'] ?? null,
            ':renovado_de_id' => $datos['renovado_de_id'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza un contrato existente.
     *
     * @param int $id
     * @param array $datos
     * @return bool
     */
    public function actualizar(int $id, array $datos): bool
    {
        $campos = [];
        $params = [':id' => $id];

        $mapeo = [
            'fecha_inicio' => 'fecha_inicio',
            'fecha_fin' => 'fecha_fin',
            'renta_mensual' => 'renta_mensual',
            'condiciones' => 'condiciones',
            'clausulas_adicionales' => 'clausulas_adicionales',
            'estado' => 'estado',
            'documento_adjunto_id' => 'documento_adjunto_id',
            'renovado_de_id' => 'renovado_de_id',
        ];

        foreach ($mapeo as $clave => $columna) {
            if (array_key_exists($clave, $datos)) {
                $campos[] = "$columna = :$clave";
                $params[':' . $clave] = $datos[$clave];
            }
        }

        if (empty($campos)) {
            return false;
        }

        $campos[] = 'fecha_actualizacion = NOW()';
        $sql = 'UPDATE contratos SET ' . implode(', ', $campos) . ' WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Marca un contrato como renovado (cuando se crea uno nuevo para reemplazarlo).
     *
     * @param int $id
     * @return bool
     */
    public function marcarComoRenovado(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE contratos SET estado = 'renovado', fecha_actualizacion = NOW() WHERE id = :id"
        );
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Obtiene todos los contratos de una estancia específica.
     *
     * @param int $estanciaId
     * @return array
     */
    public function obtenerPorEstancia(int $estanciaId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM contratos WHERE estancia_id = :estancia_id ORDER BY fecha_inicio DESC'
        );
        $stmt->execute([':estancia_id' => $estanciaId]);
        return $stmt->fetchAll();
    }

    /**
     * Marca contratos vencidos automáticamente (para ejecutar periódicamente).
     *
     * @return int Número de contratos actualizados
     */
    public function marcarVencidosAutomaticamente(): int
    {
        $stmt = $this->pdo->prepare(
            "UPDATE contratos SET estado = 'vencido', fecha_actualizacion = NOW()
             WHERE estado = 'vigente'
               AND fecha_fin IS NOT NULL
               AND fecha_fin < CURDATE()"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }
}
