<?php
declare(strict_types=1);

/**
 * Servicio ContratoService — Lógica de negocio de contratos de alquiler.
 *
 * Responsabilidad:
 *  - Coordinar el repositorio para operaciones de contratos.
 *  - Gestionar la renovación de contratos (marcar el antiguo como renovado y crear uno nuevo).
 *  - Calcular alertas de vencimiento (2 meses, 1 mes, 15 días antes).
 *  - Marcar contratos vencidos automáticamente.
 *
 * Reglas de negocio:
 *  - Un contrato tiene fecha de inicio y fin.
 *  - Al renovar, se marca el contrato anterior como 'renovado' y se crea uno nuevo.
 *  - Las alertas se generan 60, 30 y 15 días antes del vencimiento.
 */

namespace App\Services;

use App\Repositories\ContratoRepository;
use App\Repositories\EstanciaRepository;
use App\Support\Database;

class ContratoService
{
    private ContratoRepository $repo;
    private EstanciaRepository $estanciaRepo;

    public function __construct()
    {
        $this->repo = new ContratoRepository();
        $this->estanciaRepo = new EstanciaRepository();
    }

    /**
     * Obtiene todos los contratos.
     *
     * @return array
     */
    public function listar(): array
    {
        return $this->repo->obtenerTodos();
    }

    /**
     * Obtiene un contrato por ID.
     *
     * @param int $id
     * @return array|null
     */
    public function obtener(int $id): ?array
    {
        return $this->repo->obtenerPorId($id);
    }

    /**
     * Obtiene el contrato vigente de una estancia.
     *
     * @param int $estanciaId
     * @return array|null
     */
    public function obtenerVigentePorEstancia(int $estanciaId): ?array
    {
        return $this->repo->obtenerVigentePorEstancia($estanciaId);
    }

    /**
     * Obtiene contratos que vencen dentro de un número de días dado.
     *
     * @param int $dias
     * @return array
     */
    public function obtenerPorVencer(int $dias): array
    {
        return $this->repo->obtenerPorVencer($dias);
    }

    /**
     * Obtiene todos los contratos por vencer (60, 30 y 15 días).
     *
     * @return array Clasificados por días de antelación
     */
    public function obtenerAlertasVencimiento(): array
    {
        $alertas = [
            '60_dias' => [],
            '30_dias' => [],
            '15_dias' => [],
            'vencidos' => [],
        ];

        // Contratos que vencen en 60 días
        $alertas['60_dias'] = $this->repo->obtenerPorVencer(60);

        // Contratos que vencen en 30 días (excluir los de 60 días para no duplicar)
        $alertas['30_dias'] = $this->repo->obtenerPorVencer(30);

        // Contratos que vencen en 15 días (excluir los de 30 días)
        $alertas['15_dias'] = $this->repo->obtenerPorVencer(15);

        // Contratos ya vencidos
        $alertas['vencidos'] = $this->repo->obtenerVencidos();

        return $alertas;
    }

    /**
     * Crea un nuevo contrato para una estancia.
     *
     * @param array $datos
     * @param int $usuarioId
     * @return array{exito: bool, id: int|null, errores: array}
     */
    public function crear(array $datos, int $usuarioId): array
    {
        $errores = [];

        $estanciaId = (int) ($datos['estancia_id'] ?? 0);
        $fechaInicio = $datos['fecha_inicio'] ?? null;
        $fechaFin = $datos['fecha_fin'] ?? null;
        $rentaMensual = (float) ($datos['renta_mensual'] ?? 0);
        $condiciones = trim($datos['condiciones'] ?? '');
        $clausulasAdicionales = trim($datos['clausulas_adicionales'] ?? '');

        // Validaciones
        if ($estanciaId <= 0) {
            $errores[] = 'Debe seleccionar una estancia.';
        }
        if ($fechaInicio === null) {
            $errores[] = 'La fecha de inicio es obligatoria.';
        }
        if ($fechaFin === null) {
            $errores[] = 'La fecha de finalización es obligatoria.';
        }
        if ($fechaInicio !== null && $fechaFin !== null && $fechaFin < $fechaInicio) {
            $errores[] = 'La fecha de finalización debe ser posterior a la de inicio.';
        }
        if ($rentaMensual <= 0) {
            $errores[] = 'La renta mensual debe ser mayor a 0.';
        }

        // Verificar que no exista ya un contrato vigente para esta estancia
        $vigente = $this->repo->obtenerVigentePorEstancia($estanciaId);
        if ($vigente !== null) {
            $errores[] = 'Esta estancia ya tiene un contrato vigente. Debe renovarlo o finalizar el actual primero.';
        }

        if (!empty($errores)) {
            return ['exito' => false, 'id' => null, 'errores' => $errores];
        }

        $id = $this->repo->crear([
            'estancia_id' => $estanciaId,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'renta_mensual' => $rentaMensual,
            'condiciones' => $condiciones,
            'clausulas_adicionales' => $clausulasAdicionales,
            'estado' => 'vigente',
            'documento_adjunto_id' => $datos['documento_adjunto_id'] ?? null,
            'renovado_de_id' => null,
        ]);

        return ['exito' => true, 'id' => $id, 'errores' => []];
    }

    /**
     * Renueva un contrato. Marca el contrato actual como 'renovado' y crea uno nuevo.
     *
     * @param int $contratoId ID del contrato a renovar
     * @param array $datos Nuevos datos del contrato
     * @param int $usuarioId ID del usuario
     * @return array{exito: bool, id: int|null, errores: array}
     */
    public function renovar(int $contratoId, array $datos, int $usuarioId): array
    {
        $errores = [];

        $contratoActual = $this->repo->obtenerPorId($contratoId);
        if ($contratoActual === null) {
            return ['exito' => false, 'id' => null, 'errores' => ['El contrato no existe.']];
        }

        if ($contratoActual['estado'] !== 'vigente') {
            return ['exito' => false, 'id' => null, 'errores' => ['Solo se pueden renovar contratos vigentes.']];
        }

        $fechaInicio = $datos['fecha_inicio'] ?? null;
        $fechaFin = $datos['fecha_fin'] ?? null;
        $rentaMensual = (float) ($datos['renta_mensual'] ?? $contratoActual['renta_mensual']);
        $condiciones = trim($datos['condiciones'] ?? $contratoActual['condiciones'] ?? '');
        $clausulasAdicionales = trim($datos['clausulas_adicionales'] ?? $contratoActual['clausulas_adicionales'] ?? '');

        if ($fechaInicio === null) {
            $errores[] = 'La fecha de inicio es obligatoria.';
        }
        if ($fechaFin === null) {
            $errores[] = 'La fecha de finalización es obligatoria.';
        }
        if ($fechaInicio !== null && $fechaFin !== null && $fechaFin < $fechaInicio) {
            $errores[] = 'La fecha de finalización debe ser posterior a la de inicio.';
        }
        if ($rentaMensual <= 0) {
            $errores[] = 'La renta mensual debe ser mayor a 0.';
        }

        if (!empty($errores)) {
            return ['exito' => false, 'id' => null, 'errores' => $errores];
        }

        $pdo = Database::obtenerConexion();
        $pdo->beginTransaction();

        try {
            // Marcar el contrato actual como renovado
            $this->repo->marcarComoRenovado($contratoId);

            // Crear el nuevo contrato
            $nuevoId = $this->repo->crear([
                'estancia_id' => $contratoActual['estancia_id'],
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'renta_mensual' => $rentaMensual,
                'condiciones' => $condiciones,
                'clausulas_adicionales' => $clausulasAdicionales,
                'estado' => 'vigente',
                'documento_adjunto_id' => $datos['documento_adjunto_id'] ?? null,
                'renovado_de_id' => $contratoId,
            ]);

            $pdo->commit();

            return ['exito' => true, 'id' => $nuevoId, 'errores' => []];

        } catch (\Exception $e) {
            $pdo->rollBack();
            return [
                'exito' => false,
                'id' => null,
                'errores' => ['Error al renovar el contrato: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Marca un contrato como vencido.
     *
     * @param int $id
     * @return array{exito: bool, errores: array}
     */
    public function marcarVencido(int $id): array
    {
        $contrato = $this->repo->obtenerPorId($id);
        if ($contrato === null) {
            return ['exito' => false, 'errores' => ['El contrato no existe.']];
        }

        if ($contrato['estado'] !== 'vigente') {
            return ['exito' => false, 'errores' => ['El contrato ya no está vigente.']];
        }

        $resultado = $this->repo->actualizar($id, ['estado' => 'vencido']);
        return ['exito' => $resultado, 'errores' => $resultado ? [] : ['No se pudo marcar como vencido.']];
    }

    /**
     * Ejecuta la marcación automática de contratos vencidos.
     * Útil para ejecutar periódicamente (por ejemplo, mediante un script CLI).
     *
     * @return int Número de contratos marcados como vencidos
     */
    public function marcarVencidosAutomaticamente(): int
    {
        return $this->repo->marcarVencidosAutomaticamente();
    }

    /**
     * Obtiene todos los contratos de una estancia.
     *
     * @param int $estanciaId
     * @return array
     */
    public function obtenerPorEstancia(int $estanciaId): array
    {
        return $this->repo->obtenerPorEstancia($estanciaId);
    }
}
