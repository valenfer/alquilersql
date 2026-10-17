<?php
declare(strict_types=1);

/**
 * Repositorio TipoLlaveRepository — Acceso a datos de tipos de llave.
 *
 * Responsabilidad:
 *  - Consultar tipos de llave (portal, vivienda, habitación, buzón).
 */

namespace App\Repositories;

use App\Support\Database;

class TipoLlaveRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
    }

    /**
     * Obtiene todos los tipos de llave.
     *
     * @return array
     */
    public function obtenerTodos(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM tipos_llave ORDER BY nombre ASC');
        return $stmt->fetchAll();
    }
}
