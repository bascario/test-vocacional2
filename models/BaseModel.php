<?php
/**
 * Modelo base simple con operaciones CRUD reutilizables.
 * Clase abstracta de la que heredan los modelos específicos.
 */
require_once __DIR__ . '/../utils/QueryHelper.php';

abstract class BaseModel
{
    protected $db;
    protected $table;

    public function __construct()
    {
        // Obtener conexión PDO desde el singleton de Database
        $this->db = Database::getInstance()->getConnection();
    }

    public function getDb()
    {
        return $this->db;
    }

    /**
     * Buscar un registro por su id
     * @param mixed $id
     * @return array|false
     */
    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Obtener todos los registros (opcionalmente con condiciones y orden).
     *
     * @param array $conditions Condiciones WHERE (columna => valor).
     * @param string $orderBy Cláusula ORDER BY.
     * @return array Lista de registros.
     */
    public function findAll($conditions = [], $orderBy = '')
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $key => $value) {
                $whereClauses[] = "$key = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Insertar un nuevo registro y devolver el id insertado.
     *
     * @param array $data Datos a insertar (columna => valor).
     * @return string|false ID del último registro insertado.
     */
    public function create($data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));

        return $this->db->lastInsertId();
    }

    /**
     * Actualizar campos de un registro por id.
     *
     * @param mixed $id ID del registro.
     * @param array $data Datos a actualizar.
     * @return bool Resultado de la ejecución.
     */
    public function update($id, $data)
    {
        $setClauses = [];
        $params = [];

        foreach ($data as $key => $value) {
            $setClauses[] = "$key = ?";
            $params[] = $value;
        }
        $params[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Eliminar un registro por id.
     *
     * @param mixed $id ID del registro.
     * @return bool Resultado de la ejecución.
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}