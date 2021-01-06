<?php

use model\Game;

return (function () {
    require_once(dirname(__FILE__) . '/../../includes.php');

    $game = new Game();
    $game->ready();
    $status = $status = $game->getStatus();

    header('Content-Type: application/json');
    echo json_encode($status);
})();
