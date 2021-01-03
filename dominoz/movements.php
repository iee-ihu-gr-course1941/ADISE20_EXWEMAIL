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
        if ($position !== 0 && $position !== 1) {
            error_response('Invalid movement', 400);
        }

        $playerId = $this->player->getId();

        if ($this->gameStatus['turn'] !== $playerId) {
            error_response('Not your turn', 400);
        }

        $board = $this->gameStatus['board'];
        $hand = $this->gameStatus['hand'];

        if (!isset($hand[$bone])) {
            error_response('Invalid movement', 400);
        }

        $playedBone = null;
        if (count($board) === 0) {
            if ($position === 1) {
                $playedBone = [
                    $hand[$bone][1],
                    $hand[$bone][0]
                ];
            } else {
                $playedBone = $hand[$bone];
            }
        } else {
            $selectedIndex = 0;
            if ($position === 1) {
                $selectedIndex = count($board) - 1;
            }

            if (($board[$selectedIndex][$position] === $hand[$bone][0] && $position === 1) ||
                ($board[$selectedIndex][$position] === $hand[$bone][1] && $position === 0)
            ) {
                $playedBone = $hand[$bone];
            } elseif (($board[$selectedIndex][$position] === $hand[$bone][0] && $position === 0) ||
                      ($board[$selectedIndex][$position] === $hand[$bone][1] && $position === 1)
            ) {
                $playedBone = [
                    $hand[$bone][1],
                    $hand[$bone][0]
                ];
            } else {
                error_response('Invalid movement', 400);
            }
        }

        if ($position === 1) {
            $board[] = $playedBone;
        } else {
            array_unshift($board, $playedBone);
        }

        array_splice($hand, $bone, 1);

        $nextPlayer = 0;
        $players = $this->gameStatus['players'];
        foreach ($players as $player) {
            if ($player['id'] > $playerId) {
                $nextPlayer = $player['id'];
                break;
            }
        }
        if (!$nextPlayer) {
            $nextPlayer = $this->gameStatus['players'][0]['id'];
        }

        db_statement($this->db, [
            'sql' => 'UPDATE player_state SET value = ?
                WHERE field = get_enum("PSTATE_FIELD_HAND") AND player = ?',
            'bind_param' => ['si', json_encode($hand), $playerId],
            'error' => 'Could not update hand',
            'status' => 500
        ])->close();

        db_statement($this->db, [
            'sql' => 'UPDATE game_state SET value = ?
                WHERE field = get_enum("GSTATE_FIELD_BOARD") AND game = ?',
            'bind_param' => ['si', json_encode($board), $this->game->getId()],
            'error' => 'Could not update board',
            'status' => 500
        ])->close();

        db_statement($this->db, [
            'sql' => 'UPDATE game_state SET value = ?
                WHERE field = get_enum("GSTATE_FIELD_CURRENT_PLAYER") AND game = ?',
            'bind_param' => ['ii', $nextPlayer, $this->game->getId()],
            'error' => 'Could not update board',
            'status' => 500
        ])->close();
    }
}
