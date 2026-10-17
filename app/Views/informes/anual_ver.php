<?php
declare(strict_types=1);

/**
 * Vista informes/anual_ver.php — Informe anual generado.
 *
 * Responsabilidad:
 *  - Mostrar los tres bloques del informe anual: caja, cargos/deudas, fianzas.
 *  - Mostrar estadísticas adicionales.
 */

?>
<div class="tarjeta-informe-anual">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash)): ?>
        <?php foreach ($flash as $msg): ?>
            <div class="alerta alerta-<?= Security::html($msg['tipo']) ?>" role="alert"><?= Security::html($msg['valor']) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="acciones-informe">
        <a href="?r=informes/anual" class="btn-volver">Seleccionar otro año</a>
        <a href="?r=dashboard" class="btn-volver">Volver al dashboard</a>
    </div>

    <?php $b = $informe['bloque_caja']; $c = $informe['bloque_cargos_deudas']; $f = $informe['bloque_fianzas']; $e = $informe['estadisticas']; ?>

    <!-- Bloque 1: Caja -->
    <h3>1. Dinero realmente cobrado y pagado (caja)</h3>
    <div class="bloque-informe">
        <table class="tabla-listado">
            <thead>
                <tr><th>Concepto</th><th>Importe</th></tr>
            </thead>
            <tbody>
                <tr><td>Cobros de alquiler en dinero</td><td><?= Security::html(number_format($b['cobros_alquiler'], 2, ',', '.')) . ' €' ?></td></tr>
                <tr><td>Cobros de suministros (luz y agua)</td><td><?= Security::html(number_format($b['cobros_suministros'], 2, ',', '.')) . ' €' ?></td></tr>
                <tr><td>Devoluciones reales de cobros</td><td><?= Security::html(number_format($b['devoluciones_cobros'], 2, ',', '.')) . ' €' ?></td></tr>
                <tr><td><strong>Cobros ordinarios netos</strong></td><td><?= Security::html(number_format($b['cobros_ordinarios_netos'], 2, ',', '.')) . ' €' ?></td></tr>
                <tr><td>Pagos a compañías de luz y agua</td><td><?= Security::html(number_format($b['pagos_luz_agua'], 2, ',', '.')) . ' €' ?></td></tr>
                <tr><td>Pagos de comunidad e IBI</td><td><?= Security::html(number_format($b['pagos_comunidad_ibi'], 2, ',', '.')) . ' €' ?></td></tr>
                <tr><td>Pagos de reparaciones y demás gastos</td><td><?= Security::html(number_format($b['pagos_otros_gastos'], 2, ',', '.')) . ' €' ?></td></tr>
                <tr><td><strong>Pagos de gastos y suministros</strong></td><td><?= Security::html(number_format($b['pagos_gastos_suministros'], 2, ',', '.')) . ' €' ?></td></tr>
                <tr style="font-weight:bold; background:#f8f9fa;"><td>Saldo de caja de la actividad</td><td><?= Security::html(number_format($b['saldo_caja_actividad'], 2, ',', '.')) . ' €' ?></td></tr>
            </tbody>
        </table>
        <p class="nota-informe"><?= Security::html($b['nota']) ?></p>
    </div>

    <!-- Bloque 2: Cargos y deudas -->
    <h3>2. Cargos, deudas y situación a fecha de cierre</h3>
    <div class="bloque-informe">
        <table class="tabla-listado">
            <thead>
                <tr><th>Concepto</th><th>Importe</th></tr>
            </thead>
            <tbody>
                <tr><td>Alquileres correspondientes al año</td><td><?= Security::html(number_format($c['alquileres_total'], 2, ',', '.')) . ' €' ?></td></tr>
                <tr><td>Alquileres cobrados</td><td><?= Security::html(number_format($c['alquileres_cobrado'], 2, ',', '.')) . ' €' ?></td></tr>
                <tr><td>Alquileres pendientes</td><td><?= Security::html(number_format($c['alquileres_pendiente'], 2, ',', '.')) . ' €' ?></td></tr>
                <tr><td>Deuda de alquiler a fecha de cierre</td><td><?= Security::html(number_format($c['deuda_alquiler_cierre'], 2, ',', '.')) . ' €' ?></td></tr>
                <tr><td>Cuotas de suministros (total)</td><td><?= Security::html(number_format($c['suministros_total'], 2, ',', '.')) . ' €' ?></td></tr>
                <tr><td>Cuotas de suministros cobradas</td><td><?= Security::html(number_format($c['suministros_cobrado'], 2, ',', '.')) . ' €' ?></td></tr>
                <tr><td>Cuotas de suministros pendientes</td><td><?= Security::html(number_format($c['suministros_pendiente'], 2, ',', '.')) . ' €' ?></td></tr>
                <tr><td>Gastos pendientes de pagar</td><td><?= Security::html(number_format($c['gastos_pendientes_pagar'], 2, ',', '.')) . ' €' ?></td></tr>
            </tbody>
        </table>
        <p class="nota-informe"><?= Security::html($c['nota']) ?></p>
    </div>

    <!-- Bloque 3: Fianzas -->
    <h3>3. Fianzas y compensaciones sin caja</h3>
    <div class="bloque-informe">
        <table class="tabla-listado">
            <thead>
                <tr><th>Concepto</th><th>Importe</th></tr>
            </thead>
            <tbody>
                <tr><td>Saldo de fianzas al comienzo</td><td><?= Security::html(number_format($f['saldo_inicio'], 2, ',', '.')) . ' €' ?></td></tr>
                <tr><td>Fianzas recibidas en dinero</td><td><?= Security::html(number_format($f['recibidas'], 2, ',', '.')) . ' €' ?></td></tr>
                <tr><td>Fianzas devueltas en dinero</td><td><?= Security::html(number_format($f['devueltas'], 2, ',', '.')) . ' €' ?></td></tr>
                <tr><td>Aplicaciones a deudas existentes</td><td><?= Security::html(number_format($f['aplicadas_deuda'], 2, ',', '.')) . ' €' ?></td></tr>
                <tr><td>Compensaciones por daños</td><td><?= Security::html(number_format($f['compensaciones'], 2, ',', '.')) . ' €' ?></td></tr>
                <tr style="font-weight:bold; background:#f8f9fa;"><td>Saldo de fianzas al final</td><td><?= Security::html(number_format($f['saldo_final'], 2, ',', '.')) . ' €' ?></td></tr>
                <tr><td>Flujo de tesorería por fianzas</td><td><?= Security::html(number_format($f['flujo_tesorería_fianzas'], 2, ',', '.')) . ' €' ?></td></tr>
            </tbody>
        </table>
        <p class="nota-informe"><?= Security::html($f['nota']) ?></p>
    </div>

    <!-- Estadísticas -->
    <h3>Estadísticas adicionales</h3>
    <div class="bloque-informe">
        <table class="tabla-listado">
            <thead>
                <tr><th>Indicador</th><th>Valor</th></tr>
            </thead>
            <tbody>
                <tr><td>Ocupación por días</td><td><?= Security::html(number_format($e['ocupacion_porcentaje'], 1)) . '%' ?></td></tr>
                <tr><td>Días-habitación ocupados</td><td><?= $e['dias_ocupados'] ?> de <?= $e['total_dias_habitacion'] ?></td></tr>
            </tbody>
        </table>

        <h4>Ingresos de alquiler por habitación</h4>
        <table class="tabla-listado">
            <thead>
                <tr><th>Habitación</th><th>Importe</th></tr>
            </thead>
            <tbody>
                <?php foreach ($e['ingresos_por_habitacion'] as $ih): ?>
                    <tr>
                        <td><?= Security::html($ih['habitacion']) ?></td>
                        <td><?= Security::html(number_format($ih['total'], 2, ',', '.')) . ' €' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h4>Gastos agrupados por concepto</h4>
        <table class="tabla-listado">
            <thead>
                <tr><th>Concepto</th><th>Importe</th></tr>
            </thead>
            <tbody>
                <?php foreach ($e['gastos_por_concepto'] as $gc): ?>
                    <tr>
                        <td><?= Security::html($gc['concepto']) ?></td>
                        <td><?= Security::html(number_format($gc['total'], 2, ',', '.')) . ' €' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h4>Evolución mensual de cobros y pagos</h4>
        <table class="tabla-listado">
            <thead>
                <tr><th>Mes</th><th>Cobros</th><th>Pagos</th><th>Saldo</th></tr>
            </thead>
            <tbody>
                <?php foreach ($e['evolucion_mensual'] as $em): ?>
                    <tr>
                        <td><?= Security::html($em['mes']) ?></td>
                        <td><?= Security::html(number_format($em['cobros'], 2, ',', '.')) . ' €' ?></td>
                        <td><?= Security::html(number_format($em['pagos'], 2, ',', '.')) . ' €' ?></td>
                        <td><?= Security::html(number_format($em['saldo'], 2, ',', '.')) . ' €' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="nota-informe"><?= Security::html($e['nota_ocupacion']) ?></p>
        <p class="nota-informe"><?= Security::html($e['nota_fiscal']) ?></p>
    </div>
</div>

<style>
    .tarjeta-informe-anual {
        max-width: 1200px;
        margin: 2rem auto;
        background-color: #ffffff;
        border: 1px solid #dddddd;
        border-radius: 8px;
        padding: 2rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .tarjeta-informe-anual h2 {
        margin-top: 0;
        color: #1a3a5c;
    }

    .tarjeta-informe-anual h3 {
        color: #1a3a5c;
        border-bottom: 2px solid #1a3a5c;
        padding-bottom: 0.5rem;
        margin-top: 2rem;
    }

    .tarjeta-informe-anual h4 {
        color: #1a3a5c;
        margin-top: 1.5rem;
    }

    .bloque-informe {
        background-color: #f8f9fa;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .nota-informe {
        font-size: 0.875rem;
        color: #666666;
        font-style: italic;
        margin-top: 0.5rem;
    }

    .acciones-informe {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
</style>
