<?php
declare(strict_types=1);

/**
 * Clase Security — Utilidades de seguridad y sanitización.
 *
 * Responsabilidad:
 *  - Escape de salida HTML para prevenir XSS.
 *  - Sanitización de datos de entrada.
 *  - Validación de formatos de fecha y importe en español.
 *  - Generación de nombres internos seguros para adjuntos.
 *  - Prevención de doble envío (tokens de idempotencia).
 */

namespace App\Support;

class Security
{
    /**
     * Escapa una cadena para salida HTML (previene XSS).
     *
     * @param string $valor Valor a escapar
     * @return string Valor escapado
     */
    public static function html(string $valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Escapa un atributo HTML.
     *
     * @param string $valor Valor a escapar
     * @return string Valor escapado
     */
    public static function attr(string $valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Normaliza un importe en formato español (coma decimal, punto miles opcional).
     * Acepta: "1.234,56", "1234,56", "1234.56", "1,23".
     * Rechaza importes con más de dos decimales.
     *
     * @param string $valor Importe como texto
     * @return float|null Importe normalizado o null si es inválido
     */
    public static function normalizarImporte(string $valor): ?float
    {
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }

        // Eliminar puntos de miles
        $valor = str_replace('.', '', $valor);

        // Reemplazar coma decimal por punto
        $valor = str_replace(',', '.', $valor);

        // Validar que sea numérico y tenga máximo 2 decimales
        if (!is_numeric($valor)) {
            return null;
        }

        $numero = (float) $valor;

        // Comprobar máximo 2 decimales
        $decimales = strlen(substr(strrchr($valor, '.'), 1));
        if ($decimales > 2) {
            return null;
        }

        return round($numero, 2);
    }

    /**
     * Formatea un importe para presentación en español: 1.234,56 €.
     *
     * @param float $importe Importe numérico
     * @param string $moneda Símbolo de moneda (default: €)
     * @return string Importe formateado
     */
    public static function formatearImporte(float $importe, string $moneda = '€'): string
    {
        $formateado = number_format($importe, 2, ',', '.');
        return $formateado . ' ' . $moneda;
    }

    /**
     * Valida y normaliza una fecha en formato dd/mm/aaaa.
     *
     * @param string $fecha Fecha como texto
     * @return string|null Fecha en formato Y-m-d o null si es inválida
     */
    public static function normalizarFecha(string $fecha): ?string
    {
        $fecha = trim($fecha);
        if ($fecha === '') {
            return null;
        }

        // Aceptar dd/mm/aaaa o yyyy-mm-dd
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $fecha, $m)) {
            $dia = (int) $m[1];
            $mes = (int) $m[2];
            $anio = (int) $m[3];

            if (checkdate($mes, $dia, $anio)) {
                return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
            }

            return null;
        }

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $fecha, $m)) {
            $dia = (int) $m[3];
            $mes = (int) $m[2];
            $anio = (int) $m[1];

            if (checkdate($mes, $dia, $anio)) {
                return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
            }

            return null;
        }

        return null;
    }

    /**
     * Formatea una fecha para presentación en español: dd/mm/aaaa.
     *
     * @param string $fecha Fecha en formato Y-m-d
     * @return string Fecha formateada
     */
    public static function formatearFecha(string $fecha): string
    {
        $fecha = trim($fecha);
        if ($fecha === '') {
            return '';
        }

        $ts = strtotime($fecha);
        if ($ts === false) {
            return $fecha;
        }

        return date('d/m/Y', $ts);
    }

    /**
     * Genera un nombre interno seguro para un archivo adjunto.
     *
     * @param string $nombreOriginal Nombre original del archivo
     * @return string Nombre interno con extensión
     */
    public static function nombreInterno(string $nombreOriginal): string
    {
        $extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
        return bin2hex(random_bytes(16)) . '.' . $extension;
    }

    /**
     * Genera un token de idempotencia para prevenir reenvíos duplicados.
     *
     * @return string Token de 64 caracteres hexadecimales
     */
    public static function tokenIdempotencia(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Escapa valores para exportación CSV (protege contra inyección de fórmulas).
     *
     * @param string $valor Valor a escapar
     * @return string Valor seguro para CSV
     */
    public static function csv(string $valor): string
    {
        $valor = (string) $valor;

        // Si comienza con =, +, -, @, @, tab, precomponer comilla para evitar fórmulas
        if (preg_match('/^[=+\-@\t]/', $valor)) {
            $valor = "'" . $valor;
        }

        // Escapar comillas dobles duplicándolas
        $valor = str_replace('"', '""', $valor);

        // Si contiene comas, comillas o saltos de línea, envolver en comillas
        if (preg_match('/[",\r\n]/', $valor)) {
            return '"' . $valor . '"';
        }

        return $valor;
    }
}
