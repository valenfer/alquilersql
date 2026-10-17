<?php
declare(strict_types=1);

/**
 * Vista informes/anual_seleccion.php — Selección de año para el informe anual.
 */

?>
<div class="tarjeta-formulario">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash)): ?>
        <?php foreach ($flash as $msg): ?>
            <div class="alerta alerta-<?= Security::html($msg['tipo']) ?>" role="alert"><?= Security::html($msg['valor']) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <p class="ayuda">
        Seleccione el año para el cual desea generar el informe anual. El informe incluirá tres bloques
        claramente separados: dinero realmente cobrado y pagado, cargos y deudas a fecha de cierre, y
        fianzas y compensaciones sin caja.
    </p>

    <form method="get" action="?r=informes/anual/generar" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">

        <div class="campo">
            <label for="anio">Año</label>
            <select id="anio" name="anio" required>
                <?php foreach ($anios as $a): ?>
                    <option value="<?= $a ?>" <?= ($a == $anio_actual) ? 'selected' : '' ?>><?= $a ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="acciones-formulario">
            <button type="submit" class="btn-guardar">Generar informe anual</button>
            <a href="?r=dashboard" class="btn-volver">Volver al dashboard</a>
        </div>
    </form>
</div>
