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
        require_once(dirname(__FILE__) . '/../includes.php');

        $this->db = db();

        if (!isset($_SESSION['user'])) {
            error_response('Not logged in', 401);
        }
        $this->user = new User();

        if (isset($_SESSION['player'])) {
            $this->id = $_SESSION['player']['game'];
        }
    }

    public function create()
    {
        if (isset($_SESSION['player'])) {
            error_response('Already in game', 400);
        }

        $status = 0;
        db_statement($this->db, [
            'sql' => 'SELECT id FROM enums WHERE name = "GAME_STATUS_WAITING_PLAYERS"',
            'bind_result' => [&$status]
        ]);

        $this->code = $this->generateRandomString();
        $this->id = db_statement($this->db, [
            'sql' => 'INSERT INTO games (code, status) VALUES (?, ?)',
            'bind_param' => ['si', $this->code, $status],
            'return' => 'insert_id',
            'error' => 'Insertion failed',
            'status' => 500
        ]);

        $userId = $this->user->toArray()['id'];
        $playerId = db_statement($this->db, [
            'sql' => 'INSERT INTO players (user, game) VALUES (?, ?)',
            'bind_param' => ['ii', $userId, $this->id],
            'return' => 'insert_id',
            'error' => 'Insertion failed',
            'status' => 500
        ]);

        $player = new Player();
        $player->setupSession();

        $field = 0;
        db_statement($this->db, [
            'sql' => 'SELECT id FROM enums WHERE name = "GSTATE_FIELD_HOST"',
            'bind_result' => [&$field]
        ]);

        $rows = db_statement($this->db, [
            'sql' => 'INSERT INTO game_state (game, field, value) VALUES (?, ?, ?)',
            'bind_param' => ['iii', $this->id, $field, $playerId],
            'return' => 'affected_rows',
            'error' => 'Insertion failed',
            'status' => 500
        ]);

        if ($rows !== 1) {
            error_response('Insertion failed', 501);
        }

        return $this->id;
    }

    public function join($gameId)
    {
        if (isset($_SESSION['player'])) {
            error_response('Already in game', 400);
        }

        $userId = $this->user->toArray()['id'];
        $playerId = db_statement($this->db, [
            'sql' => 'INSERT INTO players (user, game) VALUES (?, ?)',
            'bind_param' => ['ii', $userId, $gameId],
            'return' => 'insert_id',
            'error' => 'Insertion failed',
            'status' => 500
        ]);
        $this->id = $gameId;

        $player = new Player();
        $player->setupSession();

        return $gameId;
    }

    public function leave()
    {
        if (!isset($_SESSION['player'])) {
            error_response('Not in a game', 400);
        }

        $playerId = $_SESSION['player']['id'];

        $rows = db_statement($this->db, [
            'sql' => 'DELETE FROM players WHERE id = ? LIMIT 1',
            'bind_param' => ['i', $playerId],
            'return' => 'affected_rows',
            'error' => 'Player deletion failed',
            'status' => 500
        ]);

        if ($rows !== 1) {
            error_response('Player deletion failed', 500);
        }

        db_statement($this->db, [
            'sql' => 'DELETE FROM player_state WHERE player = ?',
            'bind_param' => ['i', $playerId],
            'return' => 'affected_rows',
            'error' => 'Player state deletion failed',
            'status' => 500
        ]);

        unset($_SESSION['player']);

        $gameHost = 0;
        db_statement($this->db, [
            'sql' => "SELECT value
                FROM game_state
                WHERE game = ?
                    AND field = (
                        SELECT id FROM enums WHERE name = 'GSTATE_FIELD_HOST'
                    )",
            'bind_param' => ['i', $this->id],
            'bind_result' => [&$gameHost],
            'error' => 'Insertion failed',
            'status' => 500
        ]);

        $players = 0;
        db_statement($this->db, [
            'sql' => 'SELECT COUNT(id) FROM players WHERE game = ?',
            'bind_param' => ['i', $this->id],
            'bind_result' => [&$players],
            'error' => 'Insertion failed',
            'status' => 500
        ]);

        if (!$players) {
            $status = 0;
            db_statement($this->db, [
                'sql' => 'SELECT id FROM enums WHERE name = "GAME_STATUS_ENDED"',
                'bind_result' => [&$status],
                'error' => 'Insertion failed',
                'code' => 500
            ]);

            db_statement($this->db, [
                'sql' => 'UPDATE games SET status = ? WHERE id = ?',
                'bind_param' => ['ii', $status, $this->id],
                'error' => 'Game status change failed',
                'status' => 500
            ]);
        } elseif ((int)$gameHost === $playerId) {
            db_statement($this->db, [
                'sql' => 'SELECT id FROM players WHERE game = ? LIMIT 1',
                'bind_param' => ['i', $this->id],
                'bind_result' => [&$playerId],
                'error' => 'Could not find new host',
                'status' => 500
            ]);

            $field = 0;
            db_statement($this->db, [
                'sql' => 'SELECT id FROM enums WHERE name = "GSTATE_FIELD_HOST"',
                'bind_result' => [&$field]
            ]);

            db_statement($this->db, [
                'sql' => 'UPDATE game_state SET field = ?, value = ? WHERE game = ?',
                'bind_param' => ['iii', $field, $playerId, $this->id],
                'error' => 'Host update failed',
                'status' => 500
            ]);

            db_statement($this->db, [
                'sql' => 'UPDATE game_state SET field = ?, value = ? WHERE game = ?',
                'bind_param' => ['iii', $field, $playerId, $this->id],
                'error' => 'Insertion failed',
                'status' => 500
            ]);
        }

        return $rows;
    }

    public function getList()
    {
        $stmt = db_statement($this->db, [
            'sql' => "SELECT
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
                )",
            'error' => 'List query failed',
            'code' => 500
        ]);
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
