<?php
/**
 * Archivo de ejemplo de configuración de base de datos.
 * Copiar este archivo a database.php y editar con los datos reales.
 * Este archivo NO debe subirse al repositorio con credenciales reales.
 */

return [
    'host'     => 'localhost',
    'puerto'   => 3306,
    'nombre'   => 'alquiler',
    'usuario'  => 'alquiler_app',
    'password' => 'cambiar_esta_contraseña',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
