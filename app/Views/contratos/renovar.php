<?php
declare(strict_types=1);

/**
 * Vista contratos/renovar.php — Formulario para renovar un contrato de alquiler.
 *
 * Responsabilidad:
 *  - Mostrar datos del contrato actual y campos para renovar.
 *  - Permite modificar fechas, renta, condiciones y cláusulas.
 */

?>
<div class="tarjeta-formulario">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['error_contrato'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_contrato']) ?></div>
    <?php endif; ?>

    <div class="alerta alerta-info" role="alert">
        <strong>Nota:</strong> Al renovar, el contrato actual se marcará como 'renovado' y se creará uno nuevo
        con las condiciones que indique a continuación.
    </div>

    <div class="resumen-contrato-actual">
        <h3>Contrato actual</h3>
        <table class="tabla-listado">
            <tr>
                <td><strong>Inquilino:</strong></td>
                <td><?= Security::html($contrato['inquilino_nombre'] . ' ' . $contrato['inquilino_apellidos']) ?></td>
            </tr>
            <tr>
                <td><strong>Habitación:</strong></td>
                <td><?= Security::html($contrato['habitacion_numero']) ?></td>
            </tr>
            <tr>
                <td><strong>Inicio actual:</strong></td>
                <td><?= Security::html(date('d/m/Y', strtotime($contrato['fecha_inicio']))) ?></td>
            </tr>
            <tr>
                <td><strong>Fin actual:</strong></td>
                <td><?= $contrato['fecha_fin'] ? Security::html(date('d/m/Y', strtotime($contrato['fecha_fin']))) : '—' ?></td>
            </tr>
            <tr>
                <td><strong>Renta actual:</strong></td>
                <td><?= Security::html(number_format($contrato['renta_mensual'], 2, ',', '.')) . ' €' ?></td>
            </tr>
        </table>
    </div>

    <form action="?r=contratos/procesar-renovar" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
        <input type="hidden" name="contrato_id" value="<?= Security::attr($contrato['id']) ?>">

        <div class="campo">
            <label for="fecha_inicio">Nueva fecha de inicio</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" required
                   value="<?= date('Y-m-d') ?>">
        </div>

        <div class="campo">
            <label for="fecha_fin">Nueva fecha de finalización</label>
            <input type="date" id="fecha_fin" name="fecha_fin" required>
        </div>

        <div class="campo">
            <label for="renta_mensual">Nueva renta mensual (€)</label>
            <input type="text" id="renta_mensual" name="renta_mensual" required
                   value="<?= Security::html(number_format($contrato['renta_mensual'], 2, ',', '.')) ?>"
                   placeholder="Ejemplo: 350,00">
        </div>

        <div class="campo">
            <label for="condiciones">Condiciones generales</label>
            <textarea id="condiciones" name="condiciones" rows="4"><?= Security::html($contrato['condiciones'] ?? '') ?></textarea>
        </div>

        <div class="campo">
            <label for="clausulas_adicionales">Cláusulas adicionales</label>
            <textarea id="clausulas_adicionales" name="clausulas_adicionales" rows="4"><?= Security::html($contrato['clausulas_adicionales'] ?? '') ?></textarea>
        </div>

        <div class="acciones-formulario">
            <button type="submit" class="btn-guardar">Renovar contrato</button>
            <a href="?r=contratos/ver&id=<?= $contrato['id'] ?>" class="btn-volver">Volver sin guardar</a>
        </div>
    </form>
</div>
