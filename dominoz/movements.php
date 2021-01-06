<?php namespace DominoZ;

class Movements
{
    private $db;
    private $game;
    private $gameStatus;
    private $player;

    public function __construct($game, $player, $status = null)
    {
        require_once(dirname(__FILE__) . '/../includes.php');
        $this->db = db();
        $this->game = $game;
        $this->player = $player;
        if ($status) {
            $this->gameStatus = $status;
        } else {
            $this->gameStatus = $this->game->getStatus($player);
        }
    }

    public function place($bone, $position)
    {
        if ($this->gameStatus['game']['status'] !== "running") {
            error_response('Game is not running', 400);
        }

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

        db_statement($this->db, [
            'sql' => 'UPDATE player_state SET value = ?
                WHERE field = get_enum("PSTATE_FIELD_HAND") AND player = ?',
            'bind_param' => ['si', json_encode($hand), $playerId],
            'error' => 'Could not update hand',
            'status' => 500
        ])->close();
        $this->gameStatus['hand'] = $hand;

        db_statement($this->db, [
            'sql' => 'UPDATE game_state SET value = ?
                WHERE field = get_enum("GSTATE_FIELD_BOARD") AND game = ?',
            'bind_param' => ['si', json_encode($board), $this->game->getId()],
            'error' => 'Could not update board',
            'status' => 500
        ])->close();

        $this->nextPlayer();

        if (!$hand) {
            $this->winner();
            return;
        }
        $this->drawBone();
    }

    public function nextPlayer()
    {
        $nextPlayer = 0;
        $playerId = $this->player->getId();
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
        $this->gameStatus['turn'] = $nextPlayer;

        db_statement($this->db, [
            'sql' => 'UPDATE game_state SET value = ?
                WHERE field = get_enum("GSTATE_FIELD_CURRENT_PLAYER") AND game = ?',
            'bind_param' => ['ii', $nextPlayer, $this->game->getId()],
            'error' => 'Could not update board',
            'status' => 500
        ])->close();
    }

    public function suggestions()
    {
        if ($this->gameStatus['game']['status'] !== "running") {
            return null;
        }

        $board = $this->gameStatus['board'];
        $hand = $this->gameStatus['hand'];
        $countBoard = count($board) - 1;
        $suggestions = [];
        foreach ($hand as $bone) {
            if (($board[$countBoard][1] === $bone[0]) ||
                ($board[0][0] === $bone[1]) ||
                ($board[0][0] === $bone[0]) ||
                ($board[$countBoard][1] === $bone[1])
            ) {
                $suggestions[] = $bone;
            }
        }
        return $suggestions;
    }

    public function winner()
    {
        db_statement($this->db, [
            'sql' => 'UPDATE games SET status = get_enum("GAME_STATUS_ENDED") WHERE id = ?',
            'bind_param' => ['i', $this->game->getId()],
            'error' => 'Game status change failed',
            'status' => 500
        ]);

        db_statement($this->db, [
            'sql' => 'INSERT INTO game_state (field, game, value)
                VALUES (get_enum("GSTATE_FIELD_WINNER"), ?, ?)',
            'bind_param' => ['ii', $this->game->getId(), $this->player->getId()],
            'error' => 'Game field change failed',
            'status' => 500
        ]);
    }

    public function drawBone()
    {
        $player = null;
        $playerId = $this->gameStatus['turn'];

        foreach ($this->game->toArray()['players'] as $gamePlayer) {
            if ($gamePlayer->getId() !== $playerId) {
                continue;
            }

            $player = $gamePlayer;
            break;
        }

        $movements = new Movements($this->game, $player);
        $suggestions = $movements->suggestions();

        if ($suggestions) {
            return;
        }

        $remainingBones = 0;
        db_statement($this->db, [
            'sql' => 'SELECT value FROM game_state
                WHERE field = get_enum("GSTATE_FIELD_REMAINING_BONES") AND game = ?',
            'bind_param' => ['i', $this->game->getId()],
            'bind_result' => [&$remainingBones],
            'error' => 'Insertion failed',
            'code' => 500
        ]);
        $remainingBones = json_decode($remainingBones);

        if (!$remainingBones) {
            $this->winner();
            return;
        }

        $length = count($remainingBones) - 1;
        $randomNumber = rand(0, $length);

        $hand = $player->toArray()['state']['hand'];
        $hand[] = $remainingBones[$randomNumber];
        array_splice($remainingBones, $randomNumber, 1);

        db_statement($this->db, [
            'sql' => 'UPDATE player_state SET value = ?
                WHERE field = get_enum("PSTATE_FIELD_HAND") AND player = ?',
            'bind_param' => ['si', json_encode($hand), $playerId],
            'error' => 'Could not update hand',
            'status' => 500
        ])->close();
        $player->toArray()['state']['hand'] = $hand;

        db_statement($this->db, [
            'sql' => 'UPDATE game_state SET value = ?
                WHERE field = get_enum("GSTATE_FIELD_REMAINING_BONES") AND game = ?',
            'bind_param' => ['si', json_encode($remainingBones), $this->game->getId()],
            'error' => 'Could not update hand',
            'status' => 500
        ])->close();

        $suggestions = $movements->suggestions();

        if (!$suggestions) {
            $this->nextPlayer();
            $this->drawBone();
        }
    }
}
