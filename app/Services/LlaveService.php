<?php
declare(strict_types=1);

/**
 * Servicio LlaveService — Lógica de negocio de llaves.
 *
 * Responsabilidad:
 *  - Coordinar el repositorio para operaciones de llaves.
 *  - Impedir devoluciones superiores a lo entregado pendiente.
 *  - Impedir asignación simultánea de una misma unidad identificada.
 *  - Una pérdida cierra la unidad como perdida, no como devuelta.
 *  - Una reposición se registra como nueva entrega vinculada al incidente.
 *
 * Reglas (sección 10 del documento):
 *  - Catálogo de tipos de llave (portal, vivienda, habitación, buzón).
 *  - Unidades con identificador o cantidades sin códigos físicos.
 *  - Entregas y devoluciones parciales.
 *  - Impedir devoluciones superiores a lo entregado pendiente.
 */

namespace App\Services;

use App\Repositories\LlaveRepository;
use App\Repositories\MovimientoLlaveRepository;
use App\Repositories\TipoLlaveRepository;
use App\Support\Database;

class LlaveService
{
    private LlaveRepository $repo;
    private MovimientoLlaveRepository $movRepo;
    private TipoLlaveRepository $tipoRepo;

    public function __construct()
    {
        $this->repo = new LlaveRepository();
        $this->movRepo = new MovimientoLlaveRepository();
        $this->tipoRepo = new TipoLlaveRepository();
    }

    /**
     * Obtiene todos los tipos de llave.
     *
     * @return array
     */
    public function listarTipos(): array
    {
        return $this->tipoRepo->obtenerTodos();
    }

    /**
     * Obtiene todas las unidades de llave.
     *
     * @return array
     */
    public function listarLlaves(): array
    {
        return $this->repo->obtenerTodas();
    }

    /**
     * Obtiene movimientos de llave de una estancia con pendiente calculado.
     *
     * @param int $estanciaId
     * @return array
     */
    public function obtenerMovimientosEstancia(int $estanciaId): array
    {
        $movimientos = $this->movRepo->obtenerMovimientosPorEstancia($estanciaId);

        // Calcular pendiente por tipo de llave
        $tipos = $this->tipoRepo->obtenerTodos();
        $pendientes = [];
        foreach ($tipos as $tipo) {
            $pendientes[$tipo['id']] = $this->repo->calcularPendienteDevolver($estanciaId, $tipo['id']);
        }

        return [
            'movimientos' => $movimientos,
            'pendientes' => $pendientes,
        ];
    }

    /**
     * Registra una entrega de llave.
     *
     * @param int $estanciaId
     * @param int|null $llaveId (unidad identificada, opcional)
     * @param int $cantidad
     * @param string $fecha
     * @param string|null $observaciones
     * @param int $usuarioId
     * @return array{exito: bool, errores: array}
     */
    public function entregar(
        int $estanciaId,
        ?int $llaveId,
        int $cantidad,
        string $fecha,
        ?string $observaciones,
        int $usuarioId
    ): array {
        $errores = [];

        if ($cantidad <= 0) {
            $errores[] = 'La cantidad debe ser mayor a 0.';
        }

        // Si es unidad identificada, verificar que no esté ya entregada
        if ($llaveId !== null && $this->movRepo->unidadEntregada($llaveId)) {
            $errores[] = 'Esta unidad de llave ya está entregada y no está disponible.';
        }

        if (!empty($errores)) {
            return ['exito' => false, 'errores' => $errores];
        }

        $this->movRepo->crear([
            'estancia_id' => $estanciaId,
            'llave_id' => $llaveId,
            'tipo' => 'entrega',
            'cantidad' => $cantidad,
            'fecha' => $fecha,
            'observaciones' => $observaciones,
            'registrado_por' => $usuarioId,
        ]);

        // Si es unidad identificada, marcar como entregada
        if ($llaveId !== null) {
            $this->repo->actualizarEstado($llaveId, 'entregada');
        }

        return ['exito' => true, 'errores' => []];
    }

    /**
     * Registra una devolución de llave.
     *
     * @param int $estanciaId
     * @param int|null $llaveId
     * @param int $cantidad
     * @param string $fecha
     * @param string|null $observaciones
     * @param int $usuarioId
     * @return array{exito: bool, errores: array}
     */
    public function devolver(
        int $estanciaId,
        ?int $llaveId,
        int $cantidad,
        string $fecha,
        ?string $observaciones,
        int $usuarioId
    ): array {
        $errores = [];

        if ($cantidad <= 0) {
            $errores[] = 'La cantidad debe ser mayor a 0.';
        }

        // Obtener tipo de llave para verificar pendiente
        $tipoLlaveId = null;
        if ($llaveId !== null) {
            $stmt = \App\Support\Database::obtenerConexion()->prepare(
                'SELECT tipo_llave_id FROM llaves WHERE id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $llaveId]);
            $fila = $stmt->fetch();
            if ($fila) {
                $tipoLlaveId = (int) $fila['tipo_llave_id'];
            }
        }

        $verificacion = $this->movRepo->verificarDevolucion($estanciaId, $tipoLlaveId, $cantidad);
        if (!$verificacion['valido']) {
            $errores[] = 'No se pueden devolver ' . $cantidad .
                ' llave(s). Pendiente de devolver: ' . $verificacion['pendiente'] . '.';
        }

        if (!empty($errores)) {
            return ['exito' => false, 'errores' => $errores];
        }

        $this->movRepo->crear([
            'estancia_id' => $estanciaId,
            'llave_id' => $llaveId,
            'tipo' => 'devolucion',
            'cantidad' => $cantidad,
            'fecha' => $fecha,
            'observaciones' => $observaciones,
            'registrado_por' => $usuarioId,
        ]);

        // Si es unidad identificada, marcar como disponible
        if ($llaveId !== null) {
            $this->repo->actualizarEstado($llaveId, 'disponible');
        }

        return ['exito' => true, 'errores' => []];
    }

    /**
     * Registra una pérdida de llave.
     *
     * @param int $estanciaId
     * @param int|null $llaveId
     * @param int $cantidad
     * @param string $fecha
     * @param string|null $observaciones
     * @param int $usuarioId
     * @return array{exito: bool, errores: array}
     */
    public function perder(
        int $estanciaId,
        ?int $llaveId,
        int $cantidad,
        string $fecha,
        ?string $observaciones,
        int $usuarioId
    ): array {
        $errores = [];

        if ($cantidad <= 0) {
            $errores[] = 'La cantidad debe ser mayor a 0.';
        }

        if (!empty($errores)) {
            return ['exito' => false, 'errores' => $errores];
        }

        $this->movRepo->crear([
            'estancia_id' => $estanciaId,
            'llave_id' => $llaveId,
            'tipo' => 'perdida',
            'cantidad' => $cantidad,
            'fecha' => $fecha,
            'observaciones' => $observaciones,
            'registrado_por' => $usuarioId,
        ]);

        // Si es unidad identificada, marcar como perdida
        if ($llaveId !== null) {
            $this->repo->actualizarEstado($llaveId, 'perdida');
        }

        return ['exito' => true, 'errores' => []];
    }

    /**
     * Registra una reposición de llave (nueva entrega vinculada a incidente).
     *
     * @param int $estanciaId
     * @param int|null $llaveId
     * @param int $cantidad
     * @param string $fecha
     * @param string|null $observaciones
     * @param int $usuarioId
     * @return array{exito: bool, errores: array}
     */
    public function reponer(
        int $estanciaId,
        ?int $llaveId,
        int $cantidad,
        string $fecha,
        ?string $observaciones,
        int $usuarioId
    ): array {
        $errores = [];

        if ($cantidad <= 0) {
            $errores[] = 'La cantidad debe ser mayor a 0.';
        }

        if (!empty($errores)) {
            return ['exito' => false, 'errores' => $errores];
        }

        $this->movRepo->crear([
            'estancia_id' => $estanciaId,
            'llave_id' => $llaveId,
            'tipo' => 'reposicion',
            'cantidad' => $cantidad,
            'fecha' => $fecha,
            'observaciones' => $observaciones,
            'registrado_por' => $usuarioId,
        ]);

        // Si es unidad identificada, marcar como entregada
        if ($llaveId !== null) {
            $this->repo->actualizarEstado($llaveId, 'entregada');
        }

        return ['exito' => true, 'errores' => []];
    }
}
