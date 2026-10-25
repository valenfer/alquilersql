-- Esquema adaptado para SQLite.
-- Motor: SQLite. Codificación: utf8mb4 por defecto. Fechas de negocio: DATE.
-- Importes monetarios: DECIMAL(12,2) — SQLite almacena como texto pero las operaciones
-- deben usar aritmética exacta en la aplicación.
-- Zona horaria de la aplicación: Europe/Madrid (configurada en la clase Database).

PRAGMA foreign_keys = ON;
PRAGMA journal = WAL;
PRAGMA synchronous = NORMAL;

-- --------------------------------------------------------
-- 1. Usuario propietario y configuración
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `usuario` VARCHAR(80) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT (datetime('now')),
  `ultimo_acceso` DATETIME NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS `configuracion` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `clave` VARCHAR(100) NOT NULL UNIQUE,
  `valor` TEXT NOT NULL,
  `descripcion` VARCHAR(255) NULL,
  `fecha_modificacion` DATETIME NOT NULL DEFAULT (datetime('now')),
  `modificado_por` INTEGER UNSIGNED NULL,
  CONSTRAINT `fk_configuracion_modificado_por` FOREIGN KEY (`modificado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- 2. Habitaciones (4 fijas, números 1-4)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `habitaciones` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `numero` TINYINT UNSIGNED NOT NULL UNIQUE,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT NULL,
  `renta_orientativa` DECIMAL(12,2) NULL,
  `estado` TEXT NOT NULL DEFAULT 'libre',
  `fecha_creacion` DATETIME NOT NULL DEFAULT (datetime('now')),
  CONSTRAINT `chk_habitaciones_estado` CHECK (`estado` IN ('libre','ocupada','no_disponible'))
);

-- --------------------------------------------------------
-- 3. Inquilinos
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `inquilinos` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `tipo_documento` TEXT NOT NULL DEFAULT 'DNI',
  `numero_documento` VARCHAR(30) NOT NULL UNIQUE,
  `nombre` VARCHAR(100) NOT NULL,
  `apellidos` VARCHAR(150) NOT NULL,
  `telefono` VARCHAR(30) NULL,
  `email` VARCHAR(150) NULL,
  `contacto_adicional` TEXT NULL,
  `observaciones` TEXT NULL,
  `estado` TEXT NOT NULL DEFAULT 'activo',
  `fecha_creacion` DATETIME NOT NULL DEFAULT (datetime('now')),
  `fecha_archivado` DATETIME NULL,
  CONSTRAINT `chk_inquilinos_estado` CHECK (`estado` IN ('activo','archivado')),
  CONSTRAINT `chk_tipo_documento` CHECK (`tipo_documento` IN ('DNI','NIE','Otro'))
);

-- --------------------------------------------------------
-- 4. Estancias (ocupaciones)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `estancias` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `inquilino_id` INTEGER UNSIGNED NOT NULL,
  `habitacion_id` INTEGER UNSIGNED NOT NULL,
  `fecha_entrada` DATE NOT NULL,
  `fecha_salida_prevista` DATE NULL,
  `fecha_salida_efectiva` DATE NULL,
  `renta_mensual` DECIMAL(12,2) NOT NULL,
  `dia_vencimiento` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `fianza_pactada` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `referencia_contrato` VARCHAR(100) NULL,
  `contrato_adjunto_id` INTEGER UNSIGNED NULL,
  `estado` TEXT NOT NULL DEFAULT 'prevista',
  `fecha_creacion` DATETIME NOT NULL DEFAULT (datetime('now')),
  `fecha_actualizacion` DATETIME NOT NULL DEFAULT (datetime('now')),
  CONSTRAINT `fk_estancias_inquilino` FOREIGN KEY (`inquilino_id`) REFERENCES `inquilinos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_estancias_habitacion` FOREIGN KEY (`habitacion_id`) REFERENCES `habitaciones` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_estancias_contrato_adjunto` FOREIGN KEY (`contrato_adjunto_id`) REFERENCES `adjuntos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_estancias_salida_despues_entrada` CHECK (`fecha_salida_efectiva` IS NULL OR `fecha_salida_efectiva` >= `fecha_entrada`),
  CONSTRAINT `chk_estancias_prevista_despues_entrada` CHECK (`fecha_salida_prevista` IS NULL OR `fecha_salida_prevista` >= `fecha_entrada`)
);

-- Índices para prevenir solapamientos
CREATE INDEX IF NOT EXISTS `idx_estancias_habitacion_fechas` ON `estancias` (`habitacion_id`, `fecha_entrada`, `fecha_salida_efectiva`);
CREATE INDEX IF NOT EXISTS `idx_estancias_inquilino_fechas` ON `estancias` (`inquilino_id`, `fecha_entrada`, `fecha_salida_efectiva`);

-- --------------------------------------------------------
-- 5. Ausencias temporales (contexto, no afectan alquiler ni cuotas)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `ausencias_estancia` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `estancia_id` INTEGER UNSIGNED NOT NULL,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE NOT NULL,
  `observaciones` TEXT NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT (datetime('now')),
  CONSTRAINT `fk_ausencias_estancia` FOREIGN KEY (`estancia_id`) REFERENCES `estancias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_ausencias_fin_despues_inicio` CHECK (`fecha_fin` >= `fecha_inicio`)
);

-- --------------------------------------------------------
-- 6. Cargos de alquiler (mensualidades)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `cargos_alquiler` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `estancia_id` INTEGER UNSIGNED NOT NULL,
  `anio` SMALLINT UNSIGNED NOT NULL,
  `mes` TINYINT UNSIGNED NOT NULL,
  `periodo_desde` DATE NOT NULL,
  `periodo_hasta` DATE NOT NULL,
  `importe_esperado` DECIMAL(12,2) NOT NULL,
  `fecha_vencimiento` DATE NOT NULL,
  `importe_cobrado` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `estado` TEXT NOT NULL DEFAULT 'pendiente',
  `observaciones` TEXT NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT (datetime('now')),
  `creado_por` INTEGER UNSIGNED NULL,
  CONSTRAINT `fk_cargos_alquiler_estancia` FOREIGN KEY (`estancia_id`) REFERENCES `estancias` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cargos_alquiler_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_cargos_mes_valido` CHECK (`mes` BETWEEN 1 AND 12),
  CONSTRAINT `chk_cargos_hasta_despues_desde` CHECK (`periodo_hasta` >= `periodo_desde`)
);

CREATE INDEX IF NOT EXISTS `idx_cargos_alquiler_estado_vencimiento` ON `cargos_alquiler` (`estado`, `fecha_vencimiento`);
CREATE INDEX IF NOT EXISTS `idx_cargos_alquiler_anio_mes` ON `cargos_alquiler` (`anio`, `mes`);

-- --------------------------------------------------------
-- 7. Facturas de suministros (luz y agua)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `facturas_suministro` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `tipo` TEXT NOT NULL CHECK (`tipo` IN ('luz','agua')),
  `proveedor` VARCHAR(150) NOT NULL,
  `numero_factura` VARCHAR(100) NOT NULL,
  `fecha_emision` DATE NOT NULL,
  `periodo_desde` DATE NOT NULL,
  `periodo_hasta` DATE NOT NULL,
  `importe_total` DECIMAL(12,2) NOT NULL,
  `fecha_vencimiento` DATE NULL,
  `estado` TEXT NOT NULL DEFAULT 'borrador',
  `documento_adjunto_id` INTEGER UNSIGNED NULL,
  `observaciones` TEXT NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT (datetime('now')),
  `creado_por` INTEGER UNSIGNED NULL,
  `fecha_confirmacion` DATETIME NULL,
  CONSTRAINT `fk_facturas_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_facturas_documento` FOREIGN KEY (`documento_adjunto_id`) REFERENCES `adjuntos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_facturas_hasta_despues_desde` CHECK (`periodo_hasta` >= `periodo_desde`)
);

CREATE INDEX IF NOT EXISTS `idx_facturas_tipo_periodo` ON `facturas_suministro` (`tipo`, `periodo_desde`, `periodo_hasta`);
CREATE INDEX IF NOT EXISTS `idx_facturas_estado` ON `facturas_suministro` (`estado`);

-- --------------------------------------------------------
-- 8. Pagos al proveedor (cargo bancario)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `pagos_proveedor` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `factura_id` INTEGER UNSIGNED NOT NULL,
  `importe` DECIMAL(12,2) NOT NULL,
  `fecha_real` DATE NOT NULL,
  `medio` VARCHAR(50) NOT NULL DEFAULT 'transferencia',
  `referencia` VARCHAR(100) NULL,
  `observaciones` TEXT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT (datetime('now')),
  `registrado_por` INTEGER UNSIGNED NULL,
  CONSTRAINT `fk_pagos_proveedor_factura` FOREIGN KEY (`factura_id`) REFERENCES `facturas_suministro` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pagos_proveedor_registrado_por` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- 9. Reparto manual de facturas (acuerdo de inquilinos)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `repartos_factura` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `factura_id` INTEGER UNSIGNED NOT NULL,
  `fecha_acuerdo` DATE NOT NULL,
  `autor` VARCHAR(100) NOT NULL,
  `observaciones` TEXT NULL,
  `total_asignado` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `diferencia_pendiente` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `estado` TEXT NOT NULL DEFAULT 'borrador',
  `reparto_anterior_id` INTEGER UNSIGNED NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT (datetime('now')),
  CONSTRAINT `fk_repartos_factura` FOREIGN KEY (`factura_id`) REFERENCES `facturas_suministro` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_repartos_anterior` FOREIGN KEY (`reparto_anterior_id`) REFERENCES `repartos_factura` (`id`) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS `idx_repartos_factura_estado` ON `repartos_factura` (`factura_id`, `estado`);

-- --------------------------------------------------------
-- 10. Cuotas de reparto por destinatario
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `cuotas_reparto` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `reparto_id` INTEGER UNSIGNED NOT NULL,
  `estancia_id` INTEGER UNSIGNED NOT NULL,
  `importe` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `asignado_explicitamente` TINYINT(1) NOT NULL DEFAULT 1,
  `observaciones` TEXT NULL,
  CONSTRAINT `fk_cuotas_reparto` FOREIGN KEY (`reparto_id`) REFERENCES `repartos_factura` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cuotas_estancia` FOREIGN KEY (`estancia_id`) REFERENCES `estancias` (`id`) ON DELETE RESTRICT,
  UNIQUE (`reparto_id`, `estancia_id`)
);

-- --------------------------------------------------------
-- 11. Entregas de factura por destinatario
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `entregas_factura` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `factura_id` INTEGER UNSIGNED NOT NULL,
  `estancia_id` INTEGER UNSIGNED NOT NULL,
  `fecha_entrega` DATE NOT NULL,
  `medio` VARCHAR(50) NOT NULL,
  `confirmacion_recepcion` TINYINT(1) NOT NULL DEFAULT 0,
  `observaciones` TEXT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT (datetime('now')),
  `registrado_por` INTEGER UNSIGNED NULL,
  CONSTRAINT `fk_entregas_factura` FOREIGN KEY (`factura_id`) REFERENCES `facturas_suministro` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_entregas_estancia` FOREIGN KEY (`estancia_id`) REFERENCES `estancias` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_entregas_registrado_por` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- 12. Cobros recibidos (movimiento de dinero)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `cobros` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `inquilino_id` INTEGER UNSIGNED NOT NULL,
  `fecha_cobro` DATE NOT NULL,
  `medio` TEXT NOT NULL,
  `importe_total` DECIMAL(12,2) NOT NULL,
  `referencia` VARCHAR(100) NULL,
  `observaciones` TEXT NULL,
  `token_idempotencia` VARCHAR(64) NULL UNIQUE,
  `fecha_registro` DATETIME NOT NULL DEFAULT (datetime('now')),
  `registrado_por` INTEGER UNSIGNED NULL,
  CONSTRAINT `fk_cobros_inquilino` FOREIGN KEY (`inquilino_id`) REFERENCES `inquilinos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cobros_registrado_por` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS `idx_cobros_inquilino_fecha` ON `cobros` (`inquilino_id`, `fecha_cobro`);

-- --------------------------------------------------------
-- 13. Aplicaciones de cobro (desglose a deudas concretas)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `aplicaciones_cobro` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `cobro_id` INTEGER UNSIGNED NOT NULL,
  `tipo_destino` TEXT NOT NULL,
  `cargo_alquiler_id` INTEGER UNSIGNED NULL,
  `cuota_reparto_id` INTEGER UNSIGNED NULL,
  `fianza_id` INTEGER UNSIGNED NULL,
  `importe` DECIMAL(12,2) NOT NULL,
  `fecha_aplicacion` DATETIME NOT NULL DEFAULT (datetime('now')),
  CONSTRAINT `fk_aplicaciones_cobro` FOREIGN KEY (`cobro_id`) REFERENCES `cobros` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_aplicaciones_cargo_alquiler` FOREIGN KEY (`cargo_alquiler_id`) REFERENCES `cargos_alquiler` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_aplicaciones_cuota_reparto` FOREIGN KEY (`cuota_reparto_id`) REFERENCES `cuotas_reparto` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_aplicaciones_fianza` FOREIGN KEY (`fianza_id`) REFERENCES `fianzas` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_aplicaciones_destino_exclusivo` CHECK (
    (`tipo_destino` = 'alquiler' AND `cargo_alquiler_id` IS NOT NULL AND `cuota_reparto_id` IS NULL AND `fianza_id` IS NULL)
    OR (`tipo_destino` = 'suministro' AND `cargo_alquiler_id` IS NULL AND `cuota_reparto_id` IS NOT NULL AND `fianza_id` IS NULL)
    OR (`tipo_destino` = 'fianza' AND `cargo_alquiler_id` IS NULL AND `cuota_reparto_id` IS NULL AND `fianza_id` IS NOT NULL)
  )
);

-- --------------------------------------------------------
-- 14. Fianzas
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `fianzas` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `estancia_id` INTEGER UNSIGNED NOT NULL,
  `importe_pactado` DECIMAL(12,2) NOT NULL,
  `importe_recibido` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `importe_devuelto` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `importe_aplicado_deuda` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `saldo_pendiente_devolver` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `fecha_creacion` DATETIME NOT NULL DEFAULT (datetime('now')),
  `fecha_actualizacion` DATETIME NOT NULL DEFAULT (datetime('now')),
  CONSTRAINT `fk_fianzas_estancia` FOREIGN KEY (`estancia_id`) REFERENCES `estancias` (`id`) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS `idx_fianzas_estancia` ON `fianzas` (`estancia_id`);

-- --------------------------------------------------------
-- 15. Movimientos de fianza (recibos, devoluciones, aplicaciones)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `movimientos_fianza` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `fianza_id` INTEGER UNSIGNED NOT NULL,
  `tipo` TEXT NOT NULL,
  `importe` DECIMAL(12,2) NOT NULL,
  `fecha` DATE NOT NULL,
  `medio` VARCHAR(50) NULL,
  `cargo_alquiler_id` INTEGER UNSIGNED NULL,
  `motivo` VARCHAR(255) NULL,
  `observaciones` TEXT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT (datetime('now')),
  `registrado_por` INTEGER UNSIGNED NULL,
  CONSTRAINT `fk_movimientos_fianza` FOREIGN KEY (`fianza_id`) REFERENCES `fianzas` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_movimientos_fianza_cargo` FOREIGN KEY (`cargo_alquiler_id`) REFERENCES `cargos_alquiler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_movimientos_fianza_registrado_por` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- 16. Gastos del propietario
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `categorias_gasto` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `nombre` VARCHAR(100) NOT NULL UNIQUE,
  `es_configurable` TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT `chk_categorias_gasto_es_configurable` CHECK (`es_configurable` IN (0,1))
);

CREATE TABLE IF NOT EXISTS `gastos` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `categoria_id` INTEGER UNSIGNED NOT NULL,
  `descripcion` VARCHAR(255) NOT NULL,
  `importe` DECIMAL(12,2) NOT NULL,
  `fecha_gasto` DATE NOT NULL,
  `proveedor` VARCHAR(150) NULL,
  `numero_factura` VARCHAR(100) NULL,
  `relacion_habitacion` INTEGER UNSIGNED NULL,
  `es_general` TINYINT(1) NOT NULL DEFAULT 1,
  `observaciones` TEXT NULL,
  `documento_adjunto_id` INTEGER UNSIGNED NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT (datetime('now')),
  `creado_por` INTEGER UNSIGNED NULL,
  CONSTRAINT `fk_gastos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_gasto` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_gastos_habitacion` FOREIGN KEY (`relacion_habitacion`) REFERENCES `habitaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_gastos_documento` FOREIGN KEY (`documento_adjunto_id`) REFERENCES `adjuntos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_gastos_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `pagos_gasto` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `gasto_id` INTEGER UNSIGNED NOT NULL,
  `importe` DECIMAL(12,2) NOT NULL,
  `fecha_pago` DATE NOT NULL,
  `medio` VARCHAR(50) NOT NULL,
  `referencia` VARCHAR(100) NULL,
  `observaciones` TEXT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT (datetime('now')),
  `registrado_por` INTEGER UNSIGNED NULL,
  CONSTRAINT `fk_pagos_gasto` FOREIGN KEY (`gasto_id`) REFERENCES `gastos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pagos_gasto_registrado_por` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- 17. Llaves
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tipos_llave` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `nombre` VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS `llaves` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `tipo_llave_id` INTEGER UNSIGNED NOT NULL,
  `identificador` VARCHAR(100) NULL,
  `estado` TEXT NOT NULL DEFAULT 'disponible',
  CONSTRAINT `fk_llaves_tipo_llave` FOREIGN KEY (`tipo_llave_id`) REFERENCES `tipos_llave` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_llaves_estado` CHECK (`estado` IN ('disponible','entregada','perdida'))
);

CREATE TABLE IF NOT EXISTS `movimientos_llave` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `estancia_id` INTEGER UNSIGNED NOT NULL,
  `llave_id` INTEGER UNSIGNED NULL,
  `tipo` TEXT NOT NULL,
  `cantidad` INTEGER UNSIGNED NOT NULL DEFAULT 1,
  `fecha` DATE NOT NULL,
  `observaciones` TEXT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT (datetime('now')),
  `registrado_por` INTEGER UNSIGNED NULL,
  CONSTRAINT `fk_movimientos_llave_estancia` FOREIGN KEY (`estancia_id`) REFERENCES `estancias` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_movimientos_llave_llave` FOREIGN KEY (`llave_id`) REFERENCES `llaves` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_movimientos_llave_registrado_por` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- 18. Incidencias
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `incidencias` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `habitacion_id` INTEGER UNSIGNED NULL,
  `descripcion` TEXT NOT NULL,
  `fecha_aviso` DATE NOT NULL,
  `estado` TEXT NOT NULL DEFAULT 'pendiente',
  `fecha_resolucion` DATE NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT (datetime('now')),
  CONSTRAINT `fk_incidencias_habitacion` FOREIGN KEY (`habitacion_id`) REFERENCES `habitaciones` (`id`) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- 19. Adjuntos
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `adjuntos` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `nombre_original` VARCHAR(255) NOT NULL,
  `nombre_interno` VARCHAR(255) NOT NULL,
  `ruta` VARCHAR(500) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `tamano` INTEGER UNSIGNED NOT NULL,
  `fecha_subida` DATETIME NOT NULL DEFAULT (datetime('now')),
  `subido_por` INTEGER UNSIGNED NULL,
  CONSTRAINT `fk_adjuntos_subido_por` FOREIGN KEY (`subido_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
);


-- --------------------------------------------------------
-- 20. Contratos de alquiler
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `contratos` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `estancia_id` INTEGER UNSIGNED NOT NULL,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE NOT NULL,
  `renta_mensual` DECIMAL(12,2) NOT NULL,
  `condiciones` TEXT NULL,
  `clausulas_adicionales` TEXT NULL,
  `estado` TEXT NOT NULL DEFAULT 'vigente',
  `documento_adjunto_id` INTEGER UNSIGNED NULL,
  `renovado_de_id` INTEGER UNSIGNED NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT (datetime('now')),
  `fecha_actualizacion` DATETIME NOT NULL DEFAULT (datetime('now')),
  CONSTRAINT `fk_contratos_estancia` FOREIGN KEY (`estancia_id`) REFERENCES `estancias` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_contratos_documento` FOREIGN KEY (`documento_adjunto_id`) REFERENCES `adjuntos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_contratos_renovado_de` FOREIGN KEY (`renovado_de_id`) REFERENCES `contratos` (`id`) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- 21. Auditoría
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `auditoria` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `usuario_id` INTEGER UNSIGNED NULL,
  `accion` VARCHAR(100) NOT NULL,
  `tabla_afectada` VARCHAR(100) NULL,
  `registro_id` INTEGER UNSIGNED NULL,
  `motivo` VARCHAR(255) NULL,
  `fecha` DATETIME NOT NULL DEFAULT (datetime('now')),
  `direccion_ip` VARCHAR(45) NULL,
  CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
);
