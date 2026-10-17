<?php
declare(strict_types=1);

/**
 * Vista llaves/listar.php — Listado de unidades de llave.
 *
 * Responsabilidad:
 *  - Mostrar tabla de unidades de llave con tipo, identificador y estado.
 */

?>
<div class="tarjeta-llaves">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['llave_entregada'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['llave_entregada']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['llave_devuelta'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['llave_devuelta']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['llave_perdida'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['llave_perdida']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['llave_repuesta'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['llave_repuesta']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['error_llave'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_llave']) ?></div>
    <?php endif; ?>

    <div class="acciones-lista">
        <a href="?r=dashboard" class="btn-volver">Volver al dashboard</a>
    </div>

    <table class="tabla-listado">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Identificador</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($llaves)): ?>
                <tr>
                    <td colspan="3" style="text-align:center; padding:2rem;">No hay unidades de llave registradas.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($llaves as $l): ?>
                <tr>
                    <td><?= Security::html($l['tipo_nombre']) ?></td>
                    <td><?= $l['identificador'] !== null ? Security::html($l['identificador']) : '—' ?></td>
                    <td>
                        <span class="estado estado-<?= Security::html($l['estado']) ?>">
                            <?= Security::html(ucfirst($l['estado'])) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="ayuda">
        Las unidades identificadas tienen código individual. Si no hay código, se gestiona por cantidades.
        Una unidad marcada como "perdida" no se puede volver a entregar.
    </p>
</div>
