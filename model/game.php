<?php namespace model;

class Game
{
    private $db;
    private $id;
    private $user;

    public function __construct()
    {
        $this->db = require(dirname(__FILE__) . '/../database/db.php');
        if (!isset($_SESSION['user'])) {
            die('Not logged in');
        }
        $this->user = new User();
    }

    public function create()
    {
        $status = 0;
        $userId = $this->user->toArray()['id'];
        $sql = 'SELECT id FROM enums WHERE name = "GAME_STATUS_WAITING_PLAYERS"';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stmt->bind_result($status);
        $stmt->store_result();
        $stmt->fetch();
        $stmt->close();

        $sql = 'INSERT INTO games (code, status) VALUES (?, ?)';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            die('Insertion failed: ' . $this->db->error);
        }

        $code = $this->generateRandomString();
        $stmt->bind_param('si', $code, $status);
        if (!$stmt->execute()) {
            die('Insertion failed: ' . $stmt->error);
        }

        $this->id = $stmt->insert_id;
        $stmt->close();

        $sql = 'INSERT INTO players (user, game) VALUES (?, ?)';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            die('Insertion failed: ' . $this->db->error);
        }

        $stmt->bind_param('ii', $userId, $this->id);
        if (!$stmt->execute()) {
            die('Insertion failed: ' . $stmt->error);
        }

        $playerId = $stmt->insert_id;
        $stmt->close();

        $field = 0;
        $userId = $this->user->toArray()['id'];
        $sql = 'SELECT id FROM enums WHERE name = "GSTATE_FIELD_HOST"';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stmt->bind_result($field);
        $stmt->store_result();
        $stmt->fetch();
        $stmt->close();

        $sql = 'INSERT INTO game_state (game, field, value) VALUES (?, ?, ?)';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            die('Insertion failed: ' . $this->db->error);
        }

        $stmt->bind_param('iii', $this->id, $field, $playerId);
        if (!$stmt->execute()) {
            die('Insertion failed: ' . $stmt->error);
        }

        $stmt->close();

        return $this->id;
    }

    public function join()
    {
    }

    public function leave()
    {
    }

    private function generateRandomString($length = 10)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
