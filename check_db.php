<?php
require 'app/Support/Database.php';
\App\Support\Database::configurar(require 'config/database.php');
$pdo = \App\Support\Database::obtenerConexion();
$stmt = $pdo->query('SELECT COUNT(*) AS total FROM usuarios');
$total = (int) $stmt->fetch()['total'];
echo 'Total usuarios: ' . $total;