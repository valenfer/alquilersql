<?php
declare(strict_types=1);

/**
 * Servicio EstanciaService — Lógica de negocio de estancias (ocupaciones).
 *
 * Responsabilidad:
 *  - Coordinar el repositorio para operaciones de estancias.
 *  - Aplicar reglas de negocio críticas:
 *    * Intervalo [entrada, salida): el día de entrada cuenta, el de salida no.
 *    * Impedir solapamientos de ocupación en una misma habitación.
 *    * Impedir que una persona tenga dos estancias simultáneas en distintas habitaciones.
 *    * Permitir salida y entrada de personas distintas el mismo día.
 *    * Derivar estado coherentemente (prevista, activa, finalizada).
 *  - Usar transacciones para impedir conflictos por dos formularios abiertos al mismo tiempo.
 */

namespace App\Services;

use App\Repositories\EstanciaRepository;
use App\Support\Database;

class EstanciaService
{
    private EstanciaRepository $repo;

    public function __construct()
    {
        $this->repo = new EstanciaRepository();
    }

    /**
     * Obtiene todas las estancias con filtros opcionales.
     *
     * @param array $filtros
     * @return array
     */
    public function listar(array $filtros = []): array
    {
        return $this->repo->obtenerTodos($filtros);
    }

    /**
     * Obtiene una estancia por ID con datos relacionados.
     *
     * @param int $id
     * @return array|null
     */
    public function obtener(int $id): ?array
    {
        return $this->repo->obtenerPorId($id);
    }

    /**
     * Deriva el estado de una estancia basado en fechas.
     *
     * Reglas:
     * - 'prevista': fecha de entrada en el futuro
     * - 'activa': fecha de entrada ya pasó y no hay fecha de salida efectiva
     * - 'finalizada': tiene fecha de salida efectiva
     *
     * @param string $fechaEntrada
     * @param string|null $fechaSalidaEfectiva
     * @return string
     */
    public static function derivarEstado(string $fechaEntrada, ?string $fechaSalidaEfectiva): string
    {
        $hoy = date('Y-m-d');

        if ($fechaSalidaEfectiva !== null) {
            return 'finalizada';
        }

        if ($fechaEntrada > $hoy) {
            return 'prevista';
        }

        return 'activa';
    }

    /**
     * Valida y crea una estancia dentro de una transacción.
     *
     * Reglas de integridad:
     * - No solapamiento en la misma habitación.
     * - No solapamiento de persona en distintas habitaciones.
     * - Permitir salida y entrada el mismo día (intervalo [entrada, salida)).
     *
     * @param array $datos Datos de la estancia
     * @param int $usuarioId ID del usuario que registra
     * @return array{exito: bool, id: int|null, errores: array}
     */
    public function crear(array $datos, int $usuarioId): array
    {
        $errores = [];

        $inquilinoId = (int) ($datos['inquilino_id'] ?? 0);
        $habitacionId = (int) ($datos['habitacion_id'] ?? 0);
        $fechaEntrada = $datos['fecha_entrada'] ?? null;
        $fechaSalidaPrevista = $datos['fecha_salida_prevista'] ?? null;
        $fechaSalidaEfectiva = $datos['fecha_salida_efectiva'] ?? null;
        $rentaMensual = $datos['renta_mensual'] ?? null;
        $diaVencimiento = (int) ($datos['dia_vencimiento'] ?? 1);
        $fianzaPactada = (float) ($datos['fianza_pactada'] ?? 0.00);
        $referenciaContrato = trim($datos['referencia_contrato'] ?? '');

        // Validaciones básicas
        if ($inquilinoId <= 0) {
            $errores[] = 'Debe seleccionar un inquilino.';
        }
        if ($habitacionId <= 0) {
            $errores[] = 'Debe seleccionar una habitación.';
        }
        if ($fechaEntrada === null) {
            $errores[] = 'La fecha de entrada es obligatoria.';
        }
        if ($rentaMensual === null || $rentaMensual < 0) {
            $errores[] = 'La renta mensual es obligatoria y debe ser mayor o igual a 0.';
        }
        if ($diaVencimiento < 1 || $diaVencimiento > 31) {
            $errores[] = 'El día de vencimiento debe estar entre 1 y 31.';
        }

        if (!empty($errores)) {
            return ['exito' => false, 'id' => null, 'errores' => $errores];
        }

        // Derivar estado
        $estado = self::derivarEstado($fechaEntrada, $fechaSalidaEfectiva);

        // Verificar solapamientos dentro de una transacción
        $pdo = Database::obtenerConexion();
        $pdo->beginTransaction();

        try {
            // 1. Verificar solapamiento en la misma habitación
            $solapamientoHab = $this->repo->existeSolapamientoHabitacion(
                $habitacionId,
                $fechaEntrada,
                $fechaSalidaEfectiva
            );
            if ($solapamientoHab !== null) {
                $pdo->rollBack();
                return [
                    'exito' => false,
                    'id' => null,
                    'errores' => [
                        'La habitación ya tiene una ocupación solapante con esas fechas.',
                    ],
                ];
            }

            // 2. Verificar que la persona no tenga otra estancia simultánea en otra habitación
            $solapamientoPersona = $this->repo->existeSolapamientoPersona(
                $inquilinoId,
                $fechaEntrada,
                $fechaSalidaEfectiva
            );
            if ($solapamientoPersona !== null) {
                $pdo->rollBack();
                return [
                    'exito' => false,
                    'id' => null,
                    'errores' => [
                        'El inquilino ya tiene una estancia activa o prevista en otra habitación con esas fechas.',
                    ],
                ];
            }

            // 3. Crear la estancia
            $id = $this->repo->crear([
                'inquilino_id' => $inquilinoId,
                'habitacion_id' => $habitacionId,
                'fecha_entrada' => $fechaEntrada,
                'fecha_salida_prevista' => $fechaSalidaPrevista,
                'fecha_salida_efectiva' => $fechaSalidaEfectiva,
                'renta_mensual' => $rentaMensual,
                'dia_vencimiento' => $diaVencimiento,
                'fianza_pactada' => $fianzaPactada,
                'referencia_contrato' => $referenciaContrato,
                'contrato_adjunto_id' => null,
                'estado' => $estado,
            ]);

            $pdo->commit();
            return ['exito' => true, 'id' => $id, 'errores' => []];

        } catch (\Exception $e) {
            $pdo->rollBack();
            return [
                'exito' => false,
                'id' => null,
                'errores' => ['Error al crear la estancia: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Valida y actualiza una estancia dentro de una transacción.
     *
     * @param int $id ID de la estancia
     * @param array $datos Datos a actualizar
     * @param int $usuarioId ID del usuario
     * @return array{exito: bool, errores: array}
     */
    public function actualizar(int $id, array $datos, int $usuarioId): array
    {
        $errores = [];

        $fechaEntrada = $datos['fecha_entrada'] ?? null;
        $fechaSalidaEfectiva = $datos['fecha_salida_efectiva'] ?? null;
        $habitacionId = (int) ($datos['habitacion_id'] ?? 0);
        $inquilinoId = (int) ($datos['inquilino_id'] ?? 0);

        if ($fechaEntrada === null) {
            $errores[] = 'La fecha de entrada es obligatoria.';
        }

        if (!empty($errores)) {
            return ['exito' => false, 'errores' => $errores];
        }

        $pdo = Database::obtenerConexion();
        $pdo->beginTransaction();

        try {
            // Verificar solapamientos excluyendo esta estancia
            $solapamientoHab = $this->repo->existeSolapamientoHabitacion(
                $habitacionId,
                $fechaEntrada,
                $fechaSalidaEfectiva,
                $id
            );
            if ($solapamientoHab !== null) {
                $pdo->rollBack();
                return [
                    'exito' => false,
                    'errores' => ['La habitación ya tiene una ocupación solapante con esas fechas.'],
                ];
            }

            $solapamientoPersona = $this->repo->existeSolapamientoPersona(
                $inquilinoId,
                $fechaEntrada,
                $fechaSalidaEfectiva,
                $id
            );
            if ($solapamientoPersona !== null) {
                $pdo->rollBack();
                return [
                    'exito' => false,
                    'errores' => ['El inquilino ya tiene una estancia simultánea en otra habitación.'],
                ];
            }

            $estado = self::derivarEstado($fechaEntrada, $fechaSalidaEfectiva);
            $datos['estado'] = $estado;

            $resultado = $this->repo->actualizar($id, $datos);

            $pdo->commit();
            return ['exito' => $resultado, 'errores' => $resultado ? [] : ['No se pudo actualizar la estancia.']];

        } catch (\Exception $e) {
            $pdo->rollBack();
            return [
                'exito' => false,
                'errores' => ['Error al actualizar la estancia: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Finaliza una estancia estableciendo la fecha de salida efectiva.
     *
     * @param int $id ID de la estancia
     * @param string $fechaSalidaEfectiva Fecha de salida efectiva (Y-m-d)
     * @return array{exito: bool, error: string|null}
     */
    public function finalizar(int $id, string $fechaSalidaEfectiva): array
    {
        $resultado = $this->repo->finalizar($id, $fechaSalidaEfectiva);
        if (!$resultado) {
            return ['exito' => false, 'error' => 'No se pudo finalizar la estancia.'];
        }
        return ['exito' => true, 'error' => null];
    }

    /**
     * Obtiene la estancia activa actual de una habitación.
     *
     * @param int $habitacionId
     * @return array|null
     */
    public function obtenerActivaPorHabitacion(int $habitacionId): ?array
    {
        return $this->repo->obtenerActivaPorHabitacion($habitacionId);
    }
}
