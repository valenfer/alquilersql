-- Semillas base: las cuatro habitaciones y los catálogos básicos.
-- No incluye datos de demostración (ver 003_seed_demo.sql).
-- No ejecutar sobre datos reales de producción.

-- Las cuatro habitaciones permanentes del piso
INSERT INTO `habitaciones` (`numero`, `nombre`, `descripcion`, `renta_orientativa`, `estado`) VALUES
(1, 'Habitación 1', 'Habitación principal con ventana exterior.', 350.00, 'libre'),
(2, 'Habitación 2', 'Habitación interior con armario empotrado.', 320.00, 'libre'),
(3, 'Habitación 3', 'Habitación con balcón.', 340.00, 'libre'),
(4, 'Habitación 4', 'Habitación individual cercana al baño.', 300.00, 'libre')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Categorías de gasto básicas
INSERT INTO `categorias_gasto` (`nombre`, `es_configurable`) VALUES
('Comunidad', 1),
('IBI', 1),
('Reparaciones', 1),
('Averías y mantenimiento', 1),
('Seguro', 1),
('Limpieza y servicios', 1),
('Otros', 1)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Tipos de llave básicos
INSERT INTO `tipos_llave` (`nombre`) VALUES
('Portal'),
('Vivienda'),
('Habitación'),
('Buzón')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Configuración por defecto
INSERT INTO `configuracion` (`clave`, `valor`, `descripcion`) VALUES
('moneda', 'EUR', 'Moneda de la aplicación'),
('zona_horaria', 'Europe/Madrid', 'Zona horaria para instantes y fechas calculadas'),
('formato_fecha', 'd/m/Y', 'Formato de presentación de fechas'),
('formato_importe', 'es', 'Formato de presentación de importes (coma decimal, punto miles)'),
('dia_vencimiento_alquiler', '1', 'Día habitual de vencimiento de la mensualidad'),
('renta_por_defecto', '350.00', 'Renta orientativa por defecto para nuevas ocupaciones')
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`);
