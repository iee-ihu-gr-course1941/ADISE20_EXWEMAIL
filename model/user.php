<?php namespace model;

class User
{
    private $db;
    private $id;
    private $username;

    public function __construct()
    {
        $this->db = require(dirname(__FILE__) . '/../database/db.php');

        if (isset($_SESSION['user'])) {
            $user = $_SESSION['user'];
            $this->loadData($user);
        }
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
            die('Bad credentials');
        }

        $user = $result->fetch_assoc();
        $hash = $user['password'];
        if (!password_verify($password, $hash)) {
            die('Bad credentials');
        }

        unset($password);
        unset($hash);
        unset($user['password']);

        $this->loadData($user);

        $_SESSION['user'] = $this->toArray();

        return $this->id;
    }

    public function register(string $username, string $password)
    {
        if (isset($this->username)) {
            die('User logged in');
        }

        $this->username = $username;
        $hash = password_hash($password, PASSWORD_BCRYPT);
        unset($password);

        $sql = 'INSERT INTO users (username, password) VALUES (?, ?)';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            die('Insertion failed: ' . $this->db->error);
        }

        $stmt->bind_param('ss', $this->username, $hash);
        if (!$stmt->execute()) {
            die('Insertion failed: ' . $stmt->error);
        }

        $this->id = $stmt->insert_id;
        return $this->id;
    }

    private function loadData($data)
    {
        $this->id = $data['id'];
        $this->username = $data['username'];
    }

    public function toArray()
    {
        if (!isset($this->username)) {
            die('Not logged in');
        }

        return [
            'id' => $this->id,
            'username' => $this->username
        ];
    }
}
