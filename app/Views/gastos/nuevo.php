<?php
declare(strict_types=1);

/**
 * Vista gastos/nuevo.php — Formulario para registrar un gasto.
 *
 * Responsabilidad:
 *  - Mostrar campos: categoría, descripción, importe, fecha, proveedor, número de factura, habitación, observaciones.
 *  - Permitir registrar pagos parciales o completos en el mismo formulario.
 *  - Advertir que comunidad e IBI no generan cuotas a inquilinos.
 */

?>
<div class="tarjeta-formulario">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['error_gasto'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_gasto']) ?></div>
    <?php endif; ?>

    <div class="alerta alerta-info" role="alert">
        <strong>Nota:</strong> Comunidad e IBI no generan cuotas a los inquilinos.
        Separar la existencia del gasto y su pago. Se admiten pagos parciales.
    </div>

    <form action="?r=gastos/crear" method="post" id="formGasto" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">

        <div class="campo">
            <label for="categoria_id">Categoría</label>
            <select id="categoria_id" name="categoria_id" required>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= Security::html($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label for="descripcion">Descripción</label>
            <input type="text" id="descripcion" name="descripcion" required
                   placeholder="Ejemplo: Reparación de caldera">
        </div>

        <div class="campo">
            <label for="importe">Importe del gasto (€)</label>
            <input type="text" id="importe" name="importe" required
                   placeholder="Ejemplo: 90,00">
        </div>

        <div class="campo">
            <label for="fecha_gasto">Fecha del gasto o documento</label>
            <input type="date" id="fecha_gasto" name="fecha_gasto" required
                   value="<?= date('Y-m-d') ?>">
        </div>

        <div class="campo">
            <label for="proveedor">Proveedor (opcional)</label>
            <input type="text" id="proveedor" name="proveedor"
                   placeholder="Ejemplo: Fontanero Rodríguez">
        </div>

        <div class="campo">
            <label for="numero_factura">Número de factura o justificante (opcional)</label>
            <input type="text" id="numero_factura" name="numero_factura"
                   placeholder="Ejemplo: FAC-2026-045">
        </div>

        <div class="campo">
            <label for="relacion_habitacion">Relacionar con habitación (opcional)</label>
            <select id="relacion_habitacion" name="relacion_habitacion">
                <option value="">Gasto general del piso</option>
                <?php foreach ($habitaciones as $h): ?>
                    <option value="<?= $h['id'] ?>"><?= Security::html($h['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="ayuda-campo">Si se deja sin seleccionar, es un gasto general sin inquilino.</p>
        </div>

        <div class="campo">
            <label for="observaciones">Observaciones (opcional)</label>
            <textarea id="observaciones" name="observaciones" rows="2"></textarea>
        </div>

        <h3>Pagos (opcional)</h3>
        <p class="ayuda">Si el gasto ya está pagado, registre el pago aquí. Se admiten varios pagos parciales.</p>
        <div id="pagos-container">
            <div class="pago-fila" id="pago-fila-0">
                <div class="campo" style="display:flex; gap:1rem; align-items:end;">
                    <div style="flex:1;">
                        <label>Importe (€)</label>
                        <input type="text" name="pago_importe[]" placeholder="0,00">
                    </div>
                    <div style="flex:1;">
                        <label>Fecha</label>
                        <input type="date" name="pago_fecha[]" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div style="flex:1;">
                        <label>Medio</label>
                        <select name="pago_medio[]">
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="bizum">Bizum</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label>Referencia</label>
                        <input type="text" name="pago_referencia[]" placeholder="Referencia">
                    </div>
                </div>
            </div>
        </div>
        <button type="button" id="btnAgregarPago" class="btn-guardar" style="padding:0.5rem 1rem; font-size:0.9rem;">Agregar otro pago</button>

        <div class="campo">
            <label for="es_general">¿Es gasto general del piso?</label>
            <select id="es_general" name="es_general">
                <option value="1">Sí, gasto general</option>
                <option value="0">No, relacionado con una habitación</option>
            </select>
        </div>

        <div class="acciones-formulario">
            <button type="submit" class="btn-guardar">Registrar gasto</button>
            <a href="?r=gastos" class="btn-volver">Volver sin guardar</a>
        </div>
    </form>

    <script>
        document.getElementById('btnAgregarPago').addEventListener('click', function() {
            const container = document.getElementById('pagos-container');
            const index = container.children.length;
            const fila = document.createElement('div');
            fila.className = 'pago-fila';
            fila.innerHTML = `
                <div class="campo" style="display:flex; gap:1rem; align-items:end; margin-top:1rem;">
                    <div style="flex:1;">
                        <label>Importe (€)</label>
                        <input type="text" name="pago_importe[]" placeholder="0,00">
                    </div>
                    <div style="flex:1;">
                        <label>Fecha</label>
                        <input type="date" name="pago_fecha[]" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div style="flex:1;">
                        <label>Medio</label>
                        <select name="pago_medio[]">
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="bizum">Bizum</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label>Referencia</label>
                        <input type="text" name="pago_referencia[]" placeholder="Referencia">
                    </div>
                    <div style="flex:0 0 auto;">
                        <button type="button" onclick="this.closest('.pago-fila').remove()" style="padding:0.5rem;">×</button>
                    </div>
                </div>
            `;
            container.appendChild(fila);
        });
    </script>
</div>
