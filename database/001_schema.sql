-- Esquema completo de la aplicación de gestión de alquiler por habitaciones.
-- Motor: InnoDB. Codificación: utf8mb4. Fechas de negocio: DATE.
-- Importes monetarios: DECIMAL(12,2) para evitar problemas de punto flotante.
-- Zona horaria de la aplicación: Europe/Madrid.

SET FOREIGN_KEY_CHECKS=0;

-- --------------------------------------------------------
-- 1. Usuario propietario y configuración
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario` VARCHAR(80) NOT NULL COMMENT 'Nombre de inicio de sesión del propietario',
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'Hash generado con password_hash',
  `nombre` VARCHAR(100) NOT NULL COMMENT 'Nombre completo del propietario',
  `email` VARCHAR(150) NOT NULL COMMENT 'Correo de contacto del propietario',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Instante de creación del registro',
  `ultimo_acceso` DATETIME NULL COMMENT 'Último inicio de sesión exitoso',
  `activo` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = puede acceder, 0 = deshabilitado',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuarios_usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Cuenta única del propietario. No hay multiusuario.';

CREATE TABLE IF NOT EXISTS `configuracion` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `clave` VARCHAR(100) NOT NULL COMMENT 'Clave de configuración (ej. renta_vencimiento_dia, moneda, zona_horaria)',
  `valor` TEXT NOT NULL COMMENT 'Valor de configuración',
  `descripcion` VARCHAR(255) NULL COMMENT 'Explicación legible para el propietario',
  `fecha_modificacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `modificado_por` INT UNSIGNED NULL COMMENT 'Usuario que realizó el cambio',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_configuracion_clave` (`clave`),
  CONSTRAINT `fk_configuracion_modificado_por` FOREIGN KEY (`modificado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Reglas económicas y parámetros de la aplicación. Conservan historial de cambios.';

-- --------------------------------------------------------
-- 2. Habitaciones (4 fijas, números 1-4)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `habitaciones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `numero` TINYINT UNSIGNED NOT NULL COMMENT 'Número de habitación: 1, 2, 3 o 4',
  `nombre` VARCHAR(100) NOT NULL COMMENT 'Nombre visible, ej. "Habitación 2"',
  `descripcion` TEXT NULL COMMENT 'Descripción y observaciones opcionales',
  `renta_orientativa` DECIMAL(12,2) NULL COMMENT 'Renta sugerida para nuevas ocupaciones. No modifica contratos existentes.',
  `estado` ENUM('libre','ocupada','no_disponible') NOT NULL DEFAULT 'libre' COMMENT 'Situación actual de la habitación',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_habitaciones_numero` (`numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Cuatro habitaciones permanentes del piso. No se permite crear una quinta desde la interfaz.';

-- --------------------------------------------------------
-- 3. Inquilinos
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `inquilinos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tipo_documento` ENUM('DNI','NIE','Otro') NOT NULL DEFAULT 'DNI' COMMENT 'Tipo de documento de identidad',
  `numero_documento` VARCHAR(30) NOT NULL COMMENT 'Número normalizado (sin espacios, mayúsculas para NIE)',
  `nombre` VARCHAR(100) NOT NULL COMMENT 'Nombre del inquilino',
  `apellidos` VARCHAR(150) NOT NULL COMMENT 'Apellidos del inquilino',
  `telefono` VARCHAR(30) NULL COMMENT 'Teléfono opcional',
  `email` VARCHAR(150) NULL COMMENT 'Correo opcional',
  `contacto_adicional` TEXT NULL COMMENT 'Datos de contacto adicionales opcionales',
  `observaciones` TEXT NULL COMMENT 'Observaciones opcionales',
  `estado` ENUM('activo','archivado') NOT NULL DEFAULT 'activo' COMMENT 'Activo = puede tener estancias, archivado = solo historial',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_archivado` DATETIME NULL COMMENT 'Fecha en que se archivó',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inquilinos_numero_documento` (`numero_documento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Ficha personal del inquilino. El historial se conserva aunque se archive.';

-- --------------------------------------------------------
-- 4. Estancias (ocupaciones)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `estancias` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `inquilino_id` INT UNSIGNED NOT NULL COMMENT 'Persona que ocupa',
  `habitacion_id` INT UNSIGNED NOT NULL COMMENT 'Habitación del 1 al 4',
  `fecha_entrada` DATE NOT NULL COMMENT 'Día de entrada (cuenta en el intervalo [entrada, salida))',
  `fecha_salida_prevista` DATE NULL COMMENT 'Planificación, no cierra la estancia',
  `fecha_salida_efectiva` DATE NULL COMMENT 'Día de salida efectiva (no cuenta en el intervalo)',
  `renta_mensual` DECIMAL(12,2) NOT NULL COMMENT 'Renta mensual acordada para esta estancia',
  `dia_vencimiento` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Día habitual de vencimiento de la mensualidad',
  `fianza_pactada` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Fianza pactada para esta estancia',
  `referencia_contrato` VARCHAR(100) NULL COMMENT 'Referencia o notas del acuerdo',
  `contrato_adjunto_id` INT UNSIGNED NULL COMMENT 'Contrato adjunto opcional',
  `estado` ENUM('prevista','activa','finalizada') NOT NULL DEFAULT 'prevista' COMMENT 'Derivado de fechas y situación',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_estancias_inquilino` FOREIGN KEY (`inquilino_id`) REFERENCES `inquilinos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_estancias_habitacion` FOREIGN KEY (`habitacion_id`) REFERENCES `habitaciones` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_estancias_salida_despues_entrada` CHECK (`fecha_salida_efectiva` IS NULL OR `fecha_salida_efectiva` >= `fecha_entrada`),
  CONSTRAINT `chk_estancias_prevista_despues_entrada` CHECK (`fecha_salida_prevista` IS NULL OR `fecha_salida_prevista` >= `fecha_entrada`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Separa persona de estancia. Intervalo [entrada, salida): entrada cuenta, salida no.';

-- Índices para prevenir solapamientos (aplicados también en servidor con transacciones)
CREATE INDEX `idx_estancias_habitacion_fechas` ON `estancias` (`habitacion_id`, `fecha_entrada`, `fecha_salida_efectiva`);
CREATE INDEX `idx_estancias_inquilino_fechas` ON `estancias` (`inquilino_id`, `fecha_entrada`, `fecha_salida_efectiva`);

-- --------------------------------------------------------
-- 5. Ausencias temporales (contexto, no afectan alquiler ni cuotas)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `ausencias_estancia` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `estancia_id` INT UNSIGNED NOT NULL,
  `fecha_inicio` DATE NOT NULL COMMENT 'Fecha inicial de la ausencia',
  `fecha_fin` DATE NOT NULL COMMENT 'Fecha final de la ausencia (inclusiva)',
  `observaciones` TEXT NULL COMMENT 'Motivo o notas de la ausencia',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ausencias_estancia` FOREIGN KEY (`estancia_id`) REFERENCES `estancias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_ausencias_fin_despues_inicio` CHECK (`fecha_fin` >= `fecha_inicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Ausencias temporales del inquilino durante su estancia. Solo contexto para el reparto acordado.';

-- --------------------------------------------------------
-- 6. Cargos de alquiler (mensualidades)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `cargos_alquiler` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `estancia_id` INT UNSIGNED NOT NULL,
  `anio` SMALLINT UNSIGNED NOT NULL COMMENT 'Año al que corresponde la mensualidad',
  `mes` TINYINT UNSIGNED NOT NULL COMMENT 'Mes al que corresponde (1-12)',
  `periodo_desde` DATE NOT NULL COMMENT 'Fecha inicial del periodo cubierto',
  `periodo_hasta` DATE NOT NULL COMMENT 'Fecha final del periodo cubierto (inclusive)',
  `importe_esperado` DECIMAL(12,2) NOT NULL COMMENT 'Importe del cargo de alquiler',
  `fecha_vencimiento` DATE NOT NULL COMMENT 'Fecha de vencimiento',
  `importe_cobrado` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Suma de aplicaciones válidas a este cargo',
  `estado` ENUM('pendiente','parcial','pagado') NOT NULL DEFAULT 'pendiente' COMMENT 'Calculado desde cobros y aplicaciones',
  `observaciones` TEXT NULL COMMENT 'Ajustes documentados',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT UNSIGNED NULL COMMENT 'Usuario que generó el cargo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cargos_alquiler_estancia_anio_mes` (`estancia_id`, `anio`, `mes`),
  CONSTRAINT `fk_cargos_alquiler_estancia` FOREIGN KEY (`estancia_id`) REFERENCES `estancias` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cargos_alquiler_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_cargos_mes_valido` CHECK (`mes` BETWEEN 1 AND 12),
  CONSTRAINT `chk_cargos_hasta_despues_desde` CHECK (`periodo_hasta` >= `periodo_desde`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Un cargo por estancia y mes. Generación idempotente: no se duplica la misma mensualidad.';

CREATE INDEX `idx_cargos_alquiler_estado_vencimiento` ON `cargos_alquiler` (`estado`, `fecha_vencimiento`);
CREATE INDEX `idx_cargos_alquiler_anio_mes` ON `cargos_alquiler` (`anio`, `mes`);

-- --------------------------------------------------------
-- 7. Facturas de suministros (luz y agua)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `facturas_suministro` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tipo` ENUM('luz','agua') NOT NULL COMMENT 'Tipo de suministro',
  `proveedor` VARCHAR(150) NOT NULL COMMENT 'Compañía suministradora',
  `numero_factura` VARCHAR(100) NOT NULL COMMENT 'Número de factura, obligatorio al confirmar',
  `fecha_emision` DATE NOT NULL COMMENT 'Fecha de emisión de la factura',
  `periodo_desde` DATE NOT NULL COMMENT 'Fecha inicial del periodo de consumo (inclusive)',
  `periodo_hasta` DATE NOT NULL COMMENT 'Fecha final del periodo de consumo (inclusive)',
  `importe_total` DECIMAL(12,2) NOT NULL COMMENT 'Importe total de la factura',
  `fecha_vencimiento` DATE NULL COMMENT 'Fecha de vencimiento o cargo bancario previsto',
  `estado` ENUM('borrador','confirmada','anulada') NOT NULL DEFAULT 'borrador' COMMENT 'Estado de la factura',
  `documento_adjunto_id` INT UNSIGNED NULL COMMENT 'PDF, JPG o PNG opcional',
  `observaciones` TEXT NULL COMMENT 'Observaciones del propietario',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT UNSIGNED NULL COMMENT 'Usuario que registró la factura',
  `fecha_confirmacion` DATETIME NULL COMMENT 'Instante en que se confirmó',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_facturas_proveedor_tipo_numero` (`proveedor`, `tipo`, `numero_factura`),
  CONSTRAINT `fk_facturas_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_facturas_hasta_despues_desde` CHECK (`periodo_hasta` >= `periodo_desde`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Facturas de luz y agua. Estado de pago al proveedor y cobro a inquilinos son independientes.';

CREATE INDEX `idx_facturas_tipo_periodo` ON `facturas_suministro` (`tipo`, `periodo_desde`, `periodo_hasta`);
CREATE INDEX `idx_facturas_estado` ON `facturas_suministro` (`estado`);

-- --------------------------------------------------------
-- 8. Pagos al proveedor (cargo bancario)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `pagos_proveedor` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `factura_id` INT UNSIGNED NOT NULL COMMENT 'Factura de suministro pagada',
  `importe` DECIMAL(12,2) NOT NULL COMMENT 'Importe pagado al proveedor en este movimiento',
  `fecha_real` DATE NOT NULL COMMENT 'Fecha real en que el banco cargó la factura',
  `medio` VARCHAR(50) NOT NULL DEFAULT 'transferencia' COMMENT 'Medio de pago',
  `referencia` VARCHAR(100) NULL COMMENT 'Referencia bancaria opcional',
  `observaciones` TEXT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `registrado_por` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_pagos_proveedor_factura` FOREIGN KEY (`factura_id`) REFERENCES `facturas_suministro` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pagos_proveedor_registrado_por` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Uno o varios pagos al proveedor por factura. Permite pagos parciales.';

-- --------------------------------------------------------
-- 9. Reparto manual de facturas (acuerdo de inquilinos)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `repartos_factura` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `factura_id` INT UNSIGNED NOT NULL COMMENT 'Factura a la que pertenece el reparto',
  `fecha_acuerdo` DATE NOT NULL COMMENT 'Fecha en que se registra el acuerdo',
  `autor` VARCHAR(100) NOT NULL COMMENT 'Quien registra el acuerdo',
  `observaciones` TEXT NULL COMMENT 'Observaciones del reparto, ej. "Habitación 4 vacía"',
  `total_asignado` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Suma de las cuotas asignadas',
  `diferencia_pendiente` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Total factura - total asignado. Debe ser 0 para confirmar.',
  `estado` ENUM('borrador','confirmado','rectificado') NOT NULL DEFAULT 'borrador' COMMENT 'Estado del reparto',
  `reparto_anterior_id` INT UNSIGNED NULL COMMENT 'Vínculo al reparto anterior en caso de rectificación',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_repartos_factura` FOREIGN KEY (`factura_id`) REFERENCES `facturas_suministro` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_repartos_anterior` FOREIGN KEY (`reparto_anterior_id`) REFERENCES `repartos_factura` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Instantánea del acuerdo manual de reparto. Una vez confirmado, no se modifica automáticamente.';

CREATE INDEX `idx_repartos_factura_estado` ON `repartos_factura` (`factura_id`, `estado`);

-- --------------------------------------------------------
-- 10. Cuotas de reparto por destinatario
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `cuotas_reparto` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reparto_id` INT UNSIGNED NOT NULL COMMENT 'Reparto al que pertenece esta cuota',
  `estancia_id` INT UNSIGNED NOT NULL COMMENT 'Estancia destinataria (inquilino + habitación)',
  `importe` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Cuota acordada manualmente. 0,00 es un cero explícito.',
  `asignado_explicitamente` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = importe explícito (incluido 0), 0 = pendiente de completar',
  `observaciones` TEXT NULL COMMENT 'Observaciones específicas de esta cuota',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cuotas_reparto_estancia` (`reparto_id`, `estancia_id`),
  CONSTRAINT `fk_cuotas_reparto` FOREIGN KEY (`reparto_id`) REFERENCES `repartos_factura` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cuotas_estancia` FOREIGN KEY (`estancia_id`) REFERENCES `estancias` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Cuota manual por destinatario. Distingue cero confirmado de campo vacío.';

-- --------------------------------------------------------
-- 11. Entregas de factura por destinatario
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `entregas_factura` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `factura_id` INT UNSIGNED NOT NULL COMMENT 'Factura entregada',
  `estancia_id` INT UNSIGNED NOT NULL COMMENT 'Inquilino y estancia destinataria',
  `fecha_entrega` DATE NOT NULL COMMENT 'Fecha real de entrega',
  `medio` VARCHAR(50) NOT NULL COMMENT 'en mano, correo, WhatsApp, otro (anotación manual)',
  `confirmacion_recepcion` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = el destinatario confirmó recepción',
  `observaciones` TEXT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `registrado_por` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_entregas_factura` FOREIGN KEY (`factura_id`) REFERENCES `facturas_suministro` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_entregas_estancia` FOREIGN KEY (`estancia_id`) REFERENCES `estancias` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_entregas_registrado_por` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Historial de entregas por destinatario, no una única fecha global.';

-- --------------------------------------------------------
-- 12. Cobros recibidos (movimiento de dinero)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `cobros` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `inquilino_id` INT UNSIGNED NOT NULL COMMENT 'Quién realiza el pago',
  `fecha_cobro` DATE NOT NULL COMMENT 'Fecha real del cobro',
  `medio` ENUM('efectivo','transferencia','bizum','otro') NOT NULL COMMENT 'Medio de pago',
  `importe_total` DECIMAL(12,2) NOT NULL COMMENT 'Importe total recibido en este movimiento',
  `referencia` VARCHAR(100) NULL COMMENT 'Referencia bancaria opcional (no se asume única)',
  `observaciones` TEXT NULL COMMENT 'Observaciones y justificantes',
  `token_idempotencia` VARCHAR(64) NULL COMMENT 'Token para prevenir reenvíos duplicados',
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `registrado_por` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cobros_token_idempotencia` (`token_idempotencia`),
  CONSTRAINT `fk_cobros_inquilino` FOREIGN KEY (`inquilino_id`) REFERENCES `inquilinos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cobros_registrado_por` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Movimiento de dinero recibido. Nunca mezcla inquilinos ni fianzas con cobros ordinarios.';

CREATE INDEX `idx_cobros_inquilino_fecha` ON `cobros` (`inquilino_id`, `fecha_cobro`);

-- --------------------------------------------------------
-- 13. Aplicaciones de cobro (desglose a deudas concretas)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `aplicaciones_cobro` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cobro_id` INT UNSIGNED NOT NULL COMMENT 'Movimiento de cobro del que parte esta aplicación',
  `tipo_destino` ENUM('alquiler','suministro','fianza') NOT NULL COMMENT 'Tipo de destino de la aplicación',
  `cargo_alquiler_id` INT UNSIGNED NULL COMMENT 'Cargo de alquiler si tipo_destino = alquiler',
  `cuota_reparto_id` INT UNSIGNED NULL COMMENT 'Cuota de reparto si tipo_destino = suministro',
  `fianza_id` INT UNSIGNED NULL COMMENT 'Fianza si tipo_destino = fianza',
  `importe` DECIMAL(12,2) NOT NULL COMMENT 'Importe aplicado a este destino concreto',
  `fecha_aplicacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_aplicaciones_cobro` FOREIGN KEY (`cobro_id`) REFERENCES `cobros` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_aplicaciones_cargo_alquiler` FOREIGN KEY (`cargo_alquiler_id`) REFERENCES `cargos_alquiler` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_aplicaciones_destino_exclusivo` CHECK (
    (`tipo_destino` = 'alquiler' AND `cargo_alquiler_id` IS NOT NULL AND `cuota_reparto_id` IS NULL AND `fianza_id` IS NULL)
    OR (`tipo_destino` = 'suministro' AND `cargo_alquiler_id` IS NULL AND `cuota_reparto_id` IS NOT NULL AND `fianza_id` IS NULL)
    OR (`tipo_destino` = 'fianza' AND `cargo_alquiler_id` IS NULL AND `cuota_reparto_id` IS NULL AND `fianza_id` IS NOT NULL)
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Líneas de aplicación: un movimiento puede cubrir varios conceptos con desglose explícito.';

-- --------------------------------------------------------
-- 14. Fianzas
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `fianzas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `estancia_id` INT UNSIGNED NOT NULL COMMENT 'Estancia a la que pertenece la fianza',
  `importe_pactado` DECIMAL(12,2) NOT NULL COMMENT 'Fianza pactada en la estancia',
  `importe_recibido` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Cantidad realmente recibida',
  `importe_devuelto` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Cantidad devuelta en dinero',
  `importe_aplicado_deuda` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Parte aplicada a deuda existente (sin movimiento de caja)',
  `saldo_pendiente_devolver` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Saldo que queda por devolver o liquidar',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_fianzas_estancia` FOREIGN KEY (`estancia_id`) REFERENCES `estancias` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Fianzas por estancia. No es ingreso de alquiler ni beneficio ordinario.';

CREATE INDEX `idx_fianzas_estancia` ON `fianzas` (`estancia_id`);

-- --------------------------------------------------------
-- 15. Movimientos de fianza (recibos, devoluciones, aplicaciones)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `movimientos_fianza` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fianza_id` INT UNSIGNED NOT NULL,
  `tipo` ENUM('recepcion','devolucion','aplicacion_deuda','compensacion_danos') NOT NULL COMMENT 'Tipo de movimiento',
  `importe` DECIMAL(12,2) NOT NULL COMMENT 'Importe del movimiento',
  `fecha` DATE NOT NULL COMMENT 'Fecha real del movimiento',
  `medio` VARCHAR(50) NULL COMMENT 'Medio de pago (efectivo, transferencia, etc.)',
  `cargo_alquiler_id` INT UNSIGNED NULL COMMENT 'Cargo al que se aplica si es aplicacion_deuda',
  `motivo` VARCHAR(255) NULL COMMENT 'Motivo documentado',
  `observaciones` TEXT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `registrado_por` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_movimientos_fianza` FOREIGN KEY (`fianza_id`) REFERENCES `fianzas` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_movimientos_fianza_cargo` FOREIGN KEY (`cargo_alquiler_id`) REFERENCES `cargos_alquiler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_movimientos_fianza_registrado_por` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Historial trazable de movimientos de fianza.';

-- --------------------------------------------------------
-- 16. Gastos del propietario
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `categorias_gasto` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL COMMENT 'comunidad, IBI, reparaciones, seguro, limpieza, otros',
  `es_configurable` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = el propietario puede editar/eliminar',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categorias_gasto_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Catálogo de categorías de gasto. Comunidad e IBI nunca generan cuotas a inquilinos.';

CREATE TABLE IF NOT EXISTS `gastos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `categoria_id` INT UNSIGNED NOT NULL COMMENT 'Concepto del gasto',
  `descripcion` VARCHAR(255) NOT NULL COMMENT 'Descripción del gasto',
  `importe` DECIMAL(12,2) NOT NULL COMMENT 'Importe del gasto',
  `fecha_gasto` DATE NOT NULL COMMENT 'Fecha del gasto o del documento',
  `proveedor` VARCHAR(150) NULL COMMENT 'Proveedor opcional',
  `numero_factura` VARCHAR(100) NULL COMMENT 'Número de factura o justificante opcional',
  `relacion_habitacion` INT UNSIGNED NULL COMMENT 'Si se relaciona con una habitación concreta',
  `es_general` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = gasto general del piso, sin inquilino',
  `observaciones` TEXT NULL,
  `documento_adjunto_id` INT UNSIGNED NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `creado_por` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_gastos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_gasto` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_gastos_habitacion` FOREIGN KEY (`relacion_habitacion`) REFERENCES `habitaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_gastos_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Gastos del propietario. Comunidad e IBI no se reparten entre inquilinos.';

CREATE TABLE IF NOT EXISTS `pagos_gasto` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `gasto_id` INT UNSIGNED NOT NULL COMMENT 'Gasto al que se aplica este pago',
  `importe` DECIMAL(12,2) NOT NULL COMMENT 'Importe pagado en este movimiento',
  `fecha_pago` DATE NOT NULL COMMENT 'Fecha real del pago',
  `medio` VARCHAR(50) NOT NULL COMMENT 'Medio de pago',
  `referencia` VARCHAR(100) NULL,
  `observaciones` TEXT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `registrado_por` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_pagos_gasto` FOREIGN KEY (`gasto_id`) REFERENCES `gastos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pagos_gasto_registrado_por` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Pagos de gastos. Admite pagos parciales.';

-- --------------------------------------------------------
-- 17. Llaves
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tipos_llave` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL COMMENT 'portal, vivienda, habitación, buzón',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tipos_llave_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Catálogo de tipos de llave.';

CREATE TABLE IF NOT EXISTS `llaves` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tipo_llave_id` INT UNSIGNED NOT NULL COMMENT 'Tipo de llave',
  `identificador` VARCHAR(100) NULL COMMENT 'Código individual si existe, si no se gestiona cantidad',
  `estado` ENUM('disponible','entregada','perdida') NOT NULL DEFAULT 'disponible' COMMENT 'Estado de la unidad identificada',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_llaves_tipo_llave` FOREIGN KEY (`tipo_llave_id`) REFERENCES `tipos_llave` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Unidades de llave con identificación individual cuando existe.';

CREATE TABLE IF NOT EXISTS `movimientos_llave` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `estancia_id` INT UNSIGNED NOT NULL COMMENT 'Estancia e inquilino',
  `llave_id` INT UNSIGNED NULL COMMENT 'Unidad identificada (opcional)',
  `tipo` ENUM('entrega','devolucion','perdida','reposicion') NOT NULL COMMENT 'Tipo de movimiento',
  `cantidad` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Cantidad entregada, devuelta o perdida',
  `fecha` DATE NOT NULL COMMENT 'Fecha del movimiento',
  `observaciones` TEXT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `registrado_por` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_movimientos_llave_estancia` FOREIGN KEY (`estancia_id`) REFERENCES `estancias` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_movimientos_llave_llave` FOREIGN KEY (`llave_id`) REFERENCES `llaves` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_movimientos_llave_registrado_por` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Entregas, devoluciones, pérdidas y reposiciones de llaves.';

-- --------------------------------------------------------
-- 18. Incidencias
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `incidencias` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `habitacion_id` INT UNSIGNED NULL COMMENT 'Habitación o zona afectada',
  `descripcion` TEXT NOT NULL COMMENT 'Descripción de la incidencia',
  `fecha_aviso` DATE NOT NULL COMMENT 'Fecha en que se avisó',
  `estado` ENUM('pendiente','en_curso','resuelta') NOT NULL DEFAULT 'pendiente' COMMENT 'Estado de la incidencia',
  `fecha_resolucion` DATE NULL COMMENT 'Fecha de resolución',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_incidencias_habitacion` FOREIGN KEY (`habitacion_id`) REFERENCES `habitaciones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Averías y reparaciones. No es gasto hasta que existe un importe.';

-- --------------------------------------------------------
-- 19. Adjuntos
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `adjuntos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre_original` VARCHAR(255) NOT NULL COMMENT 'Nombre original del archivo',
  `nombre_interno` VARCHAR(255) NOT NULL COMMENT 'Nombre aleatorio interno para almacenamiento seguro',
  `ruta` VARCHAR(500) NOT NULL COMMENT 'Ruta relativa fuera de la raíz pública',
  `mime_type` VARCHAR(100) NOT NULL COMMENT 'Tipo MIME comprobado realmente',
  `tamano` INT UNSIGNED NOT NULL COMMENT 'Tamaño en bytes',
  `fecha_subida` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `subido_por` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_adjuntos_subido_por` FOREIGN KEY (`subido_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Archivos almacenados fuera de la raíz pública. MIME comprobado.';

-- Claves foráneas añadidas después porque adjuntos se define después de algunas tablas que lo referencia
ALTER TABLE `estancias`
ADD CONSTRAINT `fk_estancias_contrato_adjunto` FOREIGN KEY (`contrato_adjunto_id`) REFERENCES `adjuntos` (`id`) ON DELETE SET NULL;

ALTER TABLE `facturas_suministro`
ADD CONSTRAINT `fk_facturas_documento` FOREIGN KEY (`documento_adjunto_id`) REFERENCES `adjuntos` (`id`) ON DELETE SET NULL;

ALTER TABLE `gastos`
ADD CONSTRAINT `fk_gastos_documento` FOREIGN KEY (`documento_adjunto_id`) REFERENCES `adjuntos` (`id`) ON DELETE SET NULL;

-- --------------------------------------------------------
-- 19. Contratos de alquiler
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `contratos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `estancia_id` INT UNSIGNED NOT NULL COMMENT 'Estancia a la que pertenece el contrato',
  `fecha_inicio` DATE NOT NULL COMMENT 'Fecha de inicio del contrato',
  `fecha_fin` DATE NOT NULL COMMENT 'Fecha de finalización del contrato',
  `renta_mensual` DECIMAL(12,2) NOT NULL COMMENT 'Renta mensual acordada en el contrato',
  `condiciones` TEXT NULL COMMENT 'Condiciones generales del contrato',
  `clausulas_adicionales` TEXT NULL COMMENT 'Cláusulas adicionales o modificaciones',
  `estado` ENUM('vigente','vencido','renovado') NOT NULL DEFAULT 'vigente' COMMENT 'Estado del contrato',
  `documento_adjunto_id` INT UNSIGNED NULL COMMENT 'Contrato firmado adjunto',
  `renovado_de_id` INT UNSIGNED NULL COMMENT 'Contrato del que se renueva (para historial)',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_contratos_estancia` FOREIGN KEY (`estancia_id`) REFERENCES `estancias` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_contratos_documento` FOREIGN KEY (`documento_adjunto_id`) REFERENCES `adjuntos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_contratos_renovado_de` FOREIGN KEY (`renovado_de_id`) REFERENCES `contratos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Contratos de alquiler con fecha de inicio y fin. Permiten renovación con nuevas condiciones.';

-- --------------------------------------------------------
-- 20. Auditoría
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `auditoria` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NULL COMMENT 'Quién realizó el cambio',
  `accion` VARCHAR(100) NOT NULL COMMENT 'Tipo de acción realizada',
  `tabla_afectada` VARCHAR(100) NULL COMMENT 'Tabla de la base de datos afectada',
  `registro_id` INT UNSIGNED NULL COMMENT 'ID del registro afectado',
  `motivo` VARCHAR(255) NULL COMMENT 'Motivo del cambio',
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Instante del cambio',
  `direccion_ip` VARCHAR(45) NULL COMMENT 'Dirección IP del cliente',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Quién, cuándo, qué y motivo en operaciones sensibles. Sin secretos ni binarios.';

-- Claves foráneas añadidas al final porque cuotas_reparto y fianzas se definen después de aplicaciones_cobro
ALTER TABLE `aplicaciones_cobro`
ADD CONSTRAINT `fk_aplicaciones_cuota_reparto` FOREIGN KEY (`cuota_reparto_id`) REFERENCES `cuotas_reparto` (`id`) ON DELETE RESTRICT,
ADD CONSTRAINT `fk_aplicaciones_fianza` FOREIGN KEY (`fianza_id`) REFERENCES `fianzas` (`id`) ON DELETE RESTRICT;

SET FOREIGN_KEY_CHECKS=1;
