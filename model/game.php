<?php namespace model;

class Game implements \JsonSerializable
{
    private $db;
    private $user;

    private $id;
    private $code;
    private $players;
    private $state;

    public function __construct()
    {
        include dirname(__FILE__) . '/../enums.php';

        $this->db = require(dirname(__FILE__) . '/../database/db.php');
        if (!isset($_SESSION['user'])) {
            die('Not logged in');
        }
        $this->user = new User();
    }

    public function create()
    {
        if (isset($_SESSION['player'])) {
            die('Already in game');
        }

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

        $this->code = $this->generateRandomString();
        $stmt->bind_param('si', $this->code, $status);
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

        $sql = 'SELECT id, game, user FROM players WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $playerId);
        if (!$stmt->execute()) {
            die('Insertion failed: ' . $stmt->error);
        }
        $result = $stmt->get_result();

        if (!$result) {
            die('Selection of player failed');
        }

        $_SESSION['player'] = $result->fetch_assoc();

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

    public function join($gameId)
    {
        if (isset($_SESSION['player'])) {
            die('Already in game');
        }

        $userId = $this->user->toArray()['id'];
        $sql = 'INSERT INTO players (user, game) VALUES (?, ?)';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            die('Insertion failed: ' . $this->db->error);
        }

        $stmt->bind_param('ii', $userId, $gameId);
        if (!$stmt->execute()) {
            die('Insertion failed: ' . $stmt->error);
        }

        $this->id = $gameId;
        $playerId = $stmt->insert_id;
        $stmt->close();

        $sql = 'SELECT id, game, user FROM players WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $playerId);
        if (!$stmt->execute()) {
            die('Insertion failed: ' . $stmt->error);
        }
        $result = $stmt->get_result();

        if (!$result) {
            die('Selection of player failed');
        }

        $_SESSION['player'] = $result->fetch_assoc();

        return $gameId;
    }

    public function leave()
    {
    }

    public function getList()
    {
        $sql = "SELECT
                games.id,
                games.code,
                gstate_fields.name AS gstate_field,
                game_state.value AS gstate_value,
                players.id as playerId,
                users.id AS userId,
                users.username,
                pstate_fields.name AS pstate_field,
                player_state.value AS pstate_value
            FROM games
            JOIN game_state
                ON game_state.game = games.id
            JOIN enums AS gstate_fields
                ON gstate_fields.id = game_state.field
            JOIN players
                ON players.game = games.id
            JOIN users
                ON users.id = players.user
            LEFT JOIN player_state
                ON player_state.player = players.id
            LEFT JOIN enums AS pstate_fields
                ON pstate_fields.id = player_state.field
            WHERE games.status = (
                SELECT id
                FROM enums
                WHERE name = 'GAME_STATUS_WAITING_PLAYERS'
            )
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            die('List failed: ' . $this->db->error);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $games = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $gamesGrouped = [];
        foreach ($games as $game) {
            $gamesGrouped[$game['id']][] = $game;
        }

        $gamesParsed = [];
        foreach ($gamesGrouped as $group) {
            $game = new Game();
            $game->fromGameGroup($group);
            $gamesParsed[] = $game;
        }

        return $gamesParsed;
    }

    public function fromGameGroup($group)
    {
        if (count($group) === 0) {
            return;
        }

        $this->id = $group[0]['id'];
        $this->code = $group[0]['code'];
        $this->players = [];

        $this->state = [];
        foreach ($group as $gameRow) {
            $field = constant($gameRow['gstate_field']);
            $this->state[$field] = $gameRow['gstate_value'];
        }

        $this->loadPlayers($group);

        $this->state['host'] = (int)($this->state['host']);
    }

    public function loadPlayers($group)
    {
        if (count($group) === 0) {
            return;
        }

        $this->players = [];

        $playersGroupedByUser = [];
        foreach ($group as $player) {
            $playersGroupedByUser[$player['userId']][] = $player;
        }

        foreach ($playersGroupedByUser as $playerGroup) {
            if (count($playerGroup) === 0) {
                continue;
            }

            $player = new Player();
            $player->fromPlayerGroup($playerGroup);
            $this->players[] = $player;
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function toArray()
    {
        if (!isset($this->id)) {
            die('Game is not initialized');
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'players' => $this->players,
            'state' => $this->state
        ];
    }

    public function jsonSerialize()
    {
        return $this->toArray();
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
