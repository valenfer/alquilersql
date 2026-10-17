<?php
declare(strict_types=1);

/**
 * Vista estancias/formulario.php — Formulario para crear o editar una estancia.
 *
 * Responsabilidad:
 *  - Mostrar campos: inquilino, habitación, fechas, renta, día de vencimiento, fianza, referencia.
 *  - Validación de solapamientos en servidor.
 *  - Ayuda sobre el intervalo [entrada, salida).
 */

?>
<div class="tarjeta-formulario">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['error_estancia'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_estancia']) ?></div>
    <?php endif; ?>

    <form action="?r=estancias/<?= $accion === 'crear' ? 'crear' : 'actualizar' ?>" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
        <?php if ($accion === 'actualizar'): ?>
            <input type="hidden" name="id" value="<?= Security::attr($id) ?>">
        <?php endif; ?>

        <div class="campo">
            <label for="inquilino_id">Inquilino</label>
            <select id="inquilino_id" name="inquilino_id" required>
                <option value="">Seleccione un inquilino</option>
                <?php foreach ($inquilinos as $inq): ?>
                    <option value="<?= $inq['id'] ?>" <?= (($estancia['inquilino_id'] ?? null) == $inq['id']) ? 'selected' : '' ?>>
                        <?= Security::html($inq['nombre'] . ' ' . $inq['apellidos']) ?> (<?= Security::html($inq['numero_documento']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="habitacion_id">Habitación</label>
            <select id="habitacion_id" name="habitacion_id" required>
                <option value="">Seleccione una habitación</option>
                <?php foreach ($habitaciones as $h): ?>
                    <option value="<?= $h['id'] ?>" <?= (($estancia['habitacion_id'] ?? null) == $h['id']) ? 'selected' : '' ?>>
                        <?= Security::html($h['nombre']) ?> (<?= Security::html($h['estado']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="fecha_entrada">Fecha de entrada</label>
            <input type="date" id="fecha_entrada" name="fecha_entrada" required
                   value="<?= Security::html($estancia['fecha_entrada'] ?? '') ?>">
            <p class="ayuda-campo">Día en que el inquilino entra a vivir. Este día cuenta en la ocupación.</p>
        </div>

        <div class="campo">
            <label for="fecha_salida_prevista">Fecha de salida prevista (opcional)</label>
            <input type="date" id="fecha_salida_prevista" name="fecha_salida_prevista"
                   value="<?= Security::html($estancia['fecha_salida_prevista'] ?? '') ?>">
            <p class="ayuda-campo">Sirve para planificar. No cierra automáticamente la estancia.</p>
        </div>

        <div class="campo">
            <label for="fecha_salida_efectiva">Fecha de salida efectiva (opcional)</label>
            <input type="date" id="fecha_salida_efectiva" name="fecha_salida_efectiva"
                   value="<?= Security::html($estancia['fecha_salida_efectiva'] ?? '') ?>">
            <p class="ayuda-campo">Día en que el inquilino sale realmente. Este día no cuenta en la ocupación. Mientras esté vacío, la estancia sigue activa.</p>
        </div>

        <div class="campo">
            <label for="renta_mensual">Renta mensual acordada (€)</label>
            <input type="text" id="renta_mensual" name="renta_mensual" required
                   value="<?= Security::html($estancia['renta_mensual'] !== null ? number_format($estancia['renta_mensual'], 2, ',', '.') : '') ?>"
                   placeholder="Ejemplo: 350,00">
        </div>

        <div class="campo">
            <label for="dia_vencimiento">Día de vencimiento habitual</label>
            <input type="number" id="dia_vencimiento" name="dia_vencimiento" min="1" max="31"
                   value="<?= Security::attr($estancia['dia_vencimiento'] ?? 1) ?>">
            <p class="ayuda-campo">Día del mes en que vence la mensualidad. Si el día no existe en un mes, se usa el último día del mes.</p>
        </div>

        <div class="campo">
            <label for="fianza_pactada">Fianza pactada (€)</label>
            <input type="text" id="fianza_pactada" name="fianza_pactada"
                   value="<?= Security::html($estancia['fianza_pactada'] !== null ? number_format($estancia['fianza_pactada'], 2, ',', '.') : '0,00') ?>"
                   placeholder="Ejemplo: 400,00">
            <p class="ayuda-campo">Importe de la fianza para esta estancia. No es ingreso de alquiler.</p>
        </div>

        <div class="campo">
            <label for="referencia_contrato">Referencia o notas del contrato</label>
            <input type="text" id="referencia_contrato" name="referencia_contrato"
                   value="<?= Security::html($estancia['referencia_contrato'] ?? '') ?>"
                   placeholder="Ejemplo: Contrato firmado el 01/09/2026">
        </div>

        <div class="acciones-formulario">
            <button type="submit" class="btn-guardar"><?= $accion === 'crear' ? 'Crear estancia' : 'Actualizar estancia' ?></button>
            <a href="?r=estancias" class="btn-volver">Volver sin guardar</a>
        </div>
    </form>

    <p class="ayuda">
        Una misma persona no puede tener ocupaciones simultáneas en distintas habitaciones.
        Una habitación no puede tener dos ocupaciones solapadas. Salida y entrada de personas
        distintas el mismo día están permitidas.
    </p>
</div>
