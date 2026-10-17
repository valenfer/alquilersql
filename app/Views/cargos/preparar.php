<?php
declare(strict_types=1);

/**
 * Vista cargos/preparar.php — Formulario para preparar mensualidades.
 *
 * Responsabilidad:
 *  - Seleccionar año y mes para generar cargos de alquiler.
 *  - Botón para generar vista previa.
 */

?>
<div class="tarjeta-formulario">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['error_cargo'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_cargo']) ?></div>
    <?php endif; ?>

    <form action="?r=cargos/preparar/preview" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">

        <div class="campo">
            <label for="anio">Año</label>
            <select id="anio" name="anio" required>
                <?php foreach ($anios as $a): ?>
                    <option value="<?= $a ?>" <?= ($a == $anio_actual) ? 'selected' : '' ?>><?= $a ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="mes">Mes</label>
            <select id="mes" name="mes" required>
                <?php foreach ($meses as $num => $nombre): ?>
                    <option value="<?= $num ?>" <?= ($num == $mes_actual) ? 'selected' : '' ?>><?= Security::html($nombre) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="acciones-formulario">
            <button type="submit" class="btn-guardar">Generar vista previa</button>
            <a href="?r=cargos" class="btn-volver">Volver sin guardar</a>
        </div>
    </form>

    <p class="ayuda">
        Se generará un cargo por cada estancia que ocupe al menos un día en el mes seleccionado.
        Si ya existe una mensualidad para una estancia y mes, no se duplica (idempotente).
        El importe se prorratea por días naturales para el primer y último mes de la estancia.
    </p>
</div>
