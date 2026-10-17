<?php
declare(strict_types=1);

/**
 * Vista liquidacion/ver.php — Liquidación de salida de una estancia.
 *
 * Responsabilidad:
 *  - Mostrar liquidación imprimible con deudas por concepto, fianza recibida,
 *    devoluciones previas, compensaciones, saldo y llaves pendientes.
 *  - Permitir dejar la liquidación abierta si faltan facturas de suministros.
 */

?>
<div class="tarjeta-liquidacion">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash)): ?>
        <?php foreach ($flash as $msg): ?>
            <div class="alerta alerta-<?= Security::html($msg['tipo']) ?>" role="alert"><?= Security::html($msg['valor']) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="encabezado-liquidacion">
        <table class="tabla-listado">
            <tr>
                <td><strong>Inquilino:</strong></td>
                <td><?= Security::html($estancia['inquilino_nombre'] . ' ' . $estancia['inquilino_apellidos']) ?></td>
            </tr>
            <tr>
                <td><strong>Habitación:</strong></td>
                <td><?= Security::html($estancia['habitacion_numero']) ?></td>
            </tr>
            <tr>
                <td><strong>Entrada:</strong></td>
                <td><?= Security::html(date('d/m/Y', strtotime($estancia['fecha_entrada']))) ?></td>
            </tr>
            <tr>
                <td><strong>Salida efectiva:</strong></td>
                <td><?= $estancia['fecha_salida_efectiva'] ? Security::html(date('d/m/Y', strtotime($estancia['fecha_salida_efectiva']))) : '—' ?></td>
            </tr>
            <tr>
                <td><strong>Renta mensual:</strong></td>
                <td><?= Security::html(number_format($estancia['renta_mensual'], 2, ',', '.')) . ' €' ?></td>
            </tr>
        </table>
    </div>

    <?php if ($facturasPendientesReparto): ?>
        <div class="alerta alerta-advertencia" role="alert">
            <strong>Atención:</strong> Existen facturas de suministros del periodo de esta estancia
            cuyo reparto aún no se ha confirmado. La liquidación puede quedar abierta hasta que
            se conozcan las cuotas de los inquilinos.
        </div>
    <?php endif; ?>

    <!-- Deudas -->
    <h3>Deudas por concepto</h3>
    <div class="bloque-liquidacion">
        <h4>Alquiler pendiente</h4>
        <?php if (empty($deudas_alquiler)): ?>
            <p class="ayuda">No hay deudas de alquiler pendientes.</p>
        <?php else: ?>
            <table class="tabla-listado">
                <thead>
                    <tr><th>Año / Mes</th><th>Esperado</th><th>Cobrado</th><th>Pendiente</th><th>Vencimiento</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($deudas_alquiler as $d): ?>
                        <tr>
                            <td><?= $d['anio'] ?> / <?= $d['mes'] ?></td>
                            <td><?= Security::html(number_format($d['importe_esperado'], 2, ',', '.')) . ' €' ?></td>
                            <td><?= Security::html(number_format($d['importe_cobrado'], 2, ',', '.')) . ' €' ?></td>
                            <td><?= Security::html(number_format($d['saldo'], 2, ',', '.')) . ' €' ?></td>
                            <td><?= Security::html(date('d/m/Y', strtotime($d['fecha_vencimiento']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="font-weight:bold;">
                        <td>Total alquiler</td>
                        <td></td>
                        <td></td>
                        <td><?= Security::html(number_format($total_deuda_alquiler, 2, ',', '.')) . ' €' ?></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>

        <h4>Suministros pendientes (luz y agua)</h4>
        <?php if (empty($deudas_suministros)): ?>
            <p class="ayuda">No hay deudas de suministros pendientes.</p>
        <?php else: ?>
            <table class="tabla-listado">
                <thead>
                    <tr><th>Tipo</th><th>Proveedor</th><th>Factura</th><th>Periodo</th><th>Cuota</th><th>Cobrado</th><th>Pendiente</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($deudas_suministros as $d): ?>
                        <tr>
                            <td><?= Security::html(ucfirst($d['tipo'])) ?></td>
                            <td><?= Security::html($d['proveedor']) ?></td>
                            <td><?= $d['numero_factura'] !== null ? Security::html($d['numero_factura']) : '—' ?></td>
                            <td><?= Security::html(date('d/m/Y', strtotime($d['periodo_desde']))) . ' — ' . date('d/m/Y', strtotime($d['periodo_hasta'])) ?></td>
                            <td><?= Security::html(number_format($d['cuota'], 2, ',', '.')) . ' €' ?></td>
                            <td><?= Security::html(number_format($d['cobrado'], 2, ',', '.')) . ' €' ?></td>
                            <td><?= Security::html(number_format($d['saldo'], 2, ',', '.')) . ' €' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="font-weight:bold;">
                        <td colspan="4">Total suministros</td>
                        <td></td>
                        <td></td>
                        <td><?= Security::html(number_format($total_deuda_suministros, 2, ',', '.')) . ' €' ?></td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="total-deuda">
            <strong>Deuda total:</strong> <?= Security::html(number_format($total_deuda, 2, ',', '.')) . ' €' ?>
        </div>
    </div>

    <!-- Fianza -->
    <h3>Fianza</h3>
    <div class="bloque-liquidacion">
        <?php if (!$fianza): ?>
            <p class="ayuda">No se ha registrado fianza para esta estancia.</p>
        <?php else: ?>
            <table class="tabla-listado">
                <thead>
                    <tr><th>Concepto</th><th>Importe</th></tr>
                </thead>
                <tbody>
                    <tr><td>Fianza pactada</td><td><?= Security::html(number_format($fianza['importe_pactado'], 2, ',', '.')) . ' €' ?></td></tr>
                    <tr><td>Fianza recibida</td><td><?= Security::html(number_format($fianza['importe_recibido'], 2, ',', '.')) . ' €' ?></td></tr>
                    <tr><td>Devuelta previamente</td><td><?= Security::html(number_format($fianza['devueltas'], 2, ',', '.')) . ' €' ?></td></tr>
                    <tr><td>Aplicada a deudas</td><td><?= Security::html(number_format($fianza['aplicadas'], 2, ',', '.')) . ' €' ?></td></tr>
                    <tr><td>Compensaciones por daños</td><td><?= Security::html(number_format($fianza['compensaciones'], 2, ',', '.')) . ' €' ?></td></tr>
                    <tr style="font-weight:bold; background:#f8f9fa;">
                        <td>Saldo pendiente de devolver</td>
                        <td><?= Security::html(number_format($saldo_fianza, 2, ',', '.')) . ' €' ?></td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Liquidación final -->
    <h3>Resultado de la liquidación</h3>
    <div class="bloque-liquidacion resultado-final">
        <table class="tabla-listado">
            <thead>
                <tr><th>Concepto</th><th>Importe</th></tr>
            </thead>
            <tbody>
                <tr><td>Fianza disponible (recibida − devuelta − aplicada)</td><td><?= Security::html(number_format($saldo_fianza, 2, ',', '.')) . ' €' ?></td></tr>
                <tr><td>Deuda total del inquilino</td><td><?= Security::html(number_format($total_deuda, 2, ',', '.')) . ' €' ?></td></tr>
                <tr style="font-weight:bold; background:#f8f9fa;">
                    <td>Saldo a devolver al inquilino</td>
                    <td><?= Security::html(number_format($total_liquidacion, 2, ',', '.')) . ' €' ?></td>
                </tr>
            </tbody>
        </table>
        <p class="ayuda">
            Si el saldo es positivo, se debe devolver esa cantidad al inquilino.
            Si es negativo, el inquilino tiene una deuda pendiente que excede la fianza.
        </p>
    </div>

    <!-- Llaves -->
    <h3>Llaves pendientes de devolución</h3>
    <div class="bloque-liquidacion">
        <?php if ($llaves_pendientes > 0): ?>
            <div class="alerta alerta-error" role="alert">
                <strong>Atención:</strong> Quedan <?= $llaves_pendientes ?> llave(s) pendiente(s) de devolución.
                No se puede dar la estancia por finalizada hasta que se devuelvan todas las llaves.
            </div>
        <?php else: ?>
            <div class="alerta alerta-exito" role="alert">
                Todas las llaves han sido devueltas.
            </div>
        <?php endif; ?>
    </div>

    <div class="acciones-liquidacion">
        <a href="?r=fianzas/ver&id=<?= $fianza['id'] ?? 0 ?>" class="btn-guardar">Ver fianza</a>
        <a href="?r=llaves/estancia&id=<?= $estancia['id'] ?>" class="btn-guardar">Ver llaves</a>
        <a href="?r=estancias" class="btn-volver">Volver a estancias</a>
    </div>
</div>

<style>
    .tarjeta-liquidacion {
        max-width: 900px;
        margin: 2rem auto;
        background-color: #ffffff;
        border: 1px solid #dddddd;
        border-radius: 8px;
        padding: 2rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .tarjeta-liquidacion h2 {
        margin-top: 0;
        color: #1a3a5c;
    }

    .tarjeta-liquidacion h3 {
        color: #1a3a5c;
        border-bottom: 2px solid #1a3a5c;
        padding-bottom: 0.5rem;
        margin-top: 2rem;
    }

    .tarjeta-liquidacion h4 {
        color: #1a3a5c;
        margin-top: 1.5rem;
    }

    .encabezado-liquidacion {
        background-color: #f8f9fa;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .bloque-liquidacion {
        margin-bottom: 1.5rem;
    }

    .resultado-final {
        background-color: #e8f5e9;
        border: 2px solid #2e7d32;
        border-radius: 6px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .total-deuda {
        font-size: 1.125rem;
        font-weight: bold;
        margin-top: 1rem;
        padding: 1rem;
        background-color: #ffebee;
        border-radius: 6px;
        border-left: 4px solid #c62828;
    }

    .acciones-liquidacion {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
    }
</style>
