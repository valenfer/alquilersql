<?php
declare(strict_types=1);

/**
 * Vista facturas/ver.php — Detalle de una factura de suministro.
 *
 * Responsabilidad:
 *  - Mostrar información completa: tipo, proveedor, número, periodo, importe, estado.
 *  - Mostrar pagos al proveedor registrados (cargos bancarios).
 *  - Mostrar estado de pago al proveedor y saldo pendiente.
 *  - Botones para reparto, entregas y comprobación.
 */

?>
<div class="tarjeta-facturas">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash)): ?>
        <?php foreach ($flash as $msg): ?>
            <div class="alerta alerta-<?= Security::html($msg['tipo']) ?>" role="alert"><?= Security::html($msg['valor']) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="detalle-factura">
        <div class="campo-detalle">
            <label>Tipo de suministro</label>
            <span><?= Security::html(ucfirst($factura['tipo'])) ?></span>
        </div>
        <div class="campo-detalle">
            <label>Proveedor</label>
            <span><?= Security::html($factura['proveedor']) ?></span>
        </div>
        <div class="campo-detalle">
            <label>Número de factura</label>
            <span><?= $factura['numero_factura'] !== null ? Security::html($factura['numero_factura']) : '—' ?></span>
        </div>
        <div class="campo-detalle">
            <label>Fecha de emisión</label>
            <span><?= Security::html(date('d/m/Y', strtotime($factura['fecha_emision']))) ?></span>
        </div>
        <div class="campo-detalle">
            <label>Periodo de consumo</label>
            <span><?= Security::html(date('d/m/Y', strtotime($factura['periodo_desde']))) . ' — ' . date('d/m/Y', strtotime($factura['periodo_hasta'])) ?></span>
        </div>
        <div class="campo-detalle">
            <label>Importe total</label>
            <span><?= Security::html(number_format($factura['importe_total'], 2, ',', '.')) . ' €' ?></span>
        </div>
        <div class="campo-detalle">
            <label>Fecha de vencimiento / cargo bancario</label>
            <span><?= $factura['fecha_vencimiento'] ? Security::html(date('d/m/Y', strtotime($factura['fecha_vencimiento']))) : '—' ?></span>
        </div>
        <div class="campo-detalle">
            <label>Estado de la factura</label>
            <span class="estado estado-<?= Security::html($factura['estado']) ?>">
                <?= Security::html(ucfirst($factura['estado'])) ?>
            </span>
        </div>
        <div class="campo-detalle">
            <label>Pagado al proveedor</label>
            <span><?= Security::html(number_format($factura['total_pagado_proveedor'] ?? 0, 2, ',', '.')) . ' €' ?></span>
        </div>
        <div class="campo-detalle">
            <label>Saldo pendiente al proveedor</label>
            <span><?= Security::html(number_format($factura['saldo_pendiente_proveedor'] ?? 0, 2, ',', '.')) . ' €' ?></span>
        </div>
    </div>

    <h3>Pagos al proveedor (cargos bancarios)</h3>
    <?php if (empty($pagos)): ?>
        <div class="alerta alerta-info" role="alert">
            No hay pagos registrados al proveedor para esta factura.
            <?php if ($factura['estado'] === 'confirmada'): ?>
                <p class="ayuda">Use "El banco me ha cobrado esta factura" para registrar el pago al proveedor.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>Fecha real del cargo</th>
                    <th>Importe pagado</th>
                    <th>Medio</th>
                    <th>Referencia</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagos as $p): ?>
                    <tr>
                        <td><?= Security::html(date('d/m/Y', strtotime($p['fecha_real']))) ?></td>
                        <td><?= Security::html(number_format($p['importe'], 2, ',', '.')) . ' €' ?></td>
                        <td><?= Security::html(ucfirst($p['medio'])) ?></td>
                        <td><?= $p['referencia'] !== null ? Security::html($p['referencia']) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="acciones-formulario">
        <?php if ($factura['estado'] === 'confirmada'): ?>
            <a href="?r=repartos/nuevo&factura_id=<?= $factura['id'] ?>" class="btn-guardar">Introducir reparto manual</a>
            <a href="?r=entregas/nuevo&factura_id=<?= $factura['id'] ?>" class="btn-guardar">Registrar entrega a destinatarios</a>
            <a href="?r=comprobacion/ver&factura_id=<?= $factura['id'] ?>" class="btn-guardar">Comprobar pagos de esta factura</a>
        <?php endif; ?>
        <a href="?r=facturas" class="btn-volver">Volver al listado</a>
    </div>
</div>
