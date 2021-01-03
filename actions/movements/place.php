<?php

use model\Player;

return (function () {
    require_once(dirname(__FILE__) . '/../../includes.php');

    $bone = (int)$_POST['bone'];
    $position = (int)$_POST['position'];

    $player = new Player();
    $status = $player->placeBone($bone, $position);

    header('Content-Type: application/json');
    echo json_encode($status);
})();
