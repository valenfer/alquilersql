<?php
declare(strict_types=1);

/**
 * Vista fianzas/listar.php — Listado de fianzas.
 *
 * Responsabilidad:
 *  - Mostrar tabla de fianzas con inquilino, habitación, importe pactado, recibido, devuelto, aplicado, saldo pendiente.
 */

?>
<div class="tarjeta-fianzas">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['fianza_recibida'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['fianza_recibida']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['fianza_devuelta'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['fianza_devuelta']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['fianza_aplicada'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['fianza_aplicada']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['error_fianza'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_fianza']) ?></div>
    <?php endif; ?>

    <div class="acciones-lista">
        <a href="?r=dashboard" class="btn-volver">Volver al dashboard</a>
    </div>

    <table class="tabla-listado">
        <thead>
            <tr>
                <th>Inquilino</th>
                <th>Habitación</th>
                <th>Pactada</th>
                <th>Recibida</th>
                <th>Devuelta</th>
                <th>Aplicada a deuda</th>
                <th>Saldo pendiente</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($fianzas)): ?>
                <tr>
                    <td colspan="8" style="text-align:center; padding:2rem;">No hay fianzas registradas.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($fianzas as $f): ?>
                <tr>
                    <td><?= Security::html($f['inquilino_nombre'] . ' ' . $f['inquilino_apellidos']) ?></td>
                    <td><?= Security::html($f['habitacion_numero']) ?></td>
                    <td><?= Security::html(number_format($f['importe_pactado'], 2, ',', '.')) . ' €' ?></td>
                    <td><?= Security::html(number_format($f['importe_recibido'], 2, ',', '.')) . ' €' ?></td>
                    <td><?= Security::html(number_format($f['importe_devuelto'], 2, ',', '.')) . ' €' ?></td>
                    <td><?= Security::html(number_format($f['importe_aplicado_deuda'], 2, ',', '.')) . ' €' ?></td>
                    <td><?= Security::html(number_format($f['saldo_pendiente_devolver'], 2, ',', '.')) . ' €' ?></td>
                    <td>
                        <a href="?r=fianzas/ver&id=<?= $f['id'] ?>" class="btn-small">Ver</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="ayuda">
        La fianza recibida no es ingreso de alquiler ni beneficio ordinario.
        La devolución de fianza no es un gasto ordinario del piso.
        El saldo pendiente = recibida − devuelta − aplicada a deuda.
    </p>
</div>
