<?php
declare(strict_types=1);

/**
 * Vista contratos/nuevo.php — Formulario para crear un contrato de alquiler.
 *
 * Responsabilidad:
 *  - Mostrar datos de la estancia y campos para crear el contrato.
 *  - Campos: fechas, renta, condiciones, cláusulas adicionales.
 */

?>
<div class="tarjeta-formulario">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['error_contrato'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_contrato']) ?></div>
    <?php endif; ?>

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
                <td><strong>Entrada:</strong></td>
                <td><?= Security::html(date('d/m/Y', strtotime($estancia['fecha_entrada']))) ?></td>
            </tr>
            <tr>
                <td><strong>Renta actual:</strong></td>
                <td><?= Security::html(number_format($estancia['renta_actual'], 2, ',', '.')) . ' €' ?></td>
            </tr>
        </table>
    </div>

    <form action="?r=contratos/crear" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
        <input type="hidden" name="estancia_id" value="<?= Security::attr($estancia['id']) ?>">

        <div class="campo">
            <label for="fecha_inicio">Fecha de inicio</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" required
                   value="<?= date('Y-m-d') ?>">
        </div>

        <div class="campo">
            <label for="fecha_fin">Fecha de finalización</label>
            <input type="date" id="fecha_fin" name="fecha_fin" required>
            <p class="ayuda-campo">Fecha en que termina el contrato. Se usará para calcular alertas de vencimiento.</p>
        </div>

        <div class="campo">
            <label for="renta_mensual">Renta mensual (€)</label>
            <input type="text" id="renta_mensual" name="renta_mensual" required
                   value="<?= Security::html($estancia['renta_actual'] !== null ? number_format($estancia['renta_actual'], 2, ',', '.') : '') ?>"
                   placeholder="Ejemplo: 350,00">
        </div>

        <div class="campo">
            <label for="condiciones">Condiciones generales</label>
            <textarea id="condiciones" name="condiciones" rows="4"></textarea>
            <p class="ayuda-campo">Condiciones generales del contrato (opcional).</p>
        </div>

        <div class="campo">
            <label for="clausulas_adicionales">Cláusulas adicionales</label>
            <textarea id="clausulas_adicionales" name="clausulas_adicionales" rows="4"></textarea>
            <p class="ayuda-campo">Cláusulas adicionales o modificaciones al contrato (opcional).</p>
        </div>

        <div class="acciones-formulario">
            <button type="submit" class="btn-guardar">Crear contrato</button>
            <a href="?r=estancias" class="btn-volver">Volver sin guardar</a>
        </div>
    </form>
</div>
