<?php

/**
 * Clase auxiliar para construcción segura de cláusulas WHERE en consultas SQL.
 * Permite manejar filtros dinámicos y evitar inyección SQL.
 */
class QueryHelper
{
    /**
     * Construye una cláusula WHERE dinámica basada en un array de filtros.
     *
     * @param array $filters Array asociativo de filtros (campo => valor).
     * @param array $mappings Array asociativo de mapeo (filtro_key => columna_bd o configuración).
     *                        Si el valor es un array, se asume configuración avanzada:
     *                        ['col' => 'columna', 'op' => '=', 'wrapper' => '%s', 'use_or' => bool]
     * @return array Retorna ['where' => array_of_strings, 'params' => array_of_values]
     */
    public static function buildWhereClause($filters, $mappings)
    {
        $where = [];
        $params = [];

        foreach ($filters as $key => $value) {
            // Saltar valores vacíos, pero permitir 0
            if (empty($value) && $value !== '0' && $value !== 0) {
                continue;
            }

            if (!isset($mappings[$key])) {
                continue;
            }

            $config = $mappings[$key];

            // Normalizar configuración a formato array
            if (!is_array($config)) {
                $config = ['col' => $config, 'op' => '='];
            }

            $column = $config['col'];
            $operator = $config['op'] ?? '=';
            $wrapper = $config['wrapper'] ?? null;
            $useOr = $config['use_or'] ?? false; // Para búsqueda en múltiples columnas (lógica OR)

            if ($useOr && is_array($column)) {
                $orClauses = [];
                $validCol = true;
                foreach ($column as $colName) {
                    $orClauses[] = "$colName $operator ?";
                    $params[] = $wrapper ? sprintf($wrapper, $value) : $value;
                }
                $where[] = "(" . implode(" OR ", $orClauses) . ")";
            } elseif (is_array($column)) {
                // Caso no cubierto actualmente: array de columnas sin use_or (asumimos AND implícito o loop)
                foreach ($column as $colName) {
                    $where[] = "$colName $operator ?";
                    $params[] = $wrapper ? sprintf($wrapper, $value) : $value;
                }
            } else {
                $where[] = "$column $operator ?";
                $params[] = $wrapper ? sprintf($wrapper, $value) : $value;
            }
        }

        return [
            'where' => $where,
            'params' => $params
        ];
    }
}
