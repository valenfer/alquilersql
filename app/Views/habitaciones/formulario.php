<?php
declare(strict_types=1);

/**
 * Vista habitaciones/formulario.php — Formulario para crear o editar una habitación.
 *
 * Responsabilidad:
 *  - Mostrar campos: número, nombre, descripción, renta orientativa, estado.
 *  - Botón de guardar y volver.
 *  - Mensajes de error si los datos no son válidos.
 */

?>
<div class="tarjeta-formulario">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['error_habitacion'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_habitacion']) ?></div>
    <?php endif; ?>

    <?php if (!$puedeCrear): ?>
        <div class="alerta alerta-advertencia" role="alert">
            Ya existen las cuatro habitaciones del piso. No se puede crear una quinta.
        </div>
    <?php endif; ?>

    <form action="?r=habitaciones/<?= $accion === 'crear' ? 'crear' : 'actualizar' ?>" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
        <?php if ($accion === 'actualizar'): ?>
            <input type="hidden" name="id" value="<?= Security::attr($id) ?>">
        <?php endif; ?>

        <div class="campo">
            <label for="numero">Número de habitación</label>
            <input type="number" id="numero" name="numero" min="1" max="4" required
                   value="<?= Security::attr($habitacion['numero'] ?? '') ?>"
                   placeholder="1">
        </div>

        <div class="campo">
            <label for="nombre">Nombre visible</label>
            <input type="text" id="nombre" name="nombre" required
                   value="<?= Security::html($habitacion['nombre'] ?? '') ?>"
                   placeholder="Ejemplo: Habitación 2">
            <p class="ayuda-campo">Nombre que se muestra al propietario en el dashboard.</p>
        </div>

        <div class="campo">
            <label for="descripcion">Descripción y observaciones</label>
            <textarea id="descripcion" name="descripcion" rows="3"><?= Security::html($habitacion['descripcion'] ?? '') ?></textarea>
            <p class="ayuda-campo">Información opcional sobre la habitación.</p>
        </div>

        <div class="campo">
            <label for="renta_orientativa">Renta orientativa (€)</label>
            <input type="text" id="renta_orientativa" name="renta_orientativa"
                   value="<?= Security::html($habitacion['renta_orientativa'] !== null ? number_format($habitacion['renta_orientativa'], 2, ',', '.') : '') ?>"
                   placeholder="Ejemplo: 350,00">
            <p class="ayuda-campo">Renta sugerida para nuevas ocupaciones. No modifica contratos existentes.</p>
        </div>

        <div class="campo">
            <label for="estado">Estado actual</label>
            <select id="estado" name="estado">
                <option value="libre" <?= (($habitacion['estado'] ?? 'libre') === 'libre') ? 'selected' : '' ?>>Libre</option>
                <option value="ocupada" <?= (($habitacion['estado'] ?? '') === 'ocupada') ? 'selected' : '' ?>>Ocupada</option>
                <option value="no_disponible" <?= (($habitacion['estado'] ?? '') === 'no_disponible') ? 'selected' : '' ?>>No disponible temporalmente</option>
            </select>
        </div>

        <div class="acciones-formulario">
            <button type="submit" class="btn-guardar"><?= $accion === 'crear' ? 'Crear habitación' : 'Actualizar habitación' ?></button>
            <a href="?r=habitaciones" class="btn-volver">Volver sin guardar</a>
        </div>
    </form>
</div>
