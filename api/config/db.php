<?php
/* ============================================================
   Database.php — Singleton Pattern
   Only ONE connection instance exists at any time.
   All classes get their connection from here.
   ============================================================ */

class Database {

    // ── SINGLETON INSTANCE ────────────────────────────────
    private static $instance = null;

    // ── CONNECTION ────────────────────────────────────────
    private $conn;

    // ── DB CREDENTIALS ────────────────────────────────────
    private $host     = 'localhost';
    private $db_name  = 'courier_cms';
    private $username = 'root';
    private $password = 'root123';

    // ── PRIVATE CONSTRUCTOR ───────────────────────────────
    // Prevents direct instantiation (new Database())
    private function __construct() {
        $this->conn = mysqli_connect(
            $this->host,
            $this->username,
            $this->password,
            $this->db_name
        );

        if (!$this->conn) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Database connection failed: '
                             . mysqli_connect_error()
            ]);
            exit();
        }

        mysqli_set_charset($this->conn, 'utf8mb4');
    }

    // ── GET SINGLETON INSTANCE ────────────────────────────
    // Returns the single instance — creates it if not exists
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // ── GET CONNECTION ────────────────────────────────────
    // Returns the mysqli connection object
    public function getConnection() {
        return $this->conn;
    }

    // ── PREVENT CLONING ───────────────────────────────────
    private function __clone() {}

    // ── PREVENT UNSERIALIZATION ───────────────────────────
    public function __wakeup() {}
}
?>