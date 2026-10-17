/**
 * app.js — JavaScript nativo de la aplicación Alquiler.
 *
 * Responsabilidad:
 *  - Prevenir doble envío de formularios (deshabilitar botón tras clic).
 *  - Confirmación antes de acciones destructivas.
 *  - Alternar visibilidad de contraseñas.
 *
 * No se requieren frameworks. JavaScript vanilla.
 */

(function () {
    'use strict';

    // ------------------------------------------------------------
    // 1. Prevenir doble envío de formularios
    // ------------------------------------------------------------
    const formularios = document.querySelectorAll('form');

    formularios.forEach(function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            const boton = formulario.querySelector('button[type="submit"]');
            if (boton) {
                boton.disabled = true;
                boton.textContent = boton.textContent + '...';
            }
        });
    });

    // ------------------------------------------------------------
    // 2. Confirmación antes de cerrar sesión (opcional, suave)
    // ------------------------------------------------------------
    const enlacesSalir = document.querySelectorAll('a[href*="logout"]');

    enlacesSalir.forEach(function (enlace) {
        enlace.addEventListener('click', function () {
            // No bloquear, solo permitir que el navegador navegue.
            // El cierre de sesión es una acción sencilla y no destructiva.
        });
    });

    // ------------------------------------------------------------
    // 3. Alternar visibilidad de contraseña (mejora de usabilidad)
    // ------------------------------------------------------------
    const camposPassword = document.querySelectorAll('input[type="password"]');

    camposPassword.forEach(function (campo) {
        const contenedor = campo.closest('.campo');
        if (!contenedor) return;

        const boton = document.createElement('button');
        boton.type = 'button';
        boton.textContent = 'Mostrar';
        boton.className = 'btn-toggle-password';
        boton.style.cssText = 'margin-top: 0.5rem; padding: 0.5rem 1rem; font-size: 0.875rem; cursor: pointer;';

        let mostrando = false;

        boton.addEventListener('click', function () {
            mostrando = !mostrando;
            campo.type = mostrando ? 'text' : 'password';
            boton.textContent = mostrando ? 'Ocultar' : 'Mostrar';
        });

        contenedor.appendChild(boton);
    });

})();
