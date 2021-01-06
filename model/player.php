<?php namespace model;

class Player implements \JsonSerializable
{
    private $db;
    private $user;

    private $id;
    private $gameId;
    private $userId;
    private $username;
    private $state;

    public function __construct()
    {
        require_once(dirname(__FILE__) . '/../includes.php');

        $this->db = db();
        if (!isset($_SESSION['user'])) {
            error_response('Not logged in', 400);
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
            $player = $result->fetch_assoc();
            $_SESSION['player'] = $player;
            $this->id = $player['id'];
            $this->gameId = $player['game'];
        } elseif (isset($_SESSION['player'])) {
            unset($_SESSION['player']);
        }
    }

    public function placeBone($boneIndex, $position)
    {
        $this->setupSession();
        $game = new Game();
        $game->fromId($this->gameId);

        $movements = new \DominoZ\Movements($game, $this);
        $movements->place($boneIndex, $position);

        return $game->getStatus($this);
    }

    public function fromPlayerGroup($group)
    {
        $this->id = $group[0]['playerId'];
        $this->gameId = $group[0]['id'];
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

        if (isset($this->state['ready'])) {
            $this->state['ready'] = (bool)$this->state['ready'];
        }

        if (isset($this->state['hand'])) {
            $this->state['hand'] = json_decode($this->state['hand']);
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function toArray()
    {
        if (!isset($this->id)) {
            error_response('Player is not initialized', 400);
        }

        return [
            'id' => $this->id,
            'game' => $this->gameId,
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
