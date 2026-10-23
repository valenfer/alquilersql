<?php
declare(strict_types=1);

/**
 * Repositorio CargoAlquilerRepository — Acceso a datos de cargos de alquiler (mensualidades).
 *
 * Responsabilidad:
 *  - Consultar, insertar y actualizar cargos de alquiler por estancia y mes.
 *  - Verificar idempotencia: no generar dos veces la misma mensualidad (estancia + año + mes).
 *  - Calcular saldo pendiente y estado del cargo.
 *  - Obtener cargos pendientes de un inquilino para selección en cobros.
 *
 * Todas las consultas usan PDO con consultas preparadas.
 */

namespace App\Repositories;

use App\Support\Database;

class CargoAlquilerRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
    }

    /**
     * Obtiene todos los cargos con filtros opcionales.
     *
     * @param array $filtros Estancia, anio, mes, estado, inquilino_id
     * @return array
     */
    public function obtenerTodos(array $filtros = []): array
    {
        $sql = '
            SELECT c.*, e.inquilino_id, h.numero AS habitacion_numero,
                   i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos
            FROM cargos_alquiler c
            INNER JOIN estancias e ON c.estancia_id = e.id
            INNER JOIN habitaciones h ON e.habitacion_id = h.id
            INNER JOIN inquilinos i ON e.inquilino_id = i.id
            WHERE 1=1';
        $params = [];

        if (!empty($filtros['estancia_id'])) {
            $sql .= ' AND c.estancia_id = :estancia_id';
            $params[':estancia_id'] = $filtros['estancia_id'];
        }
        if (!empty($filtros['anio'])) {
            $sql .= ' AND c.anio = :anio';
            $params[':anio'] = $filtros['anio'];
        }
        if (!empty($filtros['mes'])) {
            $sql .= ' AND c.mes = :mes';
            $params[':mes'] = $filtros['mes'];
        }
        if (!empty($filtros['estado'])) {
            $sql .= ' AND c.estado = :estado';
            $params[':estado'] = $filtros['estado'];
        }
        if (!empty($filtros['inquilino_id'])) {
            $sql .= ' AND e.inquilino_id = :inquilino_id';
            $params[':inquilino_id'] = $filtros['inquilino_id'];
        }

        $sql .= ' ORDER BY c.anio DESC, c.mes DESC, h.numero ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un cargo por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, e.inquilino_id, h.numero AS habitacion_numero
            FROM cargos_alquiler c
            INNER JOIN estancias e ON c.estancia_id = e.id
            INNER JOIN habitaciones h ON e.habitacion_id = h.id
            WHERE c.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }

    /**
     * Verifica si ya existe un cargo para una estancia y mes dados (idempotencia).
     *
     * @param int $estanciaId
     * @param int $anio
     * @param int $mes
     * @return array|null El cargo existente o null
     */
    public function obtenerPorEstanciaYMes(int $estanciaId, int $anio, int $mes): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM cargos_alquiler WHERE estancia_id = :estancia_id AND anio = :anio AND mes = :mes LIMIT 1'
        );
        $stmt->execute([
            ':estancia_id' => $estanciaId,
            ':anio' => $anio,
            ':mes' => $mes,
        ]);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }

    /**
     * Obtiene cargos pendientes o parciales de una estancia.
     *
     * @param int $estanciaId
     * @return array
     */
    public function obtenerPendientesPorEstancia(int $estanciaId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM cargos_alquiler
             WHERE estancia_id = :estancia_id AND estado IN ('pendiente', 'parcial')
             ORDER BY anio DESC, mes DESC"
        );
        $stmt->execute([':estancia_id' => $estanciaId]);
        return $stmt->fetchAll();
    }

    /**
     * Crea un cargo de alquiler.
     *
     * @param array $datos
     * @return int ID del cargo creado
     */
    public function crear(array $datos): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO cargos_alquiler
             (estancia_id, anio, mes, periodo_desde, periodo_hasta, importe_esperado,
              fecha_vencimiento, importe_cobrado, estado, observaciones, fecha_creacion, creado_por)
             VALUES
             (:estancia_id, :anio, :mes, :periodo_desde, :periodo_hasta, :importe_esperado,
              :fecha_vencimiento, 0.00, 'pendiente', :observaciones, datetime('datetime('now')'), :creado_por)"
        );
        $stmt->execute([
            ':estancia_id' => $datos['estancia_id'],
            ':anio' => $datos['anio'],
            ':mes' => $datos['mes'],
            ':periodo_desde' => $datos['periodo_desde'],
            ':periodo_hasta' => $datos['periodo_hasta'],
            ':importe_esperado' => $datos['importe_esperado'],
            ':fecha_vencimiento' => $datos['fecha_vencimiento'],
            ':observaciones' => $datos['observaciones'] ?? null,
            ':creado_por' => $datos['creado_por'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza el importe cobrado y el estado de un cargo.
     *
     * El estado se recalcula desde los movimientos válidos, no se guarda a mano.
     *
     * @param int $id ID del cargo
     * @param float $importeCobrado Nuevo importe cobrado acumulado
     * @return bool
     */
    public function actualizarCobrado(int $id, float $importeCobrado): bool
    {
        $importeCobrado = round($importeCobrado, 2);

        $stmt = $this->pdo->prepare(
            'SELECT importe_esperado FROM cargos_alquiler WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        if (!$fila) {
            return false;
        }

        $importeEsperado = (float) $fila['importe_esperado'];

        if ($importeCobrado <= 0) {
            $estado = 'pendiente';
        } elseif ($importeCobrado >= $importeEsperado) {
            $estado = 'pagado';
        } else {
            $estado = 'parcial';
        }

        $stmt = $this->pdo->prepare(
            'UPDATE cargos_alquiler SET importe_cobrado = :importe_cobrado, estado = :estado WHERE id = :id'
        );
        return $stmt->execute([
            ':importe_cobrado' => $importeCobrado,
            ':estado' => $estado,
            ':id' => $id,
        ]);
    }

    /**
     * Calcula el saldo pendiente de un cargo.
     *
     * @param int $id
     * @return float Saldo pendiente (importe_esperado - importe_cobrado)
     */
    public function calcularSaldoPendiente(int $id): float
    {
        $stmt = $this->pdo->prepare(
            'SELECT importe_esperado, importe_cobrado FROM cargos_alquiler WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        if (!$fila) {
            return 0.0;
        }
        return round((float) $fila['importe_esperado'] - (float) $fila['importe_cobrado'], 2);
    }

    /**
     * Verifica si una fecha de vencimiento es válida para el mes dado.
     * Si el día no existe en el mes, se usa el último día.
     *
     * @param int $anio
     * @param int $mes
     * @param int $dia
     * @return string Fecha en formato Y-m-d
     */
    public static function fechaVencimientoValida(int $anio, int $mes, int $dia): string
    {
        $ultimoDia = (int) date('t', mktime(0, 0, 0, $mes, 1, $anio));
        $diaValido = min($dia, $ultimoDia);
        return sprintf('%04d-%02d-%02d', $anio, $mes, $diaValido);
    }
}
