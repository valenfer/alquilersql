<?php
declare(strict_types=1);

/**
 * Vista cargos/ver.php — Detalle de un cargo de alquiler.
 *
 * Responsabilidad:
 *  - Mostrar información completa de un cargo: inquilino, habitación, periodo,
 *    importe esperado, cobrado, pendiente, estado calculado, vencimiento.
 */

?>
<div class="tarjeta-cargos">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash)): ?>
        <?php foreach ($flash as $msg): ?>
            <div class="alerta alerta-<?= Security::html($msg['tipo']) ?>" role="alert"><?= Security::html($msg['valor']) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="detalle-cargo">
        <div class="campo-detalle">
            <label>Inquilino</label>
            <span><?= Security::html($cargo['inquilino_nombre'] . ' ' . $cargo['inquilino_apellidos']) ?></span>
        </div>
        <div class="campo-detalle">
            <label>Habitación</label>
            <span><?= Security::html($cargo['habitacion_numero']) ?></span>
        </div>
        <div class="campo-detalle">
            <label>Año / Mes</label>
            <span><?= $cargo['anio'] ?> / <?= $cargo['mes'] ?></span>
        </div>
        <div class="campo-detalle">
            <label>Periodo cubierto</label>
            <span><?= Security::html(date('d/m/Y', strtotime($cargo['periodo_desde']))) . ' — ' . date('d/m/Y', strtotime($cargo['periodo_hasta'])) ?></span>
        </div>
        <div class="campo-detalle">
            <label>Importe esperado</label>
            <span><?= Security::html(number_format($cargo['importe_esperado'], 2, ',', '.')) . ' €' ?></span>
        </div>
        <div class="campo-detalle">
            <label>Importe cobrado</label>
            <span><?= Security::html(number_format($cargo['importe_cobrado'], 2, ',', '.')) . ' €' ?></span>
        </div>
        <div class="campo-detalle">
            <label>Saldo pendiente</label>
            <span><?= Security::html(number_format($cargo['saldo_pendiente'], 2, ',', '.')) . ' €' ?></span>
        </div>
        <div class="campo-detalle">
            <label>Estado</label>
            <span class="estado estado-<?= Security::html($cargo['estado_calculado']) ?>">
                <?= Security::html(ucfirst($cargo['estado_calculado'])) ?>
            </span>
        </div>
        <div class="campo-detalle">
            <label>Fecha de vencimiento</label>
            <span><?= Security::html(date('d/m/Y', strtotime($cargo['fecha_vencimiento']))) ?></span>
        </div>
        <?php if ($cargo['observaciones']): ?>
            <div class="campo-detalle">
                <label>Observaciones</label>
                <span><?= Security::html($cargo['observaciones']) ?></span>
            </div>
        <?php endif; ?>
    </div>

    <div class="acciones-formulario">
        <a href="?r=cobros/nuevo" class="btn-guardar">Registrar cobro para este inquilino</a>
        <a href="?r=cargos" class="btn-volver">Volver al listado</a>
    </div>
</div>
