<?php
declare(strict_types=1);

/**
 * Vista fianzas/ver.php — Detalle de una fianza con movimientos.
 *
 * Responsabilidad:
 *  - Mostrar información completa de la fianza.
 *  - Mostrar historial de movimientos (recepción, devolución, aplicación, compensación).
 *  - Botones para devolver, aplicar o compensar.
 */

?>
<div class="tarjeta-fianzas">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash)): ?>
        <?php foreach ($flash as $msg): ?>
            <div class="alerta alerta-<?= Security::html($msg['tipo']) ?>" role="alert"><?= Security::html($msg['valor']) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="detalle-fianza">
        <div class="campo-detalle">
            <label>Inquilino</label>
            <span><?= Security::html($fianza['inquilino_nombre'] . ' ' . $fianza['inquilino_apellidos']) ?></span>
        </div>
        <div class="campo-detalle">
            <label>Habitación</label>
            <span><?= Security::html($fianza['habitacion_numero']) ?></span>
        </div>
        <div class="campo-detalle">
            <label>Fianza pactada</label>
            <span><?= Security::html(number_format($fianza['importe_pactado'], 2, ',', '.')) . ' €' ?></span>
        </div>
        <div class="campo-detalle">
            <label>Recibida</label>
            <span><?= Security::html(number_format($fianza['importe_recibido'], 2, ',', '.')) . ' €' ?></span>
        </div>
        <div class="campo-detalle">
            <label>Devuelta</label>
            <span><?= Security::html(number_format($fianza['importe_devuelto'], 2, ',', '.')) . ' €' ?></span>
        </div>
        <div class="campo-detalle">
            <label>Aplicada a deuda</label>
            <span><?= Security::html(number_format($fianza['importe_aplicado_deuda'], 2, ',', '.')) . ' €' ?></span>
        </div>
        <div class="campo-detalle">
            <label>Saldo pendiente de devolver</label>
            <span><?= Security::html(number_format($fianza['saldo_pendiente_devolver'], 2, ',', '.')) . ' €' ?></span>
        </div>
    </div>

    <h3>Movimientos de fianza</h3>
    <?php if (empty($fianza['movimientos'])): ?>
        <div class="alerta alerta-info" role="alert">No hay movimientos registrados.</div>
    <?php else: ?>
        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Importe</th>
                    <th>Medio</th>
                    <th>Motivo</th>
                    <th>Registrado por</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fianza['movimientos'] as $m): ?>
                    <tr>
                        <td><?= Security::html(date('d/m/Y', strtotime($m['fecha']))) ?></td>
                        <td><?= Security::html(ucfirst(str_replace('_', ' ', $m['tipo']))) ?></td>
                        <td><?= Security::html(number_format($m['importe'], 2, ',', '.')) . ' €' ?></td>
                        <td><?= $m['medio'] !== null ? Security::html(ucfirst($m['medio'])) : '—' ?></td>
                        <td><?= $m['motivo'] !== null ? Security::html($m['motivo']) : '—' ?></td>
                        <td><?= $m['registrado_por_nombre'] !== null ? Security::html($m['registrado_por_nombre']) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="acciones-formulario">
        <?php if ($fianza['saldo_pendiente_devolver'] > 0): ?>
            <a href="?r=fianzas/devolver&id=<?= $fianza['id'] ?>" class="btn-guardar">Devolver fianza</a>
            <a href="?r=fianzas/aplicar&id=<?= $fianza['id'] ?>" class="btn-guardar">Aplicar a deuda de alquiler</a>
        <?php endif; ?>
        <a href="?r=fianzas" class="btn-volver">Volver al listado</a>
    </div>
</div>
