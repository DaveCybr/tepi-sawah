<?php

/**
 * Database Connection Class
 * Menggunakan MySQLi dengan Prepared Statements untuk keamanan
 */

class Database
{
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($this->conn->connect_error) {
            error_log("Database Connection Error: " . $this->conn->connect_error);
            die("Koneksi database gagal. Silakan hubungi administrator.");
        }

        $this->conn->set_charset("utf8mb4");
    }

    /**
     * Singleton pattern untuk koneksi database
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Get mysqli connection
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * Execute prepared statement dengan parameter
     * @param string $sql Query dengan placeholder
     * @param string $types Tipe parameter (s=string, i=integer, d=double, b=blob)
     * @param array $params Array parameter
     * @return mysqli_stmt
     */
    public function prepare($sql, $types = '', $params = [])
    {
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log("Prepare Error: " . $this->conn->error);
            error_log("SQL: " . $sql);
            throw new Exception("Database prepare error: " . $this->conn->error);
        }

        if (!empty($params) && !empty($types)) {
            // Validasi jumlah parameter
            $typeLength = strlen($types);
            $paramCount = count($params);

            if ($typeLength !== $paramCount) {
                error_log("Parameter mismatch - Types: {$typeLength}, Params: {$paramCount}");
                error_log("Types: " . $types);
                error_log("Params: " . print_r($params, true));
                throw new Exception("Parameter count mismatch: expected {$typeLength}, got {$paramCount}");
            }

            $bindResult = $stmt->bind_param($types, ...$params);

            if (!$bindResult) {
                error_log("Bind Error: " . $stmt->error);
                throw new Exception("Failed to bind parameters: " . $stmt->error);
            }
        }

        return $stmt;
    }

    /**
     * Execute query dan return hasil
     */
    public function query($sql, $types = '', $params = [])
    {
        $stmt = $this->prepare($sql, $types, $params);
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Execute INSERT dan return last insert ID
     */
    public function insert($sql, $types = '', $params = [])
    {
        $stmt = $this->prepare($sql, $types, $params);
        $success = $stmt->execute();

        if ($success) {
            return $this->conn->insert_id;
        }

        return false;
    }

    /**
     * Execute UPDATE/DELETE dan return affected rows
     */
    public function execute($sql, $types = '', $params = [])
    {
        try {
            $stmt = $this->prepare($sql, $types, $params);

            if (!$stmt) {
                throw new Exception("Failed to prepare statement");
            }

            $success = $stmt->execute();

            if ($success) {
                return $stmt->affected_rows;
            }

            return false;
        } catch (Exception $e) {
            error_log("Database Execute Error: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Types: " . $types);
            error_log("Params: " . print_r($params, true));
            throw $e;
        }
    }

    /**
     * Escape string untuk keamanan (backup jika tidak pakai prepared statement)
     */
    public function escape($string)
    {
        return $this->conn->real_escape_string($string);
    }

    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        return $this->conn->begin_transaction();
    }

    /**
     * Commit transaction
     */
    public function commit()
    {
        return $this->conn->commit();
    }


    /**
     * Rollback transaction
     */
    public function rollback()
    {
        return $this->conn->rollback();
    }

    /**
     * Get last error
     */
    public function getError()
    {
        return $this->conn->error;
    }

    /**
     * Close connection (dipanggil otomatis saat object destroyed)
     */
    public function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
