# ESTADO DEL PROYECTO — Alquiler (Gestión de piso por habitaciones)

Este archivo rastrea el avance real del proyecto para poder continuar si se agota el contexto.
Se actualiza al finalizar cada fase.

---

## Resumen general

| Fase | Estado | Fecha | Commit |
|------|--------|-------|--------|
| Fase 1: Modelo de datos, configuración, instalación y autenticación | ✅ Completada | 17/10/2026 | `56291d6` |
| Fase 2: Habitaciones, inquilinos y estancias con prevención de solapamientos | ✅ Completada | 17/10/2026 | `d704133` |
| Fase 3: Mensualidades, cobros y aplicaciones con integridad monetaria | ✅ Completada | 17/10/2026 | `bca8f7a` |
| Fase 4: Facturas de suministros, pagos al proveedor, reparto manual, entregas, comprobación | ✅ Completada | 17/10/2026 | `a9654aa` |
| Fase 5: Fianzas, llaves, gastos e incidencias | ✅ Completada | 17/10/2026 | Pendiente commit |
| Fase 6: Dashboard, listados, exportaciones, informes, liquidaciones | ✅ Completada | 17/10/2026 | `3fae93a` |
| Fase 7: Ayudas, accesibilidad, pruebas integrales, copias, documentación final | ✅ Completada | 17/10/2026 | `1c3870c` |
| Fase 8: Contratos de alquiler con renovación y alertas de vencimiento | ✅ Completada | 17/10/2026 | Pendiente commit |

---

## Fase 1 — Completada (commit 56291d6)

### Qué se construyó

- **Esquema SQL completo** (`database/001_schema.sql`): 20 tablas, InnoDB, `utf8mb4`, claves foráneas, `DECIMAL(12,2)`, restricciones de unicidad.
- **Semillas base** (`database/002_seed_base.sql`): 4 habitaciones, categorías de gasto, tipos de llave, configuración por defecto.
- **Configuración fuera de raíz web** (`config/`): `database.example.php`, `app.example.php`.
- **Controlador frontal** (`public/index.php`): enrutado por parámetro `r`.
- **Autenticación** (`app/Controllers/AuthController.php`): login, logout, cambio de contraseña con CSRF.
- **Instalador** (`app/Controllers/InstallController.php`): crea el primer propietario si no hay usuarios.
- **Clases de soporte** (`app/Support/`): `Database` (PDO singleton), `Auth`, `Session` (flash + CSRF), `Security` (escape HTML, normalización de importes/fechas en español).
- **Vistas y CSS propios**: accesibilidad (letra 18px, botones ≥56px, foco visible, alto contraste).
- **JS nativo**: previene doble envío, toggle de contraseña.
- **README.md**: instrucciones de instalación.
- **`.gitignore`**: excluye `/tutorial_*.md`, config local, storage, dependencias.

### Tutorial (no subido a Git)

- `tutorial_fase01.md`

---

## Fase 2 — Completada (commit d704133)

### Qué se construyó

- **Repositorios**: `HabitacionRepository`, `InquilinoRepository`, `EstanciaRepository`.
- **Servicios**: `HabitacionService`, `InquilinoService`, `EstanciaService`.
- **Controladores**: `HabitacionController`, `InquilinoController`, `EstanciaController`.
- **Vistas**: listar y formulario para habitaciones, inquilinos y estancias.
- **index.php**: enrutado con submódulos (`habitaciones/editar`, `inquilinos/crear`, `estancias/finalizar`).

### Reglas clave implementadas

- Máximo 4 habitaciones (no se crea una quinta desde la interfaz).
- No borrar habitación con historial.
- Intervalo [entrada, salida): entrada cuenta, salida no cuenta.
- Sin solapamientos en misma habitación ni misma persona en distintas habitaciones.
- Validación DNI/NIE (letra de control) en servidor.
- Archivado lógico de inquilinos (no borrado físico).
- Transacciones en creación de estancias.

### Tutorial (no subido a Git)

- `tutorial_fase02.md`

---

## Fase 3 — Completada (commit bca8f7a)

### Qué se construyó

- **Repositorios**: `CargoAlquilerRepository`, `CobroRepository`, `AplicacionCobroRepository`.
- **Servicios**: `CargoAlquilerService`, `CobroService`.
- **Controladores**: `CargoAlquilerController`, `CobroController`.
- **Vistas**: listar, preparar, preview, ver (cargos); listar, nuevo (3 pasos), ver (cobros).
- **CSS**: estilos para tarjetas, detalles, pasos, filtros, estado vencido.

### Reglas clave implementadas

- Prorrateo exacto: `renta × días ocupados / días naturales del mes`, centavos enteros, redondeo una sola vez.
- Idempotencia de mensualidades: UK `(estancia_id, anio, mes)` + verificación doble en transacción.
- Cobros transaccionales: cobro + aplicaciones en una transacción.
- Suma de aplicaciones = importe recibido (validación en servidor).
- No aplicar más del saldo pendiente de un cargo.
- Token de idempotencia para prevenir doble envío.
- Estado calculado (pendiente/parcial/pagado/vencido) derivado de importes.

### Tutorial (no subido a Git)

- `tutorial_fase03.md`

---

## Fase 4 — Pendiente (siguiente a implementar)

### Objetivo

Facturas de suministros (luz/agua), pagos al proveedor, reparto manual acordado por inquilinos, entregas de facturas y comprobación de cobros por factura.

### Tareas pendientes

1. **Repositorios**: `FacturaSuministroRepository`, `PagoProveedorRepository`, `RepartoFacturaRepository`, `CuotaRepartoRepository`, `EntregaFacturaRepository`.
2. **Servicios**: `FacturaSuministroService`, `RepartoService`, `EntregaFacturaService`, `ComprobacionFacturaService`.
3. **Controladores**: `FacturaSuministroController`, `RepartoController`, `EntregaFacturaController`, `ComprobacionFacturaController`.
4. **Vistas**: listar facturas, formulario factura, pago al proveedor, reparto manual, entregas, comprobación por factura.
5. **index.php**: rutas para facturas, repartos, entregas, comprobación.

### Reglas clave a implementar

- Reparto manual únicamente (no calcular automáticamente).
- Diferencia = total factura − suma cuotas = 0 para confirmar.
- Habitación vacía no genera cuota ni parte del propietario.
- Cero explícito (0,00 €) distinto de campo vacío.
- Comprobación independiente: cuadre de reparto vs cuadre de dinero.
- Facturas pueden quedar en "Reparto pendiente" sin cuotas ficticias.

---

## Próximo paso inmediato

Crear los repositorios y servicios de la Fase 4, comenzando por `FacturaSuministroRepository` y `FacturaSuministroService`.

---

## Limitaciones conocidas (actuales)

- No hay cobros de suministros (luz/agua) todavía.
- No hay fianzas ni aplicaciones a fianza (Fase 5).
- No hay dashboard con indicadores (Fase 6).
- No hay exportación CSV ni informes anuales (Fase 6).
- Las ausencias temporales tienen tabla en BD pero sin interfaz de usuario.
- El contrato adjunto opcional tiene columna en BD pero sin subida de archivos.

---

## Fase 8 — Completada (Contratos de alquiler)

### Qué se construyó

- **Tabla `contratos`** en el esquema SQL: fecha_inicio, fecha_fin, renta_mensual, condiciones, cláusulas adicionales, estado (vigente/vencido/renovado), documento adjunto, vínculo al contrato anterior en caso de renovación.
- **Repositorio**: `ContratoRepository` — CRUD, obtener contrato vigente por estancia, obtener contratos por vencer (60, 30, 15 días), obtener contratos vencidos, marcar como renovado, marcar vencidos automáticamente.
- **Servicio**: `ContratoService` — crear contrato, renovar contrato (marca el anterior como renovado y crea uno nuevo), obtener alertas de vencimiento clasificadas por días (60, 30, 15), marcar vencido automáticamente.
- **Controlador**: `ContratoController` — listar, crear, ver, renovar, marcar vencido, ver alertas.
- **Vistas**: listar contratos, formulario nuevo contrato, detalle de contrato, formulario renovar, alertas de vencimiento.
- **Dashboard**: sección de alertas de contratos que muestra contratos por vencer en 60, 30 y 15 días, y contratos ya vencidos, con nombre e identificación de habitación e inquilino.
- **Rutas**: `contratos`, `contratos/nuevo`, `contratos/crear`, `contratos/ver`, `contratos/renovar`, `contratos/procesar-renovar`, `contratos/marcar-vencido`, `contratos/alertas`.

### Reglas clave implementadas

- Un contrato tiene fecha de inicio y fin obligatorias.
- Al renovar, el contrato anterior se marca como 'renovado' y se crea uno nuevo con posible cambio de renta y condiciones.
- No se puede crear dos contratos vigentes para la misma estancia.
- Las alertas se generan 60, 30 y 15 días antes del vencimiento.
- Los contratos vencidos se pueden marcar manualmente como vencidos.

### Tutorial (no subido a Git)

- `tutorial_contratos.md`

---

## Próximo paso inmediato

La aplicación está completa con todas las funcionalidades solicitadas. Se puede proceder a hacer pruebas funcionales y documentación adicional si es necesario.

---

## Limitaciones conocidas (actuales)

- No hay subida de archivos adjuntos para contratos (la columna `documento_adjunto_id` existe pero sin interfaz de subida de archivos).
- No hay envío automático de notificaciones por email para alertas de vencimiento (se muestran en pantalla).
- El marcado automático de contratos vencidos requiere ejecutar `marcarVencidosAutomaticamente()` manualmente o mediante un script CLI.
