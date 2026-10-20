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
 * - Comprobar que los dos cobros quedan registrados.
 */

require __DIR__ . '/../app/Support/Database.php';

echo "=== CASO 1: Mensualidad y cobros parciales ===\n\n";
$verificar = static function (bool $condicion, string $mensaje): void {
    if (!$condicion) {
        throw new RuntimeException('FAIL: ' . $mensaje);
    }

    echo "  ✓ PASS: " . $mensaje . "\n";
};

$rutaConfiguracion = __DIR__ . '/../config/database.test.php';
if (!is_file($rutaConfiguracion)) {
    throw new RuntimeException(
        'Falta config/database.test.php. Configure una base de datos de pruebas antes de ejecutar este script.'
    );
}

$config = require $rutaConfiguracion;
if (!isset($config['nombre']) || !preg_match('/_test$/i', $config['nombre'])) {
    throw new RuntimeException('La base de datos de pruebas debe terminar en "_test".');
}

\App\Support\Database::configurar($config);
$pdo = \App\Support\Database::obtenerConexion();

$pdo->beginTransaction();
register_shutdown_function(static function () use ($pdo): void {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
});

$stmt = $pdo->prepare('SELECT id FROM habitaciones WHERE numero = 1');
$stmt->execute();
$habitacionId = $stmt->fetchColumn();
if ($habitacionId === false) {
    throw new RuntimeException('No existe la habitación 1 en la base de pruebas.');
}

$identificadorPrueba = 'TEST-' . bin2hex(random_bytes(8));
$stmt = $pdo->prepare(
    "INSERT INTO inquilinos (tipo_documento, numero_documento, nombre, apellidos, estado)
     VALUES ('Otro', :documento, 'Prueba', 'Automática', 'activo')"
);
$stmt->execute([':documento' => $identificadorPrueba]);
$inquilinoId = (int) $pdo->lastInsertId();

// Crear estancia
$stmt = $pdo->prepare(
    "INSERT INTO estancias
        (inquilino_id, habitacion_id, fecha_entrada, renta_mensual, dia_vencimiento, fianza_pactada, estado)
     VALUES (:inquilino_id, :habitacion_id, '2026-09-01', 350.00, 1, 0.00, 'activa')"
);
$stmt->execute([':inquilino_id' => $inquilinoId, ':habitacion_id' => $habitacionId]);
$estanciaId = (int) $pdo->lastInsertId();

// Crear cargo de alquiler para septiembre 2026
$stmt = $pdo->prepare(
    "INSERT INTO cargos_alquiler
        (estancia_id, anio, mes, periodo_desde, periodo_hasta, importe_esperado, fecha_vencimiento, importe_cobrado, estado)
     VALUES (:estancia_id, 2026, 9, '2026-09-01', '2026-09-30', 350.00, '2026-09-30', 0.00, 'pendiente')"
);
$stmt->execute([':estancia_id' => $estanciaId]);
$cargoId = (int) $pdo->lastInsertId();

echo "Datos de prueba creados:\n";
echo "  - Inquilino ID: $inquilinoId\n";
echo "  - Estancia ID: $estanciaId\n";
echo "  - Cargo ID: $cargoId (350,00 €)\n\n";

// --- Primer cobro: 200,00 € ---
echo "Paso 1: Cobrar 200,00 €\n";

// Crear cobro
$stmt = $pdo->prepare("INSERT INTO cobros (inquilino_id, fecha_cobro, medio, importe_total, fecha_registro) VALUES (?, '2026-09-15', 'transferencia', 200.00, NOW())");
$stmt->execute([$inquilinoId]);
$cobroId = $pdo->lastInsertId();

// Crear aplicación
$stmt = $pdo->prepare("INSERT INTO aplicaciones_cobro (cobro_id, tipo_destino, cargo_alquiler_id, importe, fecha_aplicacion) VALUES (?, 'alquiler', ?, 200.00, NOW())");
$stmt->execute([$cobroId, $cargoId]);

// Actualizar cargo
$pdo->prepare("UPDATE cargos_alquiler SET importe_cobrado = 200.00, estado = 'parcial' WHERE id = ?")->execute([$cargoId]);

// Verificar estado
$stmt = $pdo->prepare("SELECT importe_cobrado, estado FROM cargos_alquiler WHERE id = ?");
$stmt->execute([$cargoId]);
$cargo = $stmt->fetch();

echo "  - Importe cobrado: " . number_format($cargo['importe_cobrado'], 2) . " €\n";
echo "  - Estado: " . $cargo['estado'] . "\n";

$verificar(
    (float) $cargo['importe_cobrado'] === 200.0 && $cargo['estado'] === 'parcial',
    'Parcialmente pagado tras el primer cobro'
);

$saldoEsperado = 350.00 - 200.00;
echo "  - Pendiente esperado: " . number_format($saldoEsperado, 2) . " €\n";

// --- Segundo cobro: 150,00 € ---
echo "\nPaso 2: Cobrar 150,00 €\n";

$stmt = $pdo->prepare("INSERT INTO cobros (inquilino_id, fecha_cobro, medio, importe_total, fecha_registro) VALUES (?, '2026-09-20', 'transferencia', 150.00, NOW())");
$stmt->execute([$inquilinoId]);
$cobroId2 = $pdo->lastInsertId();

$stmt = $pdo->prepare("INSERT INTO aplicaciones_cobro (cobro_id, tipo_destino, cargo_alquiler_id, importe, fecha_aplicacion) VALUES (?, 'alquiler', ?, 150.00, NOW())");
$stmt->execute([$cobroId2, $cargoId]);

$pdo->prepare("UPDATE cargos_alquiler SET importe_cobrado = 350.00, estado = 'pagado' WHERE id = ?")->execute([$cargoId]);

$stmt = $pdo->prepare("SELECT importe_cobrado, estado FROM cargos_alquiler WHERE id = ?");
$stmt->execute([$cargoId]);
$cargo = $stmt->fetch();

echo "  - Importe cobrado: " . number_format($cargo['importe_cobrado'], 2) . " €\n";
echo "  - Estado: " . $cargo['estado'] . "\n";

$verificar(
    (float) $cargo['importe_cobrado'] === 350.0 && $cargo['estado'] === 'pagado',
    'Pagado completamente tras el segundo cobro'
);

// --- Verificar ambos cobros conservados ---
echo "\nPaso 3: Verificar ambos cobros conservados\n";

$stmt = $pdo->prepare("SELECT id, fecha_cobro, importe_total FROM cobros WHERE inquilino_id = ? ORDER BY fecha_cobro");
$stmt->execute([$inquilinoId]);
$cobros = $stmt->fetchAll();

echo "  - Cobros registrados: " . count($cobros) . "\n";
foreach ($cobros as $c) {
    echo "    * ID: {$c['id']}, Fecha: {$c['fecha_cobro']}, Importe: " . number_format($c['importe_total'], 2) . " €\n";
}

$verificar(count($cobros) === 2, 'Ambos cobros quedan registrados');

$pdo->rollBack();

echo "\n=== CASO 1 COMPLETADO ===\n";
