<?php
require 'app/Support/Database.php';
\App\Support\Database::configurar(require 'config/database.php');
$pdo = \App\Support\Database::obtenerConexion();
echo "Conexión PDO OK\n";

// Try creating the user table (idempotent)
$pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  usuario VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  fecha_creacion DATETIME NOT NULL DEFAULT (datetime('now')),
  activo TINYINT(1) NOT NULL DEFAULT 1
)");
echo "Tabla usuarios creada\n";

// Try inserting a test user
$password = 'test1234';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    "INSERT INTO usuarios (usuario, password_hash, nombre, email, activo, fecha_creacion)
     VALUES ('testuser', :hash, 'Test User', 'test@test.com', 1, datetime('now'))"
);

$result = $stmt->execute([':hash' => $hash]);
echo "INSERT result: " . ($result ? 'OK' : 'FAIL') . "\n";

// Check if user was created
$stmt = $pdo->query("SELECT id, usuario FROM usuarios WHERE usuario = 'testuser'");
$user = $stmt->fetch();
if ($user) {
    echo "Usuario creado: id={$user['id']}, usuario={$user['usuario']}\n";
}

// Clean up
$pdo->exec("DELETE FROM usuarios WHERE usuario = 'testuser'");
echo "Usuario de prueba eliminado\n";

echo "Test completado\n";