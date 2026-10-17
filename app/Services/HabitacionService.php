<?php
declare(strict_types=1);

/**
 * Servicio HabitacionService — Lógica de negocio de habitaciones.
 *
 * Responsabilidad:
 *  - Coordinar el repositorio para operaciones de habitaciones.
 *  - Aplicar reglas de negocio: no borrar habitación con historial, no crear quinta habitación,
 *    validar número único (1-4), validar estado.
 *  - Retornar resultados estructurados con éxito/error para el controlador.
 */

namespace App\Services;

use App\Repositories\HabitacionRepository;

class HabitacionService
{
    private HabitacionRepository $repo;

    public function __construct()
    {
        $this->repo = new HabitacionRepository();
    }

    /**
     * Obtiene todas las habitaciones.
     *
     * @return array
     */
    public function listar(): array
    {
        return $this->repo->obtenerTodas();
    }

    /**
     * Obtiene una habitación por ID.
     *
     * @param int $id
     * @return array|null
     */
    public function obtener(int $id): ?array
    {
        return $this->repo->obtenerPorId($id);
    }

    /**
     * Valida y crea una habitación.
     *
     * Reglas:
     * - Número debe estar entre 1 y 4.
     * - No se permite crear una quinta habitación (máximo 4).
     * - El número debe ser único.
     *
     * @param array $datos Datos de la habitación
     * @return array{exito: bool, id: int|null, errores: array}
     */
    public function crear(array $datos): array
    {
        $errores = [];

        $numero = (int) ($datos['numero'] ?? 0);
        $nombre = trim($datos['nombre'] ?? '');
        $descripcion = trim($datos['descripcion'] ?? '');
        $rentaOrientativa = $datos['renta_orientativa'] !== '' ? (float) $datos['renta_orientativa'] : null;
        $estado = $datos['estado'] ?? 'libre';

        if ($numero < 1 || $numero > 4) {
            $errores[] = 'El número de habitación debe estar entre 1 y 4.';
        }

        if ($nombre === '') {
            $errores[] = 'El nombre de la habitación es obligatorio.';
        }

        if ($estado !== 'libre' && $estado !== 'ocupada' && $estado !== 'no_disponible') {
            $errores[] = 'El estado no es válido.';
        }

        // Verificar unicidad del número
        $existente = $this->repo->obtenerPorNumero($numero);
        if ($existente !== null) {
            $errores[] = "Ya existe la habitación número $numero.";
        }

        // Verificar que no se cree una quinta habitación
        if ($existente === null) {
            $total = $this->repo->contarTotal();
            if ($total >= 4) {
                $errores[] = 'No se puede crear una quinta habitación. El piso tiene exactamente cuatro habitaciones fijas.';
            }
        }

        if (!empty($errores)) {
            return ['exito' => false, 'id' => null, 'errores' => $errores];
        }

        $id = $this->repo->crear($numero, $nombre, $descripcion, $rentaOrientativa, $estado);
        return ['exito' => true, 'id' => $id, 'errores' => []];
    }

    /**
     * Valida y actualiza una habitación.
     *
     * @param int $id ID de la habitación
     * @param array $datos Datos a actualizar
     * @return array{exito: bool, errores: array}
     */
    public function actualizar(int $id, array $datos): array
    {
        $errores = [];

        $nombre = trim($datos['nombre'] ?? '');
        $descripcion = trim($datos['descripcion'] ?? '');
        $rentaOrientativa = $datos['renta_orientativa'] !== '' ? (float) $datos['renta_orientativa'] : null;
        $estado = $datos['estado'] ?? 'libre';

        if ($nombre === '') {
            $errores[] = 'El nombre de la habitación es obligatorio.';
        }

        if ($estado !== 'libre' && $estado !== 'ocupada' && $estado !== 'no_disponible') {
            $errores[] = 'El estado no es válido.';
        }

        // Verificar que el número no sea duplicado por otra habitación (el número no se edita normalmente,
        # pero si se permite editar, verificar unicidad)
        $existente = $this->repo->obtenerPorNumero((int) $datos['numero'] ?? 0);
        if ($existente !== null && $existente['id'] !== $id) {
            $errores[] = 'Ya existe otra habitación con ese número.';
        }

        if (!empty($errores)) {
            return ['exito' => false, 'errores' => $errores];
        }

        $numero = (int) ($datos['numero'] ?? 0);
        $resultado = $this->repo->actualizar($id, $nombre, $descripcion, $rentaOrientativa, $estado);
        return ['exito' => $resultado, 'errores' => $resultado ? [] : ['No se pudo actualizar la habitación.']];
    }

    /**
     * Elimina una habitación si no tiene historial.
     *
     * @param int $id ID de la habitación
     * @return array{exito: bool, error: string|null}
     */
    public function eliminar(int $id): array
    {
        if ($this->repo->tieneHistorial($id)) {
            return [
                'exito' => false,
                'error' => 'No se puede eliminar la habitación porque tiene historial de ocupaciones.',
            ];
        }

        $resultado = $this->repo->eliminar($id);
        if (!$resultado) {
            return ['exito' => false, 'error' => 'No se pudo eliminar la habitación.'];
        }

        return ['exito' => true, 'error' => null];
    }
}
