<?php

use model\Game;

return (function () {
    require_once(dirname(__FILE__) . '/../../includes.php');

    $game = new Game();
    $id = $game->create();

    header('Content-Type: application/json');
    echo $id;
})();
