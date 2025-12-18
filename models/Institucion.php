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

        // Validate tipo against allowed values
        $allowedTypes = ['Fiscal', 'Fiscomisional'];
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
            'tipo' => $data['tipo']
        ]);
    }

    public function getAll($limit = null)
    {
        if ($limit) {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} ORDER BY nombre LIMIT ?");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        }

        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY nombre");
        return $stmt->fetchAll();
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