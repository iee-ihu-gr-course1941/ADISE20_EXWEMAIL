<?php

return (function () {
    require_once(dirname(__FILE__) . '/../includes.php');

    session_destroy();

    header('Content-Type: application/json');
    echo json_encode(['message'=>'Success']);
})();
