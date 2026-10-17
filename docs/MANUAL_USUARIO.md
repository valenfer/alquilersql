# Manual de Usuario — Gestión de piso alquilado por habitaciones

Este manual explica, paso a paso, cómo usar la aplicación para gestionar un piso
alquilado por habitaciones. Está diseñado para una persona con pocos
conocimientos de informática. Todos los importes están en euros (€) y las fechas
en formato dd/mm/aaaa.

---

## 1. Primeros pasos

### Acceder a la aplicación

Abre tu navegador y escribe la dirección donde está instalada la aplicación,
por ejemplo:

```
http://localhost/alquiler/public/index.php
```

La primera vez, el sistema te pedirá crear la cuenta del propietario. Rellena:
nombre de usuario, nombre completo, correo electrónico y contraseña (mínimo 8
caracteres). Tras crear la cuenta, inicia sesión con tus credenciales.

### Cerrar sesión

En la parte superior derecha verás tu nombre y un botón "Cerrar sesión". Úsalo
cuando hayas terminado de trabajar.

### Cambiar contraseña

Desde el dashboard, puedes cambiar tu contraseña en cualquier momento. La
contraseña se guarda de forma segura y nadie más puede leerla.

---

## 2. Habitaciones

El piso tiene exactamente cuatro habitaciones, numeradas del 1 al 4. No se
pueden crear más ni borrar una que tenga historial de ocupaciones.

### Ver las habitaciones

Desde el menú principal, selecciona **Habitaciones**. Verás una tabla con el
número, nombre, estado (libre, ocupada o no disponible), renta orientativa e
inquilino actual si la hay.

### Editar una habitación

Haz clic en "Editar" junto a la habitación que quieras modificar. Puedes cambiar
el nombre visible, la descripción, la renta orientativa y el estado.

- **Renta orientativa**: es solo una sugerencia para nuevas ocupaciones. No
  modifica los contratos de inquilinos que ya están viviendo en la habitación.
- **Estado**: marca una habitación como "no disponible temporalmente" si no se
  puede alquilar por un tiempo.

### Borrar una habitación

Solo se puede borrar una habitación que no tenga ningún historial de ocupaciones.
Si una habitación ha tenido inquilinos, no se puede eliminar para conservar los
datos económicos.

---

## 3. Inquilinos

Cada persona que alquila una habitación es un inquilino. Una misma persona puede
tener varias estancias a lo largo del tiempo, pero nunca dos a la vez.

### Dar de alta a un inquilino

Desde el menú, selecciona **Inquilinos** y luego "Nuevo inquilino". Rellena:

- **Tipo de documento**: DNI, NIE u Otro.
- **Número de documento**: se normaliza automáticamente (sin espacios, en
  mayúsculas). Para DNI y NIE se valida que la letra sea correcta.
- **Nombre y apellidos**: obligatorios.
- **Teléfono y correo**: opcionales.
- **Contacto adicional y observaciones**: opcionales.

El número de documento no puede repetirse. Si intentas dar de alta a alguien que
ya existe, el sistema te lo avisará.

### Ver y editar inquilinos

En el listado de inquilinos verás el documento parcialmente oculto (para proteger
los datos), nombre, teléfono, correo y estado. Puedes editar los datos en
cualquier momento.

### Archivar un inquilino

Cuando un inquilino se va definitivamente, no se borra: se **archiva**. Esto
conserva todo su historial de estancias, cobros, fianzas y llaves. Un inquilino
archivado no puede tener nuevas estancias.

---

## 4. Estancias (ocupaciones)

Una estancia es el periodo en que un inquilino ocupa una habitación. Separa la
persona (inquilino) de la ocupación (estancia).

### Crear una estancia

Desde el menú, selecciona **Estancias** y luego "Nueva estancia". Rellena:

- **Inquilino**: selecciona una persona dada de alta.
- **Habitación**: selecciona una de las 4 habitaciones.
- **Fecha de entrada**: el día en que el inquilino entra. Este día cuenta.
- **Fecha de salida prevista**: solo para planificar. No cierra la estancia.
- **Renta mensual**: el importe mensual acordado.
- **Día de vencimiento**: el día del mes en que vence la renta (1-31).
- **Fianza pactada**: el importe de la fianza acordado.

### Reglas importantes

- El intervalo es **[entrada, salida)**: el día de entrada cuenta, el día de
  salida efectiva no cuenta. Esto permite que una persona salga y otra entre el
  mismo día sin solapamiento.
- No se permiten dos ocupaciones solapadas en la misma habitación.
- Una persona no puede tener dos estancias activas a la vez en habitaciones
  distintas.
- El estado se deriva automáticamente: "prevista" (entrada en el futuro),
  "activa" (ya entró y no ha salido), "finalizada" (ya salió).

### Finalizar una estancia

Cuando el inquilino se va, selecciona la estancia y pulsa "Finalizar". Indica
la fecha de salida efectiva. El sistema te mostrará una revisión de deudas,
fianza y llaves antes de confirmar.

---

## 5. Mensualidades y cobros de alquiler

### Generar mensualidades

Desde el menú, selecciona **Mensualidades** y luego "Preparar mensualidades".
Elige el año y mes. El sistema generará un cargo por cada estancia que ocupe al
menos un día en ese mes.

- Si una estancia ocupa todo el mes, paga exactamente una mensualidad.
- Si entra o sale a mitad de mes, se prorratea por días naturales:
  `renta × días ocupados / días naturales del mes`.
- No se genera dos veces la misma mensualidad (es idempotente).

Tras ver la vista previa, pulsa "Confirmar y crear mensualidades".

### Registrar un cobro

Desde el dashboard, pulsa "Registrar un cobro", o desde el menú **Cobros**.

El proceso tiene 3 pasos:

1. **¿Quién paga?**: selecciona el inquilino. Verás sus mensualidades pendientes.
2. **¿Cuánto se recibe?**: indica la cantidad recibida, fecha real y medio
   (efectivo, transferencia, Bizum u otro).
3. **Revisar y guardar**: indica cuánto de cada mensualidad se cubre con ese
   pago. La suma de las aplicaciones debe coincidir exactamente con el importe
   recibido.

Puedes hacer pagos parciales y varios cobros para una misma mensualidad. No se
permite aplicar más del saldo pendiente de una mensualidad.

---

## 6. Facturas de luz y agua

### Registrar una factura

Desde el menú, selecciona **Facturas de luz y agua** y luego "Nueva factura".

Rellena:

- **Tipo**: luz o agua.
- **Proveedor**: la compañía (ej. Iberdrola, Endesa, Canal de Isabel II).
- **Número de factura**: obligatorio al confirmar.
- **Fecha de emisión** y **periodo de consumo** (desde y hasta, como figuran en
  la factura).
- **Importe total**.
- **Fecha de vencimiento** (opcional, si ya se conoce).

Puedes guardar como borrador si no tienes todos los datos todavía. Para
confirmar, necesitas número de factura e importe total.

### Marcar como pagada al proveedor

Cuando el banco te haya cargado la factura, abre la factura y usa la opción
"El banco me ha cobrado esta factura". Indica la fecha real y el importe. Esto
registra una salida de dinero de tu cuenta.

### Reparto manual de consumos (luz y agua)

**Importante**: la aplicación no calcula cómo se reparte la factura. Los
inquilinos hacen sus cálculos fuera de la aplicación y te comunican cuánto le
corresponde a cada uno. Tú introduces esos importes.

Desde la factura, pulsa "Introducir reparto manual". Verás una lista de los
inquilinos que estuvieron en el piso durante ese periodo. Para cada uno, escribe
la cantidad que le corresponde según vuestro acuerdo.

- Una cuota de **0,00 €** es válida si se asigna explícitamente.
- Un campo vacío significa "pendiente de completar".
- Una habitación vacía no genera cuota ni parte automática del propietario.
- La suma de todas las cuotas debe ser **exactamente igual** al importe de la
  factura. Si falta o sobra dinero, el sistema te lo dirá y no te dejará
  confirmar hasta que cuadréis las cantidades con los inquilinos.

Una vez confirmado, el reparto se guarda como una instantánea y no se modifica
automáticamente aunque haya cambios después.

### Entregar la factura a los inquilinos

Desde la factura, puedes registrar entregas a cada destinatario. Indica la fecha
real, el medio (en mano, correo, WhatsApp u otro) y si confirmaron recepción.
Esto es solo una anotación: la aplicación no envía ningún mensaje real.

Puedes usar "Registrar entrega a todos los destinatarios" para crear un registro
individual para cada uno con la misma fecha y medio.

### Comprobar pagos de una factura

Desde la factura o desde Informes, pulsa "Comprobar pagos de esta factura". Verás
dos comprobaciones independientes:

1. **Cuadre del reparto**: total de la factura vs suma de cuotas acordadas.
2. **Cuadre del dinero**: total de la factura vs lo que realmente han pagado los
   inquilinos.

Son independientes: una factura puede tener el reparto cuadrado pero todavía
pendiente de cobro, o viceversa.

---

## 7. Fianzas

La fianza es un depósito que da el inquilino al entrar. **No es ingreso de
alquiler** ni beneficio ordinario.

### Recibir fianza

Desde una estancia activa, puedes registrar la recepción de la fianza. Indica el
importe, fecha y medio de pago.

### Devolver fianza

Cuando el inquilino se va, puedes devolver parte o toda la fianza. No se puede
devolver más de lo que realmente se recibió y aún no se ha aplicado a deudas.

### Aplicar fianza a deuda

Si el inquilino tiene una mensualidad pendiente, puedes aplicar parte de la
fianza para reducir esa deuda. Esto es una **compensación sin movimiento de
caja**: no se crea ningún cobro bancario, solo se reduce la deuda y la fianza
disponible.

### Compensación por daños

Si hay daños documentados que no tienen una deuda previa, puedes registrar una
compensación. Se descuenta de la fianza disponible y se vincula al gasto o
incidencia correspondiente.

---

## 8. Llaves

Cada estancia puede tener llaves entregadas (portal, vivienda, habitación,
buzón). Las llaves pueden tener un identificador individual o gestionarse por
cantidades.

### Entregar llaves

Desde la estancia, pulsa "Entregar llave". Indica el tipo, la cantidad y la
fecha. Si la llave tiene código, selecciona la unidad concreta.

### Devolver llaves

Cuando el inquilino devuelve llaves, indica la cantidad. No se pueden devolver
más de las entregadas pendientes.

### Pérdida y reposición

Si se pierde una llave, regístralo como "pérdida". Esa unidad se cierra como
perdida. Una reposición se registra como una nueva entrega vinculada al incidente
de pérdida.

---

## 9. Gastos del propietario

Registra los gastos que tú pagas como propietario: comunidad, IBI, reparaciones,
averías, seguro, limpieza, etc.

**Importante**: comunidad e IBI nunca generan cuotas a los inquilinos. No se
reparten entre ellos.

### Registrar un gasto

Desde el menú, selecciona **Gastos** y luego "Registrar gasto". Rellena:

- Categoría, descripción, importe, fecha del gasto.
- Proveedor y número de factura (opcionales).
- ¿Se relaciona con una habitación concreta o es gasto general del piso?
- Pagos: si ya lo has pagado, indica el importe, fecha y medio en el mismo
  formulario. Se admiten pagos parciales.

### Pagar un gasto pendiente

Desde el listado de gastos, pulsa "Pagar" junto a un gasto que no esté del todo
pagado. Indica el importe, fecha y medio.

---

## 10. Incidencias (averías y reparaciones)

Registra averías y problemas: descripción, habitación afectada, fecha de aviso,
estado (pendiente, en curso, resuelta) y fecha de resolución.

Una incidencia por sí sola no es un gasto. Solo se convierte en gasto cuando
registras un importe en el módulo de Gastos.

---

## 11. Dashboard

La pantalla de inicio muestra:

- El mes consultado y botones grandes para las acciones más frecuentes.
- Cuatro tarjetas de habitaciones con ocupante, renta, estado y cantidades
  pendientes (alquiler y suministros).
- Indicadores: habitaciones ocupadas/libres, alquiler esperado y cobrado,
  pendientes de alquiler/luz/agua, deuda vencida, facturas sin cargo bancario,
  facturas sin entregar, gastos pagados del mes, fianzas mantenidas, llaves
  pendientes.
- Un gráfico sencillo de ingresos y gastos mensuales con su tabla equivalente.

Cada tarjeta e indicador permite abrir el listado que explica su importe.

---

## 12. Informes

Desde el menú **Informes** puedes:

- **Resumen anual**: elige un año y verás tres bloques separados (dinero
  cobrado/pagado, cargos y deudas a fecha de cierre, fianzas sin caja) más
  estadísticas (ocupación, ingresos por habitación, gastos por concepto,
  evolución mensual).
- **Exportar listados**: descarga en CSV cualquier listado (cobros, facturas,
  gastos, incidencias, llaves, fianzas, estancias, mensualidades) para abrirlo
  en Excel o LibreOffice.

---

## 13. Liquidación de salida

Cuando finaliza una estancia, puedes generar una liquidación imprimible que
muestra:

- Deudas de alquiler pendientes.
- Deudas de suministros (luz y agua) pendientes.
- Fianza recibida, devuelta, aplicada a deudas y compensaciones.
- Saldo a devolver al inquilino (o deuda pendiente si es negativo).
- Llaves pendientes de devolución.

Puedes dejar la liquidación abierta si faltan facturas de suministros por
repartir.

---

## 14. Seguridad y copias

- Solo tú (el propietario) tienes acceso. No hay cuentas para los inquilinos.
- No se guardan contraseñas ni datos financieros en el navegador.
- Los archivos adjuntos (contratos, facturas) se almacenan fuera de la web y
  solo se descargan tras iniciar sesión.
- Se recomienda hacer copias de seguridad periódicas de la base de datos y de
  la carpeta de adjuntos.

---

## 15. Ayuda rápida

| ¿Quieres hacer...? | Ve a... |
|--------------------|---------|
| Dar de alta un inquilino | Menú → Inquilinos → Nuevo inquilino |
| Crear una estancia | Menú → Estancias → Nueva estancia |
| Generar mensualidades | Menú → Mensualidades → Preparar mensualidades |
| Registrar un cobro | Dashboard → Registrar un cobro |
| Añadir una factura de luz/agua | Dashboard → Añadir factura de luz/agua |
| Repartir una factura | Desde la factura → Introducir reparto manual |
| Registrar entrega de factura | Desde la factura → Registrar entrega |
| Comprobar pagos de una factura | Desde la factura → Comprobar pagos |
| Recibir una fianza | Desde una estancia activa → Recibir fianza |
| Entregar/devolver llaves | Menú → Llaves → seleccionar estancia |
| Registrar un gasto | Dashboard → Registrar un gasto |
| Ver el resumen anual | Menú → Informes → Resumen anual |
| Exportar un listado | Menú → Informes → Exportar |

Si tienes dudas sobre cualquier campo, las ayudas visibles junto a cada formulario
te explicarán qué debes rellenar y qué ocurrirá al guardar.
