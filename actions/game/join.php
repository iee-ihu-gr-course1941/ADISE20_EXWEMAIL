<?php

use model\Session;
use model\User;
use model\Game;

return (function () {
    include dirname(__FILE__) . '/../../model/session.php';
    include dirname(__FILE__) . '/../../model/user.php';
    include dirname(__FILE__) . '/../../model/game.php';
    include dirname(__FILE__) . '/../../model/player.php';

    Session::initialize();

    $game = new Game();
    $gameId = $_POST['game-id'];
    $id = $game->join($gameId);

    header('Content-Type: application/json');
    echo $id;
})();
