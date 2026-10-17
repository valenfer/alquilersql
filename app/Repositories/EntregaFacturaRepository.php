<?php
declare(strict_types=1);

/**
 * Repositorio EntregaFacturaRepository — Acceso a datos de entregas de facturas por destinatario.
 *
 * Responsabilidad:
 *  - Consultar, insertar entregas de factura a destinatarios.
 *  - Obtener historial de entregas de una factura.
 *  - Obtener destinatarios pendientes de entrega.
 *  - Verificar si una factura está completamente entregada.
 *
 * Todas las consultas usan PDO con consultas preparadas.
 */

namespace App\Repositories;

use App\Support\Database;

class EntregaFacturaRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
    }

    /**
     * Obtiene todas las entregas de una factura.
     *
     * @param int $facturaId
     * @return array
     */
    public function obtenerPorFactura(int $facturaId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.*, i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos,
                    h.numero AS habitacion_numero
             FROM entregas_factura e
             INNER JOIN estancias s ON e.estancia_id = s.id
             INNER JOIN inquilinos i ON s.inquilino_id = i.id
             INNER JOIN habitaciones h ON s.habitacion_id = h.id
             WHERE e.factura_id = :factura_id
             ORDER BY e.fecha_entrega ASC, e.id ASC'
        );
        $stmt->execute([':factura_id' => $facturaId]);
        return $stmt->fetchAll();
    }

    /**
     * Crea una nueva entrega de factura a un destinatario.
     *
     * @param array $datos factura_id, estancia_id, fecha_entrega, medio, confirmacion_recepcion, observaciones, registrado_por
     * @return int ID de la entrega creada
     */
    public function crear(array $datos): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO entregas_factura
             (factura_id, estancia_id, fecha_entrega, medio, confirmacion_recepcion,
              observaciones, fecha_registro, registrado_por)
             VALUES
             (:factura_id, :estancia_id, :fecha_entrega, :medio, :confirmacion_recepcion,
              :observaciones, NOW(), :registrado_por)'
        );
        $stmt->execute([
            ':factura_id' => $datos['factura_id'],
            ':estancia_id' => $datos['estancia_id'],
            ':fecha_entrega' => $datos['fecha_entrega'],
            ':medio' => $datos['medio'],
            ':confirmacion_recepcion' => $datos['confirmacion_recepcion'] ?? 0,
            ':observaciones' => $datos['observaciones'] ?? null,
            ':registrado_por' => $datos['registrado_por'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Obtiene las estancias (destinatarios) de una factura que no han recibido entrega.
     *
     * Se basa en las cuotas del reparto confirmado más reciente.
     *
     * @param int $facturaId
     * @return array Lista de estancias sin entrega o con entregas pendientes
     */
    public function obtenerDestinatariosPendientes(int $facturaId): array
    {
        $reparto = $this->obtenerUltimoRepartoConfirmado($facturaId);
        if (!$reparto) {
            return [];
        }

        $cuotas = $this->obtenerCuotasSinEntregaCompleta($reparto['id']);
        return $cuotas;
    }

    /**
     * Obtiene el último reparto confirmado de una factura.
     *
     * @param int $facturaId
     * @return array|null
     */
    private function obtenerUltimoRepartoConfirmado(int $facturaId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM repartos_factura
             WHERE factura_id = :factura_id AND estado = 'confirmado'
             ORDER BY fecha_creacion DESC
             LIMIT 1'
        );
        $stmt->execute([':factura_id' => $facturaId]);
        $fila = $stmt->fetch();
        return $fila ? $fila : null;
    }

    /**
     * Obtiene cuotas de un reparto que no tienen entrega registrada.
     *
     * @param int $repartoId
     * @return array
     */
    private function obtenerCuotasSinEntregaCompleta(int $repartoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id AS cuota_id, e.id AS estancia_id, h.numero AS habitacion_numero,
                    i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos,
                    c.importe AS cuota_importe
             FROM cuotas_reparto c
             INNER JOIN estancias e ON c.estancia_id = e.id
             INNER JOIN habitaciones h ON e.habitacion_id = h.id
             INNER JOIN inquilinos i ON e.inquilino_id = i.id
             WHERE c.reparto_id = :reparto_id
               AND NOT EXISTS (
                   SELECT 1 FROM entregas_factura ef
                   WHERE ef.estancia_id = e.id AND ef.factura_id = (
                       SELECT factura_id FROM repartos_factura WHERE id = :reparto_id
                   )
               )
             ORDER BY h.numero ASC'
        );
        $stmt->execute([':reparto_id' => $repartoId]);
        return $stmt->fetchAll();
    }

    /**
     * Verifica si una factura tiene entregas registradas.
     *
     * @param int $facturaId
     * @return bool
     */
    public function tieneEntregas(int $facturaId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS total FROM entregas_factura WHERE factura_id = :factura_id'
        );
        $stmt->execute([':factura_id' => $facturaId]);
        return (int) $stmt->fetch()['total'] > 0;
    }
}
