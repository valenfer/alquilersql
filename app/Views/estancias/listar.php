<?php
declare(strict_types=1);

/**
 * Vista estancias/listar.php — Listado de estancias (ocupaciones).
 *
 * Responsabilidad:
 *  - Mostrar estancias con inquilino, habitación, fechas de entrada/salida, renta y estado.
 *  - Filtros por habitación, inquilino y estado.
 *  - Botones para crear, editar y finalizar estancias activas.
 */

?>
<div class="tarjeta-estancias">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['estancia_creada'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['estancia_creada']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['estancia_actualizada'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['estancia_actualizada']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['estancia_finalizada'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['estancia_finalizada']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['error_estancia'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_estancia']) ?></div>
    <?php endif; ?>

    <div class="acciones-lista">
        <a href="?r=estancias/nuevo" class="btn-guardar">Nueva estancia</a>
        <a href="?r=dashboard" class="btn-volver">Volver al dashboard</a>
    </div>

    <form method="get" action="?r=estancias" class="filtros-busqueda">
        <input type="hidden" name="r" value="estancias">
        <div class="campo-filtro">
            <label for="habitacion_id">Habitación</label>
            <select id="habitacion_id" name="habitacion_id">
                <option value="">Todas</option>
                <?php foreach ($thisHabitaciones ?? [] as $h): ?>
                    <option value="<?= $h['id'] ?>" <?= (($filtros['habitacion_id'] ?? null) == $h['id']) ? 'selected' : '' ?>><?= Security::html($h['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo-filtro">
            <label for="inquilino_id">Inquilino</label>
            <select id="inquilino_id" name="inquilino_id">
                <option value="">Todos</option>
                <?php foreach ($thisInquilinos ?? [] as $inq): ?>
                    <option value="<?= $inq['id'] ?>" <?= (($filtros['inquilino_id'] ?? null) == $inq['id']) ? 'selected' : '' ?>><?= Security::html($inq['nombre'] . ' ' . $inq['apellidos']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo-filtro">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="">Todos</option>
                <option value="prevista" <?= (($filtros['estado'] ?? '') === 'prevista') ? 'selected' : '' ?>>Prevista</option>
                <option value="activa" <?= (($filtros['estado'] ?? '') === 'activa') ? 'selected' : '' ?>>Activa</option>
                <option value="finalizada" <?= (($filtros['estado'] ?? '') === 'finalizada') ? 'selected' : '' ?>>Finalizada</option>
            </select>
        </div>
        <button type="submit" class="btn-guardar">Filtrar</button>
        <a href="?r=estancias" class="btn-volver">Limpiar</a>
    </form>

    <table class="tabla-listado">
        <thead>
            <tr>
                <th>Inquilino</th>
                <th>Habitación</th>
                <th>Entrada</th>
                <th>Salida prevista</th>
                <th>Salida efectiva</th>
                <th>Renta mensual</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($estancias as $e): ?>
                <tr>
                    <td><?= Security::html($e['inquilino_nombre'] . ' ' . $e['inquilino_apellidos']) ?></td>
                    <td><?= Security::html($e['habitacion_nombre']) ?></td>
                    <td><?= Security::html(date('d/m/Y', strtotime($e['fecha_entrada']))) ?></td>
                    <td><?= $e['fecha_salida_prevista'] ? Security::html(date('d/m/Y', strtotime($e['fecha_salida_prevista']))) : '—' ?></td>
                    <td><?= $e['fecha_salida_efectiva'] ? Security::html(date('d/m/Y', strtotime($e['fecha_salida_efectiva']))) : '—' ?></td>
                    <td><?= Security::html(number_format($e['renta_mensual'], 2, ',', '.')) . ' €' ?></td>
                    <td>
                        <span class="estado estado-<?= Security::html($e['estado']) ?>">
                            <?= Security::html(ucfirst($e['estado'])) ?>
                        </span>
                    </td>
                    <td>
                        <a href="?r=estancias/editar&id=<?= $e['id'] ?>" class="btn-small">Editar</a>
                        <?php if ($e['estado'] === 'activa'): ?>
                            <form action="?r=estancias/finalizar" method="post" style="display:inline;" onsubmit="return confirm('¿Está seguro de que desea finalizar esta estancia? Se mostrará una revisión de deudas, fianza y llaves antes de confirmar.');">
                                <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
                                <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                <input type="hidden" name="fecha_salida_efectiva" value="<?= Security::html(date('d/m/Y')) ?>">
                                <button type="submit" class="btn-small btn-eliminar">Finalizar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="ayuda">
        El intervalo de ocupación es [entrada, salida): el día de entrada cuenta y el día de salida
        efectiva no cuenta. Esto permite que una persona salga y otra entre el mismo día sin solapamiento.
        No se permiten ocupaciones solapadas en la misma habitación ni estancias simultáneas de una misma persona.
    </p>
</div>
