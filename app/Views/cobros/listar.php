<?php
declare(strict_types=1);

use App\Support\Security;

/**
 * Vista cobros/listar.php — Listado de cobros recibidos.
 *
 * Responsabilidad:
 *  - Mostrar tabla de cobros con filtros por inquilino, fecha y medio.
 *  - Cada fila muestra: inquilino, fecha, medio, importe total, referencia.
 *  - Botón para registrar nuevo cobro.
 */

?>
<div class="tarjeta-cobros">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['cobro_registrado'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['cobro_registrado']['valor']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['error_cobro'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_cobro']['valor']) ?></div>
    <?php endif; ?>

    <div class="acciones-lista">
        <a href="?r=cobros/nuevo" class="btn-guardar">Registrar cobro</a>
        <a href="?r=dashboard" class="btn-volver">Volver al dashboard</a>
    </div>

    <form method="get" action="?r=cobros" class="filtros-busqueda">
        <input type="hidden" name="r" value="cobros">
        <div class="campo-filtro">
            <label for="inquilino_id">Inquilino</label>
            <select id="inquilino_id" name="inquilino_id">
                <option value="">Todos</option>
                <?php foreach ($inquilinos as $inq): ?>
                    <option value="<?= $inq['id'] ?>" <?= (($filtros['inquilino_id'] ?? null) == $inq['id']) ? 'selected' : '' ?>>
                        <?= Security::html($inq['nombre'] . ' ' . $inq['apellidos']) ?>
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
        <div class="campo-filtro">
            <label for="medio">Medio</label>
            <select id="medio" name="medio">
                <option value="">Todos</option>
                <?php foreach ($medios as $m): ?>
                    <option value="<?= $m ?>" <?= (($filtros['medio'] ?? '') === $m) ? 'selected' : '' ?>><?= Security::html(ucfirst($m)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-guardar">Filtrar</button>
        <a href="?r=cobros" class="btn-volver">Limpiar</a>
    </form>

    <table class="tabla-listado">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Inquilino</th>
                <th>Medio</th>
                <th>Importe total</th>
                <th>Referencia</th>
                <th>Ver</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($cobros)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding:2rem;">No se encontraron cobros.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($cobros as $c): ?>
                <tr>
                    <td><?= Security::html(date('d/m/Y', strtotime($c['fecha_cobro']))) ?></td>
                    <td><?= Security::html($c['inquilino_nombre'] . ' ' . $c['inquilino_apellidos']) ?></td>
                    <td><?= Security::html(ucfirst($c['medio'])) ?></td>
                    <td><?= Security::html(number_format($c['importe_total'], 2, ',', '.')) . ' €' ?></td>
                    <td><?= $c['referencia'] !== null ? Security::html($c['referencia']) : '—' ?></td>
                    <td>
                        <a href="?r=cobros/ver&id=<?= $c['id'] ?>" class="btn-small">Ver</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="ayuda">
        Cada cobro puede tener varias aplicaciones a distintas mensualidades.
        Haga clic en "Ver" para ver el desglose completo de aplicaciones.
    </p>
</div>
