<?php

use model\Session;
use model\User;
use model\Game;

return (function () {
    include dirname(__FILE__) . '/../../model/session.php';
    include dirname(__FILE__) . '/../../model/user.php';
    include dirname(__FILE__) . '/../../model/game.php';

    Session::initialize();

    $game = new Game();
    $id = $game->create();

    header('Content-Type: application/json');
    echo $id;
})();
