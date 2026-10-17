<?php
declare(strict_types=1);

/**
 * Vista contratos/ver.php — Detalle de un contrato de alquiler.
 *
 * Responsabilidad:
 *  - Mostrar información completa del contrato.
 *  - Botones para renovar o marcar como vencido si está vigente.
 */

?>
<div class="tarjeta-contratos">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash)): ?>
        <?php foreach ($flash as $msg): ?>
            <div class="alerta alerta-<?= Security::html($msg['tipo']) ?>" role="alert"><?= Security::html($msg['valor']) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="detalle-contrato">
        <div class="campo-detalle">
            <label>Inquilino</label>
            <span><?= Security::html($contrato['inquilino_nombre'] . ' ' . $contrato['inquilino_apellidos']) ?></span>
        </div>
        <div class="campo-detalle">
            <label>Documento</label>
            <span><?= Security::html($contrato['inquilino_documento']) ?></span>
        </div>
        <div class="campo-detalle">
            <label>Habitación</label>
            <span><?= Security::html($contrato['habitacion_numero']) ?></span>
        </div>
        <div class="campo-detalle">
            <label>Fecha de inicio</label>
            <span><?= Security::html(date('d/m/Y', strtotime($contrato['fecha_inicio']))) ?></span>
        </div>
        <div class="campo-detalle">
            <label>Fecha de finalización</label>
            <span><?= $contrato['fecha_fin'] ? Security::html(date('d/m/Y', strtotime($contrato['fecha_fin']))) : '—' ?></span>
        </div>
        <?php if ($diasRestantes !== null): ?>
            <div class="campo-detalle">
                <label>Días restantes</label>
                <span class="<?= $diasRestantes <= 15 ? 'estado estado-vencido' : '' ?>">
                    <?= $diasRestantes < 0 ? 'Vencido hace ' . abs($diasRestantes) . ' días' : $diasRestantes . ' días' ?>
                </span>
            </div>
        <?php endif; ?>
        <div class="campo-detalle">
            <label>Renta mensual</label>
            <span><?= Security::html(number_format($contrato['renta_mensual'], 2, ',', '.')) . ' €' ?></span>
        </div>
        <div class="campo-detalle">
            <label>Estado</label>
            <span class="estado estado-<?= Security::html($contrato['estado']) ?>">
                <?= Security::html(ucfirst($contrato['estado'])) ?>
            </span>
        </div>
    </div>

    <?php if ($contrato['condiciones']): ?>
        <div class="campo-detalle">
            <label>Condiciones generales</label>
            <span><?= Security::html($contrato['condiciones']) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($contrato['clausulas_adicionales']): ?>
        <div class="campo-detalle">
            <label>Cláusulas adicionales</label>
            <span><?= Security::html($contrato['clausulas_adicionales']) ?></span>
        </div>
    <?php endif; ?>

    <div class="acciones-formulario">
        <?php if ($contrato['estado'] === 'vigente'): ?>
            <a href="?r=contratos/renovar&id=<?= $contrato['id'] ?>" class="btn-guardar">Renovar contrato</a>
            <form action="?r=contratos/marcar-vencido" method="post" style="display:inline;" onsubmit="return confirm('¿Está seguro de que desea marcar este contrato como vencido?');">
                <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
                <input type="hidden" name="contrato_id" value="<?= $contrato['id'] ?>">
                <button type="submit" class="btn-small btn-eliminar">Marcar como vencido</button>
            </form>
        <?php endif; ?>
        <a href="?r=contratos" class="btn-volver">Volver al listado</a>
    </div>

    <?php if ($contratoRenovado): ?>
        <div class="alerta alerta-info" role="alert">
            <strong>Este contrato ha sido renovado.</strong>
            El nuevo contrato tiene fecha de inicio
            <?= Security::html(date('d/m/Y', strtotime($contratoRenovado['fecha_inicio']))) ?>
            y fecha de finalización
            <?= $contratoRenovado['fecha_fin'] ? Security::html(date('d/m/Y', strtotime($contratoRenovado['fecha_fin']))) : '—' ?>.
            <a href="?r=contratos/ver&id=<?= $contratoRenovado['id'] ?>" class="btn-small">Ver contrato renovado</a>
        </div>
    <?php endif; ?>
</div>
