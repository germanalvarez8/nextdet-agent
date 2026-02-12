<?php
/**
 * Database Utility Class
 * 
 * Proporciona una capa de abstracción para operaciones de base de datos
 * utilizando PDO con MySQL.
 * 
 * Características:
 * - Singleton pattern para conexión única
 * - Prepared statements para prevenir SQL injection
 * - Manejo de errores con logging
 * - Métodos genéricos para CRUD
 */

require_once __DIR__ . '/../config/config.php';

class Database
{
    /**
     * Instancia singleton de la conexión PDO
     * @var PDO|null
     */
    private static $connection = null;
    
    /**
     * Obtiene la conexión a la base de datos (singleton)
     * 
     * @return PDO Conexión activa a la base de datos
     * @throws PDOException Si falla la conexión
     */
    public static function getConnection()
    {
        if (self::$connection === null) {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=%s',
                    DB_HOST,
                    DB_NAME,
                    DB_CHARSET
                );
                
                $options = [
                    // Modo de error: lanzar excepciones
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    // Retornar arrays asociativos por defecto
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // Emular prepared statements: NO (más seguro)
                    PDO::ATTR_EMULATE_PREPARES => false,
                    // Conexión persistente para mejor rendimiento
                    PDO::ATTR_PERSISTENT => false,
                ];
                
                self::$connection = new PDO($dsn, DB_USER, DB_PASS, $options);
                
                self::log('Database connection established successfully', 'INFO');
                
            } catch (PDOException $e) {
                self::log('Database connection failed: ' . $e->getMessage(), 'ERROR');
                throw new Exception('Error de conexión a la base de datos: ' . $e->getMessage());
            }
        }
        
        return self::$connection;
    }
    
    /**
     * Ejecuta una consulta SQL con parámetros
     * 
     * @param string $sql Consulta SQL con placeholders (?)
     * @param array $params Parámetros a vincular
     * @return PDOStatement Statement ejecutado
     * @throws Exception Si falla la ejecución
     */
    public static function query($sql, $params = [])
    {
        try {
            $conn = self::getConnection();
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt;
            
        } catch (PDOException $e) {
            self::log('Query execution failed: ' . $e->getMessage() . ' | SQL: ' . $sql, 'ERROR');
            throw new Exception('Error ejecutando consulta: ' . $e->getMessage());
        }
    }
    
    /**
     * Inserta un registro en una tabla
     * 
     * @param string $table Nombre de la tabla
     * @param array $data Array asociativo [columna => valor]
     * @return int ID del registro insertado
     */
    public static function insert($table, $data)
    {
        $columns = array_keys($data);
        $values = array_values($data);
        
        $columnList = implode(', ', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        
        $sql = "INSERT INTO {$table} ({$columnList}) VALUES ({$placeholders})";
        
        self::query($sql, $values);
        
        return (int) self::getConnection()->lastInsertId();
    }
    
    /**
     * Actualiza registros en una tabla
     * 
     * @param string $table Nombre de la tabla
     * @param array $data Array asociativo [columna => valor] a actualizar
     * @param array $where Array asociativo [columna => valor] para la condición WHERE
     * @return int Número de filas afectadas
     */
    public static function update($table, $data, $where)
    {
        $setClause = [];
        $values = [];
        
        foreach ($data as $column => $value) {
            $setClause[] = "{$column} = ?";
            $values[] = $value;
        }
        
        $whereClause = [];
        foreach ($where as $column => $value) {
            $whereClause[] = "{$column} = ?";
            $values[] = $value;
        }
        
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $setClause),
            implode(' AND ', $whereClause)
        );
        
        $stmt = self::query($sql, $values);
        
        return $stmt->rowCount();
    }
    
    /**
     * Selecciona registros de una tabla
     * 
     * @param string $table Nombre de la tabla
     * @param array $columns Columnas a seleccionar (vacío = *)
     * @param array $where Condiciones WHERE [columna => valor]
     * @param string $orderBy Cláusula ORDER BY (ej: "timestamp DESC")
     * @param int $limit Límite de resultados
     * @return array Array de registros
     */
    public static function select($table, $columns = [], $where = [], $orderBy = null, $limit = null)
    {
        $columnList = empty($columns) ? '*' : implode(', ', $columns);
        
        $sql = "SELECT {$columnList} FROM {$table}";
        $values = [];
        
        if (!empty($where)) {
            $whereClause = [];
            foreach ($where as $column => $value) {
                $whereClause[] = "{$column} = ?";
                $values[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT " . (int) $limit;
        }
        
        $stmt = self::query($sql, $values);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtiene un solo registro
     * 
     * @param string $table Nombre de la tabla
     * @param array $where Condiciones WHERE [columna => valor]
     * @return array|null Registro encontrado o null
     */
    public static function selectOne($table, $where)
    {
        $results = self::select($table, [], $where, null, 1);
        
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Elimina registros de una tabla
     * 
     * @param string $table Nombre de la tabla
     * @param array $where Condiciones WHERE [columna => valor]
     * @return int Número de filas eliminadas
     */
    public static function delete($table, $where)
    {
        if (empty($where)) {
            throw new Exception('DELETE requiere condiciones WHERE para evitar borrado masivo');
        }
        
        $whereClause = [];
        $values = [];
        
        foreach ($where as $column => $value) {
            $whereClause[] = "{$column} = ?";
            $values[] = $value;
        }
        
        $sql = sprintf(
            "DELETE FROM %s WHERE %s",
            $table,
            implode(' AND ', $whereClause)
        );
        
        $stmt = self::query($sql, $values);
        
        return $stmt->rowCount();
    }
    
    /**
     * Inicia una transacción
     */
    public static function beginTransaction()
    {
        self::getConnection()->beginTransaction();
    }
    
    /**
     * Confirma una transacción
     */
    public static function commit()
    {
        self::getConnection()->commit();
    }
    
    /**
     * Revierte una transacción
     */
    public static function rollback()
    {
        self::getConnection()->rollback();
    }
    
    /**
     * Registra eventos en el archivo de log
     * 
     * @param string $message Mensaje a loggear
     * @param string $level Nivel de log (DEBUG, INFO, ERROR)
     */
    private static function log($message, $level = 'INFO')
    {
        // Verificar si el nivel actual permite este log
        $levels = ['DEBUG' => 1, 'INFO' => 2, 'ERROR' => 3];
        $currentLevel = $levels[LOG_LEVEL] ?? 2;
        $messageLevel = $levels[$level] ?? 2;
        
        if ($messageLevel < $currentLevel) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] [Database] {$message}" . PHP_EOL;
        
        error_log($logMessage, 3, LOG_FILE);
    }
}
