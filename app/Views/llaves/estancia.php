<?php
declare(strict_types=1);

/**
 * Vista llaves/estancia.php — Movimientos de llave de una estancia.
 *
 * Responsabilidad:
 *  - Mostrar estancia, pendiente por tipo de llave, historial de movimientos.
 *  - Botones para entregar, devolver, perder y reponer.
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
        </table>
    </div>

    <h3>Pendiente de devolver por tipo de llave</h3>
    <table class="tabla-listado">
        <thead>
            <tr>
                <th>Tipo de llave</th>
                <th>Pendiente de devolver</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tipos as $tipo): ?>
                <tr>
                    <td><?= Security::html($tipo['nombre']) ?></td>
                    <td><?= isset($pendientes[$tipo['id']]) ? $pendientes[$tipo['id']] : 0 ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Historial de movimientos</h3>
    <?php if (empty($movimientos)): ?>
        <div class="alerta alerta-info" role="alert">No hay movimientos de llave registrados.</div>
    <?php else: ?>
        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Cantidad</th>
                    <th>Identificador</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movimientos as $m): ?>
                    <tr>
                        <td><?= Security::html(date('d/m/Y', strtotime($m['fecha']))) ?></td>
                        <td><?= Security::html(ucfirst($m['tipo'])) ?></td>
                        <td><?= $m['cantidad'] ?></td>
                        <td><?= $m['identificador'] !== null ? Security::html($m['identificador']) : '—' ?></td>
                        <td><?= $m['observaciones'] !== null ? Security::html($m['observaciones']) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="acciones-formulario">
        <a href="?r=llaves/entregar&estancia_id=<?= $estancia['id'] ?>" class="btn-guardar">Entregar llave</a>
        <a href="?r=llaves/devolver&estancia_id=<?= $estancia['id'] ?>" class="btn-guardar">Devolver llave</a>
        <a href="?r=llaves/perder&estancia_id=<?= $estancia['id'] ?>" class="btn-guardar">Registrar pérdida</a>
        <a href="?r=llaves/reponer&estancia_id=<?= $estancia['id'] ?>" class="btn-guardar">Reponer llave</a>
        <a href="?r=estancias" class="btn-volver">Volver a estancias</a>
    </div>
</div>
