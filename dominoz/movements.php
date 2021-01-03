<?php namespace DominoZ;

class Movements
{
    private $db;
    private $game;
    private $gameStatus;
    private $player;

    public function __construct($game, $player)
    {
        require_once(dirname(__FILE__) . '/../includes.php');
        $this->db = db();
        $this->game = $game;
        $this->player = $player;
        $this->gameStatus = $this->game->getStatus($player);

        if ($this->gameStatus['game']['status'] !== "running") {
            error_response('Game is not running', 400);
    }
    }

    public function place($bone, $position)
    {
        $game = new \model\Game();
        $game = $game->getStatus();
        if ($game['status'] !== "running") {
            error_response('Game is not running', 400);
        }

        $player = new \model\Player();
        $playerId = $player->getId();

        if ($game['state']['currentPlayer'] !== $playerId) {
            error_response('Not your turn', 400);
        }

        $board = $game['board'];
        $hand = $game['hand'];

        if (!isset($hand[$bone])) {
            error_response('Bone does not exist', 400);
        }
        array_splice($hand, $bone, 1);

        if (count($board) === 0) {
            $board[] = $hand[$bone];
        } else {
            $selectedIndex = 0;
            if ($position === 1) {
                $selectedIndex = count($board) - 1;
            }
        }
    }
}
