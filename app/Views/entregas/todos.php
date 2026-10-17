<?php
declare(strict_types=1);

/**
 * Vista entregas/todos.php — Registrar entrega a todos los destinatarios.
 *
 * Responsabilidad:
 *  - Mostrar factura y lista de destinatarios pendientes con checkboxes.
 *  - Formulario con fecha y medio comunes para todos.
 */

?>
<div class="tarjeta-entregas">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['error_entrega'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_entrega']) ?></div>
    <?php endif; ?>

    <div class="resumen-factura">
        <h3>Factura</h3>
        <table class="tabla-listado">
            <tr>
                <td><strong>Tipo:</strong></td>
                <td><?= Security::html(ucfirst($factura['tipo'])) ?></td>
            </tr>
            <tr>
                <td><strong>Proveedor:</strong></td>
                <td><?= Security::html($factura['proveedor']) ?></td>
            </tr>
            <tr>
                <td><strong>Importe total:</strong></td>
                <td><?= Security::html(number_format($factura['importe_total'], 2, ',', '.')) . ' €' ?></td>
            </tr>
        </table>
    </div>

    <form action="?r=entregas/crear-todos" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
        <input type="hidden" name="factura_id" value="<?= $factura['id'] ?>">

        <div class="campo">
            <label for="fecha_entrega">Fecha de entrega real</label>
            <input type="date" id="fecha_entrega" name="fecha_entrega" required
                   value="<?= date('Y-m-d') ?>">
        </div>

        <div class="campo">
            <label for="medio">Medio de entrega</label>
            <select id="medio" name="medio" required>
                <option value="en mano">En mano</option>
                <option value="correo">Correo</option>
                <option value="whatsapp">WhatsApp</option>
                <option value="otro">Otro</option>
            </select>
        </div>

        <h3>Destinatarios pendientes</h3>
        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>Seleccionar</th>
                    <th>Habitación</th>
                    <th>Inquilino</th>
                    <th>Cuota acordada</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pendientes)): ?>
                    <tr>
                        <td colspan="4" style="text-align:center; padding:2rem;">No hay destinatarios pendientes.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($pendientes as $p): ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="estancia_ids[]" value="<?= $p['estancia_id'] ?>" checked>
                        </td>
                        <td><?= Security::html($p['habitacion_numero']) ?></td>
                        <td><?= Security::html($p['inquilino_nombre'] . ' ' . $p['inquilino_apellidos']) ?></td>
                        <td><?= Security::html(number_format($p['cuota_importe'], 2, ',', '.')) . ' €' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button type="submit" class="btn-guardar">Registrar entregas seleccionadas</button>
        <a href="?r=entregas/nuevo&factura_id=<?= $factura['id'] ?>" class="btn-volver">Volver sin guardar</a>
    </form>
</div>
