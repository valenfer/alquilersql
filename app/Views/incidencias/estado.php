<?php
declare(strict_types=1);

/**
 * Vista incidencias/estado.php — Formulario para cambiar el estado de una incidencia.
 */

?>
<div class="tarjeta-formulario">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['error_incidencia'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_incidencia']) ?></div>
    <?php endif; ?>

    <div class="resumen-incidencia">
        <h3>Incidencia</h3>
        <table class="tabla-listado">
            <tr>
                <td><strong>Descripción:</strong></td>
                <td><?= Security::html($incidencia['descripcion']) ?></td>
            </tr>
            <tr>
                <td><strong>Habitación:</strong></td>
                <td><?= $incidencia['habitacion_numero'] !== null ? Security::html($incidencia['habitacion_numero']) : '—' ?></td>
            </tr>
            <tr>
                <td><strong>Estado actual:</strong></td>
                <td><?= Security::html(ucfirst(str_replace('_', ' ', $incidencia['estado']))) ?></td>
            </tr>
            <tr>
                <td><strong>Fecha de aviso:</strong></td>
                <td><?= Security::html(date('d/m/Y', strtotime($incidencia['fecha_aviso']))) ?></td>
            </tr>
        </table>
    </div>

    <form action="?r=incidencias/procesar-estado" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
        <input type="hidden" name="id" value="<?= Security::attr($incidencia['id']) ?>">

        <div class="campo">
            <label for="estado">Nuevo estado</label>
            <select id="estado" name="estado" required>
                <option value="pendiente" <?= ($incidencia['estado'] === 'pendiente') ? 'selected' : '' ?>>Pendiente</option>
                <option value="en_curso" <?= ($incidencia['estado'] === 'en_curso') ? 'selected' : '' ?>>En curso</option>
                <option value="resuelta" <?= ($incidencia['estado'] === 'resuelta') ? 'selected' : '' ?>>Resuelta</option>
            </select>
        </div>

        <div class="campo">
            <label for="fecha_resolucion">Fecha de resolución (si se marca como resuelta)</label>
            <input type="date" id="fecha_resolucion" name="fecha_resolucion"
                   value="<?= $incidencia['fecha_resolucion'] ?? '' ?>">
        </div>

        <div class="acciones-formulario">
            <button type="submit" class="btn-guardar">Actualizar estado</button>
            <a href="?r=incidencias" class="btn-volver">Volver sin guardar</a>
        </div>
    </form>
</div>
