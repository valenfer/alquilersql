<?php
declare(strict_types=1);

/**
 * Vista gastos/listar.php — Listado de gastos del propietario.
 *
 * Responsabilidad:
 *  - Mostrar tabla de gastos con categoría, descripción, importe, fecha, pagado, pendiente.
 *  - Filtros por categoría y fecha.
 *  - Comunidad e IBI no generan cuotas a inquilinos.
 */

?>
<div class="tarjeta-gastos">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['gasto_creado'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['gasto_creado']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['gasto_pagado'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['gasto_pagado']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['error_gasto'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_gasto']) ?></div>
    <?php endif; ?>

    <div class="acciones-lista">
        <a href="?r=gastos/nuevo" class="btn-guardar">Registrar gasto</a>
        <a href="?r=dashboard" class="btn-volver">Volver al dashboard</a>
    </div>

    <form method="get" action="?r=gastos" class="filtros-busqueda">
        <input type="hidden" name="r" value="gastos">
        <div class="campo-filtro">
            <label for="categoria_id">Categoría</label>
            <select id="categoria_id" name="categoria_id">
                <option value="">Todas</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (($filtros['categoria_id'] ?? null) == $c['id']) ? 'selected' : '' ?>>
                        <?= Security::html($c['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo-filtro">
            <label for="fecha_desde">Desde</label>
            <input type="date" id="fecha_desde" name="fecha_desde" value="<?= Security::attr($filtros['fecha_desde'] ?? '') ?>">
        </div>
        <div class="campo-filtro">
            <label for="fecha_hasta">Hasta</label>
            <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?= Security::attr($filtros['fecha_hasta'] ?? '') ?>">
        </div>
        <button type="submit" class="btn-guardar">Filtrar</button>
        <a href="?r=gastos" class="btn-volver">Limpiar</a>
    </form>

    <table class="tabla-listado">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Categoría</th>
                <th>Descripción</th>
                <th>Importe</th>
                <th>Pagado</th>
                <th>Pendiente</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($gastos)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:2rem;">No se encontraron gastos.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($gastos as $g): ?>
                <tr>
                    <td><?= Security::html(date('d/m/Y', strtotime($g['fecha_gasto']))) ?></td>
                    <td><?= Security::html($g['categoria_nombre']) ?></td>
                    <td><?= Security::html($g['descripcion']) ?></td>
                    <td><?= Security::html(number_format($g['importe'], 2, ',', '.')) . ' €' ?></td>
                    <td><?= Security::html(number_format($g['total_pagado'] ?? 0, 2, ',', '.')) . ' €' ?></td>
                    <td><?= Security::html(number_format($g['saldo_pendiente_pago'] ?? $g['importe'], 2, ',', '.')) . ' €' ?></td>
                    <td>
                        <a href="?r=gastos/ver&id=<?= $g['id'] ?>" class="btn-small">Ver</a>
                        <?php if (($g['saldo_pendiente_pago'] ?? $g['importe']) > 0): ?>
                            <a href="?r=gastos/pagar&id=<?= $g['id'] ?>" class="btn-small">Pagar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="ayuda">
        Comunidad e IBI no generan cuotas a los inquilinos. Los gastos pueden tener pagos parciales.
        Separar la existencia del gasto y su pago.
    </p>
</div>
