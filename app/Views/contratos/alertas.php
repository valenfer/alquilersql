<?php
declare(strict_types=1);

/**
 * Vista contratos/alertas.php — Alertas de vencimiento de contratos.
 *
 * Responsabilidad:
 *  - Mostrar contratos que vencen en 60, 30 y 15 días.
 *  - Mostrar contratos ya vencidos.
 *  - Cada fila incluye nombre e identificación de la habitación e inquilino.
 */

?>
<div class="tarjeta-contratos">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash)): ?>
        <?php foreach ($flash as $msg): ?>
            <div class="alerta alerta-<?= Security::html($msg['tipo']) ?>" role="alert"><?= Security::html($msg['valor']) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="acciones-lista">
        <a href="?r=contratos" class="btn-volver">Volver al listado de contratos</a>
    </div>

    <!-- Contratos que vencen en 60 días -->
    <h3>⚠️ Vencen en 60 días o menos</h3>
    <?php if (empty($alertas['60_dias'])): ?>
        <div class="alerta alerta-info" role="alert">No hay contratos que vencen en 60 días.</div>
    <?php else: ?>
        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>Inquilino</th>
                    <th>Documento</th>
                    <th>Habitación</th>
                    <th>Fecha fin</th>
                    <th>Días restantes</th>
                    <th>Renta mensual</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alertas['60_dias'] as $c): ?>
                    <tr>
                        <td><?= Security::html($c['inquilino_nombre'] . ' ' . $c['inquilino_apellidos']) ?></td>
                        <td><?= Security::html($c['inquilino_documento']) ?></td>
                        <td><?= Security::html($c['habitacion_numero']) ?></td>
                        <td><?= Security::html(date('d/m/Y', strtotime($c['fecha_fin']))) ?></td>
                        <td><?= (int) $c['dias_restantes'] ?> días</td>
                        <td><?= Security::html(number_format($c['renta_mensual'], 2, ',', '.')) . ' €' ?></td>
                        <td>
                            <a href="?r=contratos/ver&id=<?= $c['id'] ?>" class="btn-small">Ver</a>
                            <a href="?r=contratos/renovar&id=<?= $c['id'] ?>" class="btn-small">Renovar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Contratos que vencen en 30 días -->
    <h3>⚠️ Vencen en 30 días o menos</h3>
    <?php if (empty($alertas['30_dias'])): ?>
        <div class="alerta alerta-info" role="alert">No hay contratos que vencen en 30 días.</div>
    <?php else: ?>
        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>Inquilino</th>
                    <th>Documento</th>
                    <th>Habitación</th>
                    <th>Fecha fin</th>
                    <th>Días restantes</th>
                    <th>Renta mensual</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alertas['30_dias'] as $c): ?>
                    <tr>
                        <td><?= Security::html($c['inquilino_nombre'] . ' ' . $c['inquilino_apellidos']) ?></td>
                        <td><?= Security::html($c['inquilino_documento']) ?></td>
                        <td><?= Security::html($c['habitacion_numero']) ?></td>
                        <td><?= Security::html(date('d/m/Y', strtotime($c['fecha_fin']))) ?></td>
                        <td><?= (int) $c['dias_restantes'] ?> días</td>
                        <td><?= Security::html(number_format($c['renta_mensual'], 2, ',', '.')) . ' €' ?></td>
                        <td>
                            <a href="?r=contratos/ver&id=<?= $c['id'] ?>" class="btn-small">Ver</a>
                            <a href="?r=contratos/renovar&id=<?= $c['id'] ?>" class="btn-small">Renovar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Contratos que vencen en 15 días -->
    <h3>🚨 Vencen en 15 días o menos</h3>
    <?php if (empty($alertas['15_dias'])): ?>
        <div class="alerta alerta-info" role="alert">No hay contratos que vencen en 15 días.</div>
    <?php else: ?>
        <div class="alerta alerta-advertencia" role="alert">
            <strong>¡Atención!</strong> Los siguientes contratos vencen en 15 días o menos. Se recomienda contactar a los inquilinos para renovar o finalizar.
        </div>
        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>Inquilino</th>
                    <th>Documento</th>
                    <th>Habitación</th>
                    <th>Fecha fin</th>
                    <th>Días restantes</th>
                    <th>Renta mensual</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alertas['15_dias'] as $c): ?>
                    <tr>
                        <td><?= Security::html($c['inquilino_nombre'] . ' ' . $c['inquilino_apellidos']) ?></td>
                        <td><?= Security::html($c['inquilino_documento']) ?></td>
                        <td><?= Security::html($c['habitacion_numero']) ?></td>
                        <td><?= Security::html(date('d/m/Y', strtotime($c['fecha_fin']))) ?></td>
                        <td><?= (int) $c['dias_restantes'] ?> días</td>
                        <td><?= Security::html(number_format($c['renta_mensual'], 2, ',', '.')) . ' €' ?></td>
                        <td>
                            <a href="?r=contratos/ver&id=<?= $c['id'] ?>" class="btn-small">Ver</a>
                            <a href="?r=contratos/renovar&id=<?= $c['id'] ?>" class="btn-small">Renovar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Contratos ya vencidos -->
    <h3>❌ Contratos ya vencidos</h3>
    <?php if (empty($alertas['vencidos'])): ?>
        <div class="alerta alerta-info" role="alert">No hay contratos vencidos.</div>
    <?php else: ?>
        <div class="alerta alerta-error" role="alert">
            <strong>¡Atención!</strong> Los siguientes contratos ya vencieron y aún están marcados como vigentes.
        </div>
        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>Inquilino</th>
                    <th>Documento</th>
                    <th>Habitación</th>
                    <th>Fecha fin</th>
                    <th>Días vencido</th>
                    <th>Renta mensual</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alertas['vencidos'] as $c): ?>
                    <tr>
                        <td><?= Security::html($c['inquilino_nombre'] . ' ' . $c['inquilino_apellidos']) ?></td>
                        <td><?= Security::html($c['inquilino_documento']) ?></td>
                        <td><?= Security::html($c['habitacion_numero']) ?></td>
                        <td><?= Security::html(date('d/m/Y', strtotime($c['fecha_fin']))) ?></td>
                        <td><?= (int) $c['dias_vencido'] ?> días</td>
                        <td><?= Security::html(number_format($c['renta_mensual'], 2, ',', '.')) . ' €' ?></td>
                        <td>
                            <a href="?r=contratos/ver&id=<?= $c['id'] ?>" class="btn-small">Ver</a>
                            <form action="?r=contratos/marcar-vencido" method="post" style="display:inline;" onsubmit="return confirm('¿Está seguro de que desea marcar este contrato como vencido?');">
                                <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
                                <input type="hidden" name="contrato_id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn-small btn-eliminar">Marcar vencido</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p class="ayuda">
        Las alertas se calculan automáticamente basándose en la fecha de finalización de cada contrato.
        Los contratos vencidos deben marcarse manualmente como 'vencido' desde su detalle.
    </p>
</div>
