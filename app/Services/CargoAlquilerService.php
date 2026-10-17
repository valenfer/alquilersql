<?php
declare(strict_types=1);

/**
 * Servicio CargoAlquilerService — Lógica de negocio de cargos de alquiler (mensualidades).
 *
 * Responsabilidad:
 *  - Generar cargos de alquiler por estancia y mes de forma idempotente.
 *  - Calcular prorrateo exacto para meses incompletos.
 *  - Preparar mensualidades con selección de mes y vista previa.
 *  - Recalcular estados desde cobros y aplicaciones (no guardar estado a mano).
 *
 * Reglas de prorrateo (sección 6.2 del documento):
 *  - Por defecto: renta mensual × días ocupados del mes / días naturales del mes.
 *  - Estancia que ocupa todo el mes paga exactamente una mensualidad (febrero, 30, 31 días).
 *  - Fecha de salida efectiva no se incluye en días ocupados.
 *  - Redondeo una sola vez al céntimo más próximo, empate hacia arriba.
 */

namespace App\Services;

use App\Repositories\CargoAlquilerRepository;
use App\Support\Database;

class CargoAlquilerService
{
    private CargoAlquilerRepository $repo;

    public function __construct()
    {
        $this->repo = new CargoAlquilerRepository();
    }

    /**
     * Lista todos los cargos con filtros opcionales.
     *
     * @param array $filtros
     * @return array
     */
    public function listar(array $filtros = []): array
    {
        return $this->repo->obtenerTodos($filtros);
    }

    /**
     * Obtiene un cargo por ID.
     *
     * @param int $id
     * @return array|null
     */
    public function obtener(int $id): ?array
    {
        return $this->repo->obtenerPorId($id);
    }

    /**
     * Calcula los días que una estancia ocupa dentro de un mes dado.
     *
     * Intervalo [entrada, salida): entrada cuenta, salida no cuenta.
     *
     * @param string $fechaEntrada
     * @param string|null $fechaSalidaEfectiva
     * @param int $anio
     * @param int $mes
     * @return int Días ocupados en el mes
     */
    public static function calcularDiasOcupadosEnMes(
        string $fechaEntrada,
        ?string $fechaSalidaEfectiva,
        int $anio,
        int $mes
    ): int {
        $primerDiaMes = sprintf('%04d-%02d-01', $anio, $mes);
        $ultimoDiaMes = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $anio));

        $inicio = max($fechaEntrada, $primerDiaMes);

        // La salida efectiva no cuenta en los días ocupados
        if ($fechaSalidaEfectiva !== null) {
            $fin = min(date('Y-m-d', strtotime($fechaSalidaEfectiva . ' -1 day')), $ultimoDiaMes);
        } else {
            $fin = $ultimoDiaMes;
        }

        if ($inicio > $fin) {
            return 0;
        }

        $tsInicio = strtotime($inicio);
        $tsFin = strtotime($fin);
        $dias = (int) floor(($tsFin - $tsInicio) / 86400) + 1;

        return max($dias, 0);
    }

    /**
     * Calcula los días naturales de un mes.
     *
     * @param int $anio
     * @param int $mes
     * @return int
     */
    public static function calcularDiasNaturalesMes(int $anio, int $mes): int
    {
        return (int) date('t', mktime(0, 0, 0, $mes, 1, $anio));
    }

    /**
     * Calcula el importe prorrateado de una estancia para un mes.
     *
     * Fórmula: renta mensual × días ocupados / días naturales del mes
     * Redondeo: una sola vez al céntimo más próximo, empate hacia arriba.
     *
     * @param float $rentaMensual
     * @param string $fechaEntrada
     * @param string|null $fechaSalidaEfectiva
     * @param int $anio
     * @param int $mes
     * @return array{importe: float, dias_ocupados: int, dias_naturales: int}
     */
    public static function calcularImporteProrrateado(
        float $rentaMensual,
        string $fechaEntrada,
        ?string $fechaSalidaEfectiva,
        int $anio,
        int $mes
    ): array {
        $diasOcupados = self::calcularDiasOcupadosEnMes($fechaEntrada, $fechaSalidaEfectiva, $anio, $mes);
        $diasNaturales = self::calcularDiasNaturalesMes($anio, $mes);

        // Si ocupa todo el mes, paga exactamente una mensualidad (no prorrateo)
        if ($diasOcupados >= $diasNaturales) {
            return [
                'importe' => round($rentaMensual, 2),
                'dias_ocupados' => $diasOcupados,
                'dias_naturales' => $diasNaturales,
            ];
        }

        // Prorrateo exacto: renta × días ocupados / días naturales
        // Usamos aritmética con enteros de céntimos para evitar punto flotante
        $rentaCentavos = (int) round($rentaMensual * 100);
        $importeCentavos = (int) round(($rentaCentavos * $diasOcupados) / $diasNaturales);

        // Redondeo hacia arriba en caso de empate de medio céntimo
        // (ya está implementado por round() en PHP que redondea .5 hacia arriba)

        return [
            'importe' => round($importeCentavos / 100, 2),
            'dias_ocupados' => $diasOcupados,
            'dias_naturales' => $diasNaturales,
        ];
    }

    /**
     * Determina las fechas del periodo cubierto por un cargo para una estancia y mes.
     *
     * @param int $estanciaId
     * @param int $anio
     * @param int $mes
     * @param int $diaVencimiento
     * @return array{periodo_desde: string, periodo_hasta: string, fecha_vencimiento: string, dias_ocupados: int, dias_naturales: int}
     * @throws \Exception Si la estancia no existe
     */
    public function calcularPeriodoCargo(
        int $estanciaId,
        int $anio,
        int $mes,
        int $diaVencimiento
    ): array {
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT fecha_entrada, fecha_salida_efectiva FROM estancias WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $estanciaId]);
        $estancia = $stmt->fetch();
        if (!$estancia) {
            throw new \Exception('La estancia no existe.');
        }

        $fechaEntrada = $estancia['fecha_entrada'];
        $fechaSalidaEfectiva = $estancia['fecha_salida_efectiva'];

        $primerDiaMes = sprintf('%04d-%02d-01', $anio, $mes);
        $ultimoDiaMes = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $anio));

        // El periodo comienza en el primer día del mes o en la entrada si es posterior
        $periodoDesde = max($fechaEntrada, $primerDiaMes);

        // El periodo termina en el último día del mes o en la salida efectiva si es anterior
        if ($fechaSalidaEfectiva !== null) {
            $periodoHasta = min($fechaSalidaEfectiva, $ultimoDiaMes);
            // La salida efectiva no cuenta, así que el periodo hasta es el día antes de la salida
            // pero para el periodo cubierto mostramos hasta la salida efectiva como referencia
        } else {
            $periodoHasta = $ultimoDiaMes;
        }

        $fechaVencimiento = CargoAlquilerRepository::fechaVencimientoValida($anio, $mes, $diaVencimiento);

        $diasOcupados = self::calcularDiasOcupadosEnMes($fechaEntrada, $fechaSalidaEfectiva, $anio, $mes);
        $diasNaturales = self::calcularDiasNaturalesMes($anio, $mes);

        return [
            'periodo_desde' => $periodoDesde,
            'periodo_hasta' => $periodoHasta,
            'fecha_vencimiento' => $fechaVencimiento,
            'dias_ocupados' => $diasOcupados,
            'dias_naturales' => $diasNaturales,
        ];
    }

    /**
     * Prepara cargos de alquiler para un mes y año dados (vista previa sin guardar).
     *
     * Genera un cargo por cada estancia activa o prevista que ocupe al menos un día en el mes.
     * Es idempotente: si ya existe un cargo para esa estancia y mes, no se duplica.
     *
     * @param int $anio
     * @param int $mes
     * @param int $usuarioId
     * @return array{cargos: array, total_esperado: float, errores: array}
     */
    public function prepararMensualidades(int $anio, int $mes, int $usuarioId): array
    {
        $errores = [];
        $cargos = [];
        $totalEsperado = 0.0;

        // Obtener estancias que podrían ocupar algo en este mes
        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            "SELECT e.id AS estancia_id, e.inquilino_id, e.fecha_entrada, e.fecha_salida_efectiva,
                    e.renta_mensual, e.dia_vencimiento, h.numero AS habitacion_numero,
                    i.nombre AS inquilino_nombre, i.apellidos AS inquilino_apellidos
             FROM estancias e
             INNER JOIN habitaciones h ON e.habitacion_id = h.id
             INNER JOIN inquilinos i ON e.inquilino_id = i.id
             WHERE e.estado IN ('prevista', 'activa')
               AND (
                   e.fecha_entrada <= :fin_mes
                   AND (e.fecha_salida_efectiva IS NULL OR e.fecha_salida_efectiva >= :inicio_mes)
               )
             ORDER BY h.numero ASC"
        );
        $inicioMes = sprintf('%04d-%02d-01', $anio, $mes);
        $finMes = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $anio));
        $stmt->execute([':inicio_mes' => $inicioMes, ':fin_mes' => $finMes]);
        $estancias = $stmt->fetchAll();

        foreach ($estancias as $estancia) {
            // Verificar idempotencia: no generar dos veces la misma mensualidad
            $existente = $this->repo->obtenerPorEstanciaYMes((int) $estancia['estancia_id'], $anio, $mes);
            if ($existente !== null) {
                continue; // Ya existe, saltar (idempotente)
            }

            // Calcular periodo e importe
            try {
                $periodo = $this->calcularPeriodoCargo(
                    (int) $estancia['estancia_id'],
                    $anio,
                    $mes,
                    (int) $estancia['dia_vencimiento']
                );

                $calculo = self::calcularImporteProrrateado(
                    (float) $estancia['renta_mensual'],
                    $estancia['fecha_entrada'],
                    $estancia['fecha_salida_efectiva'],
                    $anio,
                    $mes
                );

                $cargos[] = [
                    'estancia_id' => (int) $estancia['estancia_id'],
                    'inquilino_nombre' => $estancia['inquilino_nombre'] . ' ' . $estancia['inquilino_apellidos'],
                    'habitacion_numero' => (int) $estancia['habitacion_numero'],
                    'anio' => $anio,
                    'mes' => $mes,
                    'periodo_desde' => $periodo['periodo_desde'],
                    'periodo_hasta' => $periodo['periodo_hasta'],
                    'fecha_vencimiento' => $periodo['fecha_vencimiento'],
                    'importe_esperado' => $calculo['importe'],
                    'dias_ocupados' => $calculo['dias_ocupados'],
                    'dias_naturales' => $calculo['dias_naturales'],
                    'observaciones' => '',
                ];

                $totalEsperado = round($totalEsperado + $calculo['importe'], 2);

            } catch (\Exception $e) {
                $errores[] = 'Estancia #' . $estancia['estancia_id'] . ': ' . $e->getMessage();
            }
        }

        return [
            'cargos' => $cargos,
            'total_esperado' => $totalEsperado,
            'errores' => $errores,
        ];
    }

    /**
     * Guarda los cargos preparados en la base de datos (idempotente).
     *
     * @param array $cargos Cargos a guardar
     * @param int $usuarioId
     * @return array{exito: bool, creados: int, errores: array}
     */
    public function guardarCargos(array $cargos, int $usuarioId): array
    {
        $creados = 0;
        $errores = [];

        $pdo = Database::obtenerConexion();
        $pdo->beginTransaction();

        try {
            foreach ($cargos as $cargo) {
                // Doble verificación de idempotencia dentro de la transacción
                $existente = $this->repo->obtenerPorEstanciaYMes(
                    $cargo['estancia_id'],
                    $cargo['anio'],
                    $cargo['mes']
                );
                if ($existente !== null) {
                    continue;
                }

                $this->repo->crear([
                    'estancia_id' => $cargo['estancia_id'],
                    'anio' => $cargo['anio'],
                    'mes' => $cargo['mes'],
                    'periodo_desde' => $cargo['periodo_desde'],
                    'periodo_hasta' => $cargo['periodo_hasta'],
                    'importe_esperado' => $cargo['importe_esperado'],
                    'fecha_vencimiento' => $cargo['fecha_vencimiento'],
                    'observaciones' => $cargo['observaciones'] ?? '',
                    'creado_por' => $usuarioId,
                ]);
                $creados++;
            }

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            $errores[] = 'Error al guardar cargos: ' . $e->getMessage();
        }

        return [
            'exito' => empty($errores),
            'creados' => $creados,
            'errores' => $errores,
        ];
    }

    /**
     * Recupera el estado de un cargo desde sus cobros y aplicaciones.
     *
     * No guarda el estado manualmente; se calcula siempre desde los movimientos válidos.
     *
     * @param int $id
     * @return array{estado: string, importe_cobrado: float, saldo_pendiente: float}
     */
    public function recuperarEstado(int $id): array
    {
        $importeCobrado = $this->repo->totalAplicadoACargo($id);
        // totalAplicadoACargo está en AplicacionCobroRepository, pero lo delegamos aquí por simplicidad
        // En realidad necesitamos acceder al repositorio de aplicaciones. Lo hacemos indirectamente.

        $pdo = Database::obtenerConexion();
        $stmt = $pdo->prepare(
            'SELECT importe_esperado FROM cargos_alquiler WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch();
        if (!$fila) {
            return ['estado' => 'pendiente', 'importe_cobrado' => 0.0, 'saldo_pendiente' => 0.0];
        }

        $importeEsperado = (float) $fila['importe_esperado'];
        $saldoPendiente = round($importeEsperado - $importeCobrado, 2);

        if ($importeCobrado <= 0) {
            $estado = 'pendiente';
        } elseif ($importeCobrado >= $importeEsperado) {
            $estado = 'pagado';
        } else {
            $estado = 'parcial';
        }

        return [
            'estado' => $estado,
            'importe_cobrado' => $importeCobrado,
            'saldo_pendiente' => $saldoPendiente,
        ];
    }
}
