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

    public function create($seats)
    {
        if ($seats < 2 || $seats > 4) {
            error_response('Invalid seats count', 400);
        }

        $player = new Player();
        $player->setupSession();

        if (isset($_SESSION['player'])) {
            error_response('Already in game', 400);
        }

        $this->code = $this->generateRandomString();
        $this->id = db_statement($this->db, [
            'sql' => 'INSERT INTO games (status, code)
                VALUES (get_enum("GAME_STATUS_WAITING_PLAYERS"), ?)',
            'bind_param' => ['s', $this->code],
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

        $rows = db_statement($this->db, [
            'sql' => 'INSERT INTO game_state (field, game, value)
                VALUES
                    (get_enum("GSTATE_FIELD_HOST"), ?, ?),
                    (get_enum("GSTATE_FIELD_SEATS"), ?, ?)',
            'bind_param' => [
                'iiii',
                $this->id, $playerId,
                $this->id, $seats
            ],
            'return' => 'affected_rows',
            'error' => 'Insertion failed',
            'status' => 500
        ]);

        if ($rows !== 2) {
            error_response('Insertion failed', 501);
        }

        db_statement($this->db, [
            'sql' => 'INSERT INTO player_state (field, player, value)
                VALUES (get_enum("PSTATE_FIELD_READY"), ?, 0)',
            'bind_param' => ['i', $playerId],
            'return' => 'affected_rows',
            'error' => 'Insertion failed',
            'status' => 500
        ]);

        return $this->id;
    }

    public function join($gameId)
    {
        $player = new Player();
        $player->setupSession();

        if (isset($_SESSION['player'])) {
            error_response('Already in game', 400);
        }

        $status = 0;
        db_statement($this->db, [
            'sql' => 'SELECT id FROM enums WHERE name = "GAME_STATUS_WAITING_PLAYERS"',
            'bind_result' => [&$status]
        ]);

        $gameStatus = 0;
        db_statement($this->db, [
            'sql' => 'SELECT status FROM games WHERE id = ?',
            'bind_param' => ['i', $gameId],
            'bind_result' => [&$gameStatus]
        ]);

        if ($gameStatus !== $status) {
            error_response('This game is not waiting for players', 400);
        }

        $players = 0;
        db_statement($this->db, [
            'sql' => 'SELECT COUNT(id) FROM players WHERE game = ?',
            'bind_param' => ['i', $gameId],
            'bind_result' => [&$players],
            'error' => 'Insertion failed',
            'status' => 500
        ]);

        $seats = 0;
        db_statement($this->db, [
            'sql' => 'SELECT value FROM game_state
                WHERE field = get_enum("GSTATE_FIELD_SEATS") AND game = ?',
            'bind_param' => ['i', $gameId],
            'bind_result' => [&$seats],
            'error' => 'Insertion failed',
            'status' => 500
        ]);
        $seats = (int)$seats;

        if ($players === $seats) {
            error_response('Game is full', 400);
        }

        $userId = $this->user->toArray()['id'];
        $playerId = db_statement($this->db, [
            'sql' => 'INSERT INTO players (user, game) VALUES (?, ?)',
            'bind_param' => ['ii', $userId, $gameId],
            'return' => 'insert_id',
            'error' => 'Insertion failed',
            'status' => 500
        ]);

        db_statement($this->db, [
            'sql' => 'INSERT INTO player_state (field, player, value)
                VALUES (get_enum("PSTATE_FIELD_READY"), ?, 0)',
            'bind_param' => ['i', $playerId],
            'return' => 'affected_rows',
            'error' => 'Insertion failed',
            'status' => 500
        ]);

        $this->id = $gameId;

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
                    AND field = get_enum('GSTATE_FIELD_HOST')",
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

        $gameStatusRunning = 0;
        db_statement($this->db, [
            'sql' => 'SELECT id FROM enums WHERE name = "GAME_STATUS_RUNNING"',
            'bind_result' => [&$gameStatusRunning],
            'error' => 'Insertion failed',
            'code' => 500
        ]);

        $currentGameStatus = 0;
        db_statement($this->db, [
            'sql' => 'SELECT status FROM games WHERE id = ?',
            'bind_param' => ['i', $this->id],
            'bind_result' => [&$currentGameStatus],
            'error' => 'Could not select status',
            'status' => 500
        ]);

        if (!$players || $currentGameStatus === $gameStatusRunning) {
            db_statement($this->db, [
                'sql' => 'UPDATE games SET status = get_enum("GAME_STATUS_ENDED") WHERE id = ?',
                'bind_param' => ['i', $this->id],
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

            db_statement($this->db, [
                'sql' => 'UPDATE game_state SET value = ? WHERE game = ? AND field = get_enum("GSTATE_FIELD_HOST")',
                'bind_param' => ['ii', $playerId, $this->id],
                'error' => 'Host update failed',
                'status' => 500
            ]);
        }

        return $rows;
    }

    public function ready()
    {
        $player = new Player();
        $player->setupSession();

        if (!isset($_SESSION['player'])) {
            error_response('Not in a game', 400);
        }

        $playerId = $_SESSION['player']['id'];
        $rows = db_statement($this->db, [
            'sql' => 'UPDATE player_state SET value = 1
                WHERE player = ? AND field = get_enum("PSTATE_FIELD_READY")',
            'bind_param' => ['i', $playerId],
            'return' => 'affected_rows',
            'error' => 'Insertion failed',
            'status' => 500
        ]);

        $seats = 0;
        db_statement($this->db, [
            'sql' => 'SELECT value FROM game_state
                WHERE field = get_enum("GSTATE_FIELD_SEATS") AND game = ?',
            'bind_param' => ['i', $this->id],
            'bind_result' => [&$seats],
            'error' => 'Insertion failed',
            'status' => 500
        ]);
        $seats = (int)$seats;

        $readyPlayers = 0;
        db_statement($this->db, [
            'sql' => 'SELECT COUNT(players.id) FROM players
                JOIN player_state ON player_state.player = players.id
                WHERE game = ?
                AND player_state.field = get_enum("PSTATE_FIELD_READY")
                AND player_state.value = "1"',
            'bind_param' => ['i', $this->id],
            'bind_result' => [&$readyPlayers],
            'error' => 'Insertion failed',
            'status' => 500
        ]);

        if ($readyPlayers === $seats) {
            db_statement($this->db, [
                'sql' => 'UPDATE games SET status = get_enum("GAME_STATUS_RUNNING") WHERE id = ?',
                'bind_param' => ['i', $this->id],
                'error' => 'Game status change failed',
                'status' => 500
            ]);

            $host = 0;
            db_statement($this->db, [
                'sql' => 'SELECT value FROM game_state WHERE field = get_enum("GSTATE_FIELD_HOST") AND game = ?',
                'bind_param' => ['i', $this->id],
                'bind_result' => [&$host],
                'error' => 'Insertion failed',
                'code' => 500
            ]);

            db_statement($this->db, [
                'sql' => 'INSERT INTO game_state (field, game, value)
                    VALUES (get_enum("GSTATE_FIELD_CURRENT_PLAYER"), ?, ?)',
                'bind_param' => ['ii', (int)$this->id,  (int)$host],
                'error' => 'Game field change failed',
                'status' => 500
            ]);

            $board = new \DominoZ\Board($this->id);
            $board->initialize();
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
                WHERE games.status = get_enum('GAME_STATUS_WAITING_PLAYERS')",
            'error' => 'List query failed',
            'status' => 500
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
