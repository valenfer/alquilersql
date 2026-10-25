<?php
declare(strict_types=1);

use App\Support\Security;

/**
 * Vista facturas/formulario.php — Formulario para crear o editar una factura.
 *
 * Responsabilidad:
 *  - Mostrar campos: tipo, proveedor, número, fecha emisión, periodo, importe, vencimiento, estado, observaciones.
 *  - Botón "Guardar como borrador" y "Confirmar factura".
 *  - Ayuda sobre número obligatorio al confirmar y periodos solapados.
 */

?>
<div class="tarjeta-formulario">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['error_factura'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_factura']['valor']) ?></div>
    <?php endif; ?>

    <form action="?r=facturas/<?= $accion === 'crear' ? 'crear' : 'actualizar' ?>" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
        <?php if ($accion === 'actualizar'): ?>
            <input type="hidden" name="id" value="<?= Security::attr((string) $id) ?>">
        <?php endif; ?>

        <div class="campo">
            <label for="tipo">Tipo de suministro</label>
            <select id="tipo" name="tipo" required>
                <option value="luz" <?= (($factura['tipo'] ?? 'luz') === 'luz') ? 'selected' : '' ?>>Luz (electricidad)</option>
                <option value="agua" <?= (($factura['tipo'] ?? '') === 'agua') ? 'selected' : '' ?>>Agua</option>
            </select>
        </div>

        <div class="campo">
            <label for="proveedor">Proveedor o compañía</label>
            <input type="text" id="proveedor" name="proveedor" required
                   value="<?= Security::html($factura['proveedor'] ?? '') ?>"
                   placeholder="Ejemplo: Iberdrola, Endesa, Canal de Isabel II">
        </div>

        <div class="campo">
            <label for="numero_factura">Número de factura</label>
            <input type="text" id="numero_factura" name="numero_factura"
                   value="<?= Security::html($factura['numero_factura'] ?? '') ?>"
                   placeholder="Ejemplo: FAC-2026-018">
            <p class="ayuda-campo">Obligatorio al confirmar la factura. Debe ser único por proveedor y tipo de suministro.</p>
        </div>

        <div class="campo">
            <label for="fecha_emision">Fecha de emisión</label>
            <input type="date" id="fecha_emision" name="fecha_emision" required
                   value="<?= Security::attr($factura['fecha_emision'] ?? '') ?>">
        </div>

        <div class="campo">
            <label for="periodo_desde">Periodo de consumo — Desde</label>
            <input type="date" id="periodo_desde" name="periodo_desde" required
                   value="<?= Security::attr($factura['periodo_desde'] ?? '') ?>">
            <p class="ayuda-campo">Fecha inicial del periodo de consumo (inclusive, como figura en la factura).</p>
        </div>

        <div class="campo">
            <label for="periodo_hasta">Periodo de consumo — Hasta</label>
            <input type="date" id="periodo_hasta" name="periodo_hasta" required
                   value="<?= Security::attr($factura['periodo_hasta'] ?? '') ?>">
            <p class="ayuda-campo">Fecha final del periodo de consumo (inclusive, como figura en la factura).</p>
        </div>

        <div class="campo">
            <label for="importe_total">Importe total (€)</label>
            <input type="text" id="importe_total" name="importe_total"
                   value="<?= Security::html(($factura['importe_total'] ?? null) !== null ? number_format((float) $factura['importe_total'], 2, ',', '.') : '') ?>"
                   placeholder="Ejemplo: 120,00">
            <p class="ayuda-campo">Importe total de la factura. Máximo dos decimales.</p>
        </div>

        <div class="campo">
            <label for="fecha_vencimiento">Fecha de vencimiento / cargo bancario (opcional)</label>
            <input type="date" id="fecha_vencimiento" name="fecha_vencimiento"
                   value="<?= Security::attr($factura['fecha_vencimiento'] ?? '') ?>">
            <p class="ayuda-campo">Fecha prevista o real del cargo bancario. Se completa al marcar "El banco me ha cobrado esta factura".</p>
        </div>

        <div class="campo">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="borrador" <?= (($factura['estado'] ?? 'borrador') === 'borrador') ? 'selected' : '' ?>>Borrador</option>
                <option value="confirmada" <?= (($factura['estado'] ?? '') === 'confirmada') ? 'selected' : '' ?>>Confirmada</option>
            </select>
        </div>

        <div class="campo">
            <label for="motivo_solapamiento">Motivo de solapamiento (si aplica)</label>
            <textarea id="motivo_solapamiento" name="motivo_solapamiento" rows="2"><?= Security::html($factura['motivo_solapamiento'] ?? '') ?></textarea>
            <p class="ayuda-campo">Solo si el periodo se solapa con otra factura del mismo suministro. Justificar por qué (ajuste de la compañía, etc.).</p>
        </div>

        <div class="campo">
            <label for="observaciones">Observaciones (opcional)</label>
            <textarea id="observaciones" name="observaciones" rows="2"><?= Security::html($factura['observaciones'] ?? '') ?></textarea>
        </div>

        <div class="acciones-formulario">
            <?php if (!$accion === 'actualizar' || ($factura['estado'] ?? '') !== 'anulada'): ?>
                <button type="submit" name="guardar_borrador" value="1" class="btn-volver">Guardar como borrador</button>
            <?php endif; ?>
            <button type="submit" class="btn-guardar">Confirmar factura</button>
            <a href="?r=facturas" class="btn-volver">Volver sin guardar</a>
        </div>
    </form>
</div>
