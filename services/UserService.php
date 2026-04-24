<?php
require_once __DIR__ . '/../models/User.php';

class UserService
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Busca un usuario por su ID.
     *
     * @param int $id ID del usuario.
     * @return array|false Datos del usuario o false si no existe.
     */
    public function find($id)
    {
        return $this->userModel->find($id);
    }

    /**
     * Obtiene usuarios con paginación y filtros.
     *
     * @param int $limit Límite de registros.
     * @param int $offset Desplazamiento.
     * @param array $filters Filtros aplicados.
     * @return array Lista de usuarios con detalles.
     */
    public function findAll($limit, $offset, $filters)
    {
        return $this->userModel->findAllWithDetails($filters, $limit, $offset);
    }

    /**
     * Cuenta el total de usuarios según los filtros.
     *
     * @param array $filters Filtros aplicados.
     * @return int Total de registros.
     */
    public function countAll($filters)
    {
        return $this->userModel->countAllWithFilters($filters);
    }

    /**
     * Obtiene valores únicos de una columna para filtros.
     *
     * @param string $column Nombre de la columna.
     * @param string $table Nombre de la tabla (por defecto 'usuarios').
     * @return array Lista de valores únicos.
     */
    public function getUniqueValues($column, $table = 'usuarios')
    {
        // Lista blanca simple
        if (!in_array($column, ['curso', 'paralelo'])) {
            return [];
        }
        $db = $this->userModel->getDb();
        $stmt = $db->query("SELECT DISTINCT $column FROM $table WHERE $column IS NOT NULL AND $column != '' ORDER BY $column");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Actualiza el rol de un usuario y sus datos asociados (institución, zona).
     * Maneja verificaciones de permisos y consistencia de datos.
     * 
     * @param array $currentUser Datos del usuario que realiza la acción (id, rol, institucion_id, etc.)
     * @param int $targetUserId ID del usuario a actualizar.
     * @param string $newRole Nuevo rol a asignar.
     * @param array $contextData Datos adicionales (institucion_id, zona_id) para la asignación.
     * @throws Exception Si hay error de permisos o validación.
     */
    public function updateUserRole($currentUser, $targetUserId, $newRole, $contextData)
    {
        $currentUserId = $currentUser['id'];
        $currentRole = $currentUser['rol']; // Asumiendo que existe la clave 'rol'

        // Verificaciones de permisos para DECE
        if ($currentRole === 'dece') {
            $targetUser = $this->userModel->find($targetUserId);

            if (!$targetUser || empty($currentUser['institucion_id']) || ($targetUser['institucion_id'] ?? null) != $currentUser['institucion_id']) {
                throw new Exception('Acceso denegado: el usuario no pertenece a su institución');
            }

            // Solo permitir roles adecuados para la gestión escolar
            $allowedNewRoles = ['estudiante', 'dece', 'directivo', 'cuenta_oculta'];
            if (!in_array($newRole, $allowedNewRoles)) {
                throw new Exception('Acceso denegado: no tienes permiso para otorgar este rol');
            }

            // Forzar que el institucion_id sea el propio para la nueva asignación
            $contextData['institucion_id'] = $currentUser['institucion_id'];
            $contextData['zona_id'] = null;
        }

        // Actualizar rol
        $this->userModel->updateRole((int) $targetUserId, $newRole);

        // Actualizar zona_id si se proporciona (para rol zonal)
        $zonaId = $contextData['zona_id'] ?? null;
        if ($newRole === 'zonal' && !empty($zonaId)) {
            $this->userModel->updateZona((int) $targetUserId, $zonaId);
        } elseif ($newRole !== 'zonal') {
            // Limpiar zona_id si no es rol zonal
            $this->userModel->updateZona((int) $targetUserId, null);
        }

        // Actualizar institucion_id si se proporciona (para rol dece)
        $institucionId = $contextData['institucion_id'] ?? null;
        if ($newRole === 'dece' && !empty($institucionId)) {
            // Primero, desasignar esta institución de CUALQUIER OTRO usuario que tenga el rol 'dece'
            // Esto asegura que la institución se "mueva" al nuevo usuario
            $this->userModel->unassignInstitutionFromDece($institucionId);
            $this->userModel->updateInstitucion((int) $targetUserId, $institucionId);
        } elseif ($newRole !== 'dece') {
            // Limpiar institucion_id si no es rol dece
            $this->userModel->updateInstitucion((int) $targetUserId, null);
        }
    }

    /**
     * Actualiza la contraseña de un usuario.
     * Maneja verificaciones de permisos.
     *
     * @param int $actorId ID del usuario que realiza la acción.
     * @param string $actorRole Rol del usuario que realiza la acción.
     * @param int $targetUserId ID del usuario objetivo.
     * @param string $newPassword Nueva contraseña.
     * @return bool Resultado de la actualización.
     * @throws Exception Si hay error de permisos o validación.
     */
    public function updateUserPassword($actorId, $actorRole, $targetUserId, $newPassword)
    {
        if (strlen($newPassword) < 8) {
            // Usualmente la configuración define PASSWORD_MIN_LENGTH. Confiaremos en la constante global o usaremos 8.
            if (defined('PASSWORD_MIN_LENGTH')) {
                if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                    throw new Exception('La nueva contraseña debe tener al menos ' . PASSWORD_MIN_LENGTH . ' caracteres');
                }
            } else {
                if (strlen($newPassword) < 8) {
                    throw new Exception('La nueva contraseña debe tener al menos 8 caracteres');
                }
            }
        }

        // Verificaciones de permisos
        if ($actorRole === 'administrador') {
            // ok
        } elseif ($actorRole === 'dece') {
            // solo permitir si el usuario objetivo pertenece a la misma institución
            $current = $this->userModel->find($actorId);
            $target = $this->userModel->find($targetUserId);
            if (empty($current['institucion_id']) || empty($target['institucion_id']) || $current['institucion_id'] != $target['institucion_id']) {
                throw new Exception('Acceso denegado: sólo puedes cambiar contraseñas de estudiantes de tu institución');
            }
        } elseif ($actorRole === 'zonal') {
            // solo permitir si el usuario objetivo pertenece a la misma zona
            $current = $this->userModel->find($actorId);
            $target = $this->userModel->find($targetUserId);
            if (empty($current['zona_id']) || empty($target['zona_id']) || $current['zona_id'] != $target['zona_id']) {
                throw new Exception('Acceso denegado: sólo puedes cambiar contraseñas de usuarios de tu zona');
            }
        } else {
            // El cambio propio se maneja usualmente por un método diferente o si el llamador verifica actorId == targetUserId
            if ($actorId != $targetUserId) {
                throw new Exception('Acceso denegado: no tienes permiso para cambiar contraseñas de otros usuarios');
            }
        }

        return $this->userModel->updatePassword($targetUserId, $newPassword);
    }

    /**
     * Actualiza el estado de pago de un usuario.
     *
     * @param array $currentUser Datos del usuario actual.
     * @param int $targetUserId ID del usuario objetivo.
     * @param string $paymentStatus Nuevo estado de pago.
     * @return bool Resultado de la actualización.
     * @throws Exception Si no tiene permiso o el estado es inválido.
     */
    public function updatePaymentStatus($currentUser, int $targetUserId, string $paymentStatus)
    {
        if (empty($currentUser['rol']) || $currentUser['rol'] !== 'cuenta_oculta') {
            throw new Exception('Acceso denegado: no tienes permiso para actualizar el estado de pago');
        }

        $allowed = ['paid', 'unpaid'];
        if (!in_array($paymentStatus, $allowed, true)) {
            throw new Exception('Estado de pago inválido');
        }

        return $this->userModel->updatePaymentStatus($targetUserId, $paymentStatus);
    }
}
