<?php
declare(strict_types=1);

/**
 * Vista comprobacion/ver.php — Comprobación de pagos por factura.
 *
 * Responsabilidad:
 *  - Mostrar cabecera de la factura con importe, estado, pagos al proveedor.
 *  - Mostrar dos comprobaciones independientes: cuadre del reparto y cuadre del dinero.
 *  - Mostrar tabla por destinatario con cuota acordada, cobrado, pendiente, estado.
 *  - Permitir desplegar filas para ver detalle de cobros.
 */

?>
<div class="tarjeta-comprobacion">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash)): ?>
        <?php foreach ($flash as $msg): ?>
            <div class="alerta alerta-<?= Security::html($msg['tipo']) ?>" role="alert"><?= Security::html($msg['valor']) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="cabecera-comprobacion">
        <table class="tabla-listado">
            <tr>
                <td><strong>Suministro:</strong></td>
                <td><?= Security::html(ucfirst($detalle['factura']['tipo'])) ?></td>
            </tr>
            <tr>
                <td><strong>Proveedor:</strong></td>
                <td><?= Security::html($detalle['factura']['proveedor']) ?></td>
            </tr>
            <tr>
                <td><strong>Número de factura:</strong></td>
                <td><?= $detalle['factura']['numero_factura'] !== null ? Security::html($detalle['factura']['numero_factura']) : '—' ?></td>
            </tr>
            <tr>
                <td><strong>Periodo:</strong></td>
                <td><?= Security::html(date('d/m/Y', strtotime($detalle['factura']['periodo_desde']))) . ' — ' . date('d/m/Y', strtotime($detalle['factura']['periodo_hasta'])) ?></td>
            </tr>
            <tr>
                <td><strong>Importe total:</strong></td>
                <td><?= Security::html(number_format($detalle['total_factura'], 2, ',', '.')) . ' €' ?></td>
            </tr>
            <tr>
                <td><strong>Pagado al proveedor:</strong></td>
                <td><?= Security::html(number_format($detalle['total_pagado_proveedor'], 2, ',', '.')) . ' €' ?></td>
            </tr>
        </table>
    </div>

    <!-- Comprobación 1: Cuadre del reparto -->
    <h3>1. Cuadre del reparto (acuerdo de inquilinos)</h3>
    <div class="cuadre-bloque">
        <table class="tabla-listado">
            <tr>
                <td><strong>Total de la factura:</strong></td>
                <td><?= Security::html(number_format($detalle['total_factura'], 2, ',', '.')) . ' €' ?></td>
            </tr>
            <tr>
                <td><strong>Suma de cuotas acordadas:</strong></td>
                <td><?= Security::html(number_format($detalle['total_cuotas_acordadas'], 2, ',', '.')) . ' €' ?></td>
            </tr>
            <tr>
                <td><strong>Diferencia por asignar:</strong></td>
                <td class="<?= $detalle['diferencia_reparto'] === 0.0 ? 'cuadre-ok' : 'cuadre-error' ?>">
                    <?= Security::html(number_format($detalle['diferencia_reparto'], 2, ',', '.')) . ' €' ?>
                </td>
            </tr>
        </table>
        <p><strong>Estado:</strong> <span class="estado estado-<?= $detalle['estado_reparto'] === 'Reparto cuadrado' ? 'exito' : 'error' ?>"><?= Security::html($detalle['estado_reparto']) ?></span></p>
    </div>

    <!-- Comprobación 2: Cuadre del dinero -->
    <h3>2. Cuadre del dinero cobrado (ingresos de inquilinos)</h3>
    <div class="cuadre-bloque">
        <table class="tabla-listado">
            <tr>
                <td><strong>Total de la factura:</strong></td>
                <td><?= Security::html(number_format($detalle['total_factura'], 2, ',', '.')) . ' €' ?></td>
            </tr>
            <tr>
                <td><strong>Cobros netos de inquilinos:</strong></td>
                <td><?= Security::html(number_format($detalle['total_cobros_dinero'], 2, ',', '.')) . ' €' ?></td>
            </tr>
            <tr>
                <td><strong>Diferencia (factura − ingresos):</strong></td>
                <td class="<?= $detalle['diferencia_dinero'] === 0.0 ? 'cuadre-ok' : 'cuadre-error' ?>">
                    <?= Security::html(number_format($detalle['diferencia_dinero'], 2, ',', '.')) . ' €' ?>
                </td>
            </tr>
        </table>
        <p><strong>Estado:</strong> <span class="estado estado-<?= $detalle['estado_dinero'] === 'Factura cobrada íntegramente en dinero' ? 'exito' : 'advertencia' ?>"><?= Security::html($detalle['estado_dinero']) ?></span></p>
        <p class="ayuda">
            Un importe positivo indica dinero todavía no recuperado en efectivo.
            Un importe negativo indica exceso de cobro que debe revisarse o devolverse.
            Las aplicaciones de fianza se presentan separadamente (no son dinero nuevo).
        </p>
    </div>

    <!-- Tabla por destinatario -->
    <h3>Detalle por destinatario</h3>
    <table class="tabla-listado">
        <thead>
            <tr>
                <th>Habitación</th>
                <th>Inquilino y estancia</th>
                <th>Cuota acordada</th>
                <th>Cobrado en dinero</th>
                <th>Pendiente</th>
                <th>Estado</th>
                <th>Detalle</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalle['destinatarios'] as $d): ?>
                <tr>
                    <td><?= Security::html($d['habitacion']) ?></td>
                    <td><?= Security::html($d['inquilino']) ?></td>
                    <td><?= Security::html(number_format($d['cuota_acordada'], 2, ',', '.')) . ' €' ?></td>
                    <td><?= Security::html(number_format($d['cobrado_dinero'], 2, ',', '.')) . ' €' ?></td>
                    <td><?= Security::html(number_format($d['pendiente'], 2, ',', '.')) . ' €' ?></td>
                    <td>
                        <span class="estado estado-<?= $d['pendiente'] === 0.0 ? 'exito' : 'advertencia' ?>">
                            <?= $d['pendiente'] === 0.0 ? 'Cuadrado' : 'Pendiente ' . number_format($d['pendiente'], 2, ',', '.') . ' €' ?>
                        </span>
                    </td>
                    <td>
                        <button type="button" class="btn-small" onclick="toggleDetalle(<?= $d['habitacion'] ?>)">Ver movimientos</button>
                        <div id="detalle-<?= $d['habitacion'] ?>" style="display:none; margin-top:0.5rem;">
                            <table class="tabla-listado" style="font-size:0.9rem;">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Importe aplicado</th>
                                        <th>Medio</th>
                                        <th>Referencia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($d['cobros'])): ?>
                                        <tr><td colspan="4" style="text-align:center;">Sin cobros aplicados.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($d['cobros'] as $cobro): ?>
                                        <tr>
                                            <td><?= Security::html(date('d/m/Y', strtotime($cobro['fecha_cobro']))) ?></td>
                                            <td><?= Security::html(number_format($cobro['importe_aplicado'], 2, ',', '.')) . ' €' ?></td>
                                            <td><?= Security::html(ucfirst($cobro['medio'])) ?></td>
                                            <td><?= $cobro['referencia'] !== null ? Security::html($cobro['referencia']) : '—' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="2"><strong>Totales</strong></td>
                <td><strong><?= Security::html(number_format($detalle['total_cuotas_acordadas'], 2, ',', '.')) . ' €' ?></strong></td>
                <td><strong><?= Security::html(number_format($detalle['total_cobros_dinero'], 2, ',', '.')) . ' €' ?></strong></td>
                <td><strong><?= Security::html(number_format($detalle['diferencia_dinero'], 2, ',', '.')) . ' €' ?></strong></td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="acciones-formulario">
        <a href="?r=facturas/ver&id=<?= $detalle['factura']['id'] ?>" class="btn-volver">Volver a la factura</a>
    </div>
</div>

<script>
    function toggleDetalle(habitacion) {
        const el = document.getElementById('detalle-' + habitacion);
        if (el.style.display === 'none') {
            el.style.display = 'block';
        } else {
            el.style.display = 'none';
        }
    }
</script>
</div>
