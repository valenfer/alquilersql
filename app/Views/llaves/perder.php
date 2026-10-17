<?php
declare(strict_types=1);

/**
 * Vista llaves/perder.php — Formulario para registrar una pérdida de llave.
 */

?>
<div class="tarjeta-formulario">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['error_llave'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_llave']) ?></div>
    <?php endif; ?>

    <div class="alerta alerta-info" role="alert">
        Una pérdida cierra esa unidad como perdida, no como devuelta.
        Una reposición se registrará como nueva entrega vinculada al incidente.
    </div>

    <form action="?r=llaves/procesar-perder" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
        <input type="hidden" name="estancia_id" value="<?= Security::attr($estancia_id) ?>">

        <div class="campo">
            <label for="llave_id">Unidad de llave (opcional)</label>
            <select id="llave_id" name="llave_id">
                <option value="">Sin identificación</option>
            </select>
        </div>

        <div class="campo">
            <label for="tipo_llave">Tipo de llave</label>
            <select id="tipo_llave" name="tipo_llave" required>
                <?php foreach ($tipos as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= Security::html($t['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="cantidad">Cantidad perdida</label>
            <input type="number" id="cantidad" name="cantidad" min="1" value="1" required>
        </div>

        <div class="campo">
            <label for="fecha">Fecha de la pérdida</label>
            <input type="date" id="fecha" name="fecha" required
                   value="<?= date('Y-m-d') ?>">
        </div>

        <div class="campo">
            <label for="observaciones">Observaciones (opcional)</label>
            <textarea id="observaciones" name="observaciones" rows="2"></textarea>
        </div>

        <div class="acciones-formulario">
            <button type="submit" class="btn-guardar">Registrar pérdida</button>
            <a href="?r=llaves/estancia&id=<?= $estancia_id ?>" class="btn-volver">Volver sin guardar</a>
        </div>
    </form>
</div>
