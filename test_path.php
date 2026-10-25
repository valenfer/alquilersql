<?php
echo "__DIR__ de test_path.php: " . __DIR__ . "\n";
require 'config/database.php';
$config = require 'config/database.php';
echo "Ruta de BD: " . $config['ruta'] . "\n";
echo "Directorio existe: " . (is_dir(dirname($config['ruta'])) ? 'SÍ' : 'NO') . "\n";