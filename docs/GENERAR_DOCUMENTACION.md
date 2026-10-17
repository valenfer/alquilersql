# Generación de Documentación Técnica

Este documento explica cómo generar documentación técnica a partir de los
comentarios PHPDoc y JSDoc incluidos en el código. La generación de
documentación **no es un requisito para instalar o ejecutar la aplicación**;
es opcional.

---

## 1. Comentarios en el código

### PHPDoc (PHP)

Todas las clases, métodos públicos y privados con lógica relevante tienen
PHPDoc completo con:

- `@param` — tipo y nombre del parámetro
- `@return` — tipo de retorno
* `@throws` — excepciones que puede lanzar
- `@since` — versión o fase en que se introdujo

Ejemplo:

```php
/**
 * Valida y crea un inquilino.
 *
 * @param array $datos Datos del inquilino (tipo_documento, numero_documento, nombre, apellidos, etc.)
 * @return array{exito: bool, id: int|null, errores: array}
 */
public function crear(array $datos): array
```

### JSDoc (JavaScript)

Los módulos y funciones JavaScript relevantes tienen JSDoc con:

- `@param` — tipo y nombre del parámetro
- `@returns` — tipo de retorno
- `@example` — ejemplo de uso

---

## 2. Herramientas opcionales para PHP

### phpDocumentor

La herramienta estándar para generar documentación a partir de PHPDoc.

**Instalación:**

```bash
# Con Composer (opcional, no requerido para ejecutar la app)
composer require --dev phpdocumentor/phpdocumentor
```

**Uso:**

```bash
vendor/bin/phpdoc run -d app/ -t docs/generado/php/
```

Esto generará documentación HTML en `docs/generado/php/`.

### Alternativa: phpDocumentor sin Composer

Descargar el archivo PHAR desde https://phpdoc.org y ejecutar:

```bash
php phpdocumentor.phar run -d app/ -t docs/generado/php/
```

---

## 3. Herramientas opcionales para JavaScript

### JSDoc

Para generar documentación a partir de comentarios JSDoc.

**Instalación (requiere Node.js, opcional):**

```bash
npm install -g jsdoc
```

**Uso:**

```bash
jsdoc public/assets/js/ -d docs/generado/js/
```

---

## 4. Comando reproducible

Un ejemplo de script para generar toda la documentación (opcional):

```bash
#!/bin/bash
# generar_documentacion.sh

# PHP (si phpDocumentor está disponible)
if command -v phpdoc &> /dev/null; then
    phpdoc run -d app/ -t docs/generado/php/
fi

# JavaScript (si jsdoc está disponible)
if command -v jsdoc &> /dev/null; then
    jsdoc public/assets/js/ -d docs/generado/js/
fi

echo "Documentación generada en docs/generado/"
```

---

## 5. Verificación de consistencia

La aplicación incluye comentarios PHPDoc y JSDoc reales. Para verificar que
coinciden con el código:

1. Revisar que cada clase tenga un bloque PHPDoc al inicio.
2. Revisar que cada método público tenga `@param` y `@return`.
3. Revisar que cada función JavaScript relevante tenga JSDoc.
4. No usar etiquetas inventadas ni bloques que generadores no puedan interpretar.

La generación de documentación es opcional y no bloquea el uso de la aplicación.
