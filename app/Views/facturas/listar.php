<?php
declare(strict_types=1);

use App\Support\Security;

/**
 * Vista facturas/listar.php — Listado de facturas de luz y agua.
 *
 * Responsabilidad:
 *  - Mostrar tabla de facturas con tipo, proveedor, número, periodo, importe, estado.
 *  - Filtros por tipo, proveedor y estado.
 *  - Botón para nueva factura.
 */

?>
<div class="tarjeta-facturas">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['factura_creada'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['factura_creada']['valor']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['factura_actualizada'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['factura_actualizada']['valor']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['factura_anulada'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['factura_anulada']['valor']) ?></div>
    <?php endif; ?>
    <?php if (!empty($flash['error_factura'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_factura']['valor']) ?></div>
    <?php endif; ?>

    <div class="acciones-lista">
        <a href="?r=facturas/nuevo" class="btn-guardar">Nueva factura de luz/agua</a>
        <a href="?r=dashboard" class="btn-volver">Volver al dashboard</a>
    </div>

    <form method="get" action="?r=facturas" class="filtros-busqueda">
        <input type="hidden" name="r" value="facturas">
        <div class="campo-filtro">
            <label for="tipo">Tipo</label>
            <select id="tipo" name="tipo">
                <option value="">Todos</option>
                <option value="luz" <?= (($filtros['tipo'] ?? '') === 'luz') ? 'selected' : '' ?>>Luz</option>
                <option value="agua" <?= (($filtros['tipo'] ?? '') === 'agua') ? 'selected' : '' ?>>Agua</option>
            </select>
        </div>
        <div class="campo-filtro">
            <label for="proveedor">Proveedor</label>
            <input type="text" id="proveedor" name="proveedor"
                   value="<?= Security::attr($filtros['proveedor'] ?? '') ?>"
                   placeholder="Ejemplo: Iberdrola">
        </div>
        <div class="campo-filtro">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="">Todos</option>
                <option value="borrador" <?= (($filtros['estado'] ?? '') === 'borrador') ? 'selected' : '' ?>>Borrador</option>
                <option value="confirmada" <?= (($filtros['estado'] ?? '') === 'confirmada') ? 'selected' : '' ?>>Confirmada</option>
                <option value="anulada" <?= (($filtros['estado'] ?? '') === 'anulada') ? 'selected' : '' ?>>Anulada</option>
            </select>
        </div>
        <button type="submit" class="btn-guardar">Filtrar</button>
        <a href="?r=facturas" class="btn-volver">Limpiar</a>
    </form>

    <table class="tabla-listado">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Proveedor</th>
                <th>Número</th>
                <th>Periodo</th>
                <th>Importe total</th>
                <th>Pagado proveedor</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($facturas)): ?>
                <tr>
                    <td colspan="8" style="text-align:center; padding:2rem;">No se encontraron facturas.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($facturas as $f): ?>
                <tr>
                    <td><?= Security::html(ucfirst($f['tipo'])) ?></td>
                    <td><?= Security::html($f['proveedor']) ?></td>
                    <td><?= $f['numero_factura'] !== null ? Security::html($f['numero_factura']) : '—' ?></td>
                    <td><?= Security::html(date('d/m/Y', strtotime($f['periodo_desde']))) . ' — ' . date('d/m/Y', strtotime($f['periodo_hasta'])) ?></td>
                    <td><?= Security::html(number_format($f['importe_total'], 2, ',', '.')) . ' €' ?></td>
                    <td><?= Security::html(number_format($f['total_pagado_proveedor'] ?? 0, 2, ',', '.')) . ' €' ?></td>
                    <td>
                        <span class="estado estado-<?= Security::html($f['estado']) ?>">
                            <?= Security::html(ucfirst($f['estado'])) ?>
                        </span>
                    </td>
                    <td>
                        <a href="?r=facturas/ver&id=<?= $f['id'] ?>" class="btn-small">Ver</a>
                        <?php if ($f['estado'] !== 'anulada'): ?>
                            <a href="?r=facturas/editar&id=<?= $f['id'] ?>" class="btn-small">Editar</a>
                            <?php if ($f['estado'] === 'confirmada'): ?>
                                <form action="?r=facturas/anular" method="post" style="display:inline;" onsubmit="return confirm('¿Está seguro de que desea anular esta factura? Se requerirá un motivo.');">
                                    <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
                                    <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                    <input type="text" name="motivo_anulacion" placeholder="Motivo" required style="padding:0.25rem;">
                                    <button type="submit" class="btn-small btn-eliminar">Anular</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="ayuda">
        "Pagado proveedor" muestra el importe realmente abonado al banco por esta factura.
        Estados independientes: estado de la factura (borrador/confirmada/anulada) y pago al proveedor.
    </p>
</div>
