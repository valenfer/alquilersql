<?php
declare(strict_types=1);

/**
 * Vista cargos/preview.php — Vista previa de cargos de alquiler a generar.
 *
 * Responsabilidad:
 *  - Mostrar lista de cargos que se crearán con importe, periodo y días ocupados.
 *  - Mostrar total esperado.
 *  - Botón para confirmar y guardar o volver atrás.
 */

?>
<div class="tarjeta-cargos">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($errores)): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html(implode(' ', $errores)) ?></div>
    <?php endif; ?>

    <p class="resumen-total">
        <strong>Total esperado:</strong> <?= Security::html(number_format($total_esperado, 2, ',', '.')) . ' €' ?>
    </p>

    <?php if (empty($cargos)): ?>
        <div class="alerta alerta-info" role="alert">
            No se generaron cargos para este mes. Puede que no haya estancias activas o que ya existan todas las mensualidades.
        </div>
    <?php else: ?>
        <form action="?r=cargos/preparar/guardar" method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
            <input type="hidden" name="anio" value="<?= $anio ?>">
            <input type="hidden" name="mes" value="<?= $mes ?>">
            <input type="hidden" name="cargos_json" id="cargos_json" value=''>

            <table class="tabla-listado">
                <thead>
                    <tr>
                        <th>Inquilino</th>
                        <th>Habitación</th>
                        <th>Periodo</th>
                        <th>Días ocupados / Naturales</th>
                        <th>Vencimiento</th>
                        <th>Importe esperado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cargos as $c): ?>
                        <tr>
                            <td><?= Security::html($c['inquilino_nombre']) ?></td>
                            <td><?= Security::html($c['habitacion_numero']) ?></td>
                            <td><?= Security::html(date('d/m/Y', strtotime($c['periodo_desde']))) . ' — ' . date('d/m/Y', strtotime($c['periodo_hasta'])) ?></td>
                            <td><?= $c['dias_ocupados'] ?> / <?= $c['dias_naturales'] ?></td>
                            <td><?= Security::html(date('d/m/Y', strtotime($c['fecha_vencimiento']))) ?></td>
                            <td><?= Security::html(number_format($c['importe_esperado'], 2, ',', '.')) . ' €' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="acciones-formulario">
                <button type="submit" class="btn-guardar">Confirmar y crear mensualidades</button>
                <a href="?r=cargos/preparar" class="btn-volver">Volver sin guardar</a>
            </div>
        </form>

        <script>
            // Preparar los datos JSON para enviar al guardar
            document.querySelector('form').addEventListener('submit', function(e) {
                const cargos = [];
                const filas = document.querySelectorAll('table tbody tr');
                filas.forEach(function(fila) {
                    const celdas = fila.querySelectorAll('td');
                    cargos.push({
                        estancia_id: 0, // Se recupera desde el servidor por anio/mes
                        anio: <?= $anio ?>,
                        mes: <?= $mes ?>,
                        periodo_desde: celdas[2].textContent.split(' — ')[0].trim(),
                        periodo_hasta: celdas[2].textContent.split(' — ')[1].trim(),
                        fecha_vencimiento: celdas[4].textContent.trim(),
                        importe_esperado: parseFloat(celdas[5].textContent.replace(' €', '').replace('.', '').replace(',', '.')),
                        observaciones: ''
                    });
                });
                document.getElementById('cargos_json').value = JSON.stringify(cargos);
            });
        </script>
    <?php endif; ?>

    <a href="?r=cargos/preparar" class="btn-volver">Volver a selección de mes</a>
</div>
