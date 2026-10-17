# PROMPT MAESTRO — Aplicación web para gestionar un piso alquilado por habitaciones

> Instrucciones de uso: entrega este archivo completo a la IA de programación y pídele que construya el proyecto siguiendo sus requisitos. El contenido a partir de «Rol y misión» es el prompt. No se solicita una maqueta: se solicita una aplicación funcional con persistencia real en MySQL.

> Revisión de requisitos: los inquilinos calculan y acuerdan el reparto de luz y agua; el propietario introduce las cantidades resultantes. Las habitaciones vacías y las ausencias temporales se tienen en cuenta en ese acuerdo. El reparto puede ser desigual y debe existir una comprobación de cuotas y cobros por factura. Estas reglas sustituyen cualquier reparto automático o asignación de consumos de habitaciones vacías al propietario.

## 1. Rol y misión

Actúa como arquitecto de software, programador full stack sénior y diseñador de interfaces accesibles. Desarrolla una aplicación web completa para que el propietario gestione un único piso con cuatro habitaciones, identificadas permanentemente como **Habitación 1, Habitación 2, Habitación 3 y Habitación 4**.

La aplicación debe gestionar inquilinos, ocupaciones, mensualidades, cobros, facturas de luz y agua, reparto de suministros, fianzas, llaves, gastos del propietario, incidencias, estadísticas e informes anuales.

El usuario principal será una persona mayor con pocos conocimientos de informática. La facilidad de uso es un requisito central: debe entender qué debe introducir, qué significa cada importe y qué ocurrirá al guardar.

Construye una solución mantenible, segura y sencilla de instalar. No entregues únicamente una explicación, un diseño visual, pseudocódigo ni botones sin funcionamiento. Implementa el código, la base de datos, la documentación y las comprobaciones necesarias.

### Forma de trabajar

- Lee todo este documento antes de comenzar.
- Revisa el contenido existente de la carpeta del proyecto y respeta el trabajo previo que sea compatible.
- Crea primero un plan breve y el modelo de datos; después continúa con la implementación sin detenerte en el plan.
- Resuelve decisiones técnicas menores aplicando los criterios de este documento y anótalas en `docs/DECISIONES.md`.
- Pregunta solamente si hay una contradicción que impida avanzar con seguridad o una decisión económica sin valor predeterminado definido aquí.
- No sustituyas requisitos por aproximaciones visuales ni afirmes que algo está probado si no has ejecutado la comprobación.
- Si el entorno no permite ejecutar PHP o MySQL, entrega igualmente los archivos completos, indica la limitación y deja instrucciones reproducibles para probarlos.
- Trabaja por fases y conserva el avance en `docs/ESTADO_PROYECTO.md`, con tareas completadas, pendientes y siguiente paso, para poder continuar si se agota el contexto.

## 2. Tecnología y restricciones obligatorias

- **HTML5, CSS propio, JavaScript y PHP puro.**
- **MySQL** como base de datos, con InnoDB, claves foráneas y codificación `utf8mb4`.
- Se admite **jQuery**, aunque es preferible JavaScript nativo cuando resulte sencillo.
- No usar Laravel, Symfony, CodeIgniter ni ningún framework PHP.
- No usar React, Vue, Angular, Next.js ni frameworks de frontend.
- No usar Bootstrap, Tailwind ni frameworks CSS. Diseñar los estilos propios.
- No usar un ORM ni convertir Node.js, npm o un proceso de compilación en requisitos de instalación.
- Usar PDO con consultas preparadas para acceder a MySQL.
- Usar PHP con tipos y características compatibles con la versión mínima documentada en el proyecto. Indicar y comprobar los requisitos en la instalación.
- Servidor compatible con Apache/PHP/MySQL, incluido un entorno local tipo XAMPP. Documentar también el arranque con el servidor de desarrollo de PHP cuando sea posible.
- No depender de servicios externos, CDNs, APIs de pago ni conexión permanente a Internet para las funciones de gestión.
- Si utilizas jQuery, incluir su archivo localmente y documentar su procedencia y licencia.
- Para los gráficos, priorizar HTML/CSS/SVG propios y acompañarlos de tablas. No introducir bibliotecas innecesarias.
- Aplicación y mensajes en **español de España**; moneda **euro**; presentación de fechas `dd/mm/aaaa` y cantidades `1.234,56 €`.
- Guardar las fechas de negocio como `DATE` y los importes de forma exacta. Usar la zona `Europe/Madrid` para los instantes y fechas calculadas de la aplicación.
- No usar `localStorage` ni archivos JSON como sustitutos de MySQL. Solo podrán guardar preferencias visuales no sensibles.
- Mantener todos los datos y adjuntos bajo control del propietario, sin telemetría ni envío a terceros.

## 3. Alcance y reglas generales

1. Un propietario administra un piso y cuatro habitaciones fijas. No se necesita una plataforma multiusuario para inmobiliarias.
2. Cada habitación admite un único inquilino simultáneamente; el máximo simultáneo son cuatro personas.
3. Una misma persona puede tener varias estancias a lo largo del tiempo, pero no ocupaciones simultáneas en distintas habitaciones.
4. El propietario registra manualmente los cobros a los inquilinos y los pagos de gastos. No se integra banca electrónica.
5. Los inquilinos abonan el alquiler y los importes que les correspondan de luz y agua.
6. **La comunidad y el IBI corresponden al propietario y nunca se reparten entre los inquilinos.**
7. Las reparaciones, averías y demás gastos se registran inicialmente como gastos del propietario. Cualquier repercusión o compensación requiere una operación expresa y documentada; no se decide automáticamente.
8. No se necesitan cuentas de acceso para los inquilinos, pasarela de pago, mensajería automática ni envío de facturas por correo o WhatsApp.
9. Los registros de entrega de facturas y llaves son anotaciones realizadas por el propietario. Registrar una entrega no significa enviar realmente un mensaje o documento.
10. Las reglas económicas configurables deben mostrarse claramente y conservar su historial. Los cambios no pueden reescribir importes ya confirmados.
11. La aplicación es una herramienta de gestión interna. No calcular obligaciones fiscales, recargos, actualizaciones legales de renta o trámites administrativos de fianzas de forma automática.
12. **Los inquilinos calculan el reparto de luz y agua fuera de la aplicación. El propietario registra las cantidades que le comunican, aunque sean desiguales. La aplicación valida las sumas y registra los cobros; no decide el reparto ni lo calcula por días o porcentajes.**
13. Cuando alguna habitación esté vacía, los consumos se reparten entre los inquilinos de las habitaciones ocupadas según su acuerdo. Los días de vacaciones o ausencia de una persona pueden hacer que su parte de consumo se distribuya entre el resto. No imputar esas partes al propietario por defecto.

## 4. Habitaciones y situación del piso

Crear inicialmente las cuatro habitaciones, con números únicos del 1 al 4. No permitir borrar una habitación que tenga historial ni crear una quinta desde la interfaz ordinaria.

Cada habitación debe mostrar:

- Número y nombre visible, por ejemplo «Habitación 2».
- Descripción y observaciones opcionales.
- Renta orientativa para nuevas ocupaciones, sin modificar contratos existentes.
- Situación actual: libre, ocupada o no disponible temporalmente.
- Inquilino actual, fecha de entrada y renta acordada.
- Cobros pendientes, separando alquiler, luz y agua.
- Historial de ocupantes con acceso a cada estancia.

La disponibilidad temporal se registra por fechas y no puede ocultar una ocupación existente. Una habitación vacía no genera una cuota de suministro propia ni una parte automática a cargo del propietario: los inquilinos calculan cómo distribuir esos consumos entre las habitaciones ocupadas y el propietario registra el resultado.

## 5. Inquilinos, estancias e historial

### 5.1 Ficha personal

Registrar:

- Nombre y apellidos en campos claros.
- Tipo de documento: DNI, NIE u otro.
- Número de documento, normalizado para evitar duplicados por espacios o mayúsculas.
- Teléfono y correo electrónico opcionales.
- Datos de contacto adicionales y observaciones opcionales.
- Estado activo/archivado, sin eliminar su historial.

Validar formato y letra de DNI/NIE cuando proceda. Admitir otros documentos sin aplicarles reglas de DNI. No mostrar el documento completo innecesariamente en el dashboard ni en listados generales.

### 5.2 Estancia u ocupación

Separar la persona de cada estancia. Cada estancia debe conservar:

- Inquilino.
- Habitación del 1 al 4.
- Fecha de entrada.
- Fecha de salida prevista, opcional.
- Fecha de salida efectiva, opcional mientras siga ocupada.
- Renta mensual acordada y fecha de vencimiento habitual.
- Fianza pactada, movimientos de fianza y saldo pendiente de devolver.
- Referencia o notas del acuerdo y contrato adjunto opcional.
- Estado: prevista, activa o finalizada, derivado coherentemente de fechas y situación.

Usar intervalos **[entrada, salida)**: el día de entrada cuenta y el día de salida efectiva no cuenta. Explicar esto con lenguaje sencillo en las ayudas. Permitir que una persona salga y otra entre en la misma habitación el mismo día sin solapamiento.

La salida prevista sirve para planificar y no cierra automáticamente una estancia: solo la salida efectiva lo hace. Impedir ocupaciones confirmadas que se solapen. Validar también dentro de una transacción para impedir conflictos por dos formularios abiertos al mismo tiempo.

Al finalizar una estancia:

- Mantener deudas y cobros vinculados al inquilino que los originó.
- Permitir registrar después cobros, facturas tardías y devoluciones pendientes.
- Mostrar una revisión de deudas, fianza y llaves antes de confirmar.
- Permitir cerrar la estancia con asuntos pendientes si el propietario lo confirma expresamente; conservar avisos visibles.
- No traspasar automáticamente nada al siguiente ocupante de la habitación.

Si se corrige una fecha histórica que afecta a cargos o repartos confirmados, mostrar los efectos y exigir una rectificación explícita. No recalcular silenciosamente registros confirmados.

### 5.3 Vacaciones y ausencias temporales

Permitir anotar, de forma opcional, ausencias temporales de una persona durante su estancia: fecha inicial, fecha final y observaciones. Estas anotaciones sirven de contexto para el reparto acordado y pueden consultarse desde la factura cuyo periodo coincida.

- Una ausencia no termina la estancia ni libera la habitación.
- No suspende ni reduce automáticamente el alquiler y no altera la ocupación contractual utilizada en las estadísticas.
- No modifica ni calcula cuotas de luz o agua. El propietario introduce el reparto que hayan calculado los inquilinos considerando esas ausencias.
- Puede haber una cuota menor o incluso de 0,00 € para una persona ausente, según lo acordado. El resto de cuotas puede ser desigual.
- Distinguir claramente entre una habitación vacía sin inquilino y una habitación cuyo inquilino está temporalmente ausente.

## 6. Alquiler y mensualidades

### 6.1 Cargos de alquiler

Crear un cargo de alquiler por estancia y mes que identifique:

- Año y mes al que corresponde.
- Periodo concreto cubierto.
- Importe esperado.
- Fecha de vencimiento.
- Cobrado, pendiente y estado de pago.
- Observaciones y ajustes documentados.

Estados calculados: pendiente, parcialmente pagado y pagado. Mostrar «vencido» como condición adicional cuando ha pasado el vencimiento y queda saldo.

Impedir generar dos veces la misma mensualidad para una estancia. La generación repetida debe ser idempotente. No depender de un cron obligatorio: incluir una acción visible «Preparar mensualidades» con selección de mes y vista previa. Puede añadirse un script CLI opcional que use la misma lógica.

### 6.2 Renta y meses incompletos

- La renta se fija por estancia y puede cambiar mediante un historial con fecha de efecto.
- Los cambios ordinarios entran en vigor el primer día de un mes; un cambio excepcional a mitad de mes se realiza mediante un ajuste explícito y explicado.
- Conservar el importe que se aplicó a cada cargo; no consultar solo la renta actual para reconstruir el pasado.
- Por defecto, prorratear el primer y último mes por días naturales: renta mensual × días ocupados del mes / días naturales del mes.
- Una estancia que ocupa todo el mes paga exactamente una mensualidad, incluidos febrero y los meses de 30 o 31 días.
- La fecha de salida efectiva no se incluye en los días ocupados.
- Admitir que el propietario elija una mensualidad completa o un importe pactado diferente para un mes incompleto, con vista previa y motivo guardado.
- Redondear el cargo final a céntimos según las reglas de importes de este documento.
- Si la fecha de vencimiento configurada no existe en un mes, usar su último día.

### 6.3 Cobro de alquiler

El propietario debe poder introducir un cobro cada vez que lo reciba:

- Inquilino y estancia.
- Mes y año del alquiler.
- Cantidad recibida.
- Fecha real del cobro.
- Medio: efectivo, transferencia, Bizum u otro.
- Referencia y observaciones opcionales.

Permitir pagos parciales y varios cobros para una mensualidad. También permitir adelantar una mensualidad futura creada expresamente, sin crear un saldo genérico sin destino.

Al seleccionar al inquilino, mostrar sus mensualidades pendientes y una explicación como «De los 350,00 € de octubre quedan 150,00 € por cobrar».

## 7. Facturas de electricidad y agua

### 7.1 Registro de facturas

Gestionar separadamente **luz** y **agua**. Cada factura debe tener:

- Tipo de suministro.
- Proveedor o compañía.
- **Número de factura**, obligatorio al confirmar.
- Fecha de emisión.
- Periodo de consumo: fecha inicial y final inclusivas que figuran en la factura.
- **Importe total**.
- Fecha de vencimiento o fecha prevista del cargo bancario, si se conoce.
- **Fecha real de cobro por el banco**, obligatoria al marcarla como pagada al proveedor.
- Importe realmente pagado al proveedor y estado de pago, derivados de los movimientos asociados.
- Documento adjunto opcional en PDF, JPG o PNG.
- Observaciones.
- Estado: borrador, confirmada o anulada; el pago al proveedor y el cobro a inquilinos se muestran como estados independientes.

En la interfaz emplear el texto «El banco me ha cargado esta factura» para que el propietario comprenda que se trata de una salida de dinero de su cuenta. Si se marca, pedir fecha real e importe y registrar el pago al proveedor en la misma transacción. No crear una segunda salida de dinero al volver a editar la factura.

Se pueden guardar borradores sin todos los datos. Al confirmar, exigir los datos económicos y de periodo necesarios. Si todavía no existe cargo bancario, mostrar «Pendiente de cargo bancario» y no inventar una fecha.

Permitir varios pagos al proveedor para una factura si hay un pago parcial. El campo visible de fecha de cargo bancario debe apoyarse en esos movimientos: mostrar la primera fecha y el historial cuando haya varios, y la fecha de liquidación cuando esté completada.

Evitar duplicados por proveedor, suministro y número de factura normalizado. Avisar de periodos solapados con facturas del mismo suministro; permitirlos solo con motivo explícito, ya que puede haber ajustes de la compañía.

### 7.2 Reparto entre ocupantes

**El único modo de reparto del alcance inicial es la introducción manual del acuerdo de los inquilinos. No implementar una calculadora de consumo por días, porcentajes o partes iguales ni precargar importes calculados.**

Los inquilinos hacen sus cálculos y comunican cuánto corresponde a cada uno. El propietario introduce esos importes por factura. La aplicación comprueba su coherencia y permite seguir lo efectivamente cobrado.

Reglas del acuerdo que debe admitir la aplicación:

1. Las cuatro habitaciones pueden tener cuotas diferentes.
2. Si alguna habitación está vacía durante todo o parte del periodo, su consumo se distribuye entre los inquilinos según el acuerdo comunicado, sin atribuir al propietario una parte automática.
3. Si un inquilino se va de vacaciones unos días, el consumo de esos días puede distribuirse entre el resto. Su cuota final la comunican los inquilinos; la aplicación no la deduce de los días de ausencia.
4. Si cambia un ocupante durante el periodo, pueden existir más de cuatro destinatarios históricos en una factura, aunque nunca haya más de cuatro ocupaciones simultáneas. Registrar la cantidad acordada de cada estancia sin imponer una fórmula de prorrateo.
5. Una factura puede asignar a una persona 0,00 € de forma expresa. Distinguir ese cero confirmado de un campo vacío que todavía está pendiente de completar.
6. No crear una deuda para una habitación vacía ni para un futuro ocupante por el mero número de habitación.

La pantalla de reparto debe mostrar:

- Suministro, proveedor, número de factura, periodo e importe total.
- Inquilinos y estancias del periodo, con su habitación y fechas de entrada/salida como referencia.
- Ausencias anotadas que coincidan con el periodo, sin recalcular importes.
- Una caja grande por destinatario: «Cantidad que le corresponde según vuestro acuerdo».
- Un campo opcional de observaciones del reparto, por ejemplo «Habitación 4 vacía; habitación 2 de vacaciones del 5 al 12».
- Fecha en que se registra el acuerdo, autor y revisión del reparto.
- Total asignado, total de la factura y diferencia pendiente de asignar, actualizados al escribir y validados de nuevo en servidor.

`diferencia de reparto = total de la factura − suma de las cuotas acordadas de los inquilinos`

Permitir guardar un reparto incompleto como borrador. Para confirmarlo, la diferencia debe ser exactamente 0,00 € y los importes deben ser explícitos y válidos. No repartir ni corregir los céntimos automáticamente. Si falta o sobra dinero, mostrar cuánto y pedir que se revisen las cantidades con los inquilinos.

Registrar la factura y su cargo bancario no exige que ya se conozca el acuerdo de reparto: debe poder quedar «Reparto pendiente» sin inventar cuotas ni deudas individuales. Si no hubo ningún inquilino durante todo el periodo, señalar ese caso y mantener el reparto pendiente de una decisión expresa del propietario; no inventar destinatarios ni trasladar importes a futuros ocupantes.

Guardar el reparto confirmado como una instantánea con importes, destinatarios, fecha y observaciones. Una nueva estancia, unas vacaciones anotadas o una corrección posterior no deben cambiar automáticamente las cuotas ya confirmadas.

Reglas adicionales:

- No generar deudas a una persona que no ocupó una habitación en ese periodo, salvo ajuste manual explícito y justificado.
- Las facturas tardías deben usar los ocupantes históricos del periodo, aunque ya hayan salido del piso.
- Un borrador puede editarse y volver a validarse. Un reparto confirmado solo puede rectificarse mediante una operación trazable que preserve el anterior.
- Si existen cobros aplicados o entregas registradas, no permitir sustituir sin más las cuotas: presentar diferencias, preservar vínculos y pedir confirmación de la rectificación.
- Al aumentar una cuota, dejar la diferencia pendiente; al reducirla por debajo de lo ya cobrado, crear un importe a devolver vinculado a esa cuota. No perder ni reasignar cobros.
- Las facturas rectificativas o abonos se vinculan a la factura de origen, nunca se introducen como un pago negativo arbitrario. Mantener el historial y reflejar los reembolsos cuando realmente se produzcan.

### 7.3 Redondeo exacto

Trabajar con céntimos enteros en la lógica monetaria y `DECIMAL(12,2)` en MySQL, o una estrategia equivalente exacta documentada. No usar `float` binario para sumar o repartir dinero.

En suministros, aceptar importes manuales con un máximo de dos decimales y comprobar la suma exacta en céntimos. No redondear silenciosamente un importe con más decimales ni repartir diferencias. La suma de cuotas confirmadas de los inquilinos debe coincidir con la factura hasta el último céntimo.

El prorrateo del alquiler descrito en la sección 6 es independiente: calcularlo mediante aritmética exacta y redondear una sola vez el cargo final al céntimo más próximo, elevando el empate de medio céntimo. Esta regla de alquiler no se aplica al reparto de consumos.

### 7.4 Entrega de facturas a los inquilinos

Cada factura debe tener un **historial de entregas por destinatario**, no una única fecha global.

Registrar por entrega:

- Factura.
- Inquilino y estancia destinataria.
- Fecha de entrega real.
- Medio: en mano, correo, WhatsApp u otro, como anotación manual.
- Observaciones y confirmación de recepción opcional.

Mostrar quién la ha recibido, quién sigue pendiente y la primera y última fecha de entrega. Conservar varias entregas o recordatorios sin sobrescribir las anteriores.

Incluir una acción «Registrar entrega a todos los destinatarios» con fecha y medio, que cree un registro individual para cada destinatario seleccionado. Esta acción solo registra, no envía nada.

Si se rectifica una factura entregada, señalar que hay información actualizada pendiente de entregar y permitir registrar la nueva entrega sin borrar la anterior.

## 8. Cobros de luz y agua y registro unificado de cobros

### 8.1 Datos obligatorios de un cobro de suministro

Al introducir un pago recibido de un inquilino por luz o agua, exigir:

- Inquilino y estancia.
- Concepto: luz o agua.
- **Factura concreta y su número visible**.
- Cuota de ese destinatario asociada a la factura.
- **Cantidad que paga**.
- Fecha real del cobro.
- Medio de pago.
- Observaciones y justificante opcionales.

El selector debe mostrar, por ejemplo: «Luz · FAC-2026-018 · 01/09–30/09 · Le corresponde: 30,00 € · Pendiente: 20,00 €».

No permitir un cobro de suministro sin factura, asociarlo solo a un texto libre ni imputarlo a una cuota de otro inquilino. Permitir cobrar facturas de antiguos ocupantes.

### 8.2 Cobros parciales y pagos que cubren varios conceptos

- Permitir varios cobros parciales contra una misma cuota.
- Diferenciar el **movimiento de dinero recibido** y sus **aplicaciones a deudas concretas**.
- Una transferencia puede cubrir alquiler, luz y agua mediante un único movimiento con varias líneas de aplicación.
- En ese caso, obligar a desglosar claramente cuánto corresponde a cada mensualidad o factura.
- La suma de aplicaciones debe coincidir con el importe recibido. Si queda una diferencia, impedir guardar hasta asignarla correctamente.
- Impedir aplicar un importe superior al saldo pendiente de un cargo. No crear créditos sin destino automáticamente.
- Se puede repartir un cobro entre varios cargos de una misma estancia; para pagos que abarcan estancias distintas del mismo inquilino, exigir selección expresa y conservar cada vínculo. Nunca mezclar inquilinos en un mismo cobro.
- No mezclar fianzas con cobros ordinarios en este formulario: usar el módulo específico de fianzas.
- Mostrar antes de guardar un resumen con total recibido, desglose y deuda que quedará pendiente.
- Mostrar el número de factura tanto en el registro de cobro como en el historial y en el justificante.
- Usar un identificador interno único para cada movimiento y conservar la referencia bancaria opcional sin asumir que siempre será única.

### 8.3 Correcciones y devoluciones

No borrar movimientos confirmados de forma definitiva ni permitir editar sus importes sin rastro. Las correcciones deben dejar la operación original, el motivo y la operación correctora vinculada.

Una anulación por error de registro no equivale a una devolución real de dinero. Implementar y mostrar acciones diferentes para ambos casos. Si se devuelve un cobro real, registrar fecha e importe de salida y actualizar de forma coherente el saldo del cargo afectado.

Tras cualquier corrección, mantener la igualdad entre cobros efectivos, aplicaciones, devoluciones y saldos. Recalcular estados desde los movimientos válidos, sin modificarlos a mano.

### 8.4 Listado y comprobación de cobros por factura de consumo

Incluir una vista esencial titulada **«Comprobar pagos de esta factura»**, accesible desde cada factura de luz/agua y desde Informes. Debe permitir verificar que la suma de lo ingresado por los inquilinos corresponde al importe de la factura, además de comprobar el acuerdo de reparto.

Cabecera: suministro, proveedor, número de factura, periodo, importe total, estado y fechas de pago al proveedor. No mezclar facturas del mismo periodo ni los pagos al proveedor con los cobros de inquilinos.

Tabla por destinatario, con estas columnas:

| Habitación | Inquilino y estancia | Cuota acordada | Cobrado en dinero | Compensado con fianza | Pendiente | A devolver | Estado |
| --- | --- | ---: | ---: | ---: | ---: | ---: | --- |
| 1–4 | Nombre y referencia de estancia | Importe manual | Cobros netos vinculados | Aplicaciones expresas | Saldo de deuda | Devolución pendiente | Texto claro |

Al desplegar una fila, mostrar todos los cobros aplicados a esa cuota: fecha, cantidad aplicada a esa factura, medio de pago, referencia del movimiento y sus eventuales correcciones/devoluciones. Si una transferencia cubre varios conceptos, contar únicamente la parte aplicada a esta factura, no el total de la transferencia.

Mostrar dos comprobaciones independientes y visibles:

1. **Cuadre del reparto:** total factura, suma de cuotas acordadas y diferencia por asignar.
2. **Cuadre del dinero cobrado:** total factura, suma de cobros efectivos de todos sus inquilinos menos devoluciones reales y diferencia entre factura e ingresos.

`cobros netos de esta factura = suma de aplicaciones en dinero de cobros válidos a sus cuotas − devoluciones de dinero efectivamente realizadas sobre esas aplicaciones`

`diferencia entre factura e ingresos = total de factura vigente − cobros netos de esta factura`

Un importe positivo indica dinero todavía no recuperado en efectivo; un importe negativo indica exceso de cobro que debe revisarse o devolverse. Si existen aplicaciones de fianza, presentarlas separadamente: pueden liquidar la deuda sin ser un nuevo ingreso de dinero. Explicar el motivo de la diferencia de caja aunque la deuda esté saldada.

Incluir una fila de totales y estados legibles como «Reparto pendiente», «Reparto cuadrado», «Cobros parciales», «Factura cobrada íntegramente en dinero», «Deuda saldada con compensación» o «Importe pendiente de devolver». No mostrar «Factura cobrada íntegramente en dinero» solo porque las cuotas sumen el total.

Permitir filtrar el detalle por habitación, inquilino, fecha de cobro y estado, e imprimir/exportar la comprobación. Los indicadores globales de cuadre deben usar todos los destinatarios y cobros de la factura dentro de la fecha de corte seleccionada. Si se filtran filas, mostrar aparte el subtotal filtrado y aclarar que ese subconjunto no tiene por qué sumar el total de la factura.

Ejemplo: factura de luz de 120,00 €, cuotas acordadas de 50,00 €, 40,00 € y 30,00 € con una habitación vacía. Si se han cobrado 50,00 €, 20,00 € y 30,00 €, el reparto está cuadrado, los cobros suman 100,00 € y quedan 20,00 € por recuperar de la segunda persona.

## 9. Fianzas y liquidación al salir

Por cada estancia registrar:

- Importe de fianza pactado.
- Cantidades realmente recibidas, con fecha y medio de pago; admitir entregas parciales.
- Cantidades devueltas, con fecha y medio de pago; admitir devoluciones parciales.
- Retenciones o aplicaciones justificadas, con fecha, importe y motivo.
- Saldo pendiente de devolver o liquidar.
- Notas y justificantes opcionales.

Reglas:

- Una fianza recibida **no es ingreso de alquiler** ni beneficio ordinario.
- Una devolución de fianza no es un gasto ordinario del piso.
- No permitir devolver o aplicar más fianza de la realmente disponible.
- Si se aplica una parte a una deuda existente, enlazarla al cargo y reducir la deuda una sola vez. No inventar una transferencia bancaria.
- Mostrar esa aplicación como compensación sin movimiento de efectivo y separarla de los cobros en dinero.
- Si se usa una parte por daños documentados que no tienen cargo previo, registrar una única recuperación económica y vincularla al gasto o incidencia correspondiente. No duplicarla además como cobro.
- La reparación real se registra como gasto cuando corresponda y su pago como salida de dinero; la aplicación de fianza se registra aparte como compensación.
- Las compensaciones no forman parte del flujo de caja del momento en que se acuerdan; el dinero de la fianza ya se recibió antes.
- No asumir que la fianza cubre automáticamente el último mes.
- «Fianza recibida del inquilino» es distinta de un eventual depósito ante una administración. Ese trámite no se presume realizado ni es una automatización del alcance inicial.

Al finalizar, generar una liquidación imprimible con deudas por concepto, fianza recibida, devoluciones previas, compensaciones, saldo y llaves pendientes. Permitir dejar la liquidación abierta si faltan facturas de suministros.

## 10. Llaves entregadas y devueltas

Crear un catálogo de tipos de llave, por ejemplo portal, vivienda, habitación y buzón. Gestionar unidades con identificador o código individual cuando exista; si no, gestionar cantidades sin inventar códigos físicos.

Registrar:

- Estancia e inquilino.
- Tipo de llave e identificación, si la tiene.
- Cantidad entregada y fecha de entrega.
- Cantidad devuelta y fecha de devolución.
- Entregas y devoluciones parciales.
- Pérdida, reposición o incidencia, con fecha y observaciones.
- Cantidad pendiente de devolver.

Impedir devoluciones superiores a lo entregado pendiente y la asignación simultánea de una misma unidad identificada. Una pérdida cierra esa unidad como perdida, no como devuelta. Una reposición se registra como nueva entrega vinculada al incidente.

Mostrar avisos de llaves pendientes en la ficha y al cerrar una estancia. Incluir justificantes imprimibles de entrega y devolución con espacio para firma, sin afirmar que existe firma digital.

## 11. Gastos del propietario e incidencias

Registrar gastos por conceptos:

- Comunidad.
- IBI.
- Reparaciones.
- Averías y mantenimiento.
- Seguro.
- Limpieza u otros servicios.
- Otros conceptos configurables.

Cada gasto debe recoger concepto, descripción, importe, fecha del gasto o documento, proveedor opcional, número de factura o justificante opcional, relación con todo el piso o una habitación, observaciones y adjunto opcional.

Separar la existencia del gasto y su pago. Registrar cada pago con importe, fecha real y medio; admitir pagos parciales. Si se introduce un gasto ya pagado, permitir registrarlo todo en un único formulario y una transacción.

Permitir gastos generales sin vincular a un inquilino. **Comunidad e IBI no generan cargos a los inquilinos.** No incluirlos en un reparto de luz o agua ni prorratearlos automáticamente por habitación.

Las facturas de luz y agua pagadas por el propietario son salidas vinculadas a su propio módulo. Deben aparecer en los informes de gastos sin duplicarlas como un nuevo gasto manual. Avisar antes de guardar un gasto manual que parezca duplicar una factura de suministro.

Para averías, permitir una incidencia sencilla con descripción, habitación o zona, fecha de aviso, estado pendiente/en curso/resuelta, fecha de resolución y gastos relacionados. La incidencia por sí sola no es un gasto hasta que exista un importe registrado.

Permitir recordar vencimientos recurrentes de comunidad e IBI sin marcarlos como pagados ni generar duplicados automáticamente.

## 12. Dashboard y navegación

### 12.1 Pantalla de inicio

Mostrar primero el mes consultado y botones grandes:

- **Registrar un cobro**.
- **Añadir factura de luz**.
- **Añadir factura de agua**.
- **Registrar un gasto**.
- **Dar de alta a un inquilino**.

Mostrar cuatro tarjetas de habitaciones con ocupante, renta, situación y cantidades pendientes. Una habitación puede tener deuda histórica de antiguos ocupantes: distinguir esa deuda de la deuda del ocupante actual.

Indicadores con periodo visible:

- Habitaciones ocupadas de 4 y habitaciones libres.
- Alquiler esperado y cobrado, distinguiendo cargos del mes y cobros recibidos en el mes.
- Pendiente de alquiler, luz y agua.
- Deuda vencida y número de personas con deuda.
- Facturas sin cargo bancario completo.
- Facturas pendientes de entregar a algún destinatario.
- Gastos efectivamente pagados del periodo.
- Fianzas mantenidas, separadas de los ingresos.
- Llaves pendientes de devolución en estancias finalizadas.

Añadir un gráfico sencillo de ingresos y gastos mensuales y una tabla equivalente. Cada tarjeta debe permitir abrir el listado que explica su importe.

### 12.2 Menú principal

Inicio; Habitaciones; Inquilinos; Alquileres; Facturas de luz y agua; Cobros; Fianzas; Llaves; Gastos y averías; Informes; Configuración y copias.

Mantener accesos coherentes. En formularios relacionados, volver al lugar de origen conservando los filtros anteriores.

## 13. Listados, filtros e informes

Todos los listados relevantes deben disponer de filtros combinables por:

- Habitación.
- Inquilino, incluyendo antiguos.
- Estancia cuando una persona tenga varias.
- Fecha desde/hasta.
- Año y mes cuando proceda.
- Concepto: alquiler, luz, agua, comunidad, IBI, reparación u otros.
- Número de factura en suministros.
- Estado: pendiente, parcial, pagado, vencido, entregado/no entregado, según el módulo.

En cada listado indicar qué fecha filtra: «Fecha del cobro», «Periodo del alquiler», «Periodo de consumo», «Fecha del gasto» o «Fecha del pago». No presentar un único filtro ambiguo que cambie de significado sin explicarlo.

Los rangos de fechas de listados serán inclusivos en ambos extremos. Conservar filtros en la paginación, los totales, la exportación y la impresión.

Ofrecer como mínimo:

1. Inquilinos actuales e históricos y ocupación por habitación.
2. Mensualidades, cobros y deudas de alquiler.
3. Facturas de luz y agua con pago al proveedor, entrega a destinatarios, cuotas y cobros.
4. Deudas por inquilino y habitación, con desglose y antigüedad.
5. Historial de cobros con aplicaciones y números de factura.
6. Fianzas recibidas, devueltas, aplicadas y saldo.
7. Llaves entregadas, devueltas, perdidas y pendientes.
8. Gastos por concepto, habitación y fecha.
9. Liquidación individual de salida.
10. Resumen anual con totales mensuales y acumulados.
11. **Comprobación por factura de luz/agua:** cuotas acordadas, cobros de cada inquilino, detalle de sus movimientos, sumas y diferencias con el importe total de esa factura.

Cada listado mostrará número de resultados, subtotales de los resultados filtrados y paginación comprensible. No sumar solo la página visible sin indicarlo.

Exportar los listados a CSV UTF-8 compatible con Excel/LibreOffice y proporcionar vistas de impresión A4 que el navegador pueda guardar como PDF. No es obligatorio generar PDFs con una biblioteca.

Proteger las exportaciones de inyección de fórmulas y escapar correctamente delimitadores, comillas y saltos de línea. Aplicar a exportaciones e impresión el mismo control de acceso que a la aplicación.

## 14. Resumen anual y reglas de cálculo

Evitar mezclar cobros, cargos y fianzas en una sola cifra. Incluir tres bloques claramente separados.

### 14.1 Dinero realmente cobrado y pagado

Según la fecha real de cada movimiento del año elegido:

- Cobros de alquiler en dinero.
- Cobros de luz en dinero.
- Cobros de agua en dinero.
- Otros cobros ordinarios justificados, si existen.
- Devoluciones reales de cobros, separadas por concepto.
- Pagos a compañías de luz y agua.
- Pagos de comunidad e IBI.
- Pagos de reparaciones, averías y demás gastos.
- Saldo de caja de la actividad, excluyendo fianzas.

`saldo de caja de la actividad = cobros ordinarios netos de devoluciones − pagos de gastos y suministros`

Mostrar además las entradas y salidas de fianzas y el flujo total de tesorería que las incluye. Mostrar expresamente cuándo el saldo se refiere a actividad y cuándo a todo el dinero movido.

Ejemplo: un alquiler de diciembre cobrado en enero aparece en los cobros de enero, pero sigue vinculado al cargo de diciembre. Una factura cargada en diciembre y recuperada de los inquilinos en enero mantiene ambas fechas.

### 14.2 Cargos, deudas y situación a fecha de cierre

- Alquileres correspondientes al año, aunque estén sin cobrar.
- Cuotas de suministros por factura y periodo de consumo.
- Deudas de inquilinos a la fecha de corte elegida.
- Gastos y facturas todavía pendientes de pagar.
- Importes pendientes de devolver por rectificaciones.

El resumen anual predeterminado se basa en caja. El detalle por periodos sirve para consultar cargos y consumo; no presentarlo como una declaración fiscal ni como una contabilidad formal.

Cuando se solicite situación «a 31 de diciembre», calcularla con los movimientos y ajustes efectivos hasta esa fecha, aunque ahora existan cobros posteriores. No reconstruirla restando todos los cobros actuales ni usar únicamente un estado mutable de hoy.

### 14.3 Fianzas y compensaciones sin caja

- Saldo de fianzas al comienzo y al final del periodo.
- Fianzas recibidas y devueltas en dinero durante el año.
- Aplicaciones a deudas existentes.
- Compensaciones por daños u otros conceptos documentados.

Estas compensaciones deben mostrarse para explicar la liquidación y los saldos, pero no sumarse como nuevo dinero recibido en el año de aplicación. No contabilizar dos veces el mismo importe.

### 14.4 Estadísticas adicionales

- Ocupación por días: días-habitación ocupados / (4 × días del periodo) × 100.
- Ingresos de alquiler por habitación.
- Gastos agrupados por concepto.
- Evolución mensual de cobros y pagos.
- Recuperación de luz y agua por factura: importe facturado, cuotas manuales de inquilinos, cobros netos, compensaciones, pendiente, importes a devolver y diferencias de reparto y de caja. No generar una parte automática del propietario por habitaciones vacías o vacaciones.

Explicar con una frase qué mide cada indicador. No inventar tasas de rentabilidad ni valoraciones del inmueble sin datos aportados.

## 15. Diseño de la interfaz para una persona mayor

### 15.1 Legibilidad y controles

- Tamaño base de letra de al menos 18 px y títulos claramente jerarquizados.
- Botones principales de aproximadamente 56 px de alto; controles táctiles de al menos 48 × 48 px.
- Botones con texto completo: «Guardar cobro», «Añadir factura de agua», «Volver sin guardar».
- No usar iconos como único significado de una acción.
- Texto oscuro sobre fondos claros, contraste alto, espacio suficiente y líneas de lectura cómodas.
- Estados con texto y, opcionalmente, icono/color; nunca solo color.
- Foco de teclado visible, etiquetas asociadas y navegación con tabulador.
- Diseño usable en ordenador, tableta y móvil, con zoom del navegador al 200 % sin perder acciones esenciales.
- Evitar animaciones innecesarias, menús escondidos difíciles de descubrir y tablas con texto diminuto.
- En pantallas pequeñas, transformar la presentación cuando sea necesario sin ocultar importes, estados o botones importantes.

### 15.2 Formularios y ayudas

- Etiqueta permanente encima de cada campo; el placeholder no sustituye la etiqueta.
- Ayuda visible junto a los campos difíciles, sin depender de pasar el ratón.
- Ejemplos: «Cantidad recibida. Ejemplo: 87,50 €»; «Fecha en la que el banco te descontó la factura»; «Factura que está pagando este inquilino».
- Marcar qué campos son obligatorios y cuáles opcionales.
- Importes y fechas con cajas grandes, borde visible y formatos comprensibles.
- Admitir coma decimal; validar y normalizar sin confundir 1.234,56 con 1,23 ni aceptar formatos ambiguos silenciosamente.
- Combinar selector de fecha y posibilidad de escribirla; mostrar el formato esperado.
- Autocompletar datos conocidos sin ocultar su procedencia.
- Deshabilitar opciones incompatibles y explicar el motivo.
- Al cambiar de inquilino o factura, limpiar aplicaciones incompatibles y avisar antes de perder datos introducidos.
- Conservar campos válidos si hay error. Mostrar un resumen de errores y mensajes junto al campo.
- Mensajes concretos: «Quedan 20,00 € pendientes. La cantidad que has indicado es mayor».
- Confirmación persistente tras guardar y enlace para consultar el registro.
- Prevenir doble envío por doble clic tanto en frontend como en backend.
- Avisar al salir de un formulario con cambios sin guardar.

### 15.3 Asistentes y protección frente a errores

Para tareas complejas, usar pasos cortos con títulos claros:

- Cobro: quién paga → qué paga → importe y fecha → revisar y guardar.
- Factura: datos → pago al proveedor → reparto → revisión.
- Comprobación de consumos: elegir factura → revisar cuotas y cobros por inquilino → comprobar sumas y diferencias → imprimir o registrar el cobro pendiente.
- Salida: fecha efectiva → deudas → fianza → llaves → confirmar.

Permitir volver atrás sin perder datos. Mostrar siempre una pantalla o bloque de revisión antes de operaciones económicas importantes. No añadir confirmaciones repetitivas a acciones sencillas que no cambian dinero.

No poner «Eliminar» cerca de «Guardar». Para anular datos económicos, mostrar claramente qué registro se afecta y exigir motivo. Ofrecer un circuito comprensible de corrección, conservando el historial.

Añadir una sección «Ayuda» con instrucciones sencillas y ejemplos de las tareas habituales. Las ayudas deben explicar el trabajo del propietario, sin términos de programación.

## 16. Modelo de datos, integridad y transacciones

Diseña un modelo normalizado que separe, como mínimo:

- Usuario propietario y configuración.
- Habitaciones.
- Inquilinos.
- Estancias y sus condiciones de renta históricas.
- Ausencias temporales opcionales vinculadas a una estancia, sin modificar la ocupación contractual ni calcular cuotas.
- Cargos de alquiler.
- Facturas de suministros.
- Revisiones del acuerdo manual de reparto y cuotas introducidas por destinatario, distinguiendo cuotas de cero y cantidades todavía sin asignar.
- Entregas de facturas por destinatario y revisión.
- Cobros recibidos y aplicaciones a cargos/cuotas.
- Pagos a proveedores.
- Gastos, categorías y pagos de gastos.
- Movimientos y aplicaciones de fianza.
- Tipos/unidades de llaves y movimientos.
- Incidencias.
- Adjuntos y su relación autorizada con los registros.
- Correcciones, devoluciones y auditoría.

Puedes elegir nombres de tablas distintos. Documenta el diccionario de datos y el propósito de cada relación. Evita relaciones polimórficas sin integridad referencial; si un cobro puede aplicarse a alquiler o suministro, modela destinos explícitos y mutuamente excluyentes o una entidad común de cargo con subtipos coherentes.

Requisitos:

- Claves primarias, foráneas, índices y restricciones para impedir referencias huérfanas.
- Restricciones de unicidad para habitaciones, documentos normalizados, mensualidades e identidad de factura.
- No usar borrado en cascada de información económica al archivar una persona.
- Validar importes, fechas, periodos y coherencia de cada relación en servidor.
- Transacciones en altas de cobros y aplicaciones, confirmación/rectificación de repartos, fianzas, salida de estancias y registros que crean varias filas dependientes.
- Bloqueo o control de concurrencia suficiente para evitar sobrepagos, ocupaciones dobles y devoluciones excesivas.
- Detectar edición concurrente y no sobrescribir silenciosamente cambios de otra pestaña.
- Protección idempotente frente al reenvío de formularios y generación repetida de mensualidades. Un token CSRF no sustituye esta protección.
- Calcular pendientes y estados desde cargos y movimientos válidos. Si guardas valores derivados por rendimiento, mantenerlos transaccionalmente y verificar su conciliación.
- Permitir distinguir fecha efectiva del hecho económico y fecha/hora en que se registró.
- Proteger periodos cerrados de cambios silenciosos; documentar reapertura o rectificación.
- Guardar quién realizó cada cambio, cuándo y su motivo en operaciones sensibles. No guardar contraseñas, contenido binario de adjuntos ni secretos en auditoría.

## 17. Seguridad, acceso y archivos

- Inicio de sesión del propietario, cierre de sesión y cambio de contraseña.
- Crear al propietario durante la instalación o mediante un comando documentado. Sin credenciales universales ni contraseñas en el repositorio.
- Contraseñas con `password_hash` y verificación con `password_verify`.
- Sesiones con regeneración del identificador al autenticar, cookies HttpOnly y SameSite, y Secure en HTTPS.
- Caducidad razonable con aviso; no cerrar la sesión inesperadamente a mitad de un formulario sin explicación. No persistir DNI ni datos financieros en almacenamiento local del navegador para resolverlo.
- Limitación razonable de intentos de acceso y mensajes que no revelen información innecesaria.
- Validación del lado servidor, consultas preparadas, escape de salida HTML y protección CSRF en operaciones de cambio.
- Autorización en todas las páginas, acciones, descargas, exportaciones y endpoints.
- Credenciales de MySQL fuera de la carpeta pública o protegidas de acceso web; entregar un archivo de ejemplo sin secretos.
- Cuenta MySQL de ejecución con los permisos necesarios, separada de los permisos de instalación cuando sea posible.
- Errores técnicos en logs protegidos; al usuario, mensajes claros sin trazas ni credenciales.
- Adjuntos con comprobación real de MIME, extensión permitida y tamaño limitado; nombres internos aleatorios y almacenamiento fuera de la raíz pública.
- Nunca ejecutar adjuntos ni servirlos mediante rutas introducidas libremente. Descargar o visualizar a través de una acción autenticada.
- No pedir escaneos de DNI para poder usar la app. El número de documento requerido y los adjuntos opcionales son suficientes para el alcance.
- Si el propietario decide usarla en Internet, documentar HTTPS y la configuración segura del servidor. No publicarla ni exponerla automáticamente.
- Datos de demostración claramente ficticios, separados de producción y cargados solo por una acción expresa.

## 18. Copias de seguridad y restauración

Incluir una opción comprensible para descargar una copia que contenga datos y adjuntos, con fecha, versión del esquema e instrucciones de restauración. Proteger su acceso y evitar dejar copias descargables en una carpeta pública.

Documentar una ruta fiable para copias completas de MySQL y adjuntos en XAMPP y en un servidor habitual. Si la interfaz necesita utilidades del sistema, detectarlas y explicarlo; no simular una copia exitosa cuando falte una herramienta.

La restauración debe comprobar formato y versión, impedir rutas peligrosas al extraer adjuntos, pedir confirmación porque sustituye datos y ofrecer crear una copia previa. Limitarla al propietario. Puede implementarse como procedimiento de mantenimiento o herramienta CLI documentada si eso resulta más seguro y fiable que subir SQL desde una página.

No ejecutar SQL arbitrario subido desde una interfaz ordinaria ni admitir copias como si fueran documentos adjuntos. Probar el procedimiento sobre una base de datos de prueba y registrar el resultado.

## 19. Organización y documentación del código

Mantener una estructura sencilla, con separación entre presentación, reglas de negocio y persistencia, sin crear un framework propio innecesario. Estructura orientativa:

```text
public/
  index.php
  assets/css/
  assets/js/
app/
  Controllers/
  Services/
  Repositories/
  Views/
  Support/
config/
database/
  001_schema.sql
  002_seed_base.sql
  003_seed_demo.sql
  migrations/
storage/
  attachments/
  logs/
scripts/
tests/
docs/
README.md
```

Es una guía: ajusta los nombres si lo justificas. Solo `public/` debe ser la raíz web. Documenta cómo configurarlo en Apache/XAMPP sin exponer `config/`, `storage/` ni las copias.

El código debe estar **ampliamente comentado y explicado**, especialmente donde haya cálculos o relaciones económicas:

- Cabecera de cada archivo explicando su responsabilidad.
- PHPDoc completo en clases, servicios, métodos públicos y métodos privados con lógica relevante: propósito, parámetros y tipos, retornos, excepciones, efectos sobre datos y reglas de negocio.
- JSDoc en módulos y funciones JavaScript relevantes, con parámetros, tipos, retornos, eventos y efectos sobre la interfaz.
- Comentarios SQL explicando tablas, columnas relevantes, índices y restricciones.
- Comentarios CSS por componentes y decisiones de accesibilidad.
- Explicaciones paso a paso de fórmulas, prorrateo de alquiler, validación del reparto manual de consumos, conciliación por factura, redondeos, transacciones y prevención de duplicados.
- Explicar en los bloques complejos qué entra, qué validaciones se realizan, qué registros se consultan o escriben, por qué se usa esa solución y qué sucede si falla una operación.
- Documentar las consultas SQL complejas y los informes para que se entienda cómo se evitan duplicaciones al unir cobros y aplicaciones.
- Incluir ejemplos pequeños de entrada y resultado en los métodos de reparto manual, cálculo de saldos y comprobación por factura.
- No llenar el proyecto de comentarios que repiten literalmente una instrucción obvia; explicar el motivo y el efecto.
- Evitar duplicar lógica económica entre frontend y backend: el servidor es la autoridad.
- Usar nombres descriptivos y funciones de responsabilidad acotada, sin abreviaturas crípticas ni código comprimido. El objetivo es poder estudiar y comprender el proyecto con facilidad.
- Preparar comentarios con sintaxis válida para generar documentación técnica; no usar etiquetas inventadas ni bloques que los generadores no puedan interpretar.
- Incluir una guía para generar documentación a partir de PHPDoc y JSDoc, con configuración y comandos reproducibles de las herramientas opcionales elegidas. Generar documentación no debe ser un requisito para instalar o ejecutar la aplicación.

Entregar:

- `README.md`: requisitos, instalación paso a paso, arranque y comprobaciones iniciales.
- `docs/MANUAL_USUARIO.md`: alta de inquilino, mensualidad, cobro, factura, introducción del reparto calculado por los inquilinos, comprobación de pagos por factura, entrega, gasto, fianza, llaves, salida e informes, con ejemplos sencillos.
- `docs/MODELO_DATOS.md`: tablas, relaciones, restricciones y diccionario de datos.
- `docs/REGLAS_NEGOCIO.md`: fórmulas de alquiler, fechas, reparto manual de consumos, cuadre por factura, redondeo y criterios de caja. Explicar con ejemplos las habitaciones vacías, las vacaciones y la diferencia entre cuota asignada y cantidad cobrada.
- `docs/GUIA_CODIGO.md`: recorrido por los archivos, flujo de una petición, relación entre interfaz, servicios y base de datos, y explicación de un alta de factura, un cobro y su conciliación de principio a fin.
- `docs/GENERAR_DOCUMENTACION.md`: herramientas opcionales, configuración y pasos para generar documentación de PHP y JavaScript a partir de los comentarios incluidos.
- `docs/DECISIONES.md`: decisiones tomadas y valores predeterminados.
- `docs/PRUEBAS.md`: casos ejecutados, resultados y comprobaciones pendientes.
- `docs/COPIAS_SEGURIDAD.md`: copia y restauración verificadas.
- `docs/ESTADO_PROYECTO.md`: avance real y pendientes.

Los scripts SQL deben crear el esquema completo, las cuatro habitaciones y los catálogos básicos. Separar los datos de demostración y las operaciones destructivas. No incluir un `DROP DATABASE` en una instalación normal ni ejecutar el seed de demostración sobre datos reales.

## 20. Pruebas funcionales y criterios de aceptación

Crear datos ficticios reproducibles y pruebas útiles de las reglas económicas y de integridad. Pueden ser scripts PHP ejecutables sin frameworks de pruebas. No limitarse a comprobar que carga una pantalla.

### Caso 1 — Mensualidad y cobros parciales

Una estancia tiene una mensualidad de 350,00 €. Registrar 200,00 € y después 150,00 €:

- Tras el primero: parcialmente pagado, pendiente 150,00 €.
- Tras el segundo: pagado, pendiente 0,00 €.
- Conservar ambos cobros y sus fechas.
- Impedir un tercer cobro aplicado a ese mismo cargo sin saldo.

### Caso 2 — Reparto sencillo y factura identificada

Con las cuatro habitaciones ocupadas durante todo el periodo, registrar una factura de luz de 120,00 € e introducir manualmente el acuerdo de 30,00 € para cada persona:

- La aplicación debe guardar esas cuatro cuotas, sin haberlas calculado o precargado automáticamente.
- Cobrar 10,00 € a una persona y luego 20,00 € debe liquidar solo su cuota.
- Ambos cobros deben mostrar el número de esa factura.
- Las demás cuotas siguen pendientes y el pago al proveedor no cambia por recibir esos cobros.

### Caso 3 — Exactitud de céntimos y reparto descuadrado

Factura de 100,01 €: introducir cuatro cuotas de 25,00 €:

- Mostrar «Falta asignar 0,01 €» y permitir guardar como borrador, pero no confirmar el reparto.
- No modificar ninguna cuota por decisión de la aplicación.
- Al introducir el acuerdo corregido de 25,01 €, 25,00 €, 25,00 € y 25,00 €, permitir confirmarlo: suma exacta de 100,01 €.
- Rechazar formatos ambiguos o importes con más de dos decimales con una ayuda clara.

### Caso 4 — Habitación vacía

Factura de agua de 80,00 € con tres habitaciones ocupadas y una vacía todo el periodo. Los inquilinos comunican cuotas de 30,00 €, 30,00 € y 20,00 €:

- Registrar exactamente las cuotas comunicadas y confirmar que suman 80,00 €.
- No crear cuota para la habitación vacía ni parte automática del propietario.
- No imponer igualdad entre las tres cuotas ni calcularlas automáticamente.

### Caso 5 — Cambio de inquilino

Factura de 120,00 € del 1 al 30 de junio, ambos incluidos. En la habitación 1, A ocupa [1 de junio, 16 de junio) y B [16 de junio, 1 de julio). Las otras tres están ocupadas todo el periodo. Los inquilinos comunican A: 10,00 €, B: 20,00 € y 30,00 € para cada una de las otras tres personas:

- Registrar esos cinco destinatarios históricos con sus cantidades manuales, aunque el acuerdo no sea proporcional a los días.
- La suma es 120,00 € sin superar cuatro ocupantes simultáneos.
- Permitir introducir esta factura en julio, con A ya fuera.
- No atribuir la deuda de A a B.

### Caso 6 — Alquiler parcial y fechas límite

Renta de 300,00 € en un mes de 30 días, estancia [16 de junio, 1 de julio): cargo 150,00 €. Verificar también febrero bisiesto, mes completo, salida el primer día de un mes y vencimiento configurado el día 31.

### Caso 7 — Entrega de factura

Entregar la misma factura a dos personas en días distintos. Mantener ambos registros, señalar los otros destinatarios pendientes y conservar un recordatorio posterior como nueva entrega.

### Caso 8 — Transferencia con varios conceptos

Recibir 390,00 € de un inquilino: alquiler 350,00 €, luz 25,00 € y agua 15,00 €:

- Crear un movimiento de cobro con tres aplicaciones.
- Mostrar las dos facturas correspondientes.
- El resumen de caja suma 390,00 €, no 390,00 € más sus aplicaciones.
- Impedir guardar si la suma del desglose no coincide.

### Caso 9 — Fianza aplicada a deuda

Recibir fianza de 400,00 € en enero. Al salir en junio, aplicar 100,00 € a un cargo de alquiler pendiente y devolver 300,00 €:

- La deuda se reduce 100,00 € y la fianza queda en 0,00 €.
- Los 400,00 € de enero no aparecen como renta.
- La aplicación de junio no crea otro cobro bancario de 100,00 €.
- La devolución se muestra en movimientos de fianza, fuera de gastos ordinarios.

### Caso 10 — Gastos del propietario y no duplicación

Registrar comunidad de 60,00 €, IBI de 300,00 € y reparación de 90,00 €. Marcar además como pagada una factura de luz de 120,00 €:

- Los pagos de gastos/suministros suman 570,00 € si todos se realizan en el mismo periodo.
- La factura de luz cuenta una sola vez.
- Comunidad e IBI no crean cuotas de inquilinos.
- Un gasto registrado pero todavía sin pagar no se suma a caja pagada.

### Caso 11 — Llaves y salida

Entregar tres llaves, devolver dos y registrar una pendiente. Impedir devolver cuatro. Al finalizar la estancia, mostrar la pendiente y mantenerla consultable después de la salida.

### Caso 12 — Integridad, repetición y concurrencia

- Impedir dos ocupaciones solapadas en la misma habitación y dos estancias simultáneas para la misma persona.
- Admitir salida y entrada de personas distintas el mismo día.
- Reenviar la creación de una mensualidad o hacer doble clic en guardar un cobro no duplica registros.
- Dos solicitudes simultáneas no pueden cobrar conjuntamente más que la deuda ni devolver más que la fianza disponible.
- No se aceptan aplicaciones a cargos de otra persona ni a facturas anuladas.

### Caso 13 — Anulación, devolución y rectificación

Confirmar una cuota de 30,00 € y cobrarla. Rectificarla a 25,00 €:

- Conservar el reparto y cobro originales y registrar la rectificación.
- Generar 5,00 € pendientes de devolver, sin borrar el cobro de 30,00 €.
- Al devolverlos realmente, registrar la salida y dejar la cuota conciliada.
- Anular un cobro introducido por error debe conservar su rastro y restablecer la deuda; no simular una devolución bancaria.

### Caso 14 — Cierre anual e histórico

Un cargo de diciembre de 2026 se cobra en enero de 2027:

- Aparece como deuda a 31/12/2026.
- Su cobro aparece en la caja de 2027.
- Consultar el informe de 2026 después del cobro no borra la deuda que existía a su cierre.

### Caso 15 — Seguridad, uso y restauración

- Sin sesión no se accede a datos, adjuntos, exportaciones ni copias.
- Texto con etiquetas HTML se muestra escapado y una petición de cambio sin CSRF se rechaza.
- Un archivo PHP renombrado no se acepta como imagen o factura.
- Recorrer con teclado alta, factura, cobro y salida; comprobar etiquetas, foco y errores.
- Verificar móvil, ordenador y zoom al 200 % con información esencial visible.
- Exportar un listado filtrado y comprobar que totales, CSV e impresión coinciden.
- Crear una copia y restaurarla en una base de prueba; comprobar personas, cargos, movimientos y adjuntos.

### Caso 16 — Vacaciones y reparto desigual calculado por los inquilinos

Las cuatro habitaciones tienen inquilino y la persona de la habitación 2 se ausenta diez días. Para una factura de 100,00 €, los inquilinos comunican: habitación 1, 30,00 €; habitación 2, 10,00 €; habitación 3, 35,00 €; habitación 4, 25,00 €:

- Anotar la ausencia sin terminar la estancia, liberar la habitación ni modificar el alquiler.
- Introducir y conservar exactamente las cuatro cuotas acordadas, que suman 100,00 €.
- La aplicación no calcula esos importes a partir de los días ni los sustituye por cuatro cuotas iguales.
- Una ausencia añadida después no modifica un reparto ya confirmado.
- Admitir también una cuota expresa de 0,00 € en otro acuerdo que siga sumando el total; no tratarla como dato pendiente.

### Caso 17 — Comprobación de ingresos por factura

Factura de 120,00 € con cuotas de 50,00 €, 40,00 € y 30,00 €, y una habitación vacía. Registrar cobros de 50,00 €, 20,00 € y 30,00 €:

- El informe muestra reparto cuadrado a 120,00 €, cobros totales de 100,00 € y diferencia de 20,00 €.
- La segunda persona muestra 20,00 € pendientes y se pueden desplegar las fechas y movimientos de cada destinatario.
- Al cobrar sus 20,00 € restantes, el total ingresado es 120,00 € y la diferencia es 0,00 €.
- Repetir usando una transferencia que pague alquiler y luz: sumar solo su aplicación de luz a esta factura.
- Al filtrar una sola habitación, conservar el cuadre global identificado y mostrar aparte el subtotal filtrado.
- Repetir saldando una parte con fianza: mostrar la compensación separada y no afirmar que toda la factura se ha ingresado en dinero.

### Caso 18 — Reparto pendiente y documentación técnica

- Registrar y pagar al proveedor una factura cuyo acuerdo todavía no se conoce; mostrar «Reparto pendiente», sin cuotas ficticias ni errores de caja.
- Si no hay inquilinos elegibles en todo el periodo, dejar el caso señalado para decisión del propietario y no trasladarlo al siguiente ocupante.
- Comprobar que el código de cobros y conciliación se puede seguir con los comentarios, las referencias entre archivos y los ejemplos de `docs/GUIA_CODIGO.md`.
- Comprobar que la configuración y las instrucciones de generación de documentación coinciden con los bloques PHPDoc/JSDoc reales. Ejecutar la generación si las herramientas están disponibles y registrar el resultado o la limitación.

## 21. Secuencia de implementación y entrega

Implementar en este orden, con comprobaciones intermedias:

1. Modelo de datos, configuración, instalación y autenticación.
2. Habitaciones, inquilinos y estancias con prevención de solapamientos.
3. Mensualidades, cobros y aplicaciones con integridad monetaria.
4. Facturas de suministros, pagos a proveedores, registro del reparto manual acordado, entrega a destinatarios y comprobación de cobros por factura.
5. Fianzas, llaves, gastos e incidencias.
6. Dashboard, listados, exportaciones, informes y liquidaciones.
7. Ayudas, accesibilidad, pruebas integrales, copias y documentación final.

No dar por finalizado el proyecto hasta que se pueda completar este recorrido real:

**Instalar → crear al propietario → registrar cuatro inquilinos → generar alquileres → introducir factura de luz y agua → introducir el reparto comunicado por los inquilinos → registrar entrega → cobrar alquiler y suministros → comprobar por factura la suma ingresada por cada persona → registrar comunidad, IBI y reparación → consultar dashboard y resumen anual → finalizar una estancia, liquidar fianza y revisar llaves.**

Al terminar, entregar:

- Todos los archivos del proyecto y scripts MySQL.
- Configuración de ejemplo e instrucciones exactas para instalarlo.
- Datos ficticios opcionales de demostración.
- Manual para el propietario y documentación del código.
- Relación de pruebas ejecutadas y resultado real.
- Limitaciones conocidas, si las hay, con explicación concreta.
- Pasos para abrir y usar la aplicación localmente.

**Empieza ahora por analizar los requisitos, preparar el plan breve y crear el modelo de datos; después continúa hasta implementar y verificar la aplicación funcional.**
