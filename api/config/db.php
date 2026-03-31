<?php
/* ============================================================
   Database.php — Singleton DB Connection
   ============================================================ */

class Database {

    private static $instance = null;
    private $conn;

    private $host     = 'localhost';
    private $user     = 'root';
    private $password = '';
    private $database = 'courier_cms';

    private function __construct() {
        $this->conn = mysqli_connect(
            $this->host,
            $this->user,
            $this->password,
            $this->database
        );

        if (!$this->conn) {
            die(json_encode([
                'status'  => 'error',
                'message' => 'DB Connection failed: ' . mysqli_connect_error()
            ]));
        }

        mysqli_set_charset($this->conn, 'utf8');
    }

    // Singleton — only one connection ever
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Return the connection
    public function getConnection() {
        return $this->conn;
    }
}