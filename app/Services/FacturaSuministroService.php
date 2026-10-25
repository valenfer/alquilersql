<?php
declare(strict_types=1);

/**
 * Servicio FacturaSuministroService — Lógica de negocio de facturas de luz y agua.
 *
 * Responsabilidad:
 *  - Coordinar el repositorio para operaciones de facturas de suministros.
 *  - Validar duplicados por proveedor, tipo y número normalizado.
 *  - Detectar periodos solapados (permitirlos solo con motivo explícito).
 *  - Gestionar estados: borrador, confirmada, anulada.
 *  - Calcular estado de pago al proveedor derivado de pagos asociados.
 *
 * Reglas (sección 7 del documento):
 *  - Número de factura obligatorio al confirmar.
 *  - Se pueden guardar borradores sin todos los datos.
 *  - Al confirmar, exigir datos económicos y de periodo necesarios.
 *  - Evitar duplicados por proveedor, suministro y número normalizado.
 *  - Avisar de periodos solapados; permitirlos solo con motivo explícito.
 */

namespace App\Services;

use App\Repositories\FacturaSuministroRepository;
use App\Support\Database;
use App\Support\Security;

class FacturaSuministroService
{
    private FacturaSuministroRepository $repo;

    public function __construct()
    {
        $this->repo = new FacturaSuministroRepository();
    }

    /**
     * Lista todas las facturas con filtros opcionales.
     *
     * @param array $filtros
     * @return array
     */
    public function listar(array $filtros = []): array
    {
        return $this->repo->obtenerTodos($filtros);
    }

    /**
     * Obtiene una factura por ID con su estado de pago al proveedor.
     *
     * @param int $id
     * @return array|null
     */
    public function obtener(int $id): ?array
    {
        $factura = $this->repo->obtenerPorId($id);
        if ($factura === null) {
            return null;
        }

        $totalPagado = $this->repo->totalPagadoProveedor($id);
        $factura['total_pagado_proveedor'] = $totalPagado;
        $factura['saldo_pendiente_proveedor'] = round($factura['importe_total'] - $totalPagado, 2);
        $factura['pagado_completo'] = $totalPagado >= $factura['importe_total'];

        return $factura;
    }

    /**
     * Valida y crea una factura de suministro.
     *
     * Reglas:
     * - Número obligatorio al confirmar (estado = 'confirmada').
     * - No duplicados por proveedor + tipo + número.
     * - Periodo solapado solo con motivo explícito.
     *
     * @param array $datos Datos de la factura
     * @param int $usuarioId ID del usuario
     * @param bool $esBorrador Si es borrador (menos validaciones)
     * @return array{exito: bool, id: int|null, errores: array}
     */
    public function crear(array $datos, int $usuarioId, bool $esBorrador = false): array
    {
        $errores = [];

        $tipo = $datos['tipo'] ?? '';
        $proveedor = trim($datos['proveedor'] ?? '');
        $numeroFactura = trim($datos['numero_factura'] ?? '');
        $fechaEmision = $datos['fecha_emision'] ?? null;
        $periodoDesde = $datos['periodo_desde'] ?? null;
        $periodoHasta = $datos['periodo_hasta'] ?? null;
        $importeTotal = Security::normalizarImporte((string) ($datos['importe_total'] ?? ''));
        $estado = $esBorrador ? 'borrador' : ($datos['estado'] ?? 'borrador');
        $observaciones = trim($datos['observaciones'] ?? '');

        // Validaciones básicas
        if ($tipo !== 'luz' && $tipo !== 'agua') {
            $errores[] = 'El tipo de suministro debe ser luz o agua.';
        }
        if ($proveedor === '') {
            $errores[] = 'El proveedor es obligatorio.';
        }
        if ($fechaEmision === null) {
            $errores[] = 'La fecha de emisión es obligatoria.';
        }
        if ($periodoDesde === null) {
            $errores[] = 'La fecha inicial del periodo es obligatoria.';
        }
        if ($periodoHasta === null) {
            $errores[] = 'La fecha final del periodo es obligatoria.';
        }
        if ($periodoDesde !== null && $periodoHasta !== null && $periodoDesde > $periodoHasta) {
            $errores[] = 'La fecha final del periodo debe ser posterior o igual a la inicial.';
        }

        // Si se confirma, exigir número de factura e importe total
        if ($estado === 'confirmada') {
            if ($numeroFactura === '') {
                $errores[] = 'El número de factura es obligatorio al confirmar.';
            }
            if ($importeTotal === null || $importeTotal <= 0) {
                $errores[] = 'El importe total es obligatorio al confirmar.';
            }
        }

        // Verificar duplicados
        if ($numeroFactura !== '' && $proveedor !== '' && $tipo !== '') {
            $existente = $this->repo->obtenerPorProveedorTipoNumero($proveedor, $tipo, $numeroFactura);
            if ($existente !== null) {
                $errores[] = 'Ya existe una factura de este proveedor con este número y tipo de suministro.';
            }
        }

        // Verificar periodos solapados
        if ($periodoDesde !== null && $periodoHasta !== null && $tipo !== '') {
            $solapamiento = $this->repo->obtenerSolapamientoPeriodo($tipo, $periodoDesde, $periodoHasta);
            if ($solapamiento !== null) {
                $motivoSolapamiento = trim($datos['motivo_solapamiento'] ?? '');
                if ($motivoSolapamiento === '') {
                    $errores[] = 'El periodo de consumo se solapa con otra factura del mismo suministro. '
                        . 'Indique un motivo explícito para permitir el solapamiento.';
                }
            }
        }

        if (!empty($errores)) {
            return ['exito' => false, 'id' => null, 'errores' => $errores];
        }

        $id = $this->repo->crear([
            'tipo' => $tipo,
            'proveedor' => $proveedor,
            'numero_factura' => $numeroFactura,
            'fecha_emision' => $fechaEmision,
            'periodo_desde' => $periodoDesde,
            'periodo_hasta' => $periodoHasta,
            'importe_total' => $importeTotal ?? 0.00,
            'fecha_vencimiento' => $datos['fecha_vencimiento'] ?? null,
            'estado' => $estado,
            'observaciones' => $observaciones,
            'creado_por' => $usuarioId,
        ]);

        return ['exito' => true, 'id' => $id, 'errores' => []];
    }

    /**
     * Valida y actualiza una factura existente.
     *
     * @param int $id ID de la factura
     * @param array $datos Datos a actualizar
     * @param int $usuarioId ID del usuario
     * @return array{exito: bool, errores: array}
     */
    public function actualizar(int $id, array $datos, int $usuarioId): array
    {
        $errores = [];

        $tipo = $datos['tipo'] ?? '';
        $proveedor = trim($datos['proveedor'] ?? '');
        $numeroFactura = trim($datos['numero_factura'] ?? '');
        $fechaEmision = $datos['fecha_emision'] ?? null;
        $periodoDesde = $datos['periodo_desde'] ?? null;
        $periodoHasta = $datos['periodo_hasta'] ?? null;
        $importeTotal = Security::normalizarImporte((string) ($datos['importe_total'] ?? ''));
        $estado = $datos['estado'] ?? 'borrador';

        // Validaciones básicas
        if ($tipo !== 'luz' && $tipo !== 'agua') {
            $errores[] = 'El tipo de suministro debe ser luz o agua.';
        }
        if ($proveedor === '') {
            $errores[] = 'El proveedor es obligatorio.';
        }
        if ($fechaEmision === null) {
            $errores[] = 'La fecha de emisión es obligatoria.';
        }
        if ($periodoDesde === null || $periodoHasta === null) {
            $errores[] = 'Las fechas del periodo son obligatorias.';
        } elseif ($periodoDesde > $periodoHasta) {
            $errores[] = 'La fecha final del periodo debe ser posterior o igual a la inicial.';
        }

        // Si se confirma, exigir número e importe
        if ($estado === 'confirmada') {
            if ($numeroFactura === '') {
                $errores[] = 'El número de factura es obligatorio al confirmar.';
            }
            if ($importeTotal === null || $importeTotal <= 0) {
                $errores[] = 'El importe total es obligatorio al confirmar.';
            }
        }

        // Verificar duplicados excluyendo esta factura
        if ($numeroFactura !== '' && $proveedor !== '' && $tipo !== '') {
            $existente = $this->repo->obtenerPorProveedorTipoNumero($proveedor, $tipo, $numeroFactura, $id);
            if ($existente !== null) {
                $errores[] = 'Ya existe una factura de este proveedor con este número y tipo de suministro.';
            }
        }

        // Verificar solapamientos excluyendo esta factura
        if ($periodoDesde !== null && $periodoHasta !== null && $tipo !== '') {
            $solapamiento = $this->repo->obtenerSolapamientoPeriodo($tipo, $periodoDesde, $periodoHasta, $id);
            if ($solapamiento !== null) {
                $motivoSolapamiento = trim($datos['motivo_solapamiento'] ?? '');
                if ($motivoSolapamiento === '') {
                    $errores[] = 'El periodo de consumo se solapa con otra factura del mismo suministro. '
                        . 'Indique un motivo explícito para permitir el solapamiento.';
                }
            }
        }

        if (!empty($errores)) {
            return ['exito' => false, 'errores' => $errores];
        }

        // Nota: no pasamos $usuarioId al repo porque el esquema no tiene columna de auditoría de actualización
        // en facturas_suministro (solo creado_por). La auditoría se maneja en la tabla auditoria.

        $resultado = $this->repo->actualizar($id, array_merge($datos, ['importe_total' => $importeTotal ?? 0.00]));
        return ['exito' => $resultado, 'errores' => $resultado ? [] : ['No se pudo actualizar la factura.']];
    }

    /**
     * Anula una factura (cambia estado a 'anulada').
     *
     * @param int $id ID de la factura
     * @param string $motivo Motivo de la anulación
     * @return array{exito: bool, error: string|null}
     */
    public function anular(int $id, string $motivo): array
    {
        $factura = $this->repo->obtenerPorId($id);
        if ($factura === null) {
            return ['exito' => false, 'error' => 'La factura no existe.'];
        }

        if ($factura['estado'] === 'anulada') {
            return ['exito' => false, 'error' => 'La factura ya está anulada.'];
        }

        $resultado = $this->repo->actualizar($id, [
            'tipo' => $factura['tipo'],
            'proveedor' => $factura['proveedor'],
            'numero_factura' => $factura['numero_factura'],
            'fecha_emision' => $factura['fecha_emision'],
            'periodo_desde' => $factura['periodo_desde'],
            'periodo_hasta' => $factura['periodo_hasta'],
            'importe_total' => $factura['importe_total'],
            'fecha_vencimiento' => $factura['fecha_vencimiento'],
            'estado' => 'anulada',
            'observaciones' => ($factura['observaciones'] ?? '') . ' | ANULADA: ' . $motivo,
            'motivo_solapamiento' => '',
        ]);

        if ($resultado) {
            return ['exito' => true, 'error' => null];
        }

        return ['exito' => false, 'error' => 'No se pudo anular la factura.'];
    }

    /**
     * Obtiene facturas con reparto pendiente (confirmadas pero sin reparto confirmado).
     *
     * @return array
     */
    public function obtenerRepartoPendiente(): array
    {
        return $this->repo->obtenerConRepartoPendiente();
    }

    /**
     * Obtiene facturas sin cargo bancario completo.
     *
     * @return array
     */
    public function obtenerSinCargoBancarioCompleto(): array
    {
        return $this->repo->obtenerSinCargoBancarioCompleto();
    }
}
