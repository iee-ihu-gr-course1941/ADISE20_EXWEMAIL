<?php namespace model;

class User implements \JsonSerializable
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
            error_response('User logged in', 401);
        }

        $stmt = db_statement($this->db, [
            'sql' => 'SELECT id, username, password FROM users WHERE username = ?',
            'bind_param' => ['s', $username],
            'error' => 'Login failed',
            'status' => 500
        ]);

        $result = $stmt->get_result();
        if (!$result) {
            error_response('Bad credentials', 401);
        }

        $user = $result->fetch_assoc();
        $hash = $user['password'];
        if (!password_verify($password, $hash)) {
            error_response('Bad credentials', 401);
        }

        unset($password);
        unset($hash);
        unset($user['password']);

        $this->loadData($user);

        $_SESSION['user'] = $this->toArray();

        $player = new Player();
        $player->setupSession();

        return $this->id;
    }

    public function register(string $username, string $password)
    {
        if (isset($this->username)) {
            error_response('User logged in', 400);
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

    public function getId()
    {
        return $this->id;
    }

    public function toArray()
    {
        if (!isset($this->username)) {
            error_response('Not logged in', 400);
        }

        return [
            'id' => $this->id,
            'username' => $this->username
        ];
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
