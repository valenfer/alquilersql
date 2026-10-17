<?php
declare(strict_types=1);

/**
 * Vista auth/password.php — Formulario de cambio de contraseña.
 *
 * Responsabilidad:
 *  - Mostrar campos: contraseña actual, nueva contraseña, confirmar.
 *  - Botón "Cambiar contraseña".
 *  - Mensajes de error o éxito.
 */

?>
<div class="tarjeta-password">
    <h2>Cambiar contraseña</h2>

    <?php if (!empty($flash['error_password'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_password']) ?></div>
    <?php endif; ?>

    <?php if (!empty($flash['password_cambiada'])): ?>
        <div class="alerta alerta-exito" role="alert"><?= Security::html($flash['password_cambiada']) ?></div>
    <?php endif; ?>

    <form action="?r=doPassword" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">

        <div class="campo">
            <label for="password_actual">Contraseña actual</label>
            <input type="password" id="password_actual" name="password_actual" required
                   autocomplete="current-password">
        </div>

        <div class="campo">
            <label for="password_nueva">Nueva contraseña</label>
            <input type="password" id="password_nueva" name="password_nueva" required
                   autocomplete="new-password"
                   placeholder="Mínimo 8 caracteres">
        </div>

        <div class="campo">
            <label for="password_confirmar">Confirmar nueva contraseña</label>
            <input type="password" id="password_confirmar" name="password_confirmar" required
                   autocomplete="new-password">
        </div>

        <button type="submit" class="btn-guardar">Cambiar contraseña</button>
        <a href="?r=dashboard" class="btn-volver">Volver sin guardar</a>
    </form>
</div>
