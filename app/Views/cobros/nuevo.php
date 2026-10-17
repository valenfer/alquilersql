<?php
declare(strict_types=1);

/**
 * Vista cobros/nuevo.php — Formulario para registrar un cobro.
 *
 * Responsabilidad:
 *  - Paso 1: Seleccionar inquilino (carga sus cargos pendientes vía AJAX).
 *  - Paso 2: Importe recibido, fecha, medio, referencia.
 *  - Paso 3: Seleccionar aplicaciones a cargos pendientes (desglose).
 *  - Validación en tiempo real: suma de aplicaciones debe coincidir con importe total.
 */

?>
<div class="tarjeta-formulario">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['error_cobro'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_cobro']) ?></div>
    <?php endif; ?>

    <form action="?r=cobros/crear" method="post" id="formCobro" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
        <input type="hidden" name="token_idempotencia" id="token_idempotencia" value="<?= Security::attr(Session::tokenCsrf()) ?>">
        <input type="hidden" name="aplicaciones_json" id="aplicaciones_json" value='[]'>

        <div class="paso" id="paso1">
            <h3>Paso 1: ¿Quién paga?</h3>
            <div class="campo">
                <label for="inquilino_id">Inquilino</label>
                <select id="inquilino_id" name="inquilino_id" required>
                    <option value="">Seleccione un inquilino</option>
                    <?php foreach ($inquilinos as $inq): ?>
                        <option value="<?= $inq['id'] ?>"><?= Security::html($inq['nombre'] . ' ' . $inq['apellidos']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="ayuda-campo">Al seleccionar al inquilino se mostrarán sus mensualidades pendientes.</p>
            </div>
            <div id="cargosPendientesContainer" style="display:none;">
                <h4>Mensualidades pendientes</h4>
                <table class="tabla-listado" id="tablaCargosPendientes">
                    <thead>
                        <tr>
                            <th>Año / Mes</th>
                            <th>Habitación</th>
                            <th>Importe esperado</th>
                            <th>Cobrado</th>
                            <th>Pendiente</th>
                            <th>Vencimiento</th>
                            <th>Aplicar</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <p class="ayuda">
                    Ejemplo: "De los 350,00 € de octubre quedan 150,00 € por cobrar".
                    Puede aplicar a varias mensualidades en un mismo cobro.
                </p>
            </div>
        </div>

        <div class="paso" id="paso2" style="display:none;">
            <h3>Paso 2: ¿Cuánto se recibe?</h3>
            <div class="campo">
                <label for="fecha_cobro">Fecha real del cobro</label>
                <input type="date" id="fecha_cobro" name="fecha_cobro" required
                       value="<?= date('Y-m-d') ?>">
            </div>
            <div class="campo">
                <label for="medio">Medio de pago</label>
                <select id="medio" name="medio" required>
                    <?php foreach ($medios as $m): ?>
                        <option value="<?= $m ?>"><?= Security::html(ucfirst($m)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="importe_total">Cantidad recibida (€)</label>
                <input type="text" id="importe_total" name="importe_total" required
                       placeholder="Ejemplo: 150,00">
                <p class="ayuda-campo">Importe total recibido en este movimiento.</p>
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
        </div>

        <div class="paso" id="paso3" style="display:none;">
            <h3>Paso 3: Revisar y guardar</h3>
            <div id="resumenAplicaciones"></div>
            <div class="campo">
                <label>Total de aplicaciones</label>
                <span id="totalAplicaciones" style="font-weight:bold;">0,00 €</span>
            </div>
            <div class="campo">
                <label>Diferencia</label>
                <span id="diferenciaAplicaciones" style="font-weight:bold; color:#c62828;">0,00 €</span>
            </div>
        </div>

        <div class="acciones-formulario" id="botonesNavegacion">
            <button type="button" id="btnSiguiente" class="btn-guardar">Siguiente</button>
            <button type="button" id="btnAtras" class="btn-volver" style="display:none;">Atrás</button>
            <button type="submit" id="btnGuardar" class="btn-guardar" style="display:none;">Guardar cobro</button>
            <a href="?r=cobros" class="btn-volver">Volver sin guardar</a>
        </div>
    </form>

    <script>
        // Estado del formulario
        let pasoActual = 1;
        let inquilinoSeleccionado = null;
        let aplicaciones = []; // {cargo_alquiler_id, importe}
        let importeTotal = 0;

        const inquilinoSelect = document.getElementById('inquilino_id');
        const fechaCobroInput = document.getElementById('fecha_cobro');
        const medioSelect = document.getElementById('medio');
        const importeTotalInput = document.getElementById('importe_total');
        const btnSiguiente = document.getElementById('btnSiguiente');
        const btnAtras = document.getElementById('btnAtras');
        const btnGuardar = document.getElementById('btnGuardar');
        const contenedorCargos = document.getElementById('cargosPendientesContainer');
        const tablaCargos = document.getElementById('tablaCargosPendientes');
        const resumenAplicaciones = document.getElementById('resumenAplicaciones');
        const totalAplicacionesSpan = document.getElementById('totalAplicaciones');
        const diferenciaSpan = document.getElementById('diferenciaAplicaciones');
        const aplicacionesJsonInput = document.getElementById('aplicaciones_json');

        // Paso 1: seleccionar inquilino
        inquilinoSelect.addEventListener('change', async function() {
            const id = this.value;
            if (!id) return;

            inquilinoSeleccionado = id;
            contenedorCargos.style.display = 'block';

            // Cargar cargos pendientes vía AJAX
            const respuesta = await fetch('?r=cobros/cargos-pendientes&inquilino_id=' + id);
            const cargos = await respuesta.json();

            tablaCargos.querySelector('tbody').innerHTML = '';
            aplicaciones = [];

            cargos.forEach(function(cargo) {
                const fila = document.createElement('tr');
                const pendiente = parseFloat(cargo.saldo_pendiente);
                fila.innerHTML = `
                    <td>${cargo.anio} / ${cargo.mes}</td>
                    <td>Habitación ${cargo.habitacion_numero}</td>
                    <td>${parseFloat(cargo.importe_esperado).toLocaleString('es-ES', {minimumFractionDigits:2, maximumFractionDigits:2})} €</td>
                    <td>${parseFloat(cargo.importe_cobrado).toLocaleString('es-ES', {minimumFractionDigits:2, maximumFractionDigits:2})} €</td>
                    <td>${pendiente.toLocaleString('es-ES', {minimumFractionDigits:2, maximumFractionDigits:2})} €</td>
                    <td>${new Date(cargo.fecha_vencimiento).toLocaleDateString('es-ES')}</td>
                    <td>
                        <input type="number" step="0.01" min="0" max="${pendiente}" 
                               data-cargo-id="${cargo.id}" 
                               placeholder="0,00"
                               onchange="actualizarAplicacion(this)">
                    </td>
                `;
                tablaCargos.querySelector('tbody').appendChild(fila);
            });

            mostrarPaso(2);
        });

        function actualizarAplicacion(input) {
            const cargoId = parseInt(input.getAttribute('data-cargo-id'));
            const importe = parseFloat(input.value) || 0;

            // Buscar si ya existe una aplicación para este cargo
            const idx = aplicaciones.findIndex(a => a.cargo_alquiler_id === cargoId);
            if (importe > 0) {
                if (idx >= 0) {
                    aplicaciones[idx].importe = importe;
                } else {
                    aplicaciones.push({cargo_alquiler_id: cargoId, importe: importe});
                }
            } else if (idx >= 0) {
                aplicaciones.splice(idx, 1);
            }

            actualizarResumen();
        }

        function actualizarResumen() {
            let total = 0;
            aplicaciones.forEach(function(a) { total += a.importe; });
            total = Math.round(total * 100) / 100;

            totalAplicacionesSpan.textContent = total.toLocaleString('es-ES', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' €';

            const importeRecibido = parseFloat(importeTotalInput.value.replace(',', '.')) || 0;
            const diferencia = Math.round((importeRecibido - total) * 100) / 100;
            diferenciaSpan.textContent = diferencia.toLocaleString('es-ES', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' €';

            if (Math.abs(diferencia) < 0.005) {
                diferenciaSpan.style.color = '#2e7d32';
            } else {
                diferenciaSpan.style.color = '#c62828';
            }

            aplicacionesJsonInput.value = JSON.stringify(aplicaciones);
        }

        // Paso 2: importe total
        importeTotalInput.addEventListener('input', function() {
            importeTotal = parseFloat(this.value.replace(',', '.')) || 0;
            actualizarResumen();
        });

        function mostrarPaso(paso) {
            document.querySelectorAll('.paso').forEach(p => p.style.display = 'none');
            document.getElementById('paso' + paso).style.display = 'block';
            pasoActual = paso;

            btnSiguiente.style.display = paso < 3 ? 'inline-block' : 'none';
            btnAtras.style.display = paso > 1 ? 'inline-block' : 'none';
            btnGuardar.style.display = paso === 3 ? 'inline-block' : 'none';

            if (paso === 3) {
                renderizarResumenAplicaciones();
            }
        }

        btnSiguiente.addEventListener('click', function() {
            if (pasoActual === 2) {
                // Validar importe total
                const importe = parseFloat(importeTotalInput.value.replace(',', '.')) || 0;
                if (importe <= 0) {
                    alert('Debe indicar una cantidad recibida mayor a 0.');
                    return;
                }
                mostrarPaso(3);
            }
        });

        btnAtras.addEventListener('click', function() {
            mostrarPaso(pasoActual - 1);
        });

        function renderizarResumenAplicaciones() {
            let html = '<table class="tabla-listado"><thead><tr><th>Cargo</th><th>Habitación</th><th>Importe aplicado</th></tr></thead><tbody>';
            aplicaciones.forEach(function(app) {
                // Buscar info del cargo para mostrar
                const filas = tablaCargos.querySelectorAll('tbody tr');
                filas.forEach(function(fila) {
                    const input = fila.querySelector('input');
                    if (input && parseInt(input.getAttribute('data-cargo-id')) === app.cargo_alquiler_id) {
                        const celdas = fila.querySelectorAll('td');
                        html += `<tr><td>${celdas[0].textContent}</td><td>${celdas[1].textContent}</td><td>${app.importe.toLocaleString('es-ES', {minimumFractionDigits:2, maximumFractionDigits:2})} €</td></tr>`;
                    }
                });
            });
            html += '</tbody></table>';
            resumenAplicaciones.innerHTML = html;
        }

        // Validación antes de enviar
        document.getElementById('formCobro').addEventListener('submit', function(e) {
            const importe = parseFloat(importeTotalInput.value.replace(',', '.')) || 0;
            let totalApps = 0;
            aplicaciones.forEach(function(a) { totalApps += a.importe; });
            totalApps = Math.round(totalApps * 100) / 100;

            if (Math.abs(importe - totalApps) > 0.005) {
                e.preventDefault();
                alert('La suma de las aplicaciones debe coincidir con el importe recibido.');
            }
        });
    </script>
</div>
