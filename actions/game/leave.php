<?php

use model\Game;

return (function () {
    require_once(dirname(__FILE__) . '/../../includes.php');

    $game = new Game();
    $rows = $game->leave();

    header('Content-Type: application/json');
    echo $rows;
})();
