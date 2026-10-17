<?php
declare(strict_types=1);

/**
 * Controlador ExportController — Exportación de listados a CSV UTF-8.
 *
 * Responsabilidad:
 *  - Exportar listados filtrados a CSV UTF-8 compatible con Excel/LibreOffice.
 *  - Proteger contra inyección de fórmulas (escapar =, +, -, @ al inicio).
 *  - Escapar correctamente delimitadores, comillas y saltos de línea.
 *  - Aplicar el mismo control de acceso que a la aplicación (sesión requerida).
 *
 * Rutas:
 *  - r=exportar/cobros        → exportar cobros
 *  - r=exportar/facturas      → exportar facturas de suministros
 *  - r=exportar/gastos        → exportar gastos
 *  - r=exportar/incidencias   → exportar incidencias
 *  - r=exportar/llaves        → exportar movimientos de llave
 *  - r=exportar/fianzas       → exportar fianzas
 *  - r=exportar/estancias     → exportar estancias
 *  - r=exportar/cargos        → exportar cargos de alquiler
 */

namespace App\Controllers;

use App\Support\Session;
use App\Support\Security;
use App\Support\Database;

class ExportController
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::obtenerConexion();
    }

    /**
     * Exporta un listado a CSV.
     *
     * @param string $tipo Tipo de listado a exportar
     * @param array $config Configuración de la aplicación
     * @return void
     */
    public function exportar(string $tipo, array $config): void
    {
        if (!\App\Support\Auth::estaAutenticado()) {
            header('Location: ' . $config['url_base'] . '/index.php?r=login');
            exit;
        }

        $datos = $this->obtenerDatos($tipo);

        $nombreArchivo = 'alquiler_' . $tipo . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: public');

        $salida = fopen('php://output', 'w');

        // BOM UTF-8 para Excel
        fprintf($salida, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabeceras
        fputcsv($salida, $datos['cabeceras'], ';');

        // Filas
        foreach ($datos['filas'] as $fila) {
            fputcsv($salida, array_map([$this, 'valorCSV'], $fila), ';');
        }

        fclose($salida);
        exit;
    }

    /**
     * Escapa un valor para CSV (protege contra inyección de fórmulas).
     *
     * @param mixed $valor
     * @return string
     */
    private function valorCSV($valor): string
    {
        return Security::csv((string) $valor);
    }

    /**
     * Obtiene los datos para exportar según el tipo.
     *
     * @param string $tipo
     * @return array{clabeceras: array, filas: array}
     */
    private function obtenerDatos(string $tipo): array
    {
        switch ($tipo) {
            case 'cobros':
                return $this->exportarCobros();
            case 'facturas':
                return $this->exportarFacturas();
            case 'gastos':
                return $this->exportarGastos();
            case 'incidencias':
                return $this->exportarIncidencias();
            case 'llaves':
                return $this->exportarLlaves();
            case 'fianzas':
                return $this->exportarFianzas();
            case 'estancias':
                return $this->exportarEstancias();
            case 'cargos':
                return $this->exportarCargos();
            default:
                return ['cabeceras' => [], 'filas' => []];
        }
    }

    private function exportarCobros(): array
    {
        $stmt = $this->pdo->query(
            'SELECT c.fecha_cobro, i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos,
                    c.medio, c.importe_total, c.referencia, c.observaciones
             FROM cobros c
             INNER JOIN inquilinos i ON c.inquilino_id = i.id
             ORDER BY c.fecha_cobro DESC'
        );
        $filas = [];
        foreach ($stmt->fetchAll() as $f) {
            $filas[] = [
                date('d/m/Y', strtotime($f['fecha_cobro'])),
                $f['inquilino_nombre'] . ' ' . $f['inquilino_apellidos'],
                ucfirst($f['medio']),
                number_format($f['importe_total'], 2, ',', '.') . ' €',
                $f['referencia'] ?? '',
                $f['observaciones'] ?? '',
            ];
        }
        return [
            'cabeceras' => ['Fecha', 'Inquilino', 'Medio', 'Importe', 'Referencia', 'Observaciones'],
            'filas' => $filas,
        ];
    }

    private function exportarFacturas(): array
    {
        $stmt = $this->pdo->query(
            'SELECT f.tipo, f.proveedor, f.numero_factura, f.fecha_emision,
                    f.periodo_desde, f.periodo_hasta, f.importe_total, f.estado
             FROM facturas_suministro f
             ORDER BY f.periodo_desde DESC'
        );
        $filas = [];
        foreach ($stmt->fetchAll() as $f) {
            $filas[] = [
                ucfirst($f['tipo']),
                $f['proveedor'],
                $f['numero_factura'] ?? '',
                date('d/m/Y', strtotime($f['fecha_emision'])),
                date('d/m/Y', strtotime($f['periodo_desde'])) . ' - ' . date('d/m/Y', strtotime($f['periodo_hasta'])),
                number_format($f['importe_total'], 2, ',', '.') . ' €',
                ucfirst($f['estado']),
            ];
        }
        return [
            'cabeceras' => ['Tipo', 'Proveedor', 'Número', 'Emisión', 'Periodo', 'Importe', 'Estado'],
            'filas' => $filas,
        ];
    }

    private function exportarGastos(): array
    {
        $stmt = $this->pdo->query(
            'SELECT g.fecha_gasto, c.nombre AS categoria, g.descripcion, g.importe,
                    g.proveedor, g.numero_factura, h.numero AS habitacion_numero
             FROM gastos g
             INNER JOIN categorias_gasto c ON g.categoria_id = c.id
             LEFT JOIN habitaciones h ON g.relacion_habitacion = h.id
             ORDER BY g.fecha_gasto DESC'
        );
        $filas = [];
        foreach ($stmt->fetchAll() as $f) {
            $filas[] = [
                date('d/m/Y', strtotime($f['fecha_gasto'])),
                $f['categoria'],
                $f['descripcion'],
                number_format($f['importe'], 2, ',', '.') . ' €',
                $f['proveedor'] ?? '',
                $f['numero_factura'] ?? '',
                $f['habitacion_numero'] ?? '',
            ];
        }
        return [
            'cabeceras' => ['Fecha', 'Categoría', 'Descripción', 'Importe', 'Proveedor', 'Factura', 'Habitación'],
            'filas' => $filas,
        ];
    }

    private function exportarIncidencias(): array
    {
        $stmt = $this->pdo->query(
            'SELECT i.fecha_aviso, h.numero AS habitacion_numero, i.descripcion, i.estado, i.fecha_resolucion
             FROM incidencias i
             LEFT JOIN habitaciones h ON i.habitacion_id = h.id
             ORDER BY i.fecha_aviso DESC'
        );
        $filas = [];
        foreach ($stmt->fetchAll() as $f) {
            $filas[] = [
                date('d/m/Y', strtotime($f['fecha_aviso'])),
                $f['habitacion_numero'] ?? '',
                $f['descripcion'],
                ucfirst(str_replace('_', ' ', $f['estado'])),
                $f['fecha_resolucion'] ? date('d/m/Y', strtotime($f['fecha_resolucion'])) : '',
            ];
        }
        return [
            'cabeceras' => ['Fecha aviso', 'Habitación', 'Descripción', 'Estado', 'Resolución'],
            'filas' => $filas,
        ];
    }

    private function exportarLlaves(): array
    {
        $stmt = $this->pdo->query(
            'SELECT m.fecha, m.tipo, m.cantidad, l.identificador, m.observaciones,
                    h.numero AS habitacion_numero, i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos
             FROM movimientos_llave m
             INNER JOIN estancias e ON m.estancia_id = e.id
             INNER JOIN habitaciones h ON e.habitacion_id = h.id
             INNER JOIN inquilinos i ON e.inquilino_id = i.id
             LEFT JOIN llaves l ON m.llave_id = l.id
             ORDER BY m.fecha DESC'
        );
        $filas = [];
        foreach ($stmt->fetchAll() as $f) {
            $filas[] = [
                date('d/m/Y', strtotime($f['fecha'])),
                ucfirst($f['tipo']),
                $f['cantidad'],
                $f['identificador'] ?? '',
                $f['observaciones'] ?? '',
                $f['habitacion_numero'],
                $f['inquilino_nombre'] . ' ' . $f['inquilino_apellidos'],
            ];
        }
        return [
            'cabeceras' => ['Fecha', 'Tipo', 'Cantidad', 'Identificador', 'Observaciones', 'Habitación', 'Inquilino'],
            'filas' => $filas,
        ];
    }

    private function exportarFianzas(): array
    {
        $stmt = $this->pdo->query(
            'SELECT f.importe_pactado, f.importe_recibido, f.importe_devuelto,
                    f.importe_aplicado_deuda, f.saldo_pendiente_devolver,
                    h.numero AS habitacion_numero, i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos
             FROM fianzas f
             INNER JOIN estancias e ON f.estancia_id = e.id
             INNER JOIN habitaciones h ON e.habitacion_id = h.id
             INNER JOIN inquilinos i ON e.inquilino_id = i.id
             ORDER BY h.numero ASC'
        );
        $filas = [];
        foreach ($stmt->fetchAll() as $f) {
            $filas[] = [
                $f['habitacion_numero'],
                $f['inquilino_nombre'] . ' ' . $f['inquilino_apellidos'],
                number_format($f['importe_pactado'], 2, ',', '.') . ' €',
                number_format($f['importe_recibido'], 2, ',', '.') . ' €',
                number_format($f['importe_devuelto'], 2, ',', '.') . ' €',
                number_format($f['importe_aplicado_deuda'], 2, ',', '.') . ' €',
                number_format($f['saldo_pendiente_devolver'], 2, ',', '.') . ' €',
            ];
        }
        return [
            'cabeceras' => ['Habitación', 'Inquilino', 'Pactada', 'Recibida', 'Devuelta', 'Aplicada', 'Saldo pendiente'],
            'filas' => $filas,
        ];
    }

    private function exportarEstancias(): array
    {
        $stmt = $this->pdo->query(
            'SELECT e.fecha_entrada, e.fecha_salida_prevista, e.fecha_salida_efectiva,
                    e.renta_mensual, e.estado, h.numero AS habitacion_numero,
                    i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos
             FROM estancias e
             INNER JOIN habitaciones h ON e.habitacion_id = h.id
             INNER JOIN inquilinos i ON e.inquilino_id = i.id
             ORDER BY e.fecha_entrada DESC'
        );
        $filas = [];
        foreach ($stmt->fetchAll() as $f) {
            $filas[] = [
                $f['habitacion_numero'],
                $f['inquilino_nombre'] . ' ' . $f['inquilino_apellidos'],
                date('d/m/Y', strtotime($f['fecha_entrada'])),
                $f['fecha_salida_prevista'] ? date('d/m/Y', strtotime($f['fecha_salida_prevista'])) : '',
                $f['fecha_salida_efectiva'] ? date('d/m/Y', strtotime($f['fecha_salida_efectiva'])) : '',
                number_format($f['renta_mensual'], 2, ',', '.') . ' €',
                ucfirst($f['estado']),
            ];
        }
        return [
            'cabeceras' => ['Habitación', 'Inquilino', 'Entrada', 'Salida prevista', 'Salida efectiva', 'Renta', 'Estado'],
            'filas' => $filas,
        ];
    }

    private function exportarCargos(): array
    {
        $stmt = $this->pdo->query(
            'SELECT c.anio, c.mes, c.periodo_desde, c.periodo_hasta, c.importe_esperado,
                    c.importe_cobrado, c.estado, c.fecha_vencimiento,
                    h.numero AS habitacion_numero, i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos
             FROM cargos_alquiler c
             INNER JOIN estancias e ON c.estancia_id = e.id
             INNER JOIN habitaciones h ON e.habitacion_id = h.id
             INNER JOIN inquilinos i ON e.inquilino_id = i.id
             ORDER BY c.anio DESC, c.mes DESC'
        );
        $filas = [];
        foreach ($stmt->fetchAll() as $f) {
            $filas[] = [
                $f['habitacion_numero'],
                $f['inquilino_nombre'] . ' ' . $f['inquilino_apellidos'],
                $f['anio'] . '/' . $f['mes'],
                date('d/m/Y', strtotime($f['periodo_desde'])) . ' - ' . date('d/m/Y', strtotime($f['periodo_hasta'])),
                number_format($f['importe_esperado'], 2, ',', '.') . ' €',
                number_format($f['importe_cobrado'], 2, ',', '.') . ' €',
                number_format($f['importe_esperado'] - $f['importe_cobrado'], 2, ',', '.') . ' €',
                ucfirst($f['estado']),
                date('d/m/Y', strtotime($f['fecha_vencimiento'])),
            ];
        }
        return [
            'cabeceras' => ['Habitación', 'Inquilino', 'Año/Mes', 'Periodo', 'Esperado', 'Cobrado', 'Pendiente', 'Estado', 'Vencimiento'],
            'filas' => $filas,
        ];
    }
}
