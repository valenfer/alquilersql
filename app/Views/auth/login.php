<?php
declare(strict_types=1);

/**
 * Vista auth/login.php — Formulario de inicio de sesión.
 *
 * Responsabilidad:
 *  - Mostrar campos de usuario y contraseña.
 *  - Botón principal "Iniciar sesión".
 *  - Mensajes de error si las credenciales son incorrectas.
 */

?>
<div class="tarjeta-login">
    <h2>Iniciar sesión</h2>

    <?php if (!empty($flash['error_login'])): ?>
        <div class="alerta alerta-error" role="alert"><?= Security::html($flash['error_login']) ?></div>
    <?php endif; ?>

    <form action="?r=doLogin" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= Security::attr($csrf) ?>">

        <div class="campo">
            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" name="usuario" required
                   autocomplete="username"
                   placeholder="Ejemplo: propietario">
        </div>

        <div class="campo">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required
                   autocomplete="current-password"
                   placeholder="Escriba su contraseña">
        </div>

        <button type="submit" class="btn-guardar">Iniciar sesión</button>
    </form>

    <p class="ayuda">
        Si no tiene cuenta, el sistema le guiará al asistente de instalación
        para crear al propietario.
    </p>
</div>
