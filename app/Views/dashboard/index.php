<?php
declare(strict_types=1);

use App\Support\Security;

/**
 * Vista dashboard/index.php — Pantalla de inicio del dashboard.
 *
 * Responsabilidad:
 *  - Mostrar el mes consultado y botones grandes de acciones principales.
 *  - Mostrar cuatro tarjetas de habitaciones con ocupante, renta, situación y cantidades pendientes.
 *  - Mostrar indicadores con periodo visible.
 *  - Mostrar gráfico sencillo de ingresos y gastos mensuales y tabla equivalente.
 */

?>
<div class="tarjeta-dashboard">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash)): ?>
        <?php foreach ($flash as $msg): ?>
            <div class="alerta alerta-<?= Security::html($msg['tipo']) ?>" role="alert"><?= Security::html($msg['valor']) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="dashboard-header">
        <form method="get" action="?r=dashboard" class="filtro-mes">
            <label for="mes">Mes:</label>
            <select id="mes" name="mes" onchange="this.form.submit()">
                <?php foreach ($meses as $num => $nombre): ?>
                    <option value="<?= $num ?>" <?= ($num == $mes) ? 'selected' : '' ?>><?= Security::html($nombre) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="anio" onchange="this.form.submit()">
                <?php foreach ($anios as $a): ?>
                    <option value="<?= $a ?>" <?= ($a == $anio) ? 'selected' : '' ?>><?= $a ?></option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="r" value="dashboard">
        </form>
        <p class="periodo-visible">
            <strong>Periodo consultado:</strong> <?= Security::html($mes_nombre) ?> <?= $anio ?>
        </p>
    </div>

    <div class="acciones-principales">
        <a href="?r=cobros/nuevo" class="btn-accion btn-accion-cobro">Registrar un cobro</a>
        <a href="?r=facturas/nuevo" class="btn-accion btn-accion-luz">Añadir factura de luz</a>
        <a href="?r=facturas/nuevo" class="btn-accion btn-accion-agua">Añadir factura de agua</a>
        <a href="?r=gastos/nuevo" class="btn-accion btn-accion-gasto">Registrar un gasto</a>
        <a href="?r=inquilinos/nuevo" class="btn-accion btn-accion-inquilino">Dar de alta a un inquilino</a>
    </div>

    <h3>Estado de las habitaciones</h3>
    <div class="tarjetas-habitaciones">
        <?php foreach ($habitaciones as $h): ?>
            <div class="tarjeta-habitacion">
                <h4><?= Security::html($h['nombre']) ?></h4>
                <p><strong>Situación:</strong> <span class="estado estado-<?= Security::html($h['estado']) ?>"><?= Security::html(ucfirst($h['estado'])) ?></span></p>
                <?php if ($h['estancia_id']): ?>
                    <p><strong>Inquilino:</strong> <?= Security::html($h['inquilino_nombre'] . ' ' . $h['inquilino_apellidos']) ?></p>
                    <p><strong>Entrada:</strong> <?= Security::html(date('d/m/Y', strtotime($h['fecha_entrada']))) ?></p>
                    <p><strong>Renta:</strong> <?= Security::html(number_format($h['renta_actual'], 2, ',', '.')) . ' €' ?></p>
                <?php else: ?>
                    <p><strong>Inquilino:</strong> —</p>
                    <p><strong>Renta orientativa:</strong> <?= $h['renta_orientativa'] !== null ? Security::html(number_format($h['renta_orientativa'], 2, ',', '.')) . ' €' : '—' ?></p>
                <?php endif; ?>
                <p><strong>Pendiente alquiler:</strong> <?= Security::html(number_format($h['pendiente_alquiler'], 2, ',', '.')) . ' €' ?></p>
                <p><strong>Pendiente suministros:</strong> <?= Security::html(number_format($h['pendiente_suministros'], 2, ',', '.')) . ' €' ?></p>
                <a href="?r=estancias/editar&id=<?= $h['estancia_id'] ?? 0 ?>" class="btn-small">Ver detalle</a>
            </div>
        <?php endforeach; ?>
    </div>

    <h3>Indicadores del mes</h3>
    <div class="indicadores-grid">
        <div class="indicador">
            <div class="indicador-valor"><?= $indicadores['habitaciones_ocupadas'] ?> / 4</div>
            <div class="indicador-label">Habitaciones ocupadas</div>
        </div>
        <div class="indicador">
            <div class="indicador-valor"><?= $indicadores['habitaciones_libres'] ?></div>
            <div class="indicador-label">Habitaciones libres</div>
        </div>
        <div class="indicador">
            <div class="indicador-valor"><?= Security::html(number_format($indicadores['alquiler_esperado'], 2, ',', '.')) . ' €' ?></div>
            <div class="indicador-label">Alquiler esperado</div>
        </div>
        <div class="indicador">
            <div class="indicador-valor"><?= Security::html(number_format($indicadores['alquiler_cobrado'], 2, ',', '.')) . ' €' ?></div>
            <div class="indicador-label">Alquiler cobrado</div>
        </div>
        <div class="indicador">
            <div class="indicador-valor"><?= Security::html(number_format($indicadores['pendiente_alquiler'], 2, ',', '.')) . ' €' ?></div>
            <div class="indicador-label">Pendiente alquiler</div>
        </div>
        <div class="indicador">
            <div class="indicador-valor"><?= Security::html(number_format($indicadores['pendiente_luz'] + $indicadores['pendiente_agua'], 2, ',', '.')) . ' €' ?></div>
            <div class="indicador-label">Pendiente luz y agua</div>
        </div>
        <div class="indicador">
            <div class="indicador-valor"><?= Security::html(number_format($indicadores['deuda_vencida'], 2, ',', '.')) . ' €' ?></div>
            <div class="indicador-label">Deuda vencida</div>
        </div>
        <div class="indicador">
            <div class="indicador-valor"><?= $indicadores['personas_con_deuda'] ?></div>
            <div class="indicador-label">Personas con deuda</div>
        </div>
        <div class="indicador">
            <div class="indicador-valor"><?= $indicadores['facturas_sin_cargo'] ?></div>
            <div class="indicador-label">Facturas sin cargo bancario</div>
        </div>
        <div class="indicador">
            <div class="indicador-valor"><?= $indicadores['facturas_sin_entregar'] ?></div>
            <div class="indicador-label">Facturas sin entregar</div>
        </div>
        <div class="indicador">
            <div class="indicador-valor"><?= Security::html(number_format($indicadores['gastos_pagados'], 2, ',', '.')) . ' €' ?></div>
            <div class="indicador-label">Gastos pagados</div>
        </div>
        <div class="indicador">
            <div class="indicador-valor"><?= Security::html(number_format($indicadores['fianzas_mantenidas'], 2, ',', '.')) . ' €' ?></div>
            <div class="indicador-label">Fianzas mantenidas</div>
        </div>
    <div class="indicador">
        <div class="indicador-valor"><?= $indicadores['llaves_pendientes'] ?></div>
        <div class="indicador-label">Llaves pendientes</div>
    </div>
</div>

<h3>📋 Alertas de contratos</h3>
<div class="alertas-contratos">
    <?php
    $alertas = $alertas_contratos ?? [];
    $tieneAlertas = false;
    
    // Contar total de alertas
    foreach ($alertas as $tipo => $lista) {
        if (!empty($lista)) {
            $tieneAlertas = true;
        }
    }
    ?>
    
    <?php if (!$tieneAlertas): ?>
        <div class="alerta alerta-exito" role="alert">No hay contratos por vencer ni vencidos.</div>
    <?php else: ?>
        <?php if (!empty($alertas['15_dias'])): ?>
            <div class="alerta alerta-error" role="alert">
                <strong>🚨 Contratos que vencen en 15 días o menos:</strong>
                <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                    <?php foreach ($alertas['15_dias'] as $c): ?>
                        <li><?= Security::html($c['inquilino_nombre'] . ' ' . $c['inquilino_apellidos']) ?> - Habitación <?= Security::html($c['habitacion_numero']) ?> - Vence <?= Security::html(date('d/m/Y', strtotime($c['fecha_fin']))) ?> (<?= (int) $c['dias_restantes'] ?> días)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($alertas['30_dias'])): ?>
            <div class="alerta alerta-advertencia" role="alert">
                <strong>⚠️ Contratos que vencen en 30 días:</strong>
                <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                    <?php foreach ($alertas['30_dias'] as $c): ?>
                        <li><?= Security::html($c['inquilino_nombre'] . ' ' . $c['inquilino_apellidos']) ?> - Habitación <?= Security::html($c['habitacion_numero']) ?> - Vence <?= Security::html(date('d/m/Y', strtotime($c['fecha_fin']))) ?> (<?= (int) $c['dias_restantes'] ?> días)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($alertas['vencidos'])): ?>
            <div class="alerta alerta-error" role="alert">
                <strong>❌ Contratos ya vencidos:</strong>
                <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                    <?php foreach ($alertas['vencidos'] as $c): ?>
                        <li><?= Security::html($c['inquilino_nombre'] . ' ' . $c['inquilino_apellidos']) ?> - Habitación <?= Security::html($c['habitacion_numero']) ?> - Venció <?= Security::html(date('d/m/Y', strtotime($c['fecha_fin']))) ?> (<?= (int) $c['dias_vencido'] ?> días atrás)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 1rem;">
            <a href="?r=contratos/alertas" class="btn-guardar">Ver todas las alertas de contratos</a>
        </div>
    <?php endif; ?>
</div>

<h3>Ingresos y gastos mensuales (<?= $anio ?>)</h3>
    <div class="grafico-tabla">
        <div class="grafico-container">
            <svg id="grafico-barras" width="100%" height="300" role="img" aria-label="Gráfico de ingresos y gastos mensuales">
                <defs>
                    <style>
                        .bar-ingreso { fill: #2e7d32; }
                        .bar-gasto { fill: #c62828; }
                        .axis text { font-size: 12px; fill: #333; }
                        .axis line { stroke: #ccc; }
                    </style>
                </defs>
                <g class="axis">
                    <?php $maxValor = max(array_merge($grafico['ingresos'], $grafico['gastos'], [1])); ?>
                    <?php $anchoBarra = 35; $espacio = 15; $inicioX = 50; $inicioY = 250; $altoMax = 200; ?>
                    <?php foreach ($grafico['meses'] as $i => $mes): ?>
                        <?php $x = $inicioX + $i * ($anchoBarra + $espacio); ?>
                        <?php $hIngreso = $grafico['ingresos'][$i] / $maxValor * $altoMax; ?>
                        <?php $hGasto = $grafico['gastos'][$i] / $maxValor * $altoMax; ?>
                        <rect x="<?= $x ?>" y="<?= $inicioY - $hIngreso ?>" width="<?= $anchoBarra * 0.45 ?>" height="<?= $hIngreso ?>" class="bar-ingreso" />
                        <rect x="<?= $x + $anchoBarra * 0.55 ?>" y="<?= $inicioY - $hGasto ?>" width="<?= $anchoBarra * 0.45 ?>" height="<?= $hGasto ?>" class="bar-gasto" />
                        <text x="<?= $x + $anchoBarra / 2 ?>" y="<?= $inicioY + 20 ?>" text-anchor="middle"><?= Security::html($mes) ?></text>
                    <?php endforeach; ?>
                    <text x="10" y="20" font-weight="bold">€</text>
                </g>
            </svg>
            <p class="leyenda-grafico">■ Ingresos ■ Gastos</p>
        </div>
        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>Mes</th>
                    <th>Ingresos</th>
                    <th>Gastos</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grafico['meses'] as $i => $mes): ?>
                    <tr>
                        <td><?= Security::html($mes) ?></td>
                        <td><?= Security::html(number_format($grafico['ingresos'][$i], 2, ',', '.')) . ' €' ?></td>
                        <td><?= Security::html(number_format($grafico['gastos'][$i], 2, ',', '.')) . ' €' ?></td>
                        <td><?= Security::html(number_format($grafico['ingresos'][$i] - $grafico['gastos'][$i], 2, ',', '.')) . ' €' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p class="ayuda">
        Los indicadores muestran el periodo seleccionado. Cada tarjeta de habitación permite abrir
        el listado que explica su importe. Los ingresos y gastos se calculan según la fecha real
        de cada movimiento, excluyendo las fianzas.
    </p>
</div>

<style>
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .filtro-mes {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .filtro-mes select {
        padding: 0.5rem;
        font-size: 1rem;
        border: 2px solid #cccccc;
        border-radius: 6px;
    }

    .periodo-visible {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1a3a5c;
    }

    .acciones-principales {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .btn-accion {
        padding: 1rem 2rem;
        font-size: 1.125rem;
        font-weight: 600;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        border: none;
        min-width: 200px;
        text-align: center;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-accion-cobro { background-color: #1a3a5c; color: #ffffff; }
    .btn-accion-cobro:hover { background-color: #0d2a4a; }

    .btn-accion-luz { background-color: #e67e22; color: #ffffff; }
    .btn-accion-luz:hover { background-color: #d35400; }

    .btn-accion-agua { background-color: #2980b9; color: #ffffff; }
    .btn-accion-agua:hover { background-color: #21618c; }

    .btn-accion-gasto { background-color: #c0392b; color: #ffffff; }
    .btn-accion-gasto:hover { background-color: #a93226; }

    .btn-accion-inquilino { background-color: #8e44ad; color: #ffffff; }
    .btn-accion-inquilino:hover { background-color: #7d3c98; }

    .btn-accion:focus-visible {
        outline: 3px solid #1a3a5c;
        outline-offset: 2px;
    }

    .tarjetas-habitaciones {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .tarjeta-habitacion {
        flex: 1 1 220px;
        max-width: 260px;
        background-color: #ffffff;
        border: 2px solid #dddddd;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .tarjeta-habitacion h4 {
        margin-top: 0;
        color: #1a3a5c;
        border-bottom: 2px solid #1a3a5c;
        padding-bottom: 0.5rem;
    }

    .tarjeta-habitacion p {
        margin: 0.5rem 0;
        font-size: 1rem;
    }

    .indicadores-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .indicador {
        background-color: #ffffff;
        border: 2px solid #dddddd;
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .indicador-valor {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1a3a5c;
    }

    .indicador-label {
        font-size: 0.875rem;
        color: #666666;
        margin-top: 0.25rem;
    }

    .grafico-tabla {
        display: flex;
        flex-wrap: wrap;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .grafico-container {
        flex: 1 1 400px;
        max-width: 600px;
    }

    .leyenda-grafico {
        margin-top: 0.5rem;
        font-size: 0.875rem;
        color: #666666;
    }
</style>
