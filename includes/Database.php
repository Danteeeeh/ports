<?php
class Database {
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'siasystem';
    private $conn;
    private $lastError = '';

    public function __construct() {
        try {
            // Disable error reporting to handle connection issues gracefully
            mysqli_report(MYSQLI_REPORT_OFF);
            
            // First try to connect without database
            $this->conn = new mysqli($this->host, $this->username, $this->password);
            
            if ($this->conn->connect_error) {
                throw new Exception('Connection failed: ' . $this->conn->connect_error);
            }

            // Create database if it doesn't exist
            $result = $this->conn->query("CREATE DATABASE IF NOT EXISTS {$this->database}");
            if ($result === false) {
                throw new Exception('Failed to create database: ' . $this->conn->error);
            }
            
            // Select the database
            $selectResult = $this->conn->select_db($this->database);
            if ($selectResult === false) {
                throw new Exception('Failed to select database: ' . $this->conn->error);
            }
            
            // Set charset to ensure proper encoding
            $this->conn->set_charset('utf8mb4');
            
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log('Database Connection Error: ' . $e->getMessage());
            // Don't die immediately, allow for graceful error handling
            $this->conn = null;
        }
    }

    public function getConnection() {
        if ($this->conn === null) {
            throw new Exception('Database connection is not established: ' . $this->lastError);
        }
        return $this->conn;
    }
    
    public function isConnected() {
        return ($this->conn !== null && $this->conn->ping());
    }
    
    public function getLastError() {
        return $this->lastError;
    }

    public function query($sql) {
        try {
            if (!$this->isConnected()) {
                throw new Exception('Database connection is not established');
            }
            
            $result = $this->conn->query($sql);
            if ($result === false) {
                $this->lastError = 'Query failed: ' . $this->conn->error . ' SQL: ' . $sql;
                throw new Exception($this->lastError);
            }
            return $result;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log('Database Query Error: ' . $e->getMessage());
            return false;
        }
    }

    public function prepare($sql) {
        try {
            if (!$this->isConnected()) {
                throw new Exception('Database connection is not established');
            }
            
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                $this->lastError = 'Prepare failed: ' . $this->conn->error . ' SQL: ' . $sql;
                throw new Exception($this->lastError);
            }
            return $stmt;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log('Database Prepare Error: ' . $e->getMessage());
            return false;
        }
    }
    
    // Transaction methods
    public function beginTransaction() {
        try {
            if (!$this->isConnected()) {
                throw new Exception('Database connection is not established');
            }
            
            // Make sure we're using a storage engine that supports transactions (like InnoDB)
            if (!$this->conn->begin_transaction()) {
                $this->lastError = 'Failed to start transaction: ' . $this->conn->error;
                throw new Exception($this->lastError);
            }
            return true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log('Transaction Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function commit() {
        try {
            if (!$this->isConnected()) {
                throw new Exception('Database connection is not established');
            }
            
            if (!$this->conn->commit()) {
                $this->lastError = 'Failed to commit transaction: ' . $this->conn->error;
                throw new Exception($this->lastError);
            }
            return true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log('Commit Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function rollback() {
        try {
            if (!$this->isConnected()) {
                throw new Exception('Database connection is not established');
            }
            
            if (!$this->conn->rollback()) {
                $this->lastError = 'Failed to rollback transaction: ' . $this->conn->error;
                throw new Exception($this->lastError);
            }
            return true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log('Rollback Error: ' . $e->getMessage());
            return false;
        }
    }

    public function escape($value) {
        if (!$this->isConnected()) {
            throw new Exception('Database connection is not established');
        }
        return $this->conn->real_escape_string($value);
    }
    
    public function getLastInsertId() {
        if (!$this->isConnected()) {
            return false;
        }
        $result = $this->query("SELECT LAST_INSERT_ID() as last_id");
        if ($result && $row = $result->fetch_assoc()) {
            return $row['last_id'];
        }
        return false;
    }

    public function insert($table, $data) {
        try {
            if (!$this->isConnected()) {
                throw new Exception('Database connection is not established');
            }
            
            $columns = implode(', ', array_keys($data));
            $values = implode(', ', array_map(function($value) {
                return "'" . $this->escape($value) . "'";
            }, array_values($data)));
            
            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$values})";
            $result = $this->query($sql);
            
            return $result;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log('Insert Error: ' . $e->getMessage());
            return false;
        }
    }

    public function update($table, $data, $where) {
        try {
            if (!$this->isConnected()) {
                throw new Exception('Database connection is not established');
            }
            
            $set = implode(', ', array_map(function($key, $value) {
                return "{$key} = '" . $this->escape($value) . "'";
            }, array_keys($data), array_values($data)));
            
            $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
            return $this->query($sql);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log('Update Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function delete($table, $where) {
        try {
            if (!$this->isConnected()) {
                throw new Exception('Database connection is not established');
            }
            
            $sql = "DELETE FROM {$table} WHERE {$where}";
            return $this->query($sql);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log('Delete Error: ' . $e->getMessage());
            return false;
        }
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
