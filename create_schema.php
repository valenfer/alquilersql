<?php
require 'app/Support/Database.php';
\App\Support\Database::configurar(require 'config/database.php');
$pdo = \App\Support\Database::obtenerConexion();
$pdo->exec(file_get_contents('database/001_schema.sql'));
echo "Schema creado exitosamente\n";