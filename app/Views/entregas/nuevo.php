<?php
declare(strict_types=1);

/**
 * Vista entregas/nuevo.php — Registrar entrega de factura a destinatarios.
 *
 * Responsabilidad:
 *  - Mostrar factura, historial de entregas, destinatarios pendientes.
 *  - Formulario para entregar a un destinatario individual.
 *  - Botón para entregar a todos los destinatarios pendientes.
 */

?>
<div class="tarjeta-entregas">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['entrega_registrada'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['entrega_registrada']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['entregas_registradas'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['entregas_registradas']) ?></div>
    <?php endif; ?>
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
                <td><strong>Número:</strong></td>
                <td><?= $factura['numero_factura'] !== null ? Security::html($factura['numero_factura']) : '—' ?></td>
            </tr>
            <tr>
                <td><strong>Importe total:</strong></td>
                <td><?= Security::html(number_format($factura['importe_total'], 2, ',', '.')) . ' €' ?></td>
            </tr>
        </table>
    </div>

    <h3>Historial de entregas por destinatario</h3>
    <?php if (empty($entregas)): ?>
        <div class="alerta alerta-info" role="alert">No hay entregas registradas todavía.</div>
    <?php else: ?>
        <table class="tabla-listado">
            <thead>
                <tr>
                    <th>Habitación</th>
                    <th>Inquilino</th>
                    <th>Fecha de entrega</th>
                    <th>Medio</th>
                    <th>Recepción confirmada</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entregas as $e): ?>
                    <tr>
                        <td><?= Security::html($e['habitacion_numero']) ?></td>
                        <td><?= Security::html($e['inquilino_nombre'] . ' ' . $e['inquilino_apellidos']) ?></td>
                        <td><?= Security::html(date('d/m/Y', strtotime($e['fecha_entrega']))) ?></td>
                        <td><?= Security::html(ucfirst($e['medio'])) ?></td>
                        <td><?= $e['confirmacion_recepcion'] ? 'Sí' : 'No' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h3>Registrar nueva entrega</h3>
    <form action="?r=entregas/crear" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
        <input type="hidden" name="factura_id" value="<?= $factura['id'] ?>">

        <div class="campo">
            <label for="estancia_id">Destinatario (inquilino y estancia)</label>
            <select id="estancia_id" name="estancia_id" required>
                <option value="">Seleccione un destinatario</option>
                <?php foreach ($pendientes as $p): ?>
                    <option value="<?= $p['estancia_id'] ?>">
                        Habitación <?= Security::html($p['habitacion_numero']) ?> —
                        <?= Security::html($p['inquilino_nombre'] . ' ' . $p['inquilino_apellidos']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

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
            <p class="ayuda-campo">Anotación manual. Registrar una entrega no significa enviar realmente un mensaje.</p>
        </div>

        <div class="campo">
            <label for="confirmacion_recepcion">¿El destinatario confirmó recepción?</label>
            <select id="confirmacion_recepcion" name="confirmacion_recepcion">
                <option value="0">No / Pendiente</option>
                <option value="1">Sí, confirmó recepción</option>
            </select>
        </div>

        <div class="campo">
            <label for="observaciones">Observaciones (opcional)</label>
            <textarea id="observaciones" name="observaciones" rows="2"></textarea>
        </div>

        <button type="submit" class="btn-guardar">Registrar entrega</button>
        <a href="?r=facturas/ver&id=<?= $factura['id'] ?>" class="btn-volver">Volver a la factura</a>
    </form>

    <?php if (!empty($pendientes)): ?>
        <h3>Entregar a todos los destinatarios pendientes</h3>
        <p class="ayuda">
            Crea un registro individual para cada destinatario seleccionado con la misma fecha y medio.
            Esta acción solo registra, no envía nada.
        </p>
        <a href="?r=entregas/todos&factura_id=<?= $factura['id'] ?>" class="btn-guardar">Registrar entrega a todos los destinatarios</a>
    <?php endif; ?>

    <a href="?r=facturas/ver&id=<?= $factura['id'] ?>" class="btn-volver">Volver a la factura</a>
</div>
