<?php
declare(strict_types=1);

/**
 * Vista gastos/pagar.php — Formulario para pagar un gasto.
 */

?>
<div class="tarjeta-formulario">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['error_gasto'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_gasto']) ?></div>
    <?php endif; ?>

    <div class="resumen-gasto">
        <h3>Gasto</h3>
        <table class="tabla-listado">
            <tr>
                <td><strong>Descripción:</strong></td>
                <td><?= Security::html($gasto['descripcion']) ?></td>
            </tr>
            <tr>
                <td><strong>Importe total:</strong></td>
                <td><?= Security::html(number_format($gasto['importe'], 2, ',', '.')) . ' €' ?></td>
            </tr>
            <tr>
                <td><strong>Ya pagado:</strong></td>
                <td><?= Security::html(number_format($gasto['total_pagado'], 2, ',', '.')) . ' €' ?></td>
            </tr>
            <tr>
                <td><strong>Pendiente:</strong></td>
                <td><?= Security::html(number_format($gasto['saldo_pendiente_pago'], 2, ',', '.')) . ' €' ?></td>
            </tr>
        </table>
    </div>

    <form action="?r=gastos/procesar-pagar" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
        <input type="hidden" name="gasto_id" value="<?= Security::attr($gasto['id']) ?>">

        <div class="campo">
            <label for="importe">Importe a pagar (€)</label>
            <input type="text" id="importe" name="importe" required
                   placeholder="Ejemplo: 90,00">
            <p class="ayuda-campo">Máximo: <?= Security::html(number_format($gasto['saldo_pendiente_pago'], 2, ',', '.')) . ' €' ?></p>
        </div>

        <div class="campo">
            <label for="fecha_pago">Fecha de pago</label>
            <input type="date" id="fecha_pago" name="fecha_pago" required
                   value="<?= date('Y-m-d') ?>">
        </div>

        <div class="campo">
            <label for="medio">Medio de pago</label>
            <select id="medio" name="medio" required>
                <option value="efectivo">Efectivo</option>
                <option value="transferencia">Transferencia</option>
                <option value="bizum">Bizum</option>
                <option value="otro">Otro</option>
            </select>
        </div>

        <div class="campo">
            <label for="referencia">Referencia (opcional)</label>
            <input type="text" id="referencia" name="referencia"
                   placeholder="Ejemplo: transferencia bancaria">
        </div>

        <div class="campo">
            <label for="observaciones">Observaciones (opcional)</label>
            <textarea id="observaciones" name="observaciones" rows="2"></textarea>
        </div>

        <div class="acciones-formulario">
            <button type="submit" class="btn-guardar">Registrar pago</button>
            <a href="?r=gastos/ver&id=<?= $gasto['id'] ?>" class="btn-volver">Volver sin guardar</a>
        </div>
    </form>
</div>
