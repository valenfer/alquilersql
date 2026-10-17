<?php
declare(strict_types=1);

/**
 * Repositorio HabitacionRepository — Acceso a datos de habitaciones.
 *
 * Responsabilidad:
 *  - Consultar, insertar, actualizar y eliminar habitaciones en la base de datos.
 *  - Verificar si una habitación tiene historial (estancias asociadas) antes de permitir su borrado.
 *  - Contar el número total de habitaciones (para impedir crear una quinta).
 *
 * Todas las consultas usan PDO con consultas preparadas.
 */

namespace App\Repositories;

use App\Support\Database;

class HabitacionRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
    }

    /**
     * Obtiene todas las habitaciones ordenadas por número.
     *
     * @return array Lista de habitaciones
     */
    public function obtenerTodas(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM habitaciones ORDER BY numero ASC'
        );
        return $stmt->fetchAll();
    }

    /**
     * Obtiene una habitación por su ID.
     *
     * @param int $id ID de la habitación
     * @return array|null Datos de la habitación o null si no existe
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM habitaciones WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }

    /**
     * Obtiene una habitación por su número.
     *
     * @param int $numero Número de habitación (1-4)
     * @return array|null Datos de la habitación o null si no existe
     */
    public function obtenerPorNumero(int $numero): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM habitaciones WHERE numero = :numero LIMIT 1'
        );
        $stmt->execute([':numero' => $numero]);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }

    /**
     * Verifica si una habitación tiene estancias asociadas (historial).
     *
     * @param int $id ID de la habitación
     * @return bool true si tiene al menos una estancia
     */
    public function tieneHistorial(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS total FROM estancias WHERE habitacion_id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetch()['total'] > 0;
    }

    /**
     * Cuenta el número total de habitaciones creadas.
     *
     * @return int Número de habitaciones
     */
    public function contarTotal(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) AS total FROM habitaciones');
        return (int) $stmt->fetch()['total'];
    }

    /**
     * Crea una nueva habitación.
     *
     * @param int $numero Número de habitación (1-4)
     * @param string $nombre Nombre visible
     * @param string|null $descripcion Descripción opcional
     * @param float|null $rentaOrientativa Renta orientativa
     * @param string $estado Estado inicial
     * @return int ID de la habitación creada
     */
    public function crear(
        int $numero,
        string $nombre,
        ?string $descripcion,
        ?float $rentaOrientativa,
        string $estado
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO habitaciones (numero, nombre, descripcion, renta_orientativa, estado, fecha_creacion)
             VALUES (:numero, :nombre, :descripcion, :renta_orientativa, :estado, NOW())'
        );
        $stmt->execute([
            ':numero' => $numero,
            ':nombre' => $nombre,
            ':descripcion' => $descripcion,
            ':renta_orientativa' => $rentaOrientativa,
            ':estado' => $estado,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza los datos de una habitación.
     *
     * @param int $id ID de la habitación
     * @param string $nombre Nombre visible
     * @param string|null $descripcion Descripción opcional
     * @param float|null $rentaOrientativa Renta orientativa
     * @param string $estado Estado
     * @return bool true si se actualizó
     */
    public function actualizar(
        int $id,
        string $nombre,
        ?string $descripcion,
        ?float $rentaOrientativa,
        string $estado
    ): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE habitaciones
             SET nombre = :nombre, descripcion = :descripcion, renta_orientativa = :renta_orientativa, estado = :estado
             WHERE id = :id'
        );
        return $stmt->execute([
            ':id' => $id,
            ':nombre' => $nombre,
            ':descripcion' => $descripcion,
            ':renta_orientativa' => $rentaOrientativa,
            ':estado' => $estado,
        ]);
    }

    /**
     * Elimina una habitación solo si no tiene historial.
     *
     * @param int $id ID de la habitación
     * @return bool true si se eliminó
     */
    public function eliminar(int $id): bool
    {
        if ($this->tieneHistorial($id)) {
            return false;
        }

        $stmt = $this->pdo->prepare('DELETE FROM habitaciones WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }
}
