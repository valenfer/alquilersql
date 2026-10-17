<?php
/**
 * Archivo de ejemplo de configuración de la aplicación.
 * Copiar este archivo a app.php y editar según el entorno.
 */

return [
    'nombre_app'       => 'Alquiler — Gestión de piso por habitaciones',
    'url_base'         => 'http://localhost/alquiler/public',
    'zona_horaria'     => 'Europe/Madrid',
    'moneda'           => 'EUR',
    'idioma'           => 'es-ES',
    'sesion_nombre'    => 'alquiler_sesion',
    'sesion_ttl'       => 14400, // 4 horas en segundos
    'csrf_nombre'      => 'alquiler_csrf',
    'max_intentos_login' => 5,
    'tiempo_bloqueo_login' => 300, // 5 minutos
    'ruta_attachments' => __DIR__ . '/../storage/attachments',
    'ruta_logs'        => __DIR__ . '/../storage/logs',
    'ruta_backups'     => __DIR__ . '/../storage/backups',
    'extensiones_permitidas' => ['pdf','jpg','jpeg','png'],
    'tamano_maximo_archivo' => 5 * 1024 * 1024, // 5 MB
];
