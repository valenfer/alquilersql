<?php
declare(strict_types=1);

/**
 * Servicio EntregaFacturaService — Lógica de negocio de entregas de facturas por destinatario.
 *
 * Responsabilidad:
 *  - Registrar entregas de factura a destinatarios individuales.
 *  - Obtener historial de entregas de una factura.
 *  - Identificar destinatarios pendientes de entrega.
 *  - Registrar entrega a todos los destinatarios seleccionados de una vez.
 *
 * Reglas (sección 7.4 del documento):
 *  - Cada factura tiene un historial de entregas por destinatario, no una única fecha global.
 *  - Conservar varias entregas o recordatorios sin sobrescribir las anteriores.
 *  - La acción "Registrar entrega a todos los destinatarios" crea un registro individual por destinatario.
 *  - Solo registra, no envía nada.
 */

namespace App\Services;

use App\Repositories\EntregaFacturaRepository;
use App\Repositories\FacturaSuministroRepository;
use App\Support\Database;

class EntregaFacturaService
{
    private EntregaFacturaRepository $repo;
    private FacturaSuministroRepository $facturaRepo;

    public function __construct()
    {
        $this->repo = new EntregaFacturaRepository();
        $this->facturaRepo = new FacturaSuministroRepository();
    }

    /**
     * Obtiene todas las entregas de una factura con datos del destinatario.
     *
     * @param int $facturaId
     * @return array
     */
    public function listar(int $facturaId): array
    {
        return $this->repo->obtenerPorFactura($facturaId);
    }

    /**
     * Obtiene los destinatarios pendientes de entrega de una factura.
     *
     * @param int $facturaId
     * @return array
     */
    public function obtenerPendientes(int $facturaId): array
    {
        return $this->repo->obtenerDestinatariosPendientes($facturaId);
    }

    /**
     * Registra una entrega de factura a un destinatario individual.
     *
     * @param array $datos factura_id, estancia_id, fecha_entrega, medio, confirmacion_recepcion, observaciones
     * @param int $usuarioId
     * @return array{exito: bool, id: int|null, errores: array}
     */
    public function registrar(array $datos, int $usuarioId): array
    {
        $errores = [];

        $facturaId = (int) ($datos['factura_id'] ?? 0);
        $estanciaId = (int) ($datos['estancia_id'] ?? 0);
        $fechaEntrega = $datos['fecha_entrega'] ?? null;
        $medio = trim($datos['medio'] ?? '');

        if ($facturaId <= 0) {
            $errores[] = 'Factura no válida.';
        }
        if ($estanciaId <= 0) {
            $errores[] = 'Destinatario no válido.';
        }
        if ($fechaEntrega === null) {
            $errores[] = 'La fecha de entrega es obligatoria.';
        }
        if ($medio === '') {
            $errores[] = 'El medio de entrega es obligatorio.';
        }

        if (!empty($errores)) {
            return ['exito' => false, 'id' => null, 'errores' => $errores];
        }

        $id = $this->repo->crear([
            'factura_id' => $facturaId,
            'estancia_id' => $estanciaId,
            'fecha_entrega' => $fechaEntrega,
            'medio' => $medio,
            'confirmacion_recepcion' => $datos['confirmacion_recepcion'] ?? 0,
            'observaciones' => $datos['observaciones'] ?? null,
            'registrado_por' => $usuarioId,
        ]);

        return ['exito' => true, 'id' => $id, 'errores' => []];
    }

    /**
     * Registra entregas a todos los destinatarios seleccionados de una vez.
     *
     * @param int $facturaId
     * @param array $estanciaIds IDs de estancias destinatarias
     * @param string $fechaEntrega
     * @param string $medio
     * @param int $usuarioId
     * @return array{exito: bool, creados: int, errores: array}
     */
    public function registrarATodos(
        int $facturaId,
        array $estanciaIds,
        string $fechaEntrega,
        string $medio,
        int $usuarioId
    ): array {
        $creados = 0;
        $errores = [];

        foreach ($estanciaIds as $estanciaId) {
            $resultado = $this->registrar([
                'factura_id' => $facturaId,
                'estancia_id' => $estanciaId,
                'fecha_entrega' => $fechaEntrega,
                'medio' => $medio,
                'registrado_por' => $usuarioId,
            ], $usuarioId);

            if ($resultado['exito']) {
                $creados++;
            } else {
                $errores[] = 'Estancia #' . $estanciaId . ': ' . implode(' ', $resultado['errores']);
            }
        }

        return [
            'exito' => empty($errores),
            'creados' => $creados,
            'errores' => $errores,
        ];
    }

    /**
     * Verifica si una factura tiene entregas registradas.
     *
     * @param int $facturaId
     * @return bool
     */
    public function tieneEntregas(int $facturaId): bool
    {
        return $this->repo->tieneEntregas($facturaId);
    }
}
