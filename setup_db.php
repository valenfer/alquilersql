<?php
require 'app/Support/Database.php';
\App\Support\Database::configurar(require 'config/database.php');
$pdo = \App\Support\Database::obtenerConexion();

// 1. Crear tabla usuarios
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
    echo "✓ Tabla usuarios creada\n";
} catch (PDOException $e) {
    echo "✗ Error en usuarios: " . $e->getMessage() . "\n";
}

// 2. Crear tabla configuracion
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracion (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      clave VARCHAR(100) NOT NULL UNIQUE,
      valor TEXT NOT NULL,
      descripcion VARCHAR(255) NULL,
      fecha_modificacion DATETIME NOT NULL DEFAULT (datetime('now')),
      modificado_por INTEGER UNSIGNED NULL
    )");
    echo "✓ Tabla configuracion creada\n";
    
    // Insertar configuración por defecto si no existe
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM configuracion");
    $total = (int) $stmt->fetch()['total'];
    if ($total == 0) {
        $pdo->exec("INSERT INTO configuracion (clave, valor, descripcion) VALUES ('moneda', 'EUR', 'Moneda de la aplicación')");
        $pdo->exec("INSERT INTO configuracion (clave, valor, descripcion) VALUES ('zona_horaria', 'Europe/Madrid', 'Zona horaria')");
        $pdo->exec("INSERT INTO configuracion (clave, valor, descripcion) VALUES ('dia_vencimiento_alquiler', '1', 'Día de vencimiento de mensualidad')");
        $pdo->exec("INSERT INTO configuracion (clave, valor, descripcion) VALUES ('renta_por_defecto', '350.00', 'Renta orientativa por defecto')");
        echo "✓ Configuración por defecto insertada\n";
    } else {
        echo "· Configuración ya existe\n";
    }
} catch (PDOException $e) {
    echo "✗ Error en configuracion: " . $e->getMessage() . "\n";
}

// 3. Crear tabla habitaciones y insertar 4 habitaciones
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS habitaciones (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      numero TINYINT UNSIGNED NOT NULL UNIQUE,
      nombre VARCHAR(100) NOT NULL,
      estado TEXT NOT NULL DEFAULT 'libre'
    )");
    echo "✓ Tabla habitaciones creada\n";
    
    // Insertar las 4 habitaciones si no existen
    $pdo->exec("INSERT OR IGNORE INTO habitaciones (numero, nombre) VALUES (1, 'Habitación 1')");
    $pdo->exec("INSERT OR IGNORE INTO habitaciones (numero, nombre) VALUES (2, 'Habitación 2')");
    $pdo->exec("INSERT OR IGNORE INTO habitaciones (numero, nombre) VALUES (3, 'Habitación 3')");
    $pdo->exec("INSERT OR IGNORE INTO habitaciones (numero, nombre) VALUES (4, 'Habitación 4')");
    echo "✓ 4 habitaciones insertadas\n";
} catch (PDOException $e) {
    echo "✗ Error en habitaciones: " . $e->getMessage() . "\n";
}

// 4. Verificar todas las tablas
echo "\n--- Verificando tablas ---\n";
$tablas = ['usuarios', 'configuracion', 'habitaciones'];
foreach ($tablas as $tabla) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM $tabla");
        $total = (int) $stmt->fetch()['total'];
        echo "  $tabla: $total registros\n";
    } catch (PDOException $e) {
        echo "  $tabla: ERROR - " . $e->getMessage() . "\n";
    }
}