<?php
declare(strict_types=1);

/**
 * Vista cobros/ver.php — Detalle de un cobro con sus aplicaciones.
 *
 * Responsabilidad:
 *  - Mostrar información del cobro: inquilino, fecha, medio, importe total, referencia.
 *  - Mostrar tabla de aplicaciones: cargo, año/mes, importe aplicado.
 *  - Mostrar total de aplicaciones y verificar que coincida con el importe del cobro.
 */

?>
<div class="tarjeta-cobros">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash)): ?>
        <?php foreach ($flash as $msg): ?>
            <div class="alerta alerta-<?= Security::html($msg['tipo']) ?>" role="alert"><?= Security::html($msg['valor']) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="detalle-cobro">
        <div class="campo-detalle">
            <label>Inquilino</label>
            <span><?= Security::html($cobro['inquilino_nombre'] . ' ' . $cobro['inquilino_apellidos']) ?></span>
        </div>
        <div class="campo-detalle">
            <label>Fecha del cobro</label>
            <span><?= Security::html(date('d/m/Y', strtotime($cobro['fecha_cobro']))) ?></span>
        </div>
        <div class="campo-detalle">
            <label>Medio de pago</label>
            <span><?= Security::html(ucfirst($cobro['medio'])) ?></span>
        </div>
        <div class="campo-detalle">
            <label>Importe total recibido</label>
            <span><?= Security::html(number_format($cobro['importe_total'], 2, ',', '.')) . ' €' ?></span>
        </div>
        <div class="campo-detalle">
            <label>Referencia</label>
            <span><?= $cobro['referencia'] !== null ? Security::html($cobro['referencia']) : '—' ?></span>
        </div>
        <div class="campo-detalle">
            <label>Observaciones</label>
            <span><?= $cobro['observaciones'] !== null ? Security::html($cobro['observaciones']) : '—' ?></span>
        </div>
        <div class="campo-detalle">
            <label>Fecha de registro</label>
            <span><?= Security::html(date('d/m/Y H:i', strtotime($cobro['fecha_registro']))) ?></span>
        </div>
    </div>

    <h3>Aplicaciones (desglose a mensualidades)</h3>

    <?php if (empty($cobro['aplicaciones'])): ?>
        <div class="alerta alerta-info" role="alert">Este cobro no tiene aplicaciones registradas.</div>
    <?php else: ?>
        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>Año / Mes</th>
                    <th>Habitación</th>
                    <th>Periodo</th>
                    <th>Importe aplicado</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalAplicado = 0;
                foreach ($cobro['aplicaciones'] as $app): ?>
                    <tr>
                        <td><?= $app['anio'] ?> / <?= $app['mes'] ?></td>
                        <td><?= Security::html($app['habitacion_numero'] ?? '—') ?></td>
                        <td><?= $app['periodo_desde'] && $app['periodo_hasta'] ? date('d/m/Y', strtotime($app['periodo_desde'])) . ' — ' . date('d/m/Y', strtotime($app['periodo_hasta'])) : '—' ?></td>
                        <td><?= Security::html(number_format($app['importe'], 2, ',', '.')) . ' €' ?></td>
                    </tr>
                    <?php $totalAplicado += (float) $app['importe']; ?>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3"><strong>Total aplicado</strong></td>
                    <td><strong><?= Security::html(number_format($totalAplicado, 2, ',', '.')) . ' €' ?></strong></td>
                </tr>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="acciones-formulario">
        <a href="?r=cobros" class="btn-volver">Volver al listado</a>
    </div>
</div>
