<?php
require 'app/Support/Database.php';
\App\Support\Database::configurar(require 'config/database.php');
$pdo = \App\Support\Database::obtenerConexion();

echo "Creando tablas esenciales...\n";

// 1. usuarios - es la más importante para la instalación
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      usuario VARCHAR(80) NOT NULL UNIQUE,
      password_hash VARCHAR(255) NOT NULL,
      nombre VARCHAR(100) NOT NULL,
      email VARCHAR(150) NOT NULL,
      fecha_creacion DATETIME NOT NULL DEFAULT (datetime('now')),
      activo TINYINT(1) NOT NULL DEFAULT 1
    )");
    echo "  ✓ usuarios\n";
} catch (PDOException $e) {
    echo "  ✗ usuarios ERROR: " . $e->getMessage() . "\n";
}

// 2. configuracion
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracion (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      clave VARCHAR(100) NOT NULL UNIQUE,
      valor TEXT NOT NULL,
      descripcion VARCHAR(255) NULL,
      fecha_modificacion DATETIME NOT NULL DEFAULT (datetime('now')),
      modificado_por INTEGER UNSIGNED NULL
    )");
    // Add the ON UPDATE trigger manually
    $pdo->exec("CREATE TRIGGER IF NOT EXISTS configuracion_update_timestamp AFTER UPDATE ON configuracion BEGIN UPDATE configuracion SET fecha_modificacion = datetime('now') WHERE id = NEW.id; END");
    echo "  ✓ configuracion\n";
} catch (PDOException $e) {
    echo "  ✗ configuracion ERROR: " . $e->getMessage() . "\n";
}

// 3. habitaciones (4 habitaciones fijas)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS habitaciones (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      numero TINYINT UNSIGNED NOT NULL UNIQUE,
      nombre VARCHAR(100) NOT NULL,
      descripcion TEXT NULL,
      renta_orientativa DECIMAL(12,2) NULL,
      estado TEXT NOT NULL DEFAULT 'libre',
      fecha_creacion DATETIME NOT NULL DEFAULT (datetime('now'))
    )");
    // Insertar las 4 habitaciones si no existen
    $pdo->exec("INSERT OR IGNORE INTO habitaciones (numero, nombre, estado) VALUES (1, 'Habitación 1', 'libre')");
    $pdo->exec("INSERT OR IGNORE INTO habitaciones (numero, nombre, estado) VALUES (2, 'Habitación 2', 'libre')");
    $pdo->exec("INSERT OR IGNORE INTO habitaciones (numero, nombre, estado) VALUES (3, 'Habitación 3', 'libre')");
    $pdo->exec("INSERT OR IGNORE INTO habitaciones (numero, nombre, estado) VALUES (4, 'Habitación 4', 'libre')");
    echo "  ✓ habitaciones\n";
} catch (PDOException $e) {
    echo "  ✗ habitaciones ERROR: " . $e->getMessage() . "\n";
}

// Verificar tablas creadas
echo "\nTablas verificadas:\n";
$tables = ['usuarios', 'configuracion', 'habitaciones'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM $table");
        $total = (int) $stmt->fetch()['total'];
        echo "  $table: $total registros\n";
    } catch (PDOException $e) {
        echo "  $table ERROR: " . $e->getMessage() . "\n";
    }
}