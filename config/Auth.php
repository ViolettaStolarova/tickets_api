<?php
require_once 'Database.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    public function validateApiKey($plainKey) {
        if (empty($plainKey)) {
            return false;
        }

        $stmt = $this->db->prepare("SELECT api_key, is_active FROM api_keys WHERE is_active = TRUE LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        return password_verify($plainKey, $row['api_key']);
    }
}