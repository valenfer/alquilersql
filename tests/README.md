# Pruebas Funcionales

Este directorio contiene scripts PHP independientes para ejecutar las pruebas
funcionales descritas en `docs/PRUEBAS.md`.

## Cómo ejecutar

Cada script es independiente y se ejecuta desde la consola:

```bash
php tests/caso_01_mensualidad_cobros_parciales.php
```

## Requisitos

- PHP 8.0+
- MySQL/MariaDB con la base de datos `alquiler` creada
- Archivo `config/database.php` con credenciales válidas (o usar
  `config/database.example.php` como referencia)

## Lista de pruebas

| Script | Caso | Descripción |
|--------|------|-------------|
| caso_01_mensualidad_cobros_parciales.php | 1 | Mensualidad y cobros parciales |
| caso_02_reparto_sencillo.php | 2 | Reparto sencillo y factura identificada |
| caso_03_exactitud_centimos.php | 3 | Exactitud de céntimos |
| caso_04_habitacion_vacia.php | 4 | Habitación vacía |
| caso_05_cambio_inquilino.php | 5 | Cambio de inquilino |
| caso_06_alquiler_partial.php | 6 | Alquiler parcial y fechas límite |
| caso_07_entrega_factura.php | 7 | Entrega de factura |
| caso_08_transferencia_varios_conceptos.php | 8 | Transferencia con varios conceptos |
| caso_09_fianza_aplicada.php | 9 | Fianza aplicada a deuda |
| caso_10_gastos_propietario.php | 10 | Gastos del propietario |
| caso_11_llaves_salida.php | 11 | Llaves y salida |
| caso_12_integridad_concurrencia.php | 12 | Integridad, repetición y concurrencia |
| caso_13_anulacion_devolucion.php | 13 | Anulación, devolución y rectificación |
| caso_14_cierre_anual.php | 14 | Cierre anual e histórico |
| caso_15_seguridad_restauracion.php | 15 | Seguridad, uso y restauración |
| caso_16_vacaciones_reparto.php | 16 | Vacaciones y reparto desigual |
| caso_17_comprobacion_factura.php | 17 | Comprobación de ingresos por factura |
| caso_18_reparto_pendiente.php | 18 | Reparto pendiente |

> **Nota:** La mayoría de scripts aún están por implementar. El caso 1 está
> completo como ejemplo. Los demás se crearán siguiendo el mismo patrón.

## Advertencia

Ejecutar las pruebas sobre una base de datos de prueba, no sobre datos reales
de producción. Los scripts crean y eliminan datos de prueba.
