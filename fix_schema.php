<?php
require 'app/Support/Database.php';
\App\Support\Database::configurar(require 'config/database.php');
$pdo = \App\Support\Database::obtenerConexion();

// Apply key statements one by one
echo "Aplicando schema...\n";

// PRAGMAs
$pdo->exec('PRAGMA foreign_keys = ON');
$pdo->exec('PRAGMA journal = WAL');
$pdo->exec('PRAGMA synchronous = NORMAL');

// Create usuarios table
$pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  usuario VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  fecha_creacion DATETIME NOT NULL DEFAULT (datetime('now')),
  ultimo_acceso DATETIME NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1
)");
echo "Tabla usuarios creada\n";

// Verify
try {
    $stmt = $pdo->query('SELECT COUNT(*) AS total FROM usuarios');
    $total = (int) $stmt->fetch()['total'];
    echo "Total usuarios: $total\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}