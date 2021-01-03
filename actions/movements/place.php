<?php

use DominoZ\Movements;

return (function () {
    require_once(dirname(__FILE__) . '/../../includes.php');

    $bone = $_POST['bone'];
    $position = $_POST['position'];

    $movements = new Movements();
    $rows = $movements->place($bone, $position);

    header('Content-Type: application/json');
    echo $rows;
})();
