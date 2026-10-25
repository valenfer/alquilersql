<?php
declare(strict_types=1);

/**
 * Layout base de la aplicación.
 *
 * Responsabilidad:
 *  - Definir el esqueleto HTML común (doctype, head, header, footer).
 *  - Insertar CSS, JS y mensajes flash.
 *  - Renderizar la vista concreta en la variable $contenido.
 *
 * Accesibilidad:
 *  - Idioma español de España.
 *  - Meta viewport para móviles.
 *  - Charset UTF-8.
 *  - Tamaño de letra base 18px definido en CSS.
 */

$titulo = $titulo ?? 'Alquiler';
$flash = $flash ?? [];
$csrf = $csrf ?? '';
$config = $config ?? [];
$usuario = isset($usuario) ? $usuario : null;

?>
<!DOCTYPE html>
<html lang="es-ES">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= \App\Support\Security::html($titulo) ?> — Alquiler</title>
    <link rel="stylesheet" href="<?= \App\Support\Security::attr($config['url_base'] ?? '') ?>/assets/css/style.css">
</head>
<body>
    <header class="cabecera">
        <div class="contenedor-cabecera">
            <h1 class="nombre-app">Alquiler</h1>
            <?php if ($usuario): ?>
                <div class="usuario-sesion">
                    <span>Usuario: <?= \App\Support\Security::html($usuario['nombre']) ?></span>
                    <a href="?r=logout" class="btn-salir">Cerrar sesión</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <main class="contenido">
        <?php foreach ($flash as $clave => $msg): ?>
            <div class="alerta alerta-<?= \App\Support\Security::html($msg['tipo']) ?>" role="alert">
                <?= \App\Support\Security::html($msg['valor']) ?>
            </div>
        <?php endforeach; ?>

        <?= $contenido ?>
    </main>

    <footer class="pie">
        <div class="contenedor-pie">
            <p>&copy; <?= date('Y') ?> Alquiler — Gestión de piso por habitaciones. Uso interno.</p>
        </div>
    </footer>

    <?php if (!empty($csrf)): ?>
        <input type="hidden" id="csrf_token" value="<?= \App\Support\Security::attr($csrf) ?>">
    <?php endif; ?>

    <script src="<?= \App\Support\Security::attr($config['url_base'] ?? '') ?>/assets/js/app.js"></script>
</body>
</html>
