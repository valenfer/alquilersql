<?php
require 'app/Support/Database.php';
\App\Support\Database::configurar(require 'config/database.php');
$pdo = \App\Support\Database::obtenerConexion();

$schema = file_get_contents('database/001_schema.sql');
$statements = explode(';', $schema);

$created = 0;
$skipped = 0;

foreach ($statements as $statement) {
    $statement = trim($statement);
    if ($statement === '' || str_starts_with($statement, '--')) {
        continue;
    }
    try {
        $pdo->exec($statement);
        $created++;
    } catch (PDOException $e) {
        // Some tables might have dependencies; skip and try later
        $skipped++;
    }
}

echo "Schema aplicado: $created statements ejecutados, $skipped omitidos\n";

// Verify key tables
$check_tables = ['usuarios', 'habitaciones', 'configuracion'];
foreach ($check_tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM $table");
        $total = (int) $stmt->fetch()['total'];
        echo "Tabla $table: $total registros\n";
    } catch (PDOException $e) {
        echo "Tabla $table ERROR: " . $e->getMessage() . "\n";
    }
}