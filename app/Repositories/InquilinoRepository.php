<?php
declare(strict_types=1);

/**
 * Repositorio InquilinoRepository — Acceso a datos de inquilinos.
 *
 * Responsabilidad:
 *  - Consultar, insertar y actualizar inquilinos.
 *  - Verificar unicidad del número de documento normalizado.
 *  - Buscar inquilinos por documento, estado y para listados con filtros.
 *
 * Todas las consultas usan PDO con consultas preparadas.
 */

namespace App\Repositories;

use App\Support\Database;

class InquilinoRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
    }

    /**
     * Obtiene todos los inquilinos, con opción de filtrar por estado.
     *
     * @param string|null $estado 'activo', 'archivado' o null para todos
     * @return array Lista de inquilinos
     */
    public function obtenerTodos(?string $estado = null): array
    {
        if ($estado !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM inquilinos WHERE estado = :estado ORDER BY apellidos, nombre'
            );
            $stmt->execute([':estado' => $estado]);
        } else {
            $stmt = $this->pdo->query(
                'SELECT * FROM inquilinos ORDER BY apellidos, nombre'
            );
        }
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un inquilino por su ID.
     *
     * @param int $id ID del inquilino
     * @return array|null Datos del inquilino o null si no existe
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM inquilinos WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }

    /**
     * Busca un inquilino por su número de documento normalizado.
     *
     * @param string $numeroDocumento Número de documento normalizado
     * @return array|null Datos del inquilino o null si no existe
     */
    public function obtenerPorDocumento(string $numeroDocumento): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM inquilinos WHERE numero_documento = :numero_documento LIMIT 1'
        );
        $stmt->execute([':numero_documento' => $numeroDocumento]);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }

    /**
     * Verifica si un documento ya existe (excluyendo un ID opcional para edición).
     *
     * @param string $numeroDocumento Número de documento
     * @param int|null $idExcluir ID a excluir de la búsqueda (para edición)
     * @return bool true si ya existe otro inquilino con ese documento
     */
    public function existeDocumento(string $numeroDocumento, ?int $idExcluir = null): bool
    {
        if ($idExcluir !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) AS total FROM inquilinos
                 WHERE numero_documento = :numero_documento AND id != :id_excluir'
            );
            $stmt->execute([
                ':numero_documento' => $numeroDocumento,
                ':id_excluir' => $idExcluir,
            ]);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) AS total FROM inquilinos WHERE numero_documento = :numero_documento'
            );
            $stmt->execute([':numero_documento' => $numeroDocumento]);
        }
        return (int) $stmt->fetch()['total'] > 0;
    }

    /**
     * Crea un nuevo inquilino.
     *
     * @param array $datos Datos del inquilino
     * @return int ID del inquilino creado
     */
    public function crear(array $datos): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO inquilinos
             (tipo_documento, numero_documento, nombre, apellidos, telefono, email,
              contacto_adicional, observaciones, estado, fecha_creacion)
             VALUES
             (:tipo_documento, :numero_documento, :nombre, :apellidos, :telefono, :email,
              :contacto_adicional, :observaciones, :estado, NOW())'
        );
        $stmt->execute([
            ':tipo_documento' => $datos['tipo_documento'],
            ':numero_documento' => $datos['numero_documento'],
            ':nombre' => $datos['nombre'],
            ':apellidos' => $datos['apellidos'],
            ':telefono' => $datos['telefono'] ?? null,
            ':email' => $datos['email'] ?? null,
            ':contacto_adicional' => $datos['contacto_adicional'] ?? null,
            ':observaciones' => $datos['observaciones'] ?? null,
            ':estado' => $datos['estado'] ?? 'activo',
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza los datos de un inquilino.
     *
     * @param int $id ID del inquilino
     * @param array $datos Datos a actualizar
     * @return bool true si se actualizó
     */
    public function actualizar(int $id, array $datos): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE inquilinos
             SET tipo_documento = :tipo_documento, numero_documento = :numero_documento,
                 nombre = :nombre, apellidos = :apellidos, telefono = :telefono,
                 email = :email, contacto_adicional = :contacto_adicional,
                 observaciones = :observaciones, estado = :estado,
                 fecha_archivado = CASE WHEN :estado = 'archivado' AND estado != 'archivado' THEN NOW() ELSE fecha_archivado END
             WHERE id = :id'
        );
        return $stmt->execute([
            ':id' => $id,
            ':tipo_documento' => $datos['tipo_documento'],
            ':numero_documento' => $datos['numero_documento'],
            ':nombre' => $datos['nombre'],
            ':apellidos' => $datos['apellidos'],
            ':telefono' => $datos['telefono'] ?? null,
            ':email' => $datos['email'] ?? null,
            ':contacto_adicional' => $datos['contacto_adicional'] ?? null,
            ':observaciones' => $datos['observaciones'] ?? null,
            ':estado' => $datos['estado'] ?? 'activo',
        ]);
    }

    /**
     * Obtiene el número de estancias asociadas a un inquilino (para impedir borrado en cascada).
     *
     * @param int $id ID del inquilino
     * @return int Número de estancias
     */
    public function contarEstancias(int $id): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS total FROM estancias WHERE inquilino_id = :id'
        );
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetch()['total'];
    }
}
