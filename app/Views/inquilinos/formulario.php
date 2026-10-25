<?php
declare(strict_types=1);

use App\Support\Security;

/**
 * Vista inquilinos/formulario.php — Formulario para crear o editar un inquilino.
 *
 * Responsabilidad:
 *  - Mostrar campos: tipo de documento, número, nombre, apellidos, teléfono, email, contacto adicional, observaciones, estado.
 *  - Validación de DNI/NIE con ejemplos de ayuda.
 *  - Botón de guardar y volver.
 */

?>
<div class="tarjeta-formulario">
    <h2><?= Security::html($titulo) ?></h2>

    <?php if (!empty($flash['error_inquilino'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_inquilino']['valor']) ?></div>
    <?php endif; ?>

    <form action="?r=inquilinos/<?= $accion === 'crear' ? 'crear' : 'actualizar' ?>" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">
        <?php if ($accion === 'actualizar'): ?>
            <input type="hidden" name="id" value="<?= Security::attr((string) $id) ?>">
        <?php endif; ?>

        <div class="campo">
            <label for="tipo_documento">Tipo de documento</label>
            <select id="tipo_documento" name="tipo_documento" required>
                <option value="DNI" <?= (($inquilino['tipo_documento'] ?? 'DNI') === 'DNI') ? 'selected' : '' ?>>DNI</option>
                <option value="NIE" <?= (($inquilino['tipo_documento'] ?? '') === 'NIE') ? 'selected' : '' ?>>NIE</option>
                <option value="Otro" <?= (($inquilino['tipo_documento'] ?? '') === 'Otro') ? 'selected' : '' ?>>Otro</option>
            </select>
        </div>

        <div class="campo">
            <label for="numero_documento">Número de documento</label>
            <input type="text" id="numero_documento" name="numero_documento" required
                   value="<?= Security::html($inquilino['numero_documento'] ?? '') ?>"
                   placeholder="Ejemplo: 12345678Z o X1234567L">
            <p class="ayuda-campo">
                Se normalizará automáticamente (sin espacios, mayúsculas).
                Para DNI: 8 dígitos + letra. Para NIE: letra inicial X/Y/Z + 7 dígitos + letra.
            </p>
        </div>

        <div class="campo">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" required
                   value="<?= Security::html($inquilino['nombre'] ?? '') ?>"
                   placeholder="Ejemplo: Juan">
        </div>

        <div class="campo">
            <label for="apellidos">Apellidos</label>
            <input type="text" id="apellidos" name="apellidos" required
                   value="<?= Security::html($inquilino['apellidos'] ?? '') ?>"
                   placeholder="Ejemplo: Pérez García">
        </div>

        <div class="campo">
            <label for="telefono">Teléfono (opcional)</label>
            <input type="tel" id="telefono" name="telefono"
                   value="<?= Security::html($inquilino['telefono'] ?? '') ?>"
                   placeholder="Ejemplo: 600 00 00 00">
        </div>

        <div class="campo">
            <label for="email">Correo electrónico (opcional)</label>
            <input type="email" id="email" name="email"
                   value="<?= Security::html($inquilino['email'] ?? '') ?>"
                   placeholder="Ejemplo: juan@ejemplo.com">
        </div>

        <div class="campo">
            <label for="contacto_adicional">Contacto adicional (opcional)</label>
            <textarea id="contacto_adicional" name="contacto_adicional" rows="2"><?= Security::html($inquilino['contacto_adicional'] ?? '') ?></textarea>
            <p class="ayuda-campo">Datos de contacto adicionales opcionales.</p>
        </div>

        <div class="campo">
            <label for="observaciones">Observaciones (opcional)</label>
            <textarea id="observaciones" name="observaciones" rows="2"><?= Security::html($inquilino['observaciones'] ?? '') ?></textarea>
        </div>

        <div class="campo">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="activo" <?= (($inquilino['estado'] ?? 'activo') === 'activo') ? 'selected' : '' ?>>Activo</option>
                <option value="archivado" <?= (($inquilino['estado'] ?? '') === 'archivado') ? 'selected' : '' ?>>Archivado</option>
            </select>
            <p class="ayuda-campo">Archivar conserva el historial sin eliminar datos económicos.</p>
        </div>

        <div class="acciones-formulario">
            <button type="submit" class="btn-guardar"><?= $accion === 'crear' ? 'Dar de alta al inquilino' : 'Actualizar inquilino' ?></button>
            <a href="?r=inquilinos" class="btn-volver">Volver sin guardar</a>
        </div>
    </form>
</div>
