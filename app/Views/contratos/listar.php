<?php
declare(strict_types=1);

/**
 * Vista contratos/listar.php — Listado de contratos de alquiler.
 *
 * Responsabilidad:
 *  - Mostrar tabla de contratos con inquilino, habitación, fechas, renta y estado.
 *  - Botones para crear, renovar, ver y marcar como vencido.
 */

?>
<div class="tarjeta-contratos">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['contrato_creado'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['contrato_creado']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['contrato_renovado'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['contrato_renovado']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['contrato_vencido'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['contrato_vencido']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['error_contrato'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_contrato']) ?></div>
    <?php endif; ?>

    <div class="acciones-lista">
        <a href="?r=contratos/alertas" class="btn-guardar">Ver alertas de vencimiento</a>
        <a href="?r=dashboard" class="btn-volver">Volver al dashboard</a>
    </div>

    <table class="tabla-listado">
        <thead>
            <tr>
                <th>Inquilino</th>
                <th>Habitación</th>
                <th>Inicio</th>
                <th>Fin</th>
                <th>Renta mensual</th>
                <th>Estado</th>
                <th>Días restantes</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($contratos)): ?>
                <tr>
                    <td colspan="8" style="text-align:center; padding:2rem;">No hay contratos registrados.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($contratos as $c): ?>
                <tr>
                    <td><?= Security::html($c['inquilino_nombre'] . ' ' . $c['inquilino_apellidos']) ?></td>
                    <td><?= Security::html($c['habitacion_numero']) ?></td>
                    <td><?= Security::html(date('d/m/Y', strtotime($c['fecha_inicio']))) ?></td>
                    <td><?= $c['fecha_fin'] ? Security::html(date('d/m/Y', strtotime($c['fecha_fin']))) : '—' ?></td>
                    <td><?= Security::html(number_format($c['renta_mensual'], 2, ',', '.')) . ' €' ?></td>
                    <td>
                        <span class="estado estado-<?= Security::html($c['estado']) ?>">
                            <?= Security::html(ucfirst($c['estado'])) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($c['fecha_fin']): ?>
                            <?php
                            $dias = (int) floor((strtotime($c['fecha_fin']) - time()) / 86400);
                            if ($dias < 0): ?>
                                <span class="estado estado-vencido">Vencido hace <?= abs($dias) ?> días</span>
                            <?php elseif ($dias <= 15): ?>
                                <span class="estado estado-vencido"><?= $dias ?> días restantes</span>
                            <?php elseif ($dias <= 30): ?>
                                <span class="estado estado-advertencia"><?= $dias ?> días restantes</span>
                            <?php else: ?>
                                <?= $dias ?> días
                            <?php endif; ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?r=contratos/ver&id=<?= $c['id'] ?>" class="btn-small">Ver</a>
                        <?php if ($c['estado'] === 'vigente'): ?>
                            <a href="?r=contratos/renovar&id=<?= $c['id'] ?>" class="btn-small">Renovar</a>
                            <form action="?r=contratos/marcar-vencido" method="post" style="display:inline;" onsubmit="return confirm('¿Está seguro de que desea marcar este contrato como vencido?');">
                                <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
                                <input type="hidden" name="contrato_id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn-small btn-eliminar">Vencer</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="ayuda">
        Los contratos vencidos se muestran en rojo. Los contratos que vencen en 15 días o menos
        también se destacan. Puede crear nuevos contratos desde la ficha de una estancia activa.
    </p>
</div>
