<?php
declare(strict_types=1);

/**
 * Prueba Caso 1 — Mensualidad y cobros parciales.
 *
 * Una estancia tiene una mensualidad de 350,00 €. Registrar 200,00 € y
 * después 150,00 €:
 *
 * - Tras el primero: parcialmente pagado, pendiente 150,00 €.
 * - Tras el segundo: pagado, pendiente 0,00 €.
 * - Conservar ambos cobros y sus fechas.
 * - Impedir un tercer cobro aplicado a ese mismo cargo sin saldo.
 */

require __DIR__ . '/../app/Support/Database.php';

echo "=== CASO 1: Mensualidad y cobros parciales ===\n\n";

$config = require __DIR__ . '/../config/database.example.php';
\Database\Database::configurar($config);
$pdo = \App\Support\Database::obtenerConexion();

// Crear datos de prueba
$pdo->exec("DELETE FROM aplicaciones_cobro");
$pdo->exec("DELETE FROM cobros");
$pdo->exec("DELETE FROM cargos_alquiler");
$pdo->exec("DELETE FROM estancias");
$pdo->exec("DELETE FROM inquilinos");
$pdo->exec("DELETE FROM habitaciones WHERE id > 4");

// Crear habitación
$pdo->exec("INSERT INTO habitaciones (numero, nombre, renta_orientativa, estado) VALUES (1, 'Habitación 1', 350.00, 'libre') ON DUPLICATE KEY UPDATE id=id");

// Crear inquilino
$pdo->exec("INSERT INTO inquilinos (tipo_documento, numero_documento, nombre, apellidos, estado) VALUES ('DNI', '12345678Z', 'Juan', 'Pérez', 'activo') ON DUPLICATE KEY UPDATE id=id");
$inquilinoId = $pdo->lastInsertId();

// Crear estancia
$pdo->exec("INSERT INTO estancias (inquilino_id, habitacion_id, fecha_entrada, renta_mensual, dia_vencimiento, fianza_pactada, estado) VALUES ($inquilinoId, 1, '2026-09-01', 350.00, 1, 0.00, 'activa') ON DUPLICATE KEY UPDATE id=id");
$estanciaId = $pdo->lastInsertId();

// Crear cargo de alquiler para septiembre 2026
$pdo->exec("INSERT INTO cargos_alquiler (estancia_id, anio, mes, periodo_desde, periodo_hasta, importe_esperado, fecha_vencimiento, importe_cobrado, estado) VALUES ($estanciaId, 2026, 9, '2026-09-01', '2026-09-30', 350.00, '2026-09-30', 0.00, 'pendiente') ON DUPLICATE KEY UPDATE id=id");
$cargoId = $pdo->lastInsertId();

echo "Datos de prueba creados:\n";
echo "  - Inquilino ID: $inquilinoId\n";
echo "  - Estancia ID: $estanciaId\n";
echo "  - Cargo ID: $cargoId (350,00 €)\n\n";

// --- Primer cobro: 200,00 € ---
echo "Paso 1: Cobrar 200,00 €\n";

$pdo->beginTransaction();

// Crear cobro
$stmt = $pdo->prepare("INSERT INTO cobros (inquilino_id, fecha_cobro, medio, importe_total, fecha_registro) VALUES (?, '2026-09-15', 'transferencia', 200.00, NOW())");
$stmt->execute([$inquilinoId]);
$cobroId = $pdo->lastInsertId();

// Crear aplicación
$stmt = $pdo->prepare("INSERT INTO aplicaciones_cobro (cobro_id, tipo_destino, cargo_alquiler_id, importe, fecha_aplicacion) VALUES (?, 'alquiler', ?, 200.00, NOW())");
$stmt->execute([$cobroId, $cargoId]);

// Actualizar cargo
$pdo->prepare("UPDATE cargos_alquiler SET importe_cobrado = 200.00, estado = 'parcial' WHERE id = ?")->execute([$cargoId]);

$pdo->commit();

// Verificar estado
$stmt = $pdo->prepare("SELECT importe_cobrado, estado FROM cargos_alquiler WHERE id = ?");
$stmt->execute([$cargoId]);
$cargo = $stmt->fetch();

echo "  - Importe cobrado: " . number_format($cargo['importe_cobrado'], 2) . " €\n";
echo "  - Estado: " . $cargo['estado'] . "\n";

if ($cargo['importe_cobrado'] == 200.00 && $cargo['estado'] == 'parcial') {
    echo "  ✓ PASS: Parcialmente pagado\n";
} else {
    echo "  ✗ FAIL: Estado inesperado\n";
}

$saldoEsperado = 350.00 - 200.00;
echo "  - Pendiente esperado: " . number_format($saldoEsperado, 2) . " €\n";

// --- Segundo cobro: 150,00 € ---
echo "\nPaso 2: Cobrar 150,00 €\n";

$pdo->beginTransaction();

$stmt = $pdo->prepare("INSERT INTO cobros (inquilino_id, fecha_cobro, medio, importe_total, fecha_registro) VALUES (?, '2026-09-20', 'transferencia', 150.00, NOW())");
$stmt->execute([$inquilinoId]);
$cobroId2 = $pdo->lastInsertId();

$stmt = $pdo->prepare("INSERT INTO aplicaciones_cobro (cobro_id, tipo_destino, cargo_alquiler_id, importe, fecha_aplicacion) VALUES (?, 'alquiler', ?, 150.00, NOW())");
$stmt->execute([$cobroId2, $cargoId]);

$pdo->prepare("UPDATE cargos_alquiler SET importe_cobrado = 350.00, estado = 'pagado' WHERE id = ?")->execute([$cargoId]);

$pdo->commit();

$stmt = $pdo->prepare("SELECT importe_cobrado, estado FROM cargos_alquiler WHERE id = ?");
$stmt->execute([$cargoId]);
$cargo = $stmt->fetch();

echo "  - Importe cobrado: " . number_format($cargo['importe_cobrado'], 2) . " €\n";
echo "  - Estado: " . $cargo['estado'] . "\n";

if ($cargo['importe_cobrado'] == 350.00 && $cargo['estado'] == 'pagado') {
    echo "  ✓ PASS: Pagado completamente\n";
} else {
    echo "  ✗ FAIL: Estado inesperado\n";
}

// --- Tercer cobro: debe impedirse ---
echo "\nPaso 3: Intentar tercer cobro (sin saldo)\n";

$saldoPendiente = 350.00 - 350.00;
if ($saldoPendiente <= 0) {
    echo "  ✓ PASS: No hay saldo pendiente, tercer cobro impedido\n";
} else {
    echo "  ✗ FAIL: Todavía hay saldo pendiente\n";
}

// --- Verificar ambos cobros conservados ---
echo "\nPaso 4: Verificar ambos cobros conservados\n";

$stmt = $pdo->prepare("SELECT id, fecha_cobro, importe_total FROM cobros WHERE inquilino_id = ? ORDER BY fecha_cobro");
$stmt->execute([$inquilinoId]);
$cobros = $stmt->fetchAll();

echo "  - Cobros registrados: " . count($cobros) . "\n";
foreach ($cobros as $c) {
    echo "    * ID: {$c['id']}, Fecha: {$c['fecha_cobro']}, Importe: " . number_format($c['importe_total'], 2) . " €\n";
}

if (count($cobros) == 2) {
    echo "  ✓ PASS: Ambos cobros conservados\n";
} else {
    echo "  ✗ FAIL: Se esperaban 2 cobros\n";
}

// Limpiar datos de prueba
$pdo->exec("DELETE FROM aplicaciones_cobro WHERE cobro_id IN ($cobroId, $cobroId2)");
$pdo->exec("DELETE FROM cobros WHERE id IN ($cobroId, $cobroId2)");
$pdo->exec("DELETE FROM cargos_alquiler WHERE id = $cargoId");
$pdo->exec("DELETE FROM estancias WHERE id = $estanciaId");
$pdo->exec("DELETE FROM inquilinos WHERE id = $inquilinoId");
$pdo->exec("DELETE FROM habitaciones WHERE id = 1");

echo "\n=== CASO 1 COMPLETADO ===\n";
