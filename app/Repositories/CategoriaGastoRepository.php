<?php
declare(strict_types=1);

/**
 * Repositorio CategoriaGastoRepository — Acceso a datos de categorías de gasto.
 */

namespace App\Repositories;

use App\Support\Database;

class CategoriaGastoRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
    }

    /**
     * Obtiene todas las categorías de gasto.
     *
     * @return array
     */
    public function obtenerTodos(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM categorias_gasto ORDER BY nombre ASC');
        return $stmt->fetchAll();
    }
}
