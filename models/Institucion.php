<?php
class Institucion extends BaseModel
{
    protected $table = 'instituciones_educativas';

    /**
     * Crea una nueva institución educativa.
     *
     * @param array $data Datos de la institución.
     * @return string ID de la institución creada.
     * @throws Exception Si faltan datos o hay duplicados.
     */
    public function createInstitution($data)
    {
        // Validar campos requeridos
        if (empty($data['nombre']) || empty($data['codigo']) || empty($data['tipo'])) {
            throw new Exception('Nombre, código y tipo son obligatorios');
        }

        // Normalizar
        $data['nombre'] = trim($data['nombre']);
        $data['codigo'] = strtoupper(trim($data['codigo']));
        $data['tipo'] = trim($data['tipo']);
        $data['provincia'] = trim($data['provincia'] ?? '');
        $data['canton'] = trim($data['canton'] ?? '');
        $data['zona'] = trim($data['zona'] ?? '');
        $data['distrito'] = trim($data['distrito'] ?? '');

        // Validar tipo contra valores permitidos
        $allowedTypes = ['Fiscal', 'Fiscomisional', 'Particular', 'Municipal'];
        if (!in_array($data['tipo'], $allowedTypes, true)) {
            throw new Exception('Tipo de institución inválido');
        }

        // Verificar unicidad del código
        $existing = $this->findByCodigo($data['codigo']);
        if ($existing) {
            throw new Exception('El código AMIE ya existe en otra institución');
        }

        // Insertar
        return $this->create([
            'nombre' => $data['nombre'],
            'codigo' => $data['codigo'],
            'tipo' => $data['tipo'],
            'provincia' => $data['provincia'],
            'canton' => $data['canton'],
            'zona' => $data['zona'],
            'distrito' => $data['distrito']
        ]);
    }

    /**
     * Actualiza una institución existente.
     *
     * @param int $id ID de la institución.
     * @param array $data Datos a actualizar.
     * @return bool Resultado de la actualización.
     * @throws Exception Si hay errores de validación.
     */
    public function updateInstitution($id, $data)
    {
        // Validar campos requeridos si se proporcionan
        if (isset($data['nombre']) && empty($data['nombre']))
            throw new Exception('El nombre es obligatorio');

        if (isset($data['tipo'])) {
            $allowedTypes = ['Fiscal', 'Fiscomisional', 'Particular', 'Municipal'];
            if (!in_array($data['tipo'], $allowedTypes, true)) {
                throw new Exception('Tipo de institución inválido');
            }
        }

        if (!empty($data['codigo'])) {
            $data['codigo'] = strtoupper(trim($data['codigo']));
            $existing = $this->findByCodigo($data['codigo']);
            if ($existing && $existing['id'] != $id) {
                throw new Exception('El código AMIE ya existe en otra institución');
            }
        }

        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            if (in_array($key, ['nombre', 'tipo', 'codigo', 'provincia', 'canton', 'zona', 'distrito'])) {
                $fields[] = "$key = ?";
                $params[] = trim($value);
            }
        }

        if (empty($fields))
            return false;

        $params[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Obtiene instituciones con paginación y filtros avazados.
     *
     * @param int|null $limit Límite de registros.
     * @param int $offset Desplazamiento.
     * @param array $filters Filtros de búsqueda (search, provincia, etc).
     * @return array Lista de instituciones.
     */
    public function getAll($limit = null, $offset = 0, $filters = [])
    {
        $sql = "SELECT * FROM {$this->table}";

        $mappings = [
            'search' => [
                'col' => ['nombre', 'codigo', 'provincia', 'canton', 'zona', 'distrito', 'tipo'],
                'op' => 'LIKE',
                'wrapper' => '%%%s%%',
                'use_or' => true
            ],
            'provincia' => 'provincia',
            'canton' => 'canton',
            'zona' => 'zona',
            'distrito' => 'distrito',
            'tipo' => 'tipo'
        ];

        $queryRef = QueryHelper::buildWhereClause($filters, $mappings);
        $where = $queryRef['where'];
        $params = $queryRef['params'];

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY id";

        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int) $limit;
            $params[] = (int) $offset;
        }

        $stmt = $this->db->prepare($sql);

        $i = 1;
        foreach ($params as $param) {
            if (is_int($param)) {
                $stmt->bindValue($i++, $param, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($i++, $param);
            }
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Cuenta el total de instituciones según filtros.
     *
     * @param array $filters Filtros aplicados.
     * @return int Total de registros.
     */
    public function countAll($filters = [])
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";

        $mappings = [
            'search' => [
                'col' => ['nombre', 'codigo', 'provincia', 'canton', 'zona', 'distrito', 'tipo'],
                'op' => 'LIKE',
                'wrapper' => '%%%s%%',
                'use_or' => true
            ],
            'provincia' => 'provincia',
            'canton' => 'canton',
            'zona' => 'zona',
            'distrito' => 'distrito',
            'tipo' => 'tipo'
        ];

        $queryRef = QueryHelper::buildWhereClause($filters, $mappings);
        $where = $queryRef['where'];
        $params = $queryRef['params'];

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Busca una institución por su código AMIE.
     */
    public function findByCodigo($codigo)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE codigo = ?");
        $stmt->execute([$codigo]);
        return $stmt->fetch();
    }

    /**
     * Búsqueda ligera para autocompletado (nombre o código).
     */
    public function search($q, $limit = 20)
    {
        $q = trim($q);
        if ($q === '')
            return [];

        $like = "%" . $q . "%";
        // Buscar por nombre y código AMIE
        $stmt = $this->db->prepare("SELECT id, nombre, codigo, tipo FROM {$this->table} WHERE nombre LIKE ? OR codigo LIKE ? ORDER BY nombre LIMIT ?");
        $stmt->execute([$like, $like, (int) $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener todas las instituciones en una zona específica.
     */
    public function getByZona($zona)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE zona = ? 
            ORDER BY nombre
        ");
        $stmt->execute([$zona]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener lista de zonas únicas.
     */
    public function getZonaList()
    {
        $stmt = $this->db->query("
            SELECT DISTINCT zona 
            FROM {$this->table} 
            WHERE zona IS NOT NULL AND zona != ''
            ORDER BY zona
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Obtener lista de distritos únicos.
     */
    public function getDistritoList($zona = null)
    {
        $sql = "SELECT DISTINCT distrito FROM {$this->table} WHERE distrito IS NOT NULL AND distrito != ''";
        $params = [];

        if ($zona) {
            $sql .= " AND zona = ?";
            $params[] = $zona;
        }

        $sql .= " ORDER BY distrito";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Obtener instituciones por distrito.
     */
    public function getByDistrito($distrito)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE distrito = ? ORDER BY nombre");
        $stmt->execute([$distrito]);
        return $stmt->fetchAll();
    }

    /**
     * Actualizar zona y distrito para una institución.
     */
    public function updateLocationConfig($id, $zona, $distrito)
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET zona = ?, distrito = ?
            WHERE id = ?
        ");
        return $stmt->execute([$zona, $distrito, $id]);
    }

    // Mantener updateZona heredado para compatibilidad si es necesario
    public function updateZona($id, $zona)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET zona = ? WHERE id = ?");
        return $stmt->execute([$zona, $id]);
    }
}