<?php
declare(strict_types=1);

/**
 * Vista gastos/ver.php — Detalle de un gasto.
 *
 * Responsabilidad:
 *  - Mostrar información completa del gasto.
 *  - Mostrar pagos registrados y saldo pendiente.
 */

?>
<div class="tarjeta-gastos">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash)): ?>
        <?php foreach ($flash as $msg): ?>
            <div class="alerta alerta-<?= Security::html($msg['tipo']) ?>" role="alert"><?= Security::html($msg['valor']) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="detalle-gasto">
        <div class="campo-detalle">
            <label>Categoría</label>
            <span><?= Security::html($gasto['categoria_nombre']) ?></span>
        </div>
        <div class="campo-detalle">
            <label>Descripción</label>
            <span><?= Security::html($gasto['descripcion']) ?></span>
        </div>
        <div class="campo-detalle">
            <label>Importe</label>
            <span><?= Security::html(number_format($gasto['importe'], 2, ',', '.')) . ' €' ?></span>
        </div>
        <div class="campo-detalle">
            <label>Fecha</label>
            <span><?= Security::html(date('d/m/Y', strtotime($gasto['fecha_gasto']))) ?></span>
        </div>
        <div class="campo-detalle">
            <label>Proveedor</label>
            <span><?= $gasto['proveedor'] !== null ? Security::html($gasto['proveedor']) : '—' ?></span>
        </div>
        <div class="campo-detalle">
            <label>Número de factura</label>
            <span><?= $gasto['numero_factura'] !== null ? Security::html($gasto['numero_factura']) : '—' ?></span>
        </div>
        <div class="campo-detalle">
            <label>Pagado</label>
            <span><?= Security::html(number_format($gasto['total_pagado'], 2, ',', '.')) . ' €' ?></span>
        </div>
        <div class="campo-detalle">
            <label>Pendiente de pago</label>
            <span><?= Security::html(number_format($gasto['saldo_pendiente_pago'], 2, ',', '.')) . ' €' ?></span>
        </div>
    </div>

    <h3>Pagos registrados</h3>
    <?php if (empty($gasto['pagos'])): ?>
        <div class="alerta alerta-info" role="alert">No hay pagos registrados para este gasto.</div>
    <?php else: ?>
        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Importe</th>
                    <th>Medio</th>
                    <th>Referencia</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gasto['pagos'] as $p): ?>
                    <tr>
                        <td><?= Security::html(date('d/m/Y', strtotime($p['fecha_pago']))) ?></td>
                        <td><?= Security::html(number_format($p['importe'], 2, ',', '.')) . ' €' ?></td>
                        <td><?= Security::html(ucfirst($p['medio'])) ?></td>
                        <td><?= $p['referencia'] !== null ? Security::html($p['referencia']) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="acciones-formulario">
        <?php if ($gasto['saldo_pendiente_pago'] > 0): ?>
            <a href="?r=gastos/pagar&id=<?= $gasto['id'] ?>" class="btn-guardar">Registrar pago</a>
        <?php endif; ?>
        <a href="?r=gastos" class="btn-volver">Volver al listado</a>
    </div>
</div>
