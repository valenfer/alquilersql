<?php
require 'app/Support/Database.php';
\App\Support\Database::configurar(require 'config/database.php');
$pdo = \App\Support\Database::obtenerConexion();

// Test if we can query usuarios table
try {
    $stmt = $pdo->query('SELECT COUNT(*) AS total FROM usuarios');
    $total = (int) $stmt->fetch()['total'];
    echo 'Usuarios en BD: ' . $total . "\n";
} catch (Exception $e) {
    echo 'Error al consultar usuarios: ' . $e->getMessage() . "\n";
}