<?php

use model\Game;

return (function () {
    require_once(dirname(__FILE__) . '/../../includes.php');

    $game = new Game();
    $gameId = $_POST['game-id'];
    $status = $game->join($gameId);

    header('Content-Type: application/json');
    echo json_encode($status);
})();
