# Modelo de Datos — Gestión de piso alquilado por habitaciones

Este documento describe las tablas de la base de datos, sus relaciones,
restricciones y el propósito de cada campo. El esquema se crea con
`database/001_schema.sql` y las semillas base con `database/002_seed_base.sql`.

Motor: InnoDB. Codificación: utf8mb4. Fechas de negocio: DATE. Importes:
DECIMAL(12,2).

---

## 1. Usuario propietario y configuración

### `usuarios`
Cuenta única del propietario. No hay multiusuario.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| usuario | VARCHAR(80) UK | Nombre de inicio de sesión |
| password_hash | VARCHAR(255) | Hash con password_hash |
| nombre | VARCHAR(100) | Nombre completo |
| email | VARCHAR(150) | Correo de contacto |
| fecha_creacion | DATETIME | Instante de creación |
| ultimo_acceso | DATETIME | Último login exitoso |
| activo | TINYINT(1) | 1 = puede acceder, 0 = deshabilitado |

### `configuracion`
Reglas económicas y parámetros. Conservan historial de cambios.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| clave | VARCHAR(100) UK | Clave (ej. moneda, zona_horaria) |
| valor | TEXT | Valor de configuración |
| descripcion | VARCHAR(255) | Explicación legible |
| fecha_modificacion | DATETIME | Última modificación |
| modificado_por | INT UNSIGNED FK → usuarios | Usuario que cambió |

---

## 2. Habitaciones

### `habitaciones`
Cuatro habitaciones permanentes (números 1-4). No se permite crear una quinta desde la interfaz.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| numero | TINYINT UNSIGNED UK | Número: 1, 2, 3 o 4 |
| nombre | VARCHAR(100) | Nombre visible (ej. "Habitación 2") |
| descripcion | TEXT | Descripción y observaciones |
| renta_orientativa | DECIMAL(12,2) | Renta sugerida para nuevas ocupaciones |
| estado | ENUM | libre / ocupada / no_disponible |
| fecha_creacion | DATETIME | Fecha de creación |

---

## 3. Inquilinos

### `inquilinos`
Ficha personal. El historial se conserva aunque se archive. No hay borrado en cascada de información económica.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| tipo_documento | ENUM | DNI / NIE / Otro |
| numero_documento | VARCHAR(30) UK | Normalizado (sin espacios, mayúsculas) |
| nombre | VARCHAR(100) | Nombre |
| apellidos | VARCHAR(150) | Apellidos |
| telefono | VARCHAR(30) | Teléfono opcional |
| email | VARCHAR(150) | Correo opcional |
| contacto_adicional | TEXT | Contacto adicional opcional |
| observaciones | TEXT | Observaciones opcionales |
| estado | ENUM | activo / archivado |
| fecha_creacion | DATETIME | Fecha de alta |
| fecha_archivado | DATETIME | Fecha de archivo |

---

## 4. Estancias

### `estancias`
Separa persona de ocupación. Intervalo [entrada, salida): entrada cuenta, salida no.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| inquilino_id | INT UNSIGNED FK → inquilinos | Persona que ocupa |
| habitacion_id | INT UNSIGNED FK → habitaciones | Habitación 1-4 |
| fecha_entrada | DATE | Día de entrada (cuenta) |
| fecha_salida_prevista | DATE | Planificación, no cierra |
| fecha_salida_efectiva | DATE | Día de salida (no cuenta) |
| renta_mensual | DECIMAL(12,2) | Renta mensual acordada |
| dia_vencimiento | TINYINT UNSIGNED | Día de vencimiento (1-31) |
| fianza_pactada | DECIMAL(12,2) | Fianza pactada |
| referencia_contrato | VARCHAR(100) | Referencia del acuerdo |
| contrato_adjunto_id | INT UNSIGNED FK → adjuntos | Contrato opcional |
| estado | ENUM | prevista / activa / finalizada |
| fecha_creacion | DATETIME | Fecha de creación |
| fecha_actualizacion | DATETIME | Última actualización |

Índices: `idx_estancias_habitacion_fechas`, `idx_estancias_inquilino_fechas` (para prevenir solapamientos).

### `ausencias_estancia`
Ausencias temporales. Solo contexto para el reparto acordado. No afectan alquiler ni cuotas.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| estancia_id | INT UNSIGNED FK → estancias | Estancia |
| fecha_inicio | DATE | Fecha inicial (inclusive) |
| fecha_fin | DATE | Fecha final (inclusive) |
| observaciones | TEXT | Motivo o notas |
| fecha_creacion | DATETIME | Fecha de creación |

---

## 5. Cargos de alquiler

### `cargos_alquiler`
Un cargo por estancia y mes. Generación idempotente (UK: estancia + año + mes).

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| estancia_id | INT UNSIGNED FK → estancias | Estancia |
| anio | SMALLINT UNSIGNED | Año |
| mes | TINYINT UNSIGNED | Mes (1-12) |
| periodo_desde | DATE | Fecha inicial del periodo |
| periodo_hasta | DATE | Fecha final del periodo (inclusive) |
| importe_esperado | DECIMAL(12,2) | Importe del cargo |
| fecha_vencimiento | DATE | Fecha de vencimiento |
| importe_cobrado | DECIMAL(12,2) | Suma de aplicaciones válidas |
| estado | ENUM | pendiente / parcial / pagado |
| observaciones | TEXT | Ajustes documentados |
| fecha_creacion | DATETIME | Fecha de creación |
| creado_por | INT UNSIGNED FK → usuarios | Usuario que generó |

Índices: `idx_cargos_alquiler_estado_vencimiento`, `idx_cargos_alquiler_anio_mes`.

---

## 6. Facturas de suministros

### `facturas_suministro`
Facturas de luz y agua. Estado de pago al proveedor y cobro a inquilinos son independientes.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| tipo | ENUM | luz / agua |
| proveedor | VARCHAR(150) | Compañía |
| numero_factura | VARCHAR(100) | Número (obligatorio al confirmar) |
| fecha_emision | DATE | Fecha de emisión |
| periodo_desde | DATE | Fecha inicial consumo (inclusive) |
| periodo_hasta | DATE | Fecha final consumo (inclusive) |
| importe_total | DECIMAL(12,2) | Importe total |
| fecha_vencimiento | DATE | Vencimiento / cargo bancario previsto |
| estado | ENUM | borrador / confirmada / anulada |
| documento_adjunto_id | INT UNSIGNED FK → adjuntos | PDF/JPG/PNG opcional |
| observaciones | TEXT | Observaciones |
| fecha_creacion | DATETIME | Fecha de creación |
| creado_por | INT UNSIGNED FK → usuarios | Usuario |
| fecha_confirmacion | DATETIME | Instante de confirmación |

UK: `uk_facturas_proveedor_tipo_numero` (proveedor + tipo + número).

### `pagos_proveedor`
Pagos al banco por factura. Permite varios pagos parciales.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| factura_id | INT UNSIGNED FK → facturas_suministro | Factura |
| importe | DECIMAL(12,2) | Importe pagado |
| fecha_real | DATE | Fecha real del cargo |
| medio | VARCHAR(50) | Medio de pago |
| referencia | VARCHAR(100) | Referencia bancaria opcional |
| observaciones | TEXT | Observaciones |
| fecha_registro | DATETIME | Fecha de registro |
| registrado_por | INT UNSIGNED FK → usuarios | Usuario |

---

## 7. Reparto manual

### `repartos_factura`
Instantánea del acuerdo manual de reparto por factura. Una vez confirmado, no se modifica automáticamente.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| factura_id | INT UNSIGNED FK → facturas_suministro | Factura |
| fecha_acuerdo | DATE | Fecha del acuerdo |
| autor | VARCHAR(100) | Quién registra |
| observaciones | TEXT | Observaciones del reparto |
| total_asignado | DECIMAL(12,2) | Suma de cuotas |
| diferencia_pendiente | DECIMAL(12,2) | Total factura - total asignado (debe ser 0 para confirmar) |
| estado | ENUM | borrador / confirmado / rectificado |
| reparto_anterior_id | INT UNSIGNED FK → repartos_factura | Vínculo a reparto anterior |
| fecha_creacion | DATETIME | Fecha de creación |

### `cuotas_reparto`
Cuota manual por destinatario. Distingue cero explícito (0,00 €) de campo vacío.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| reparto_id | INT UNSIGNED FK → repartos_factura | Reparto |
| estancia_id | INT UNSIGNED FK → estancias | Destinatario |
| importe | DECIMAL(12,2) | Cuota acordada manualmente |
| asignado_explicitamente | TINYINT(1) | 1 = explícito (incluido 0), 0 = pendiente |
| observaciones | TEXT | Observaciones específicas |

UK: `uk_cuotas_reparto_estancia` (reparto + estancia).

### `entregas_factura`
Historial de entregas por destinatario, no una única fecha global.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| factura_id | INT UNSIGNED FK → facturas_suministro | Factura |
| estancia_id | INT UNSIGNED FK → estancias | Destinatario |
| fecha_entrega | DATE | Fecha real de entrega |
| medio | VARCHAR(50) | en mano / correo / WhatsApp / otro |
| confirmacion_recepcion | TINYINT(1) | 1 = confirmó recepción |
| observaciones | TEXT | Observaciones |
| fecha_registro | DATETIME | Fecha de registro |
| registrado_por | INT UNSIGNED FK → usuarios | Usuario |

---

## 8. Cobros y aplicaciones

### `cobros`
Movimiento de dinero recibido. Nunca mezcla inquilinos ni fianzas con cobros ordinarios.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| inquilino_id | INT UNSIGNED FK → inquilinos | Quién paga |
| fecha_cobro | DATE | Fecha real del cobro |
| medio | ENUM | efectivo / transferencia / bizum / otro |
| importe_total | DECIMAL(12,2) | Importe total recibido |
| referencia | VARCHAR(100) | Referencia bancaria opcional |
| observaciones | TEXT | Observaciones y justificantes |
| token_idempotencia | VARCHAR(64) UK | Token para prevenir reenvíos duplicados |
| fecha_registro | DATETIME | Fecha de registro |
| registrado_por | INT UNSIGNED FK → usuarios | Usuario |

### `aplicaciones_cobro`
Líneas de aplicación: un movimiento puede cubrir varios conceptos con desglose explícito.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| cobro_id | INT UNSIGNED FK → cobros | Movimiento de cobro |
| tipo_destino | ENUM | alquiler / suministro / fianza |
| cargo_alquiler_id | INT UNSIGNED FK → cargos_alquiler | Si tipo = alquiler |
| cuota_reparto_id | INT UNSIGNED FK → cuotas_reparto | Si tipo = suministro |
| fianza_id | INT UNSIGNED FK → fianzas | Si tipo = fianza |
| importe | DECIMAL(12,2) | Importe aplicado |
| fecha_aplicacion | DATETIME | Fecha de aplicación |

CHK: `chk_aplicaciones_destino_exclusivo` — solo un destino no nulo según tipo.

---

## 9. Fianzas

### `fianzas`
Fianzas por estancia. No es ingreso de alquiler ni beneficio ordinario.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| estancia_id | INT UNSIGNED FK → estancias | Estancia |
| importe_pactado | DECIMAL(12,2) | Fianza pactada |
| importe_recibido | DECIMAL(12,2) | Cantidad realmente recibida |
| importe_devuelto | DECIMAL(12,2) | Cantidad devuelta en dinero |
| importe_aplicado_deuda | DECIMAL(12,2) | Parte aplicada a deuda (sin caja) |
| saldo_pendiente_devolver | DECIMAL(12,2) | Saldo por devolver o liquidar |
| fecha_creacion | DATETIME | Fecha de creación |
| fecha_actualizacion | DATETIME | Última actualización |

### `movimientos_fianza`
Historial trazable de movimientos de fianza.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| fianza_id | INT UNSIGNED FK → fianzas | Fianza |
| tipo | ENUM | recepcion / devolucion / aplicacion_deuda / compensacion_danos |
| importe | DECIMAL(12,2) | Importe del movimiento |
| fecha | DATE | Fecha real |
| medio | VARCHAR(50) | Medio de pago |
| cargo_alquiler_id | INT UNSIGNED FK → cargos_alquiler | Cargo si aplicacion_deuda |
| motivo | VARCHAR(255) | Motivo documentado |
| observaciones | TEXT | Observaciones |
| fecha_registro | DATETIME | Fecha de registro |
| registrado_por | INT UNSIGNED FK → usuarios | Usuario |

---

## 10. Gastos del propietario

### `categorias_gasto`
Catálogo de categorías. Comunidad e IBI nunca generan cuotas a inquilinos.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| nombre | VARCHAR(100) UK | comunidad, IBI, reparaciones, etc. |
| es_configurable | TINYINT(1) | 1 = editable por el propietario |

### `gastos`
Gastos del propietario. Comunidad e IBI no se reparten entre inquilinos.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| categoria_id | INT UNSIGNED FK → categorias_gasto | Concepto |
| descripcion | VARCHAR(255) | Descripción |
| importe | DECIMAL(12,2) | Importe |
| fecha_gasto | DATE | Fecha del gasto o documento |
| proveedor | VARCHAR(150) | Proveedor opcional |
| numero_factura | VARCHAR(100) | Factura o justificante opcional |
| relacion_habitacion | TINYINT UNSIGNED FK → habitaciones | Habitación relacionada |
| es_general | TINYINT(1) | 1 = gasto general del piso |
| observaciones | TEXT | Observaciones |
| documento_adjunto_id | INT UNSIGNED FK → adjuntos | Adjunto opcional |
| fecha_creacion | DATETIME | Fecha de creación |
| creado_por | INT UNSIGNED FK → usuarios | Usuario |

### `pagos_gasto`
Pagos de gastos. Admite pagos parciales.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| gasto_id | INT UNSIGNED FK → gastos | Gasto |
| importe | DECIMAL(12,2) | Importe pagado |
| fecha_pago | DATE | Fecha real del pago |
| medio | VARCHAR(50) | Medio de pago |
| referencia | VARCHAR(100) | Referencia opcional |
| observaciones | TEXT | Observaciones |
| fecha_registro | DATETIME | Fecha de registro |
| registrado_por | INT UNSIGNED FK → usuarios | Usuario |

---

## 11. Llaves

### `tipos_llave`
Catálogo de tipos de llave.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| nombre | VARCHAR(100) UK | portal, vivienda, habitación, buzón |

### `llaves`
Unidades de llave con identificación individual cuando existe.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| tipo_llave_id | INT UNSIGNED FK → tipos_llave | Tipo |
| identificador | VARCHAR(100) | Código individual (opcional) |
| estado | ENUM | disponible / entregada / perdida |

### `movimientos_llave`
Entregas, devoluciones, pérdidas y reposiciones.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| estancia_id | INT UNSIGNED FK → estancias | Estancia |
| llave_id | INT UNSIGNED FK → llaves | Unidad (opcional) |
| tipo | ENUM | entrega / devolucion / perdida / reposicion |
| cantidad | INT UNSIGNED | Cantidad |
| fecha | DATE | Fecha del movimiento |
| observaciones | TEXT | Observaciones |
| fecha_registro | DATETIME | Fecha de registro |
| registrado_por | INT UNSIGNED FK → usuarios | Usuario |

---

## 12. Incidencias

### `incidencias`
Averías y reparaciones. No es gasto hasta que existe un importe.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| habitacion_id | INT UNSIGNED FK → habitaciones | Habitación o zona |
| descripcion | TEXT | Descripción |
| fecha_aviso | DATE | Fecha de aviso |
| estado | ENUM | pendiente / en_curso / resuelta |
| fecha_resolucion | DATE | Fecha de resolución |
| fecha_creacion | DATETIME | Fecha de creación |

---

## 13. Adjuntos

### `adjuntos`
Archivos almacenados fuera de la raíz pública. MIME comprobado realmente.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| nombre_original | VARCHAR(255) | Nombre original |
| nombre_interno | VARCHAR(255) | Nombre aleatorio interno |
| ruta | VARCHAR(500) | Ruta relativa fuera de raíz pública |
| mime_type | VARCHAR(100) | Tipo MIME comprobado |
| tamano | INT UNSIGNED | Tamaño en bytes |
| fecha_subida | DATETIME | Fecha de subida |
| subido_por | INT UNSIGNED FK → usuarios | Usuario |

---

## 14. Auditoría

### `auditoria`
Quién, cuándo, qué y motivo en operaciones sensibles. Sin secretos ni binarios.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | INT UNSIGNED PK | Identificador |
| usuario_id | INT UNSIGNED FK → usuarios | Quién realizó el cambio |
| accion | VARCHAR(100) | Tipo de acción |
| tabla_afectada | VARCHAR(100) | Tabla afectada |
| registro_id | INT UNSIGNED | ID del registro |
| motivo | VARCHAR(255) | Motivo del cambio |
| fecha | DATETIME | Instante del cambio |
| direccion_ip | VARCHAR(45) | IP del cliente |

---

## 15. Restricciones clave

- **Unicidad**: habitación (numero), inquilino (numero_documento normalizado), cargo (estancia + anio + mes), factura (proveedor + tipo + número normalizado), cobro (token_idempotencia), cuota (reparto + estancia).
- **Claves foráneas**: todas con ON DELETE RESTRICT o SET NULL. Nunca borrado en cascada de información económica.
- **CHECK**: fecha_salida >= fecha_entrada, mes entre 1-12, periodo_hasta >= periodo_desde, destino exclusivo en aplicaciones.
- **Intervalo [entrada, salida)**: validado en servidor con transacciones para prevenir solapamientos por habitación y por persona.
