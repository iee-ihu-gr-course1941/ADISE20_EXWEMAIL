<?php namespace model;

use mysqli;

class User
{
    private mysqli $db;
    private int $id;
    private string $username;
    private string $password;

    public function __construct()
    {
        $this->db = require(dirname(__FILE__) . '/../database/db.php');
    }

    public function login(string $username, string $password)
    {
        if (isset($this->username)) {
            die('User logged in');
        }

        $sql = 'SELECT id, username, password FROM users WHERE username = ?';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            die('Login failed: ' . $this->db->error);
        }

        $stmt->bind_param('s', $username);
        if (!$stmt->execute()) {
            die('Login failed: ' . $stmt->error);
        }

        $result = $stmt->get_result();
        if (!$result) {
            die('36 Bad credentials');
        }

        $user = $result->fetch_assoc();
        $hash = $user['password'];
        if (!password_verify($password, $hash)) {
            die('Bad credentials');
        }

        $this->username = $username;
        $this->id = $user['id'];

        return $this->id;
    }

    public function register(string $username, string $password)
    {
        if (isset($this->username)) {
            die('User logged in');
        }

        $this->username = $username;
        $this->password = password_hash($password, PASSWORD_BCRYPT);
        unset($password);

        $sql = 'INSERT INTO users (username, password) VALUES (?, ?)';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            die('Insertion failed: ' . $this->db->error);
        }

        $stmt->bind_param('ss', $this->username, $this->password);
        if (!$stmt->execute()) {
            die('Insertion failed: ' . $stmt->error);
        }

        $this->id = $stmt->insert_id;
        return $this->id;
    }
}
