<?php
declare(strict_types=1);

use App\Support\Security;

/**
 * Vista inquilinos/listar.php — Listado de inquilinos.
 *
 * Responsabilidad:
 *  - Mostrar inquilinos con documento normalizado (no completo en listado general),
 *    nombre, apellidos, teléfono, email y estado.
 *  - Filtro por estado (activo/archivado/todos).
 *  - Botones para crear, editar y archivar.
 */

?>
<div class="tarjeta-inquilinos">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['inquilino_creado'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['inquilino_creado']['valor']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['inquilino_actualizado'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['inquilino_actualizado']['valor']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['inquilino_archivado'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['inquilino_archivado']['valor']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['error_inquilino'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_inquilino']['valor']) ?></div>
    <?php endif; ?>

    <div class="acciones-lista">
        <a href="?r=inquilinos/nuevo" class="btn-guardar">Nuevo inquilino</a>
        <a href="?r=dashboard" class="btn-volver">Volver al dashboard</a>
    </div>

    <div class="filtros-estado">
        <form method="get" action="?r=inquilinos" style="display:inline;">
            <label for="estado">Filtrar por estado:</label>
            <select id="estado" name="estado" onchange="this.form.submit()">
                <option value="" <?= ($estado_actual ?? '') === '' ? 'selected' : '' ?>>Todos</option>
                <option value="activo" <?= ($estado_actual ?? '') === 'activo' ? 'selected' : '' ?>>Activos</option>
                <option value="archivado" <?= ($estado_actual ?? '') === 'archivado' ? 'selected' : '' ?>>Archivados</option>
            </select>
            <input type="hidden" name="r" value="inquilinos">
        </form>
    </div>

    <table class="tabla-listado">
        <thead>
            <tr>
                <th>Documento</th>
                <th>Nombre y apellidos</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($inquilinos as $i): ?>
                <tr>
                    <td>
                        <?php if ($i['tipo_documento'] !== 'Otro'): ?>
                            <?= Security::html(substr($i['numero_documento'], 0, -3)) ?>· · <?= Security::html(substr($i['numero_documento'], -3)) ?>
                        <?php else: ?>
                            <?= Security::html($i['numero_documento']) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= Security::html($i['nombre'] . ' ' . $i['apellidos']) ?></td>
                    <td><?= $i['telefono'] !== null ? Security::html($i['telefono']) : '—' ?></td>
                    <td><?= $i['email'] !== null ? Security::html($i['email']) : '—' ?></td>
                    <td>
                        <span class="estado estado-<?= Security::html($i['estado']) ?>">
                            <?= Security::html(ucfirst($i['estado'])) ?>
                        </span>
                    </td>
                    <td>
                        <a href="?r=inquilinos/editar&id=<?= $i['id'] ?>" class="btn-small">Editar</a>
                        <?php if ($i['estado'] === 'activo'): ?>
                            <form action="?r=inquilinos/archivar" method="post" style="display:inline;" onsubmit="return confirm('¿Está seguro de que desea archivar este inquilino? Su historial se conservará.');">
                                <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
                                <input type="hidden" name="id" value="<?= $i['id'] ?>">
                                <button type="submit" class="btn-small btn-eliminar">Archivar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="ayuda">
        El número de documento se muestra parcialmente en el listado general para proteger
        los datos del inquilino. Para ver el documento completo, acceda a la ficha del inquilino.
        Archivar no elimina el historial de estancias, cobros ni fianzas.
    </p>
</div>
