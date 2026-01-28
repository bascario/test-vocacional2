<?php
class Institucion extends BaseModel
{
    protected $table = 'instituciones_educativas';

    public function createInstitution($data)
    {
        // Validate required fields
        if (empty($data['nombre']) || empty($data['codigo']) || empty($data['tipo'])) {
            throw new Exception('Nombre, código y tipo son obligatorios');
        }

        // Normalize
        $data['nombre'] = trim($data['nombre']);
        $data['codigo'] = strtoupper(trim($data['codigo']));
        $data['tipo'] = trim($data['tipo']);
        $data['provincia'] = trim($data['provincia'] ?? '');
        $data['canton'] = trim($data['canton'] ?? '');
        $data['zona'] = trim($data['zona'] ?? '');
        $data['distrito'] = trim($data['distrito'] ?? '');

        // Validate tipo against allowed values
        $allowedTypes = ['Fiscal', 'Fiscomisional', 'Particular', 'Municipal'];
        if (!in_array($data['tipo'], $allowedTypes, true)) {
            throw new Exception('Tipo de institución inválido');
        }

        // Check codigo uniqueness
        $existing = $this->findByCodigo($data['codigo']);
        if ($existing) {
            throw new Exception('El código AMIE ya existe en otra institución');
        }

        // Insert
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

    public function updateInstitution($id, $data)
    {
        // Validate required fields if provided
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

    public function getAll($limit = null, $offset = 0, $filters = [])
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        $where = [];

        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $sParam = "%" . $s . "%";
            $where[] = "(nombre LIKE ? OR codigo LIKE ? OR provincia LIKE ? OR canton LIKE ? OR zona LIKE ? OR distrito LIKE ? OR tipo LIKE ?)";
            $params[] = $sParam;
            $params[] = $sParam;
            $params[] = $sParam;
            $params[] = $sParam;
            $params[] = $sParam;
            $params[] = $sParam;
            $params[] = $sParam;
        }

        if (!empty($filters['provincia'])) {
            $where[] = "provincia = ?";
            $params[] = $filters['provincia'];
        }
        if (!empty($filters['canton'])) {
            $where[] = "canton = ?";
            $params[] = $filters['canton'];
        }
        if (!empty($filters['zona'])) {
            $where[] = "zona = ?";
            $params[] = $filters['zona'];
        }
        if (!empty($filters['distrito'])) {
            $where[] = "distrito = ?";
            $params[] = $filters['distrito'];
        }
        if (!empty($filters['tipo'])) {
            $where[] = "tipo = ?";
            $params[] = $filters['tipo'];
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY id";

        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int) $limit;
            $params[] = (int) $offset;

            $stmt = $this->db->prepare($sql);
            // We use bindValue for integers in LIMIT/OFFSET to avoid issues with execute() treating them as strings
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

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countAll($filters = [])
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $params = [];
        $where = [];

        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $sParam = "%" . $s . "%";
            $where[] = "(nombre LIKE ? OR codigo LIKE ? OR provincia LIKE ? OR canton LIKE ? OR zona LIKE ? OR distrito LIKE ? OR tipo LIKE ?)";
            $params[] = $sParam;
            $params[] = $sParam;
            $params[] = $sParam;
            $params[] = $sParam;
            $params[] = $sParam;
            $params[] = $sParam;
            $params[] = $sParam;
        }

        if (!empty($filters['provincia'])) {
            $where[] = "provincia = ?";
            $params[] = $filters['provincia'];
        }
        if (!empty($filters['canton'])) {
            $where[] = "canton = ?";
            $params[] = $filters['canton'];
        }
        if (!empty($filters['zona'])) {
            $where[] = "zona = ?";
            $params[] = $filters['zona'];
        }
        if (!empty($filters['distrito'])) {
            $where[] = "distrito = ?";
            $params[] = $filters['distrito'];
        }
        if (!empty($filters['tipo'])) {
            $where[] = "tipo = ?";
            $params[] = $filters['tipo'];
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function findByCodigo($codigo)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE codigo = ?");
        $stmt->execute([$codigo]);
        return $stmt->fetch();
    }

    public function search($q, $limit = 20)
    {
        $q = trim($q);
        if ($q === '')
            return [];

        $like = "%" . $q . "%";
        // Search by both name and AMIE code
        $stmt = $this->db->prepare("SELECT id, nombre, codigo, tipo FROM {$this->table} WHERE nombre LIKE ? OR codigo LIKE ? ORDER BY nombre LIMIT ?");
        $stmt->execute([$like, $like, (int) $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get all institutions in a specific zona
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
     * Get list of unique zonas
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
     * Get list of unique distritos
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
     * Get institutions by distrito
     */
    public function getByDistrito($distrito)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE distrito = ? ORDER BY nombre");
        $stmt->execute([$distrito]);
        return $stmt->fetchAll();
    }

    /**
     * Update zona and distrito for an institution
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

    // Keep legacy updateZona for compatibility if needed
    public function updateZona($id, $zona)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET zona = ? WHERE id = ?");
        return $stmt->execute([$zona, $id]);
    }
}