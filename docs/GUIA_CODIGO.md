# Guía del Código — Gestión de piso alquilado por habitaciones

Este documento recorre los archivos principales, explica el flujo de una
petición, la relación entre interfaz, servicios y base de datos, y detalla
un alta de factura, un cobro y su conciliación de principio a fin.

---

## 1. Estructura de carpetas

```
public/            -> Única raíz web expuesta al navegador
  index.php        -> Controlador frontal (Front Controller)
  assets/css/      -> Estilos CSS propios
  assets/js/       -> JavaScript nativo
  assets/js/vendor/ -> jQuery local (opcional, con licencia)
app/
  Controllers/     -> Controladores de petición (reciben HTTP, responden)
  Services/        -> Lógica de negocio (reglas, transacciones, validaciones)
  Repositories/    -> Acceso a datos (PDO, consultas preparadas)
  Views/           -> Plantillas HTML (presentación)
  Support/         -> Clases transversales (BD, auth, sesión, seguridad)
config/            -> Configuración (fuera de raíz web)
database/          -> Esquema SQL y semillas
scripts/           -> Scripts de mantenimiento y CLI
storage/           -> Adjuntos, logs, copias de seguridad
tests/             -> Pruebas funcionales (PHP puro)
docs/              -> Documentación del proyecto
```

Solo `public/` debe ser la raíz web. `config/`, `storage/`, `app/` y
`database/` no deben estar expuestos.

---

## 2. Clases de soporte (app/Support/)

### `Database.php`
Conexión PDO singleton. Configura UTF-8mb4, zona horaria Europe/Madrid y
modo de errores por excepción. Todas las consultas usan consultas preparadas.

### `Auth.php`
Autenticación del propietario: login, logout, cambio de contraseña, control
de intentos fallidos y bloqueo temporal. Usa password_hash / password_verify.

### `Session.php`
Gestión de sesión segura: cookies HttpOnly, SameSite=Lax, Secure (si HTTPS),
mensajes flash, token CSRF.

### `Security.php`
Escape HTML (XSS), normalización de importes y fechas en formato español,
nombres internos seguros para adjuntos, token de idempotencia, escape para CSV.

---

## 3. Flujo de una petición HTTP

1. El navegador solicita `public/index.php?r=controlador/accion`.
2. `index.php` carga la configuración, inicia la sesión y la base de datos.
3. Determina el controlador y acción según el parámetro `r` (primer segmento)
   y la subruta (segundo segmento, separado por `/`).
4. Instancia el controlador y llama al método de acción.
5. El controlador usa un Servicio para la lógica de negocio.
6. El Servicio usa un Repositorio para acceder a la base de datos con PDO.
7. El controlador renderiza una Vista (plantilla HTML) dentro del Layout.

---

## 4. Ejemplo: alta de una factura de luz (Fase 4)

### Petición
`POST ?r=facturas/crear`

### Controlador: `FacturaSuministroController::crear()`
1. Valida el token CSRF.
2. Recopila los datos del POST (tipo, proveedor, número, fechas, importe, etc.).
3. Llama a `FacturaSuministroService::crear()`.

### Servicio: `FacturaSuministroService::crear()`
1. Valida que tipo sea luz o agua, proveedor no vacío, fechas válidas.
2. Si se confirma (estado = 'confirmada'), exige número de factura e importe total.
3. Verifica que no exista duplicado (proveedor + tipo + número).
4. Verifica que no haya periodo solapado (o que haya motivo explícito).
5. Llama al repositorio para crear la factura.

### Repositorio: `FacturaSuministroRepository::crear()`
Inserta en la tabla `facturas_suministro` con consulta preparada.

### Respuesta
Redirige a `?r=facturas/ver&id=X` con mensaje flash de éxito o error.

---

## 5. Ejemplo: registrar un cobro con aplicaciones (Fase 3)

### Petición
`POST ?r=cobros/crear` con JSON de aplicaciones.

### Controlador: `CobroController::crear()`
1. Valida CSRF.
2. Decodifica el JSON de aplicaciones.
3. Llama a `CobroService::crearConAplicaciones()`.

### Servicio: `CobroService::crearConAplicaciones()`
1. Valida inquilino, fecha, medio, importe total.
2. Verifica token de idempotencia (no duplicar si ya existe).
3. Para cada aplicación: valida cargo, importe > 0, no excede saldo pendiente.
4. Valida que suma de aplicaciones = importe total recibido.
5. Dentro de una transacción:
   a. Crea el cobro en tabla `cobros`.
   b. Crea cada aplicación en tabla `aplicaciones_cobro`.
   c. Actualiza `importe_cobrado` y `estado` de cada cargo afectado.
6. Hace commit o rollback si falla algo.

### Respuesta
Redirige al detalle del cobro con mensaje flash.

---

## 6. Ejemplo: comprobar pagos por factura (Fase 4)

### Petición
`GET ?r=comprobacion/ver&factura_id=X`

### Controlador: `ComprobacionFacturaController::ver()`
1. Valida autenticación.
2. Llama a `ComprobacionFacturaService::obtenerDetalle()`.

### Servicio: `ComprobacionFacturaService::obtenerDetalle()`
1. Obtiene la factura y su último reparto confirmado.
2. Calcula cuadre del reparto: total factura vs suma de cuotas acordadas.
3. Para cada destinatario, obtiene cobros aplicados a su cuota.
4. Calcula cuadre del dinero: total factura vs cobros netos.
5. Determina estados: "Reparto cuadrado", "Cobros parciales", etc.

### Vista: `comprobacion/ver.php`
Muestra las dos comprobaciones independientes y la tabla por destinatario con
filas desplegables para ver el detalle de cobros.

---

## 7. Relación entre capas

```
Vista (HTML)  ←→  Controlador  ←→  Servicio  ←→  Repositorio  ←→  MySQL
   (presentación)   (orquestación)  (negocio)    (SQL)        (datos)
```

- El **Controlador** maneja la petición HTTP, valida CSRF, llama al Servicio y renderiza la Vista.
- El **Servicio** contiene la lógica de negocio, valida reglas y coordina transacciones.
- El **Repositorio** ejecuta consultas SQL preparadas y devuelve arrays.
- La **Vista** solo presenta datos, sin lógica de negocio.

---

## 8. Transacciones clave

| Operación | Tablas afectadas | Tabla |
|-----------|-----------------|-------|
| Crear estancia | estancias | transaccional (previene solapamientos) |
| Crear cobro + aplicaciones | cobros, aplicaciones_cobro, cargos_alquiler | transaccional |
| Recibir/devolver/aplicar fianza | fianzas, movimientos_fianza, cargos_alquiler | transaccional |
| Crear gasto con pagos | gastos, pagos_gasto | transaccional |
| Crear reparto + cuotas | repartos_factura, cuotas_reparto | transaccional |

---

## 9. Prevención de duplicados

- **Mensualidades**: UK (estancia_id, anio, mes) + verificación doble en transacción.
- **Facturas**: UK (proveedor, tipo, numero_factura normalizado).
- **Cobros**: token_idempotencia único.
- **Mensajes flash**: se consumen una sola vez.
