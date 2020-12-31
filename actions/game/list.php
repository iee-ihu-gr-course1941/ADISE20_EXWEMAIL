<?php

use model\Game;

return (function () {
    require_once(dirname(__FILE__) . '/../../includes.php');

    $game = new Game();
    $games = $game->getList();

    header('Content-Type: application/json');
    echo json_encode($games);
})();
