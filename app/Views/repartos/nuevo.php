<?php
declare(strict_types=1);

/**
 * Vista repartos/nuevo.php — Pantalla de reparto manual de consumos.
 *
 * Responsabilidad:
 *  - Mostrar factura, periodo, importe total.
 *  - Mostrar estancias del periodo con habitación, inquilino, fechas.
 *  - Mostrar ausencias anotadas que coincidan con el periodo.
 *  - Caja grande por destinatario: "Cantidad que le corresponde según vuestro acuerdo".
 *  - Mostrar total asignado, diferencia pendiente, validación en tiempo real.
 */

?>
<div class="tarjeta-reparto">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['error_reparto'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_reparto']) ?></div>
    <?php endif; ?>

    <div class="resumen-factura">
        <h3>Factura</h3>
        <table class="tabla-listado">
            <tr>
                <td><strong>Tipo:</strong></td>
                <td><?= Security::html(ucfirst($factura['tipo'])) ?></td>
            </tr>
            <tr>
                <td><strong>Proveedor:</strong></td>
                <td><?= Security::html($factura['proveedor']) ?></td>
            </tr>
            <tr>
                <td><strong>Número:</strong></td>
                <td><?= $factura['numero_factura'] !== null ? Security::html($factura['numero_factura']) : '—' ?></td>
            </tr>
            <tr>
                <td><strong>Periodo:</strong></td>
                <td><?= Security::html(date('d/m/Y', strtotime($factura['periodo_desde']))) . ' — ' . date('d/m/Y', strtotime($factura['periodo_hasta'])) ?></td>
            </tr>
            <tr>
                <td><strong>Importe total:</strong></td>
                <td><?= Security::html(number_format($factura['importe_total'], 2, ',', '.')) . ' €' ?></td>
            </tr>
        </table>
    </div>

    <form action="?r=repartos/crear" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
        <input type="hidden" name="factura_id" value="<?= $factura['id'] ?>">
        <input type="hidden" name="fecha_acuerdo" value="<?= date('Y-m-d') ?>">

        <div class="campo">
            <label for="autor">¿Quién registra este acuerdo?</label>
            <input type="text" id="autor" name="autor" required
                   placeholder="Ejemplo: Juan Pérez (propietario)">
        </div>

        <div class="campo">
            <label for="observaciones">Observaciones del reparto</label>
            <textarea id="observaciones" name="observaciones" rows="2"
                      placeholder="Ejemplo: Habitación 4 vacía; habitación 2 de vacaciones del 5 al 12"><?= Security::html($reparto['observaciones'] ?? '') ?></textarea>
            <p class="ayuda-campo">Anotar aquí cualquier circunstancia relevante del reparto acordado.</p>
        </div>

        <h3>Destinatarios y cuotas acordadas</h3>
        <p class="ayuda">
            Introduzca la cantidad que cada inquilino ha acordado pagar.
            El importe es manual: la aplicación no lo calcula.
            Una cuota de <strong>0,00 €</strong> es válida si se asigna explícitamente.
        </p>

        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>Habitación</th>
                    <th>Inquilino y estancia</th>
                    <th>Cantidad que le corresponde según vuestro acuerdo (€)</th>
                    <th>Referencia (fechas entrada/salida)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($estancias)): ?>
                    <tr>
                        <td colspan="4" style="text-align:center; padding:2rem;">
                            No hay ocupantes en el periodo de esta factura.
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($estancias as $e): ?>
                    <tr>
                        <td><?= Security::html($e['habitacion_numero']) ?></td>
                        <td><?= Security::html($e['inquilino_nombre'] . ' ' . $e['inquilino_apellidos']) ?></td>
                        <td>
                            <input type="text" name="cuota_[<?= $e['estancia_id'] ?>]"
                                   value="<?= isset($reparto['cuotas']) ? (isset($reparto['cuotas'][$e['estancia_id']]['importe']) ? number_format($reparto['cuotas'][$e['estancia_id']]['importe'], 2, ',', '.') : '') : '' ?>"
                                   placeholder="0,00"
                                   onchange="actualizarTotales()">
                        </td>
                        <td>
                            <?= Security::html(date('d/m/Y', strtotime($e['fecha_entrada']))) ?> —
                            <?= $e['fecha_salida_efectiva'] ? date('d/m/Y', strtotime($e['fecha_salida_efectiva'])) : '—' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!empty($ausencias)): ?>
            <h4>Ausencias anotadas en el periodo</h4>
            <table class="tabla-listado">
                <thead>
                    <tr>
                        <th>Inquilino</th>
                        <th>Habitación</th>
                        <th>Ausencia</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ausencias as $a): ?>
                        <tr>
                            <td><?= Security::html($a['inquilino_nombre'] . ' ' . $a['inquilino_apellidos']) ?></td>
                            <td><?= Security::html($a['habitacion_numero']) ?></td>
                            <td><?= Security::html(date('d/m/Y', strtotime($a['fecha_inicio']))) . ' — ' . date('d/m/Y', strtotime($a['fecha_fin'])) ?></td>
                            <td><?= Security::html($a['observaciones'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="ayuda">Las ausencias son solo contexto para el reparto acordado; no se recalculan importes automáticamente.</p>
        <?php endif; ?>

        <div class="resumen-reparto">
            <h3>Resumen del reparto</h3>
            <table class="tabla-listado">
                <tr>
                    <td><strong>Total de la factura:</strong></td>
                    <td id="totalFactura"><?= Security::html(number_format($factura['importe_total'], 2, ',', '.')) . ' €' ?></td>
                </tr>
                <tr>
                    <td><strong>Total asignado en cuotas:</strong></td>
                    <td id="totalAsignado">0,00 €</td>
                </tr>
                <tr>
                    <td><strong>Diferencia pendiente:</strong></td>
                    <td id="diferenciaPendiente" style="font-weight:bold; color:#c62828;">0,00 €</td>
                </tr>
            </table>
            <p id="mensajeDiferencia" class="ayuda" style="color:#c62828;"></p>
        </div>

        <div class="acciones-formulario">
            <button type="submit" name="guardar_borrador" value="1" class="btn-volver">Guardar como borrador</button>
            <button type="submit" class="btn-guardar">Confirmar reparto</button>
            <a href="?r=facturas/ver&id=<?= $factura['id'] ?>" class="btn-volver">Volver sin guardar</a>
        </div>
    </form>

    <script>
        function actualizarTotales() {
            const inputs = document.querySelectorAll('input[name^="cuota_"]');
            let total = 0;
            let tieneVacias = false;

            inputs.forEach(function(input) {
                const valor = input.value.trim();
                if (valor === '') {
                    tieneVacias = true;
                    return;
                }
                const num = parseFloat(valor.replace('.', '').replace(',', '.'));
                if (!isNaN(num)) {
                    total += num;
                }
            });

            total = Math.round(total * 100) / 100;
            const totalFactura = parseFloat('<?= $factura['importe_total'] ?>');
            const diferencia = Math.round((totalFactura - total) * 100) / 100;

            document.getElementById('totalAsignado').textContent = total.toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';
            document.getElementById('diferenciaPendiente').textContent = diferencia.toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';

            const mensaje = document.getElementById('mensajeDiferencia');
            if (tieneVacias) {
                mensaje.textContent = 'Faltan cuotas por completar.';
                mensaje.style.color = '#c62828';
            } else if (Math.abs(diferencia) < 0.005) {
                mensaje.textContent = 'Reparto cuadrado: la suma de cuotas coincide con el total de la factura.';
                mensaje.style.color = '#2e7d32';
            } else if (diferencia > 0) {
                mensaje.textContent = 'Falta asignar ' + diferencia.toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €.';
                mensaje.style.color = '#c62828';
            } else {
                mensaje.textContent = 'Sobra asignado ' + Math.abs(diferencia).toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €.';
                mensaje.style.color = '#c62828';
            }
        }

        // Inicializar totales al cargar
        actualizarTotales();
    </script>
</div>
