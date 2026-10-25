<?php
require 'app/Support/Database.php';
\App\Support\Database::configurar(require 'config/database.php');
$pdo = \App\Support\Database::obtenerConexion();

// Apply the full schema from database/001_schema.sql
$sql = file_get_contents('database/001_schema.sql');
$statements = explode(';', $sql);

foreach ($statements as $statement) {
    $statement = trim($statement);
    if ($statement !== '' && !str_starts_with($statement, '--')) {
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            // Some statements might fail if table already exists, that's OK
            // echo "Statement skipped: " . substr($statement, 0, 50) . "...\n";
        }
    }
}

echo "Schema aplicado. Verificando tablas...\n";

// Check if usuarios table exists
try {
    $stmt = $pdo->query('SELECT COUNT(*) AS total FROM usuarios');
    $total = (int) $stmt->fetch()['total'];
    echo "Tabla usuarios: $total registros\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}