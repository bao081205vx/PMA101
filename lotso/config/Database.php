<?php
class Database {
    private $host = "localhost";
    private $dbname = "lotso";
    private $username = "root";
    private $password = "";
    private $conn;

    public function __construct() {
        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->dbname);
            if ($this->conn->connect_error) {
                throw new Exception("Kết nối thất bại: " . $this->conn->connect_error);
            }
            
            // Đặt charset UTF8MB4
            if (!$this->conn->set_charset("utf8mb4")) {
                throw new Exception("Lỗi loading character set utf8mb4: " . $this->conn->error);
            }
        } catch (Exception $e) {
            die("Lỗi kết nối database: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
