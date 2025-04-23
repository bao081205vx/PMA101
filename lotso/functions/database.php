<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'lotso';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
            $this->conn->set_charset("utf8");
            
            if ($this->conn->connect_error) {
                error_log("Database connection failed: " . $this->conn->connect_error);
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }

            error_log("Database connected successfully");
            return $this->conn;
        } catch(Exception $e) {
            error_log("Database error: " . $e->getMessage());
            throw new Exception("Database connection error: " . $e->getMessage());


        }
    }
}
