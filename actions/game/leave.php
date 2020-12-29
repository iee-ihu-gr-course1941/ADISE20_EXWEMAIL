<?php

use model\Session;
use model\Game;

return (function () {
    include dirname(__FILE__) . '/../../model/session.php';
    include dirname(__FILE__) . '/../../model/user.php';
    include dirname(__FILE__) . '/../../model/game.php';
    include dirname(__FILE__) . '/../../model/player.php';

    Session::initialize();

    $game = new Game();
    $rows = $game->leave();

    header('Content-Type: application/json');
    echo $rows;
})();
