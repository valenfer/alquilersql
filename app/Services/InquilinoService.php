<?php
declare(strict_types=1);

/**
 * Servicio InquilinoService — Lógica de negocio de inquilinos.
 *
 * Responsabilidad:
 *  - Coordinar el repositorio para operaciones de inquilinos.
 *  - Validar formato de DNI/NIE (letra de control).
 *  - Normalizar el número de documento (sin espacios, mayúsculas).
 *  - Verificar unicidad del documento.
 *  - No permitir borrado en cascada de información económica al archivar.
 */

namespace App\Services;

use App\Repositories\InquilinoRepository;

class InquilinoService
{
    private InquilinoRepository $repo;

    public function __construct()
    {
        $this->repo = new InquilinoRepository();
    }

    /**
     * Obtiene todos los inquilinos, opcionalmente filtrados por estado.
     *
     * @param string|null $estado
     * @return array
     */
    public function listar(?string $estado = null): array
    {
        return $this->repo->obtenerTodos($estado);
    }

    /**
     * Obtiene un inquilino por ID.
     *
     * @param int $id
     * @return array|null
     */
    public function obtener(int $id): ?array
    {
        return $this->repo->obtenerPorId($id);
    }

    /**
     * Normaliza un número de documento: elimina espacios y pasa a mayúsculas.
     *
     * @param string $numero
     * @return string
     */
    private static function normalizarDocumento(string $numero): string
    {
        return strtoupper(preg_replace('/\s+/', '', $numero));
    }

    /**
     * Valida la letra de un DNI español.
     *
     * @param string $numero Número sin letra (8 dígitos)
     * @param string $letra Letra a validar
     * @return bool
     */
    private static function validarDNI(string $numero, string $letra): bool
    {
        $letras = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $resto = (int) $numero % 23;
        return strtoupper($letra) === $letras[$resto];
    }

    /**
     * Valida la letra de un NIE español.
     *
     * @param string $numero Número sin letra (7 dígitos, la inicial X/Y/Z se convierte)
     * @param string $letra Letra a validar
     * @return bool
     */
    private static function validarNIE(string $numero, string $letra): bool
    {
        $numero = str_replace(['X', 'Y', 'Z'], ['0', '1', '2'], substr($numero, 0, 1)) . substr($numero, 1);
        $letras = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $resto = (int) $numero % 23;
        return strtoupper($letra) === $letras[$resto];
    }

    /**
     * Valida un DNI o NIE completo.
     *
     * @param string $tipoDocumento 'DNI', 'NIE' u 'Otro'
     * @param string $numeroDocumento Número completo con letra
     * @return array{valido: bool, error: string|null}
     */
    public static function validarDocumentoIdentidad(string $tipoDocumento, string $numeroDocumento): array
    {
        $numeroDocumento = trim($numeroDocumento);

        if ($tipoDocumento === 'DNI') {
            // DNI: 8 dígitos + 1 letra
            if (!preg_match('/^[0-9]{8}[A-Z]$/i', $numeroDocumento)) {
                return ['valido' => false, 'error' => 'El DNI debe tener 8 dígitos y una letra (ejemplo: 12345678Z).'];
            }
            $numeros = substr($numeroDocumento, 0, 8);
            $letra = substr($numeroDocumento, 8, 1);
            if (!self::validarDNI($numeros, $letra)) {
                return ['valido' => false, 'error' => 'La letra del DNI no es correcta.'];
            }
            return ['valido' => true, 'error' => null];
        }

        if ($tipoDocumento === 'NIE') {
            // NIE: letra inicial (X/Y/Z) + 7 dígitos + 1 letra final
            if (!preg_match('/^[XYZ][0-9]{7}[A-Z]$/i', $numeroDocumento)) {
                return ['valido' => false, 'error' => 'El NIE debe tener una letra inicial (X/Y/Z), 7 dígitos y una letra final (ejemplo: X1234567L).'];
            }
            $inicial = substr($numeroDocumento, 0, 1);
            $numeros = substr($numeroDocumento, 1, 7);
            $letraFinal = substr($numeroDocumento, 8, 1);
            if (!self::validarNIE($numeros, $letraFinal)) {
                return ['valido' => false, 'error' => 'La letra del NIE no es correcta.'];
            }
            return ['valido' => true, 'error' => null];
        }

        // Tipo "Otro": sin validación de formato específico
        return ['valido' => true, 'error' => null];
    }

    /**
     * Valida y crea un inquilino.
     *
     * @param array $datos Datos del inquilino
     * @return array{exito: bool, id: int|null, errores: array}
     */
    public function crear(array $datos): array
    {
        $errores = [];

        $tipoDocumento = $datos['tipo_documento'] ?? 'DNI';
        $numeroDocumento = self::normalizarDocumento($datos['numero_documento'] ?? '');
        $nombre = trim($datos['nombre'] ?? '');
        $apellidos = trim($datos['apellidos'] ?? '');
        $telefono = trim($datos['telefono'] ?? '');
        $email = trim($datos['email'] ?? '');
        $contactoAdicional = trim($datos['contacto_adicional'] ?? '');
        $observaciones = trim($datos['observaciones'] ?? '');
        $estado = $datos['estado'] ?? 'activo';

        // Validar tipo de documento
        if (!in_array($tipoDocumento, ['DNI', 'NIE', 'Otro'], true)) {
            $errores[] = 'Tipo de documento no válido.';
        }

        // Validar documento según tipo
        if ($numeroDocumento === '') {
            $errores[] = 'El número de documento es obligatorio.';
        } else {
            $validacion = self::validarDocumentoIdentidad($tipoDocumento, $numeroDocumento);
            if (!$validacion['valido']) {
                $errores[] = $validacion['error'];
            }
        }

        if ($nombre === '') {
            $errores[] = 'El nombre es obligatorio.';
        }

        if ($apellidos === '') {
            $errores[] = 'Los apellidos son obligatorios.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo electrónico no es válido.';
        }

        if (!in_array($estado, ['activo', 'archivado'], true)) {
            $errores[] = 'El estado no es válido.';
        }

        // Verificar unicidad del documento normalizado
        if ($numeroDocumento !== '' && $this->repo->existeDocumento($numeroDocumento)) {
            $errores[] = 'Ya existe un inquilino con este número de documento.';
        }

        if (!empty($errores)) {
            return ['exito' => false, 'id' => null, 'errores' => $errores];
        }

        $id = $this->repo->crear([
            'tipo_documento' => $tipoDocumento,
            'numero_documento' => $numeroDocumento,
            'nombre' => $nombre,
            'apellidos' => $apellidos,
            'telefono' => $telefono,
            'email' => $email,
            'contacto_adicional' => $contactoAdicional,
            'observaciones' => $observaciones,
            'estado' => $estado,
        ]);

        return ['exito' => true, 'id' => $id, 'errores' => []];
    }

    /**
     * Valida y actualiza un inquilino.
     *
     * @param int $id ID del inquilino
     * @param array $datos Datos a actualizar
     * @return array{exito: bool, errores: array}
     */
    public function actualizar(int $id, array $datos): array
    {
        $errores = [];

        $tipoDocumento = $datos['tipo_documento'] ?? 'DNI';
        $numeroDocumento = self::normalizarDocumento($datos['numero_documento'] ?? '');
        $nombre = trim($datos['nombre'] ?? '');
        $apellidos = trim($datos['apellidos'] ?? '');
        $telefono = trim($datos['telefono'] ?? '');
        $email = trim($datos['email'] ?? '');
        $contactoAdicional = trim($datos['contacto_adicional'] ?? '');
        $observaciones = trim($datos['observaciones'] ?? '');
        $estado = $datos['estado'] ?? 'activo';

        if ($numeroDocumento === '') {
            $errores[] = 'El número de documento es obligatorio.';
        } else {
            $validacion = self::validarDocumentoIdentidad($tipoDocumento, $numeroDocumento);
            if (!$validacion['valido']) {
                $errores[] = $validacion['error'];
            }
        }

        if ($nombre === '') {
            $errores[] = 'El nombre es obligatorio.';
        }

        if ($apellidos === '') {
            $errores[] = 'Los apellidos son obligatorios.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo electrónico no es válido.';
        }

        // Verificar unicidad excluyendo el inquilino actual
        if ($numeroDocumento !== '' && $this->repo->existeDocumento($numeroDocumento, $id)) {
            $errores[] = 'Ya existe otro inquilino con este número de documento.';
        }

        if (!empty($errores)) {
            return ['exito' => false, 'errores' => $errores];
        }

        $resultado = $this->repo->actualizar($id, [
            'tipo_documento' => $tipoDocumento,
            'numero_documento' => $numeroDocumento,
            'nombre' => $nombre,
            'apellidos' => $apellidos,
            'telefono' => $telefono,
            'email' => $email,
            'contacto_adicional' => $contactoAdicional,
            'observaciones' => $observaciones,
            'estado' => $estado,
        ]);

        return ['exito' => $resultado, 'errores' => $resultado ? [] : ['No se pudo actualizar el inquilino.']];
    }

    /**
     * Verifica si se puede archivar un inquilino.
     *
     * Archivar siempre es posible porque no se elimina físicamente (se conserva el historial).
     * Solo se cambia el estado a 'archivado'. No hay borrado en cascada de información económica.
     *
     * @param int $id ID del inquilino
     * @return array{puede: bool, motivo: string|null}
     */
    public function puedeArchivar(int $id): array
    {
        return ['puede' => true, 'motivo' => null];
    }
}
