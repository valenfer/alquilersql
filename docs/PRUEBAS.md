# Pruebas Funcionales — Gestión de piso alquilado por habitaciones

Este documento registra los casos de prueba ejecutados, sus resultados y las
comprobaciones pendientes. Las pruebas son scripts PHP ejecutables sin
frameworks de pruebas, ubicados en `tests/`.

---

## 1. Caso 1 — Mensualidad y cobros parciales

**Descripción:** Una estancia tiene una mensualidad de 350,00 €. Registrar
200,00 € y después 150,00 €.

**Criterios de aceptación:**
- [ ] Tras el primero: parcialmente pagado, pendiente 150,00 €.
- [ ] Tras el segundo: pagado, pendiente 0,00 €.
- [ ] Conservar ambos cobros y sus fechas.
- [ ] Impedir un tercer cobro aplicado a ese mismo cargo sin saldo.

**Estado:** Pendiente de ejecución

**Script:** `tests/caso_01_mensualidad_cobros_parciales.php`

---

## 2. Caso 2 — Reparto sencillo y factura identificada

**Descripción:** Con las cuatro habitaciones ocupadas durante todo el periodo,
registrar una factura de luz de 120,00 € e introducir manualmente el acuerdo
de 30,00 € para cada persona.

**Criterios de aceptación:**
- [ ] La aplicación guarda esas cuatro cuotas, sin calcularlas automáticamente.
- [ ] Cobrar 10,00 € a una persona y luego 20,00 € debe liquidar solo su cuota.
- [ ] Ambos cobros muestran el número de esa factura.
- [ ] Las demás cuotas siguen pendientes y el pago al proveedor no cambia.

**Estado:** Pendiente de ejecución

**Script:** `tests/caso_02_reparto_sencillo.php`

---

## 3. Caso 3 — Exactitud de céntimos y reparto descuadrado

**Descripción:** Factura de 100,01 €: introducir cuatro cuotas de 25,00 €.

**Criterios de aceptación:**
- [ ] Mostrar «Falta asignar 0,01 €» y permitir guardar como borrador.
- [ ] No modificar ninguna cuota por decisión de la aplicación.
- [ ] Al introducir 25,01 €, 25,00 €, 25,00 €, 25,00 €, permitir confirmar.
- [ ] Rechazar formatos ambiguos o importes con más de dos decimales.

**Estado:** Pendiente de ejecución

**Script:** `tests/caso_03_exactitud_centimos.php`

---

## 4. Caso 4 — Habitación vacía

**Descripción:** Factura de agua de 80,00 € con tres habitaciones ocupadas y
una vacía todo el periodo. Cuotas: 30,00 €, 30,00 €, 20,00 €.

**Criterios de aceptación:**
- [ ] Registrar exactamente las cuotas comunicadas y confirmar que suman 80,00 €.
- [ ] No crear cuota para la habitación vacía.
- [ ] No crear parte automática del propietario.
- [ ] No imponer igualdad entre las tres cuotas.

**Estado:** Pendiente de ejecución

**Script:** `tests/caso_04_habitacion_vacia.php`

---

## 5. Caso 5 — Cambio de inquilino

**Descripción:** Factura de 120,00 € del 1 al 30 de junio. Habitación 1: A
[1/6, 16/6) y B [16/6, 1/7). Otras tres ocupadas todo el periodo.

**Criterios de aceptación:**
- [ ] Registrar cinco destinatarios históricos con cantidades manuales.
- [ ] Suma 120,00 € sin superar cuatro ocupantes simultáneos.
- [ ] Permitir introducir en julio, con A ya fuera.
- [ ] No atribuir la deuda de A a B.

**Estado:** Pendiente de ejecución

**Script:** `tests/caso_05_cambio_inquilino.php`

---

## 6. Caso 6 — Alquiler parcial y fechas límite

**Descripción:** Renta de 300,00 € en mes de 30 días, estancia [16/6, 1/7):
cargo 150,00 €. Verificar febrero bisiesto, mes completo, salida primer día,
vencimiento día 31.

**Criterios de aceptación:**
- [ ] Febrero bisiesto: 29 días naturales.
- [ ] Mes completo: paga exactamente una mensualidad.
- [ ] Salida el primer día de un mes: no cuenta ese día.
- [ ] Vencimiento día 31: usar último día del mes si no existe.

**Estado:** Pendiente de ejecución

**Script:** `tests/caso_06_alquiler_partial.php`

---

## 7. Caso 7 — Entrega de factura

**Descripción:** Entregar la misma factura a dos personas en días distintos.

**Criterios de aceptación:**
- [ ] Mantener ambos registros de entrega.
- [ ] Señalar los otros destinatarios pendientes.
- [ ] Conservar un recordatorio posterior como nueva entrega.

**Estado:** Pendiente de ejecución

**Script:** `tests/caso_07_entrega_factura.php`

---

## 8. Caso 8 — Transferencia con varios conceptos

**Descripción:** Recibir 390,00 €: alquiler 350,00 €, luz 25,00 €, agua 15,00 €.

**Criterios de aceptación:**
- [ ] Crear un movimiento con tres aplicaciones.
- [ ] Mostrar las dos facturas correspondientes.
- [ ] Suma de caja: 390,00 €, no más.
- [ ] Impedir guardar si la suma del desglose no coincide.

**Estado:** Pendiente de ejecución

**Script:** `tests/caso_08_transferencia_varios_conceptos.php`

---

## 9. Caso 9 — Fianza aplicada a deuda

**Descripción:** Fianza 400,00 € en enero. Salir en junio: aplicar 100,00 € a
cargo de alquiler, devolver 300,00 €.

**Criterios de aceptación:**
- [ ] Deuda se reduce 100,00 €. Fianza queda en 0,00 €.
- [ ] Los 400,00 € de enero no aparecen como renta.
- [ ] La aplicación no crea otro cobro bancario de 100,00 €.
- [ ] La devolución se muestra en movimientos de fianza.

**Estado:** Pendiente de ejecución

**Script:** `tests/caso_09_fianza_aplicada.php`

---

## 10. Caso 10 — Gastos del propietario y no duplicación

**Descripción:** Comunidad 60,00 €, IBI 300,00 €, reparación 90,00 €. Factura
de luz 120,00 € pagada.

**Criterios de aceptación:**
- [ ] Pagos de gastos/suministros suman 570,00 € si todos en el mismo periodo.
- [ ] La factura de luz cuenta una sola vez.
- [ ] Comunidad e IBI no crean cuotas a inquilinos.
- [ ] Gasto sin pagar no suma a caja pagada.

**Estado:** Pendiente de ejecución

**Script:** `tests/caso_10_gastos_propietario.php`

---

## 11. Caso 11 — Llaves y salida

**Descripción:** Entregar tres llaves, devolver dos, una pendiente.

**Criterios de aceptación:**
- [ ] Impedir devolver cuatro llaves.
- [ ] Al finalizar la estancia, mostrar la pendiente.
- [ ] Mantener consultable después de la salida.

**Estado:** Pendiente de ejecución

**Script:** `tests/caso_11_llaves_salida.php`

---

## 12. Caso 12 — Integridad, repetición y concurrencia

**Descripción:** Impedir solapamientos, doble clic, doble envío, solicitudes
simultáneas.

**Criterios de aceptación:**
- [ ] Impedir dos ocupaciones solapadas en misma habitación.
- [ ] Impedir dos estancias simultáneas para misma persona.
- [ ] Admitir salida y entrada el mismo día.
- [ ] Reenviar creación de mensualidad no duplica.
- [ ] Doble clic en guardar cobro no duplica.
- [ ] Dos solicitudes simultáneas no cobran más que la deuda.
- [ ] No aceptar aplicaciones a cargos de otra persona ni facturas anuladas.

**Estado:** Pendiente de ejecución

**Script:** `tests/caso_12_integridad_concurrencia.php`

---

## 13. Caso 13 — Anulación, devolución y rectificación

**Descripción:** Cuota 30,00 € confirmada y cobrada. Rectificar a 25,00 €.

**Criterios de aceptación:**
- [ ] Conservar reparto y cobro originales, registrar rectificación.
- [ ] Generar 5,00 € pendientes de devolver.
- [ ] Al devolverlos, registrar salida y dejar cuota conciliada.
- [ ] Anular cobro por error: conservar rastro, restablecer deuda.

**Estado:** Pendiente de ejecución

**Script:** `tests/caso_13_anulacion_devolucion.php`

---

## 14. Caso 14 — Cierre anual e histórico

**Descripción:** Cargo de diciembre 2026 cobrado en enero 2027.

**Criterios de aceptación:**
- [ ] Aparece como deuda a 31/12/2026.
- [ ] Su cobro aparece en caja de 2027.
- [ ] Informe de 2026 no borra la deuda del cierre.

**Estado:** Pendiente de ejecución

**Script:** `tests/caso_14_cierre_anual.php`

---

## 15. Caso 15 — Seguridad, uso y restauración

**Descripción:** Verificar seguridad, acceso, archivos, teclado, móvil, copias.

**Criterios de aceptación:**
- [ ] Sin sesión no se accede a datos, adjuntos, exportaciones ni copias.
- [ ] HTML se muestra escapado. CSRF sin token se rechaza.
- [ ] Archivo PHP renombrado no se acepta como imagen.
- [ ] Recorrer con teclado: alta, factura, cobro, salida.
- [ ] Verificar móvil, ordenador y zoom 200 %.
- [ ] Exportar listado filtrado: CSV e impresión coinciden.
- [ ] Crear copia y restaurar en base de prueba.

**Estado:** Pendiente de ejecución

**Script:** `tests/caso_15_seguridad_restauracion.php`

---

## 16. Caso 16 — Vacaciones y reparto desigual

**Descripción:** Cuatro habitaciones, habitación 2 ausente 10 días. Factura
100,00 €: H1 30,00 €, H2 10,00 €, H3 35,00 €, H4 25,00 €.

**Criterios de aceptación:**
- [ ] Anotar ausencia sin terminar la estancia ni modificar alquiler.
- [ ] Introducir y conservar las cuatro cuotas exactamente.
- [ ] La aplicación no calcula importes a partir de días.
- [ ] Ausencia añadida después no modifica reparto confirmado.
- [ ] Admitir cuota 0,00 € expresada que sume el total.

**Estado:** Pendiente de ejecución

**Script:** `tests/caso_16_vacaciones_reparto.php`

---

## 17. Caso 17 — Comprobación de ingresos por factura

**Descripción:** Factura 120,00 €, cuotas 50,00 €, 40,00 €, 30,00 €, habitación
vacía. Cobros 50,00 €, 20,00 €, 30,00 €.

**Criterios de aceptación:**
- [ ] Reparto cuadrado a 120,00 €, cobros 100,00 €, diferencia 20,00 €.
- [ ] Segunda persona muestra 20,00 € pendientes.
- [ ] Al cobrar resto, total 120,00 €, diferencia 0,00 €.
- [ ] Transferencia multi-concepto: sumar solo aplicación de luz.
- [ ] Filtrar habitación: conservar cuadre global, subtotal aparte.
- [ ] Saldar con fianza: mostrar compensación separada.

**Estado:** Pendiente de ejecución

**Script:** `tests/caso_17_comprobacion_factura.php`

---

## 18. Caso 18 — Reparto pendiente y documentación

**Descripción:** Factura pagada sin reparto conocido. Documentación técnica.

**Criterios de aceptación:**
- [ ] Mostrar «Reparto pendiente», sin cuotas ficticias.
- [ ] Si no hay inquilinos en el periodo, señalar para decisión del propietario.
- [ ] Código de cobros y conciliación se puede seguir con comentarios.
- [ ] Configuración y generación de documentación coinciden con PHPDoc/JSDoc.

**Estado:** Pendiente de ejecución

**Script:** `tests/caso_18_reparto_pendiente.php`

---

## 2. Cómo ejecutar las pruebas

Las pruebas son scripts PHP independientes que se ejecutan desde la consola:

```bash
php tests/caso_01_mensualidad_cobros_parciales.php
```

Cada script:
1. Conecta a una base de datos de prueba.
2. Crea los datos necesarios.
3. Ejecuta la operación bajo prueba.
4. Verifica los criterios de aceptación.
5. Imprime el resultado (PASS/FAIL) y limpia los datos de prueba.

Se recomienda ejecutarlas sobre una base de datos de prueba, no sobre datos
reales de producción.

---

## 3. Resultados de pruebas ejecutadas

| Caso | Descripción | Estado | Fecha | Observaciones |
|------|-------------|--------|-------|---------------|
| 1 | Mensualidad y cobros parciales | Pendiente | — | — |
| 2 | Reparto sencillo | Pendiente | — | — |
| 3 | Exactitud de céntimos | Pendiente | — | — |
| 4 | Habitación vacía | Pendiente | — | — |
| 5 | Cambio de inquilino | Pendiente | — | — |
| 6 | Alquiler parcial | Pendiente | — | — |
| 7 | Entrega de factura | Pendiente | — | — |
| 8 | Transferencia varios conceptos | Pendiente | — | — |
| 9 | Fianza aplicada a deuda | Pendiente | — | — |
| 10 | Gastos propietario | Pendiente | — | — |
| 11 | Llaves y salida | Pendiente | — | — |
| 12 | Integridad y concurrencia | Pendiente | — | — |
| 13 | Anulación y rectificación | Pendiente | — | — |
| 14 | Cierre anual | Pendiente | — | — |
| 15 | Seguridad y restauración | Pendiente | — | — |
| 16 | Vacaciones y reparto | Pendiente | — | — |
| 17 | Comprobación por factura | Pendiente | — | — |
| 18 | Reparto pendiente | Pendiente | — | — |
