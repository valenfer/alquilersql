<?php
declare(strict_types=1);

/**
 * Repositorio FianzaRepository — Acceso a datos de fianzas.
 *
 * Responsabilidad:
 *  - Consultar, insertar y actualizar fianzas por estancia.
 *  - Calcular saldo disponible (recibido - devuelto - aplicado a deuda).
 *  - Obtener fianzas con datos del inquilino y habitación.
 *
 * Todas las consultas usan PDO con consultas preparadas.
 */

namespace App\Repositories;

use App\Support\Database;

class FianzaRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
    }

    /**
     * Obtiene todas las fianzas con datos relacionados.
     *
     * @return array
     */
    public function obtenerTodas(): array
    {
        $stmt = $this->pdo->query(
            'SELECT f.*, e.id AS estancia_id, h.numero AS habitacion_numero,
                   i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos
             FROM fianzas f
             INNER JOIN estancias e ON f.estancia_id = e.id
             INNER JOIN habitaciones h ON e.habitacion_id = h.id
             INNER JOIN inquilinos i ON e.inquilino_id = i.id
             ORDER BY h.numero ASC'
        );
        return $stmt->fetchAll();
    }

    /**
     * Obtiene una fianza por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT f.*, e.id AS estancia_id, h.numero AS habitacion_numero,
                   i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos
             FROM fianzas f
             INNER JOIN estancias e ON f.estancia_id = e.id
             INNER JOIN habitaciones h ON e.habitacion_id = h.id
             INNER JOIN inquilinos i ON e.inquilino_id = i.id
             WHERE f.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }

    /**
     * Obtiene la fianza de una estancia.
     *
     * @param int $estanciaId
     * @return array|null
     */
    public function obtenerPorEstancia(int $estanciaId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT f.* FROM fianzas f WHERE f.estancia_id = :estancia_id LIMIT 1'
        );
        $stmt->execute([':estancia_id' => $estanciaId]);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }

    /**
     * Calcula el saldo disponible de la fianza.
     *
     * Saldo = recibido - devuelto - aplicado a deuda
     *
     * @param int $fianzaId
     * @return float Saldo disponible
     */
    public function calcularSaldoDisponible(int $fianzaId): float
    {
        $stmt = $this->pdo->prepare(
            'SELECT importe_recibido, importe_devuelto, importe_aplicado_deuda FROM fianzas WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $fianzaId]);
        $fila = $stmt->fetch();
        if (!$fila) {
            return 0.0;
        }

        return round(
            (float) $fila['importe_recibido']
            - (float) $fila['importe_devuelto']
            - (float) $fila['importe_aplicado_deuda'],
            2
        );
    }

    /**
     * Crea una nueva fianza.
     *
     * @param array $datos estancia_id, importe_pactado, importe_recibido, ...
     * @return int ID de la fianza creada
     */
    public function crear(array $datos): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO fianzas
             (estancia_id, importe_pactado, importe_recibido, importe_devuelto,
              importe_aplicado_deuda, saldo_pendiente_devolver, fecha_creacion)
             VALUES
             (:estancia_id, :importe_pactado, :importe_recibido, 0.00, 0.00,
              :saldo_pendiente_devolver, datetime('datetime('now')'))'
        );
        $saldoPendiente = (float) ($datos['importe_recibido'] ?? 0.00);
        $stmt->execute([
            ':estancia_id' => $datos['estancia_id'],
            ':importe_pactado' => $datos['importe_pactado'],
            ':importe_recibido' => $datos['importe_recibido'] ?? 0.00,
            ':saldo_pendiente_devolver' => $saldoPendiente,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza los importes de una fianza (recibido, devuelto, aplicado).
     *
     * @param int $id ID de la fianza
     * @param array $datos Campos a actualizar
     * @return bool
     */
    public function actualizarImportes(int $id, array $datos): bool
    {
        $campos = [];
        $params = [':id' => $id];

        $mapeo = [
            'importe_recibido' => 'importe_recibido',
            'importe_devuelto' => 'importe_devuelto',
            'importe_aplicado_deuda' => 'importe_aplicado_deuda',
            'saldo_pendiente_devolver' => 'saldo_pendiente_devolver',
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

        $sql = 'UPDATE fianzas SET ' . implode(', ', $campos) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
}
