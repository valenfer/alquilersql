# Reglas de Negocio — Gestión de piso alquilado por habitaciones

Este documento explica las fórmulas, fechas, reglas de reparto, cuadre por factura,
redondeos y criterios de caja. Incluye ejemplos para habitaciones vacías, vacaciones
y la diferencia entre cuota asignada y cantidad cobrada.

---

## 1. Prorrateo del alquiler (sección 6.2)

### Fórmula

Para el primer y último mes de una estancia, se prorratea por días naturales:

```
importe = renta mensual × días ocupados del mes / días naturales del mes
```

### Reglas

- **Días ocupados**: intervalo [entrada, salida). El día de entrada cuenta, el día de salida efectiva no cuenta.
- **Estancia que ocupa todo el mes**: paga exactamente una mensualidad, incluidos febrero (28 o 29 días) y meses de 30 o 31 días.
- **Redondeo**: una sola vez al céntimo más próximo, empate de medio céntimo hacia arriba.
- **Fecha de vencimiento**: si el día configurado no existe en un mes (ej. día 31 en febrero), se usa el último día del mes.
- **Cambio de renta**: ordinariamente entra en vigor el primer día de un mes. Cambios a mitad de mes requieren ajuste explícito con motivo guardado.
- **Conservación**: el importe aplicado a cada cargo se guarda; no se consulta solo la renta actual para reconstruir el pasado.

### Ejemplo: caso 6 del documento

Renta de 300,00 € en un mes de 30 días, estancia [16 de junio, 1 de julio):

- Días ocupados: 16, 17, ..., 29 = 14 días (el día 30 no cuenta porque es la salida efectiva)
- Días naturales: 30
- Importe: 300,00 × 14 / 30 = 140,00 €

Wait, el documento dice cargo 150,00 €. Revisemos:

Estancia [16 de junio, 1 de julio): entrada 16/06, salida efectiva 01/07.
- Días ocupados: 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30 = 15 días
- Días naturales de junio: 30
- Importe: 300,00 × 15 / 30 = 150,00 € ✓

---

## 2. Reparto manual de consumos (sección 7.2)

### Regla fundamental

**La aplicación no calcula ni precarga importes.** Los inquilinos calculan el
reparto fuera de la aplicación y comunican cuánto corresponde a cada uno. El
propietario introduce esos importes manualmente.

### Reglas del acuerdo

1. Las cuatro habitaciones pueden tener cuotas diferentes.
2. Si alguna habitación está vacía, su consumo se distribuye entre los inquilinos
   según el acuerdo, sin atribuir al propietario una parte automática.
3. Si un inquilino se va de vacaciones, su cuota final la comunican los inquilinos;
   la aplicación no la deduce de los días de ausencia.
4. Si cambia un ocupante durante el periodo, pueden existir más de cuatro
   destinatarios históricos (aunque nunca más de cuatro simultáneos).
5. Una cuota de 0,00 € es válida y expresa. Distinguir de campo vacío (pendiente).
6. No crear deuda para una habitación vacía ni para un futuro ocupante por el
   mero número de habitación.

### Validación de confirmación

Para confirmar un reparto:
- Todas las cuotas deben ser explícitas (ningún campo vacío).
- La diferencia debe ser exactamente 0,00 €:

```
diferencia = total de la factura − suma de cuotas acordadas
```

- No se redondean céntimos automáticamente. Si falta o sobra, se pide revisar con los inquilinos.

### Ejemplo: caso 4 del documento

Factura de agua de 80,00 €, tres habitaciones ocupadas, una vacía.

Cuotas comunicadas: 30,00 €, 30,00 €, 20,00 €.

- Suma: 80,00 € ✓
- No se crea cuota para la habitación vacía.
- No se imponen cuotas iguales.

### Ejemplo: caso 5 del documento

Factura de 120,00 €, periodo 1-30 de junio. Habitación 1: A [1/6, 16/6) y B [16/6, 1/7). Otras tres ocupadas todo el mes.

Cuotas comunicadas: A: 10,00 €, B: 20,00 €, y 30,00 € para cada una de las otras tres.

- Suma: 10 + 20 + 30 + 30 + 30 = 120,00 € ✓
- Cinco destinatarios históricos, aunque nunca más de cuatro simultáneos.
- No se atribuye la deuda de A a B.

### Ejemplo: caso 16 del documento (vacaciones)

Cuatro habitaciones ocupadas. Habitación 2 ausente 10 días. Factura de 100,00 €.

Cuotas comunicadas: H1: 30,00 €, H2: 10,00 €, H3: 35,00 €, H4: 25,00 €.

- Suma: 100,00 € ✓
- La ausencia no modifica el alquiler ni se calcula automáticamente.
- La cuota de 0,00 € es válida si se comunica expresamente.

---

## 3. Cuadre por factura (sección 8.4)

Dos comprobaciones independientes y visibles:

### 3.1 Cuadre del reparto

```
diferencia de reparto = total de la factura − suma de cuotas acordadas
```

- Debe ser 0,00 € para confirmar el reparto.
- Muestra total factura, suma de cuotas y diferencia.

### 3.2 Cuadre del dinero cobrado

```
cobros netos = suma de aplicaciones en dinero válidos a las cuotas − devoluciones reales
diferencia caja = total de factura − cobros netos
```

- Importe positivo: dinero todavía no recuperado.
- Importe negativo: exceso de cobro que debe revisarse o devolverse.
- Las aplicaciones de fianza se presentan por separado (no son dinero nuevo).

### Ejemplo: caso 17 del documento

Factura de 120,00 €. Cuotas: 50,00 €, 40,00 €, 30,00 € (habitación vacía).

Cobros registrados: 50,00 €, 20,00 €, 30,00 €.

- Reparto: cuadrado (50+40+30 = 120,00 €) ✓
- Dinero cobrado: 100,00 €
- Diferencia caja: 120,00 − 100,00 = 20,00 € (pendiente de la segunda persona)
- Al cobrar los 20,00 € restantes: diferencia caja = 0,00 € ✓

---

## 4. Redondeo exacto (sección 7.3)

### Suministros

- Importes manuales con máximo dos decimales.
- Suma exacta en céntimos. No redondear silenciosamente.
- La suma de cuotas confirmadas debe coincidir con la factura hasta el último céntimo.

### Alquiler

- Cálculo mediante aritmética exacta.
- Redondeo una sola vez al céntimo más próximo, empate hacia arriba.
- Esta regla es independiente del reparto de consumos.

---

## 5. Criterios de caja (sección 14)

### Saldo de caja de la actividad

```
saldo de caja = cobros ordinarios netos de devoluciones − pagos de gastos y suministros
```

Incluye:
- Cobros de alquiler, luz, agua (fecha real del movimiento).
- Pagos a compañías de luz y agua.
- Pagos de comunidad, IBI, reparaciones, etc.

Excluye:
- Fianzas (se muestran por separado).

### Ejemplo: caso 14 del documento

Cargo de diciembre 2026 cobrado en enero 2027:

- A 31/12/2026: aparece como deuda.
- En caja de 2027: aparece como cobro de enero.
- El informe de 2026 después del cobro no borra la deuda que existía a su cierre.

### Ejemplo: caso 10 del documento

Comunidad 60,00 €, IBI 300,00 €, reparación 90,00 €, factura de luz 120,00 € pagada.

- Pagos de gastos/suministros: 570,00 € (si todos en el mismo periodo).
- La factura de luz cuenta una sola vez.
- Comunidad e IBI no crean cuotas a inquilinos.
- Un gasto registrado pero sin pagar no suma a caja pagada.

---

## 6. Fianzas (sección 9)

- Fianza recibida ≠ ingreso de alquiler.
- Devolución de fianza ≠ gasto ordinario.
- No devolver ni aplicar más de lo disponible.
- Aplicación a deuda: reduce la deuda una sola vez, sin movimiento de caja.
- Compensación por daños: única recuperación económica, sin duplicar como cobro.
- No asumir que la fianza cubre automáticamente el último mes.

### Ejemplo: caso 9 del documento

Fianza 400,00 € recibida en enero. Salir en junio: aplicar 100,00 € a cargo de alquiler, devolver 300,00 €.

- Deuda se reduce 100,00 €. Fianza queda en 0,00 €.
- Los 400,00 € de enero no aparecen como renta.
- La aplicación no crea otro cobro bancario.
- La devolución se muestra en movimientos de fianza, fuera de gastos ordinarios.

---

## 7. Llaves (sección 10)

- Catálogo de tipos: portal, vivienda, habitación, buzón.
- Unidades con identificador individual o cantidades sin códigos físicos.
- Impedir devoluciones superiores a lo entregado pendiente.
- Impedir asignación simultánea de una misma unidad identificada.
- Pérdida cierra unidad como perdida.
- Reposición = nueva entrega vinculada al incidente.

### Ejemplo: caso 11 del documento

Entregar 3 llaves, devolver 2, 1 pendiente. Impedir devolver 4.

---

## 8. Gastos del propietario (sección 11)

- Comunidad e IBI nunca generan cuotas a inquilinos.
- Separar existencia del gasto y su pago (pagos parciales admitidos).
- Facturas de luz y agua pagadas por el propietario aparecen en informes de gastos
  sin duplicarlas como gasto manual.
- Avisar antes de guardar un gasto que parezca duplicar una factura de suministro.

---

## 9. Seguridad e integridad (secciones 12, 16, 17, 20)

- Contraseñas con password_hash / password_verify.
- Sesiones con regeneración de ID, cookies HttpOnly y SameSite.
- CSRF en operaciones de cambio.
- Consultas preparadas con PDO.
- Transacciones en cobros, repartos, fianzas, salidas de estancias.
- Bloqueo de concurrencia para evitar sobrepagos y ocupaciones dobles.
- Token de idempotencia para prevenir reenvíos duplicados.
- No borrado en cascada de información económica al archivar una persona.
- Adjuntos con MIME comprobado, fuera de raíz pública.
