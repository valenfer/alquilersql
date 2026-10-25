<?php
declare(strict_types=1);

/**
 * Vista install/instalar.php — Asistente de creación del propietario.
 *
 * Responsabilidad:
 *  - Mostrar formulario para crear la primera cuenta de propietario.
 *  - Campos: usuario, nombre, email, contraseña, confirmar.
 *  - Botón "Crear cuenta".
 */

?>
<div class="tarjeta-instalacion">
    <h2>Instalación — Crear cuenta de propietario</h2>

    <p class="descripcion">
        Esta es la primera vez que se ejecuta la aplicación. Cree la cuenta del
        propietario para poder acceder al sistema.
    </p>

    <?php if (!empty($flash['error_instalacion'])): ?>
        <div class="alerta alerta-error" role="alert"><?= \App\Support\Security::html($flash['error_instalacion']['valor']) ?></div>
    <?php endif; ?>

    <form action="?r=doInstall" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::attr($csrf) ?>">

        <div class="campo">
            <label for="usuario">Nombre de usuario</label>
            <input type="text" id="usuario" name="usuario" required
                   autocomplete="username"
                   placeholder="Ejemplo: propietario">
            <p class="ayuda-campo">Nombre que usará para iniciar sesión.</p>
        </div>

        <div class="campo">
            <label for="nombre">Nombre completo</label>
            <input type="text" id="nombre" name="nombre" required
                   placeholder="Ejemplo: Juan Pérez García">
        </div>

        <div class="campo">
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" required
                   placeholder="Ejemplo: juan@ejemplo.com">
        </div>

        <div class="campo">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required
                   autocomplete="new-password"
                   placeholder="Mínimo 8 caracteres">
        </div>

        <div class="campo">
            <label for="password_confirmar">Confirmar contraseña</label>
            <input type="password" id="password_confirmar" name="password_confirmar" required
                   autocomplete="new-password">
        </div>

        <button type="submit" class="btn-guardar">Crear cuenta</button>
    </form>
</div>
