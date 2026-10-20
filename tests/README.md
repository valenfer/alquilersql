# Pruebas funcionales

Los casos descritos en `docs/PRUEBAS.md` son criterios de prueba; no todos
tienen un script automatizado. Actualmente solo está implementado el caso 1.

## Preparar una base de datos aislada

1. Crear una base de datos exclusiva para pruebas, por ejemplo `alquiler_test`,
   e importar `database/001_schema.sql` y `database/002_seed_base.sql`.
2. Copiar `config/database.example.php` a `config/database.test.php` y
   configurar sus credenciales para esa base de datos.
3. Ejecutar el caso desde la raíz del proyecto:

```bash
php tests/caso_01_mensualidad_cobros_parciales.php
```

El script exige que el nombre de la base de datos termine en `_test` y revierte
los cambios con una transacción. No se debe cambiar esa protección ni apuntar
las pruebas a datos reales.

## Casos automatizados

| Script | Caso | Descripción |
|--------|------|-------------|
| `caso_01_mensualidad_cobros_parciales.php` | 1 | Persistencia de cobros parciales |

El caso 1 es una prueba de persistencia con SQL directo; todavía no sustituye
pruebas automatizadas de los servicios de negocio ni comprueba el rechazo de
un cobro que exceda el saldo. Los casos restantes están documentados como
escenarios pendientes en `docs/PRUEBAS.md`.
