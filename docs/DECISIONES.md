# Decisiones Técnicas — Gestión de piso alquilado por habitaciones

Este documento registra las decisiones técnicas tomadas durante el desarrollo,
los valores predeterminados y el motivo de cada una.

---

## 1. Stack tecnológico

| Decisión | Valor | Motivo |
|----------|-------|--------|
| Lenguaje backend | PHP 8.0+ | Requisito obligatorio del documento. No frameworks. |
| Base de datos | SQLite | Motor de base de datos embebido, sin servidor separado, archivo local. |
| Codificación | utf8mb4 | Requisito obligatorio. Soporte completo de Unicode. |
| Acceso a datos | PDO con consultas preparadas | Requisito obligatorio. Previene inyección SQL. |
| Frontend | HTML5 + CSS propio + JS nativo | Requisito obligatorio. No frameworks CSS ni JS. |
| jQuery | Solo si se justifica, archivo local | Admitido pero preferible JS nativo. |
| ORM | Ninguno | Requisito: no usar ORM. |
| Build tools | Ninguna (no Node/npm) | Requisito: no requerir proceso de compilación. |

---

## 2. Modelo de datos

| Decisión | Valor | Motivo |
|----------|-------|--------|
| Importes monetarios | DECIMAL(12,2) | Evitar errores de punto flotante con dinero. |
| Fechas de negocio | DATE | Requisito del documento. |
| Zona horaria | Europe/Madrid | Requisito del documento. |
| Intervalo de estancia | [entrada, salida) | Entrada cuenta, salida no. Permite cambio el mismo día. |
| Máximo habitaciones | 4 | Requisito del documento. |
| Borrado lógico | Archivar en lugar de eliminar | Conservar historial (inquilinos, estancias). |
| Tabla de adjuntos | Separada, fuera de raíz pública | Seguridad: MIME comprobado, nombres aleatorios. |

---

## 3. Reglas económicas

| Decisión | Valor | Motivo |
|----------|-------|--------|
| Reparto de suministros | Manual únicamente | Requisito: no calcular automáticamente. |
| Diferencia de reparto | Debe ser 0,00 € para confirmar | Requisito: suma exacta hasta el céntimo. |
| Cero de cuota | 0,00 € explícito vs vacío (pendiente) | Requisito: distinguir cero confirmado de dato sin asignar. |
| Habitación vacía | No genera cuota ni parte del propietario | Requisito: los inquilinos deciden el reparto. |
| Prorrateo alquiler | renta × días / días naturales, redondeo una vez | Requisito sección 6.2 y 7.3. |
| Comunidad e IBI | Nunca se reparten a inquilinos | Requisito sección 3 y 11. |
| Fianza | No es ingreso de alquiler | Requisito sección 9. |
| Aplicación fianza a deuda | Sin movimiento de caja | Requisito sección 9. |

---

## 4. Seguridad

| Decisión | Valor | Motivo |
|----------|-------|--------|
| Hash de contraseñas | password_hash / PASSWORD_DEFAULT | Requisito obligatorio. |
| Regeneración de sesión | session_regenerate_id(true) | Previene fijación de sesión. |
| Cookies | HttpOnly, SameSite=Lax, Secure (HTTPS) | Requisito obligatorio. |
| CSRF | Token en todas las operaciones de cambio | Requisito obligatorio. |
| Intentos de login | Máximo 5, bloqueo 5 minutos | Previene fuerza bruta. |
| Adjuntos | MIME comprobado, extensión, tamaño ≤ 5 MB | Requisito sección 17. |
| Raíz web | Solo public/ | Seguridad: no exponer config ni código. |

---

## 5. Valores predeterminados

| Clave | Valor | Descripción |
|-------|-------|-------------|
| moneda | EUR | Moneda de la aplicación |
| zona_horaria | Europe/Madrid | Zona horaria para instantes |
| formato_fecha | d/m/Y | Formato de presentación |
| formato_importe | es | Coma decimal, punto miles |
| dia_vencimiento_alquiler | 1 | Día de vencimiento de la mensualidad |
| renta_por_defecto | 350.00 | Renta orientativa por defecto |
| sesion_ttl | 14400 segundos (4 horas) | Tiempo de vida de sesión |
| max_intentos_login | 5 | Intentos antes de bloqueo |
| tiempo_bloqueo_login | 300 segundos (5 min) | Duración del bloqueo |
| tamano_maximo_archivo | 5 MB | Tamaño máximo de adjuntos |
| extensiones_permitidas | pdf, jpg, jpeg, png | Tipos de archivo admitidos |

---

## 6. Decisiones pendientes o por revisar

| Decisión | Estado | Notas |
|----------|--------|-------|
| Uso de jQuery | Pendiente | Por ahora JS nativo suficiente. |
| Generación de PDF | Pendiente | Solo impresión A4 del navegador. |
| Script CLI para mensualidades | Pendiente | Opcional, puede añadirse luego. |
| Copia de seguridad automática | Pendiente | Por ahora es manual desde la interfaz. |
