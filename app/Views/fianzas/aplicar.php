<?php
declare(strict_types=1);

/**
 * Vista fianzas/aplicar.php — Formulario para aplicar fianza a deuda de alquiler.
 *
 * Responsabilidad:
 *  - Mostrar saldo disponible y cargos pendientes de la estancia.
 *  - Advertir que la aplicación es compensación sin movimiento de caja.
 */

?>
<div class="tarjeta-formulario">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['error_fianza'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_fianza']) ?></div>
    <?php endif; ?>

    <div class="alerta alerta-info" role="alert">
        <strong>Nota:</strong> La aplicación de fianza a deuda es una compensación sin movimiento de caja.
        No se crea otro cobro bancario. La deuda se reduce una sola vez.
    </div>

    <div class="resumen-fianza">
        <h3>Fianza</h3>
        <table class="tabla-listado">
            <tr>
                <td><strong>Inquilino:</strong></td>
                <td><?= Security::html($fianza['inquilino_nombre'] . ' ' . $fianza['inquilino_apellidos']) ?></td>
            </tr>
            <tr>
                <td><strong>Habitación:</strong></td>
                <td><?= Security::html($fianza['habitacion_numero']) ?></td>
            </tr>
            <tr>
                <td><strong>Saldo disponible:</strong></td>
                <td><?= Security::html(number_format($fianza['saldo_pendiente_devolver'], 2, ',', '.')) . ' €' ?></td>
            </tr>
        </table>
    </div>

    <h3>Cargos de alquiler pendientes</h3>
    <?php if (empty($cargosPendientes)): ?>
        <div class="alerta alerta-info" role="alert">No hay cargos de alquiler pendientes para esta estancia.</div>
    <?php else: ?>
        <form action="?r=fianzas/procesar-aplicar" method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
            <input type="hidden" name="fianza_id" value="<?= Security::attr($fianza['id']) ?>">

            <table class="tabla-listado">
                <thead>
                    <tr>
                        <th>Año / Mes</th>
                        <th>Saldo pendiente</th>
                        <th>Aplicar (€)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cargosPendientes as $c): ?>
                        <tr>
                            <td><?= $c['anio'] ?> / <?= $c['mes'] ?></td>
                            <td><?= Security::html(number_format($c['saldo_pendiente'], 2, ',', '.')) . ' €' ?></td>
                            <td>
                                <input type="text" name="importe"
                                       placeholder="0,00"
                                       onchange="validarImporte(this, <?= $c['saldo_pendiente'] ?>, <?= $fianza['saldo_pendiente_devolver'] ?>)">
                                <input type="hidden" name="cargo_alquiler_id" value="<?= $c['id'] ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="campo">
                <label for="fecha">Fecha de aplicación</label>
                <input type="date" id="fecha" name="fecha" required
                       value="<?= date('Y-m-d') ?>">
            </div>

            <div class="campo">
                <label for="motivo">Motivo</label>
                <input type="text" id="motivo" name="motivo"
                       placeholder="Ejemplo: Aplicación a deuda de alquiler octubre">
            </div>

            <div class="acciones-formulario">
                <button type="submit" class="btn-guardar">Aplicar fianza a deuda</button>
                <a href="?r=fianzas/ver&id=<?= $fianza['id'] ?>" class="btn-volver">Volver sin guardar</a>
            </div>
        </form>
    <?php endif; ?>

    <script>
        function validarImporte(input, saldoCargo, saldoFianza) {
            const valor = parseFloat(input.value.replace(',', '.')) || 0;
            const max = Math.min(saldoCargo, saldoFianza);
            if (valor > max) {
                input.style.borderColor = '#c62828';
                alert('El importe no puede exceder el saldo disponible (' +
                      max.toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €).');
            } else {
                input.style.borderColor = '';
            }
        }
    </script>
</div>
