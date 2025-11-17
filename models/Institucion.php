<?php
class Institucion extends BaseModel {
    protected $table = 'instituciones_educativas';

    public function createInstitution($data) {
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

    public function getAll($limit = null) {
        if ($limit) {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} ORDER BY nombre LIMIT ?");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        }

        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY nombre");
        return $stmt->fetchAll();
    }

    public function findByCodigo($codigo) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE codigo = ?");
        $stmt->execute([$codigo]);
        return $stmt->fetch();
    }

    public function search($q, $limit = 20) {
        $q = trim($q);
        if ($q === '') return [];

        $like = "%" . $q . "%";
        $stmt = $this->db->prepare("SELECT id, nombre, codigo, tipo FROM {$this->table} WHERE nombre LIKE ? OR codigo LIKE ? ORDER BY nombre LIMIT ?");
        $stmt->execute([$like, $like, (int)$limit]);
        return $stmt->fetchAll();
    }
}
?>
