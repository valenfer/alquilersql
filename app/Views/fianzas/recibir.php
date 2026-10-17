<?php
declare(strict_types=1);

/**
 * Vista fianzas/recibir.php — Formulario para recibir una fianza.
 *
 * Responsabilidad:
 *  - Mostrar datos de la estancia y campos para recibir la fianza.
 *  - Advertir que la fianza no es ingreso de alquiler.
 */

?>
<div class="tarjeta-formulario">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['error_fianza'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_fianza']) ?></div>
    <?php endif; ?>

    <div class="alerta alerta-info" role="alert">
        <strong>Nota:</strong> La fianza recibida no es ingreso de alquiler ni beneficio ordinario.
        Se guarda como depósito a devolver o aplicar al salir.
    </div>

    <div class="resumen-estancia">
        <h3>Estancia</h3>
        <table class="tabla-listado">
            <tr>
                <td><strong>Inquilino:</strong></td>
                <td><?= Security::html($estancia['inquilino_nombre'] . ' ' . $estancia['inquilino_apellidos']) ?></td>
            </tr>
            <tr>
                <td><strong>Habitación:</strong></td>
                <td><?= Security::html($estancia['habitacion_numero']) ?></td>
            </tr>
            <tr>
                <td><strong>Fianza pactada:</strong></td>
                <td><?= Security::html(number_format($estancia['fianza_pactada'], 2, ',', '.')) . ' €' ?></td>
            </tr>
        </table>
    </div>

    <form action="?r=fianzas/crear" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
        <input type="hidden" name="estancia_id" value="<?= Security::attr($estancia['id']) ?>">

        <div class="campo">
            <label for="importe">Importe recibido (€)</label>
            <input type="text" id="importe" name="importe" required
                   value="<?= Security::html($estancia['fianza_pactada'] !== null ? number_format($estancia['fianza_pactada'], 2, ',', '.') : '') ?>"
                   placeholder="Ejemplo: 400,00">
        </div>

        <div class="campo">
            <label for="fecha">Fecha de recepción</label>
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
            <label for="referencia">Referencia (opcional)</label>
            <input type="text" id="referencia" name="referencia"
                   placeholder="Ejemplo: transferencia bancaria">
        </div>

        <div class="campo">
            <label for="observaciones">Observaciones (opcional)</label>
            <textarea id="observaciones" name="observaciones" rows="2"></textarea>
        </div>

        <div class="acciones-formulario">
            <button type="submit" class="btn-guardar">Recibir fianza</button>
            <a href="?r=estancias" class="btn-volver">Volver sin guardar</a>
        </div>
    </form>
</div>
