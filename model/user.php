<?php namespace model;

class User
{
    private $db;
    private $id;
    private $username;

    public function __construct()
    {
        $this->db = db();

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

        $stmt = db_statement($this->db, [
            'sql' => 'SELECT id, username, password FROM users WHERE username = ?',
            'bind_param' => ['s', $username],
            'error' => 'Login failed',
            'status' => 500
        ]);

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

        $this->id = db_statement($this->db, [
            'sql' => 'INSERT INTO users (username, password) VALUES (?, ?)',
            'bind_param' => ['ss', $this->username, $hash],
            'return' => 'insert_id',
            'error' => 'Insertion failed',
            'status' => 500
        ]);

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
