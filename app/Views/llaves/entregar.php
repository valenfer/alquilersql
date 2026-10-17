<?php
declare(strict_types=1);

/**
 * Vista llaves/entregar.php — Formulario para entregar una llave.
 */

?>
<div class="tarjeta-formulario">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['error_llave'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_llave']) ?></div>
    <?php endif; ?>

    <form action="?r=llaves/procesar-entregar" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
        <input type="hidden" name="estancia_id" value="<?= Security::attr($estancia_id) ?>">

        <div class="campo">
            <label for="llave_id">Unidad de llave (opcional)</label>
            <select id="llave_id" name="llave_id">
                <option value="">Sin identificación (gestionar por cantidad)</option>
                <?php foreach ($llaves_disponibles as $l): ?>
                    <option value="<?= $l['id'] ?>">
                        <?= Security::html($l['tipo_nombre']) . ' — ' . ($l['identificador'] ?? 'Sin código') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="ayuda-campo">Si la llave tiene código individual, selecciónalo. Si no, deja sin seleccionar y se gestionará por cantidad.</p>
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
            <label for="cantidad">Cantidad entregada</label>
            <input type="number" id="cantidad" name="cantidad" min="1" value="1" required>
        </div>

        <div class="campo">
            <label for="fecha">Fecha de entrega</label>
            <input type="date" id="fecha" name="fecha" required
                   value="<?= date('Y-m-d') ?>">
        </div>

        <div class="campo">
            <label for="observaciones">Observaciones (opcional)</label>
            <textarea id="observaciones" name="observaciones" rows="2"></textarea>
        </div>

        <div class="acciones-formulario">
            <button type="submit" class="btn-guardar">Entregar llave</button>
            <a href="?r=llaves/estancia&id=<?= $estancia_id ?>" class="btn-volver">Volver sin guardar</a>
        </div>
    </form>
</div>
