<?php

use model\Game;

return (function () {
    require_once(dirname(__FILE__) . '/../../includes.php');

    $seats = 2;
    if (isset($_POST['seats'])) {
        $seats = (int)$_POST['seats'];
    }

    $game = new Game();
    $status = $game->create($seats);

    header('Content-Type: application/json');
    echo json_encode($status);
})();
