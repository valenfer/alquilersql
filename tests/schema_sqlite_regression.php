<?php
declare(strict_types=1);

// Reproduce una instalación parcial sin tocar la base del propietario.
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('PRAGMA foreign_keys = ON');
$pdo->exec("CREATE TABLE usuarios (id INTEGER PRIMARY KEY, usuario TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, nombre TEXT NOT NULL, email TEXT NOT NULL, activo INTEGER NOT NULL DEFAULT 1)");
$pdo->exec("INSERT INTO usuarios VALUES (1, 'dueno', 'hash-de-prueba', 'Propietario', 'test@example.invalid', 1)");
$pdo->exec("CREATE TABLE habitaciones (id INTEGER PRIMARY KEY, numero INTEGER UNIQUE NOT NULL, nombre TEXT NOT NULL, descripcion TEXT, renta_orientativa DECIMAL(12,2), estado TEXT NOT NULL DEFAULT 'libre', fecha_creacion DATETIME)");
$pdo->exec("INSERT INTO habitaciones (id, numero, nombre) VALUES (1, 1, 'Habitación ya existente')");
$schema = file_get_contents(__DIR__ . '/../database/001_schema.sql');
$pdo->exec($schema);
$pdo->exec($schema); // Debe admitir reintentos sin duplicar índices o tablas.
$required = ['estancias', 'inquilinos', 'cargos_alquiler', 'facturas_suministro', 'repartos_factura', 'cuotas_reparto', 'fianzas', 'contratos', 'auditoria'];
foreach ($required as $table) {
    if (!$pdo->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=" . $pdo->quote($table))->fetchColumn()) {
        throw new RuntimeException("Falta la tabla $table");
    }
}
if ($pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() !== 1 ||
    $pdo->query('SELECT nombre FROM habitaciones WHERE numero=1')->fetchColumn() !== 'Habitación ya existente') {
    throw new RuntimeException('Se alteraron datos existentes');
}
if ($pdo->query('PRAGMA integrity_check')->fetchColumn() !== 'ok' || $pdo->query('PRAGMA foreign_key_check')->fetch()) {
    throw new RuntimeException('Error de integridad o claves foráneas');
}
echo "PASS: esquema SQLite reintentable, datos conservados e integridad correcta\n";
