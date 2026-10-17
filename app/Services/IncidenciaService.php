<?php
declare(strict_types=1);

/**
 * Servicio IncidenciaService — Lógica de negocio de incidencias (averías/reparaciones).
 *
 * Responsabilidad:
 *  - Coordinar el repositorio para operaciones de incidencia.
 *  - Una incidencia por sí sola no es un gasto hasta que existe un importe registrado.
 *  - Estados: pendiente, en curso, resuelta.
 */

namespace App\Services;

use App\Repositories\IncidenciaRepository;

class IncidenciaService
{
    private IncidenciaRepository $repo;

    public function __construct()
    {
        $this->repo = new IncidenciaRepository();
    }

    /**
     * Obtiene todas las incidencias con filtros.
     *
     * @param array $filtros
     * @return array
     */
    public function listar(array $filtros = []): array
    {
        return $this->repo->obtenerTodos($filtros);
    }

    /**
     * Crea una nueva incidencia.
     *
     * @param array $datos habitacion_id, descripcion, fecha_aviso, estado, fecha_resolucion
     * @return array{exito: bool, id: int|null, errores: array}
     */
    public function crear(array $datos): array
    {
        $errores = [];

        $descripcion = trim($datos['descripcion'] ?? '');
        $fechaAviso = $datos['fecha_aviso'] ?? null;
        $estado = $datos['estado'] ?? 'pendiente';
        $fechaResolucion = $datos['fecha_resolucion'] ?? null;

        if ($descripcion === '') {
            $errores[] = 'La descripción es obligatoria.';
        }
        if ($fechaAviso === null) {
            $errores[] = 'La fecha de aviso es obligatoria.';
        }
        if (!in_array($estado, ['pendiente', 'en_curso', 'resuelta'], true)) {
            $errores[] = 'El estado no es válido.';
        }

        if (!empty($errores)) {
            return ['exito' => false, 'id' => null, 'errores' => $errores];
        }

        $id = $this->repo->crear([
            'habitacion_id' => $datos['habitacion_id'] ?? null,
            'descripcion' => $descripcion,
            'fecha_aviso' => $fechaAviso,
            'estado' => $estado,
            'fecha_resolucion' => $fechaResolucion,
        ]);

        return ['exito' => true, 'id' => $id, 'errores' => []];
    }

    /**
     * Actualiza el estado de una incidencia.
     *
     * @param int $id
     * @param string $estado
     * @param string|null $fechaResolucion
     * @return array{exito: bool, errores: array}
     */
    public function actualizarEstado(int $id, string $estado, ?string $fechaResolucion = null): array
    {
        if (!in_array($estado, ['pendiente', 'en_curso', 'resuelta'], true)) {
            return ['exito' => false, 'errores' => ['Estado no válido.']];
        }

        $resultado = $this->repo->actualizarEstado($id, $estado, $fechaResolucion);
        return ['exito' => $resultado, 'errores' => $resultado ? [] : ['No se pudo actualizar la incidencia.']];
    }
}
