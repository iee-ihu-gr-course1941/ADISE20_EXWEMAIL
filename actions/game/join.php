<?php

use model\Game;

return (function () {
    require_once(dirname(__FILE__) . '/../../includes.php');

    $game = new Game();
    $gameId = $_POST['game-id'];
    $id = $game->join($gameId);

    header('Content-Type: application/json');
    echo $id;
})();
