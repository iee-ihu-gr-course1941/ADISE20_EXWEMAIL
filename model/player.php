<?php namespace model;

class Player implements \JsonSerializable
{
    private $db;
    private $user;

    private $id;
    private $game;
    private $userId;
    private $username;
    private $state;

    public function __construct()
    {
        require_once(dirname(__FILE__) . '/../includes.php');

        $this->db = db();
        if (!isset($_SESSION['user'])) {
            die('Not logged in');
        }
        $this->user = new User();
    }

    public function setupSession()
    {
        $stmt = db_statement($this->db, [
            'sql' => "SELECT
                    players.id,
                    players.game,
                    players.user
                FROM players
                JOIN  games
                    ON games.id = players.game
                JOIN enums AS status
                    ON status.id = games.status
                WHERE players.user = ? AND (
                    status.name = 'GAME_STATUS_WAITING_PLAYERS'
                    OR status.name = 'GAME_STATUS_RUNNING'
                )",
            'bind_param' => ['i', $this->user->getId()],
            'error' => 'Could not load player',
            'status' => 500
        ]);

        $result = $stmt->get_result();
        if ($result) {
            $_SESSION['player'] = $result->fetch_assoc();
        }
    }

    public function fromPlayerGroup($group)
    {
        $this->id = $group[0]['playerId'];
        $this->game = $group[0]['id'];
        $this->userId = $group[0]['userId'];
        $this->username = $group[0]['username'];

        $this->state = [];
        foreach ($group as $playerRow) {
            if ($playerRow['pstate_field'] == null) {
                continue;
            }
            $field = constant($playerRow['pstate_field']);
            $this->state[$field] = $playerRow['pstate_value'];
        }
    }

    public function toArray()
    {
        if (!isset($this->id)) {
            die('Player is not initialized');
        }

        return [
            'id' => $this->id,
            'game' => $this->game,
            'userId' => $this->userId,
            'username' => $this->username,
            'state' => $this->state
        ];
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
