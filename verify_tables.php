<?php
require 'app/Support/Database.php';
\App\Support\Database::configurar(require 'config/database.php');
$pdo = \App\Support\Database::obtenerConexion();

$tables = ['usuarios', 'habitaciones', 'inquilinos', 'estancias', 'configuracion'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM $table");
        $total = (int) $stmt->fetch()['total'];
        echo "Tabla $table: $total registros\n";
    } catch (PDOException $e) {
        echo "Tabla $table ERROR: " . $e->getMessage() . "\n";
    }
}