<?php
require_once __DIR__ . '/../models/Institucion.php';

class InstitutionService
{
    private $institucionModel;

    public function __construct()
    {
        $this->institucionModel = new Institucion();
    }

    /**
     * Obtiene todas las instituciones con paginación y filtros.
     *
     * @param int $limit Límite de registros por página.
     * @param int $offset Desplazamiento.
     * @param array $filters Filtros aplicados.
     * @return array Lista de instituciones.
     */
    public function getAll($limit, $offset, $filters)
    {
        return $this->institucionModel->getAll($limit, $offset, $filters);
    }

    /**
     * Cuenta el total de instituciones según los filtros.
     *
     * @param array $filters Filtros aplicados.
     * @return int Total de registros.
     */
    public function countAll($filters)
    {
        return $this->institucionModel->countAll($filters);
    }

    /**
     * Busca una institución por su ID.
     *
     * @param int $id ID de la institución.
     * @return array|false Datos de la institución o false si no existe.
     */
    public function find($id)
    {
        return $this->institucionModel->find($id);
    }

    /**
     * Elimina una institución por su ID.
     *
     * @param int $id ID de la institución a eliminar.
     * @return bool True si se eliminó correctamente.
     */
    public function delete($id)
    {
        return $this->institucionModel->delete($id);
    }

    /**
     * Crea o actualiza una institución.
     *
     * @param array $data Datos del formulario (incluye 'id' para actualizar).
     * @return bool True si la operación fue exitosa.
     */
    public function save($data)
    {
        $id = $data['id'] ?? null;
        $fields = [
            'nombre' => $data['nombre'] ?? '',
            'codigo' => $data['codigo'] ?? '',
            'tipo' => $data['tipo'] ?? '',
            'provincia' => $data['provincia'] ?? '',
            'canton' => $data['canton'] ?? '',
            'zona' => $data['zona'] ?? '',
            'distrito' => $data['distrito'] ?? ''
        ];

        if ($id) {
            return $this->institucionModel->updateInstitution($id, $fields);
        } else {
            return $this->institucionModel->createInstitution($fields);
        }
    }

    /**
     * Obtiene valores únicos de una columna para filtros.
     *
     * @param string $column Nombre de la columna.
     * @return array Lista de valores únicos.
     */
    public function getUniqueValues($column)
    {
        // Lista blanca de columnas permitidas para evitar inyección SQL
        $allowed = ['provincia', 'canton', 'zona', 'distrito', 'tipo'];
        if (!in_array($column, $allowed)) {
            return [];
        }

        $db = $this->institucionModel->getDb();
        $stmt = $db->query("SELECT DISTINCT $column FROM instituciones_educativas WHERE $column IS NOT NULL AND $column != '' ORDER BY $column");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Métodos envoltorios para búsquedas específicas

    /**
     * Obtiene instituciones por zona.
     *
     * @param string $zona Zona educativa.
     * @return array Lista de instituciones.
     */
    public function getByZona($zona)
    {
        return $this->institucionModel->getByZona($zona);
    }

    /**
     * Obtiene instituciones por distrito.
     *
     * @param string $distrito Distrito educativo.
     * @return array Lista de instituciones.
     */
    public function getByDistrito($distrito)
    {
        return $this->institucionModel->getByDistrito($distrito);
    }

    /**
     * Obtiene la lista de todas las zonas disponibles.
     *
     * @return array Lista de zonas.
     */
    public function getZonas()
    {
        return $this->institucionModel->getZonaList();
    }

    /**
     * Obtiene la lista de distritos, opcionalmente filtrados por zona.
     *
     * @param string|null $zona Filtro opcional por zona.
     * @return array Lista de distritos.
     */
    public function getDistritos($zona = null)
    {
        return $this->institucionModel->getDistritoList($zona);
    }

    /**
     * Busca instituciones por término de búsqueda (nombre o código).
     *
     * @param string $q Término de búsqueda.
     * @param int $limit Límite de resultados.
     * @return array Lista de instituciones encontradas.
     */
    public function search($q, $limit = 50)
    {
        return $this->institucionModel->search($q, $limit);
    }
}
