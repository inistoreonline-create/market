<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct() {
        $database_url = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
        if ($database_url) {
            $db_parts = parse_url($database_url);
            $this->host = $db_parts['host'] ?? 'localhost';
            $this->db_name = isset($db_parts['path']) ? ltrim($db_parts['path'], '/') : 'gamemarket';
            $this->username = $db_parts['user'] ?? 'postgres';
            $this->password = $db_parts['pass'] ?? '';
            $this->port = $db_parts['port'] ?? 5432;
        } else {
            // Default values when DATABASE_URL is not set
            $this->host = '/var/run/postgresql';
            $this->db_name = 'hiajacom_market';
            $this->username = 'hiajacom';
            $this->password = 'Puriarteri45&';
            $this->port = 5432;
        }
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>
