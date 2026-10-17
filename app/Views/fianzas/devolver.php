<?php
declare(strict_types=1);

/**
 * Vista fianzas/devolver.php — Formulario para devolver una fianza.
 *
 * Responsabilidad:
 *  - Mostrar saldo disponible y campos para devolver.
 *  - Advertir que la devolución no es gasto ordinario.
 */

?>
<div class="tarjeta-formulario">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['error_fianza'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_fianza']) ?></div>
    <?php endif; ?>

    <div class="alerta alerta-info" role="alert">
        <strong>Nota:</strong> La devolución de fianza no es un gasto ordinario del piso.
        No se puede devolver más del saldo disponible.
    </div>

    <div class="resumen-fianza">
        <h3>Fianza</h3>
        <table class="tabla-listado">
            <tr>
                <td><strong>Inquilino:</strong></td>
                <td><?= Security::html($fianza['inquilino_nombre'] . ' ' . $fianza['inquilino_apellidos']) ?></td>
            </tr>
            <tr>
                <td><strong>Habitación:</strong></td>
                <td><?= Security::html($fianza['habitacion_numero']) ?></td>
            </tr>
            <tr>
                <td><strong>Recibida:</strong></td>
                <td><?= Security::html(number_format($fianza['importe_recibido'], 2, ',', '.')) . ' €' ?></td>
            </tr>
            <tr>
                <td><strong>Devuelta previamente:</strong></td>
                <td><?= Security::html(number_format($fianza['importe_devuelto'], 2, ',', '.')) . ' €' ?></td>
            </tr>
            <tr>
                <td><strong>Aplicada a deuda:</strong></td>
                <td><?= Security::html(number_format($fianza['importe_aplicado_deuda'], 2, ',', '.')) . ' €' ?></td>
            </tr>
            <tr>
                <td><strong>Saldo disponible:</strong></td>
                <td><?= Security::html(number_format($fianza['saldo_pendiente_devolver'], 2, ',', '.')) . ' €' ?></td>
            </tr>
        </table>
    </div>

    <form action="?r=fianzas/procesar-devolver" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
        <input type="hidden" name="fianza_id" value="<?= Security::attr($fianza['id']) ?>">

        <div class="campo">
            <label for="importe">Importe a devolver (€)</label>
            <input type="text" id="importe" name="importe" required
                   placeholder="Ejemplo: 300,00">
            <p class="ayuda-campo">Máximo: <?= Security::html(number_format($fianza['saldo_pendiente_devolver'], 2, ',', '.')) . ' €' ?></p>
        </div>

        <div class="campo">
            <label for="fecha">Fecha de devolución</label>
            <input type="date" id="fecha" name="fecha" required
                   value="<?= date('Y-m-d') ?>">
        </div>

        <div class="campo">
            <label for="medio">Medio de pago</label>
            <select id="medio" name="medio" required>
                <option value="efectivo">Efectivo</option>
                <option value="transferencia">Transferencia</option>
                <option value="bizum">Bizum</option>
                <option value="otro">Otro</option>
            </select>
        </div>

        <div class="campo">
            <label for="observaciones">Observaciones (opcional)</label>
            <textarea id="observaciones" name="observaciones" rows="2"></textarea>
        </div>

        <div class="acciones-formulario">
            <button type="submit" class="btn-guardar">Devolver fianza</button>
            <a href="?r=fianzas/ver&id=<?= $fianza['id'] ?>" class="btn-volver">Volver sin guardar</a>
        </div>
    </form>
</div>
