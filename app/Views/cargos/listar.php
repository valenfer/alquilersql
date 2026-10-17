<?php
declare(strict_types=1);

/**
 * Vista cargos/listar.php — Listado de mensualidades y cargos de alquiler.
 *
 * Responsabilidad:
 *  - Mostrar tabla de cargos con filtros por año, mes, estado e inquilino.
 *  - Cada fila muestra: inquilino, habitación, año/mes, periodo, importe esperado,
 *    importe cobrado, saldo pendiente, estado y vencimiento.
 *  - Botón para preparar nuevas mensualidades.
 */

?>
<div class="tarjeta-cargos">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['cargos_creados'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['cargos_creados']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['error_cargo'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_cargo']) ?></div>
    <?php endif; ?>

    <div class="acciones-lista">
        <a href="?r=cargos/preparar" class="btn-guardar">Preparar mensualidades</a>
        <a href="?r=dashboard" class="btn-volver">Volver al dashboard</a>
    </div>

    <form method="get" action="?r=cargos" class="filtros-busqueda">
        <input type="hidden" name="r" value="cargos">
        <div class="campo-filtro">
            <label for="anio">Año</label>
            <select id="anio" name="anio">
                <option value="">Todos</option>
                <?php foreach ($anios as $a): ?>
                    <option value="<?= $a ?>" <?= (($filtros['anio'] ?? null) == $a) ? 'selected' : '' ?>><?= $a ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo-filtro">
            <label for="mes">Mes</label>
            <select id="mes" name="mes">
                <option value="">Todos</option>
                <?php foreach ($meses as $num => $nombre): ?>
                    <option value="<?= $num ?>" <?= (($filtros['mes'] ?? null) == $num) ? 'selected' : '' ?>><?= Security::html($nombre) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo-filtro">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="">Todos</option>
                <option value="pendiente" <?= (($filtros['estado'] ?? '') === 'pendiente') ? 'selected' : '' ?>>Pendiente</option>
                <option value="parcial" <?= (($filtros['estado'] ?? '') === 'parcial') ? 'selected' : '' ?>>Parcial</option>
                <option value="pagado" <?= (($filtros['estado'] ?? '') === 'pagado') ? 'selected' : '' ?>>Pagado</option>
            </select>
        </div>
        <button type="submit" class="btn-guardar">Filtrar</button>
        <a href="?r=cargos" class="btn-volver">Limpiar</a>
    </form>

    <table class="tabla-listado">
        <thead>
            <tr>
                <th>Inquilino</th>
                <th>Habitación</th>
                <th>Año / Mes</th>
                <th>Periodo</th>
                <th>Importe esperado</th>
                <th>Cobrado</th>
                <th>Pendiente</th>
                <th>Estado</th>
                <th>Vencimiento</th>
                <th>Ver</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($cargos)): ?>
                <tr>
                    <td colspan="10" style="text-align:center; padding:2rem;">No se encontraron cargos.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($cargos as $c): ?>
                <tr>
                    <td><?= Security::html($c['inquilino_nombre'] . ' ' . $c['inquilino_apellidos']) ?></td>
                    <td><?= Security::html($c['habitacion_numero']) ?></td>
                    <td><?= $c['anio'] ?> / <?= $c['mes'] ?></td>
                    <td><?= Security::html(date('d/m/Y', strtotime($c['periodo_desde']))) . ' — ' . date('d/m/Y', strtotime($c['periodo_hasta'])) ?></td>
                    <td><?= Security::html(number_format($c['importe_esperado'], 2, ',', '.')) . ' €' ?></td>
                    <td><?= Security::html(number_format($c['importe_cobrado'], 2, ',', '.')) . ' €' ?></td>
                    <td>
                        <?php
                        $saldo = round($c['importe_esperado'] - $c['importe_cobrado'], 2);
                        echo Security::html(number_format($saldo, 2, ',', '.')) . ' €';
                        ?>
                    </td>
                    <td>
                        <span class="estado estado-<?= Security::html($c['estado']) ?>">
                            <?= Security::html(ucfirst($c['estado'])) ?>
                        </span>
                        <?php if ($c['estado'] === 'pendiente' || $c['estado'] === 'parcial'): ?>
                            <?php
                            $hoy = date('Y-m-d');
                            $vencido = $c['fecha_vencimiento'] < $hoy && $saldo > 0;
                            if ($vencido): ?>
                                <br><span class="estado estado-vencido">Vencido</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td><?= Security::html(date('d/m/Y', strtotime($c['fecha_vencimiento']))) ?></td>
                    <td>
                        <a href="?r=cargos/ver&id=<?= $c['id'] ?>" class="btn-small">Ver</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="ayuda">
        Los estados se calculan desde los cobros y aplicaciones registrados.
        "Vencido" es una condición adicional cuando ha pasado el vencimiento y queda saldo pendiente.
        La generación es idempotente: no se crea dos veces la misma mensualidad para una estancia.
    </p>
</div>
