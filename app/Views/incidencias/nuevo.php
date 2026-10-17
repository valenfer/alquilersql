<?php
declare(strict_types=1);

/**
 * Vista incidencias/nuevo.php — Formulario para crear una incidencia.
 */

?>
<div class="tarjeta-formulario">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['error_incidencia'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_incidencia']) ?></div>
    <?php endif; ?>

    <div class="alerta alerta-info" role="alert">
        Una incidencia por sí sola no es un gasto hasta que existe un importe registrado.
    </div>

    <form action="?r=incidencias/crear" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">

        <div class="campo">
            <label for="habitacion_id">Habitación o zona (opcional)</label>
            <select id="habitacion_id" name="habitacion_id">
                <option value="">Sin habitación específica</option>
                <?php foreach ($habitaciones as $h): ?>
                    <option value="<?= $h['id'] ?>"><?= Security::html($h['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="3" required
                      placeholder="Ejemplo: Fuga de agua en el baño de la habitación 2"></textarea>
        </div>

        <div class="campo">
            <label for="fecha_aviso">Fecha de aviso</label>
            <input type="date" id="fecha_aviso" name="fecha_aviso" required
                   value="<?= date('Y-m-d') ?>">
        </div>

        <div class="campo">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="pendiente">Pendiente</option>
                <option value="en_curso">En curso</option>
                <option value="resuelta">Resuelta</option>
            </select>
        </div>

        <div class="campo">
            <label for="fecha_resolucion">Fecha de resolución (opcional)</label>
            <input type="date" id="fecha_resolucion" name="fecha_resolucion">
        </div>

        <div class="acciones-formulario">
            <button type="submit" class="btn-guardar">Registrar incidencia</button>
            <a href="?r=incidencias" class="btn-volver">Volver sin guardar</a>
        </div>
    </form>
</div>
