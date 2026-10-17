<?php
declare(strict_types=1);

/**
 * Vista habitaciones/listar.php — Listado de habitaciones del piso.
 *
 * Responsabilidad:
 *  - Mostrar las cuatro habitaciones con su número, nombre, estado, inquilino actual y renta.
 *  - Botones para crear, editar y eliminar (si no tiene historial).
 *  - Mensajes flash de éxito o error.
 */

?>
<div class="tarjeta-habitaciones">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['habitacion_creada'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['habitacion_creada']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['habitacion_actualizada'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['habitacion_actualizada']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['habitacion_eliminada'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['habitacion_eliminada']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['error_habitacion'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_habitacion']) ?></div>
    <?php endif; ?>

    <div class="acciones-lista">
        <?php if ($puedeCrear ?? true): ?>
            <a href="?r=habitaciones/nuevo" class="btn-guardar">Nueva habitación</a>
        <?php else: ?>
            <span class="btn-guardar" style="opacity:0.6; cursor:not-allowed;" title="Máximo cuatro habitaciones">Nueva habitación</span>
        <?php endif; ?>
        <a href="?r=dashboard" class="btn-volver">Volver al dashboard</a>
    </div>

    <table class="tabla-listado">
        <thead>
            <tr>
                <th>Número</th>
                <th>Nombre</th>
                <th>Estado</th>
                <th>Renta orientativa</th>
                <th>Inquilino actual</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($habitaciones as $h): ?>
                <tr>
                    <td><?= Security::html($h['numero']) ?></td>
                    <td><?= Security::html($h['nombre']) ?></td>
                    <td>
                        <span class="estado estado-<?= Security::html($h['estado']) ?>">
                            <?= Security::html(ucfirst(str_replace('_', ' ', $h['estado']))) ?>
                        </span>
                    </td>
                    <td><?= $h['renta_orientativa'] !== null ? Security::html(number_format($h['renta_orientativa'], 2, ',', '.')) . ' €' : '—' ?></td>
                    <td><?= isset($h['inquilino_actual']) ? Security::html($h['inquilino_actual']) : '—' ?></td>
                    <td>
                        <a href="?r=habitaciones/editar&id=<?= $h['id'] ?>" class="btn-small">Editar</a>
                        <?php if (!$h['tiene_historial'] ?? true): ?>
                            <form action="?r=habitaciones/eliminar" method="post" style="display:inline;" onsubmit="return confirm('¿Está seguro de que desea eliminar esta habitación?');">
                                <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
                                <input type="hidden" name="id" value="<?= $h['id'] ?>">
                                <button type="submit" class="btn-small btn-eliminar">Eliminar</button>
                            </form>
                        <?php else: ?>
                            <span class="btn-small" style="opacity:0.6; cursor:not-allowed;" title="Tiene historial, no se puede eliminar">Eliminar</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="ayuda">
        El piso tiene exactamente cuatro habitaciones (1 a 4). Una habitación con historial
        de ocupaciones no se puede eliminar para conservar los datos económicos.
    </p>
</div>
