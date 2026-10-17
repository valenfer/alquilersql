<?php
declare(strict_types=1);

/**
 * Vista incidencias/listar.php — Listado de incidencias.
 *
 * Responsabilidad:
 *  - Mostrar tabla de incidencias con habitación, descripción, fecha de aviso, estado.
 *  - Filtros por estado y habitación.
 */

?>
<div class="tarjeta-incidencias">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['incidencia_creada'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['incidencia_creada']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['incidencia_actualizada'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['incidencia_actualizada']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['error_incidencia'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_incidencia']) ?></div>
    <?php endif; ?>

    <div class="acciones-lista">
        <a href="?r=incidencias/nuevo" class="btn-guardar">Nueva incidencia</a>
        <a href="?r=dashboard" class="btn-volver">Volver al dashboard</a>
    </div>

    <form method="get" action="?r=incidencias" class="filtros-busqueda">
        <input type="hidden" name="r" value="incidencias">
        <div class="campo-filtro">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="">Todos</option>
                <option value="pendiente" <?= (($filtros['estado'] ?? '') === 'pendiente') ? 'selected' : '' ?>>Pendiente</option>
                <option value="en_curso" <?= (($filtros['estado'] ?? '') === 'en_curso') ? 'selected' : '' ?>>En curso</option>
                <option value="resuelta" <?= (($filtros['estado'] ?? '') === 'resuelta') ? 'selected' : '' ?>>Resuelta</option>
            </select>
        </div>
        <div class="campo-filtro">
            <label for="habitacion_id">Habitación</label>
            <select id="habitacion_id" name="habitacion_id">
                <option value="">Todas</option>
                <?php foreach ($habitaciones as $h): ?>
                    <option value="<?= $h['id'] ?>" <?= (($filtros['habitacion_id'] ?? null) == $h['id']) ? 'selected' : '' ?>>
                        <?= Security::html($h['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-guardar">Filtrar</button>
        <a href="?r=incidencias" class="btn-volver">Limpiar</a>
    </form>

    <table class="tabla-listado">
        <thead>
            <tr>
                <th>Fecha de aviso</th>
                <th>Habitación</th>
                <th>Descripción</th>
                <th>Estado</th>
                <th>Fecha resolución</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($incidencias)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding:2rem;">No se encontraron incidencias.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($incidencias as $i): ?>
                <tr>
                    <td><?= Security::html(date('d/m/Y', strtotime($i['fecha_aviso']))) ?></td>
                    <td><?= $i['habitacion_numero'] !== null ? Security::html($i['habitacion_numero']) : '—' ?></td>
                    <td><?= Security::html($i['descripcion']) ?></td>
                    <td>
                        <span class="estado estado-<?= Security::html($i['estado']) ?>">
                            <?= Security::html(ucfirst(str_replace('_', ' ', $i['estado']))) ?>
                        </span>
                    </td>
                    <td><?= $i['fecha_resolucion'] !== null ? Security::html(date('d/m/Y', strtotime($i['fecha_resolucion']))) : '—' ?></td>
                    <td>
                        <a href="?r=incidencias/estado&id=<?= $i['id'] ?>" class="btn-small">Cambiar estado</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="ayuda">
        Una incidencia por sí sola no es un gasto hasta que existe un importe registrado.
        Los gastos relacionados se registran en el módulo de Gastos.
    </p>
</div>
