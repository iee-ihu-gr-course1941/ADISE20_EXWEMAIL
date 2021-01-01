<?php

use model\User;

return (function () {
    require_once(dirname(__FILE__) . '/../includes.php');

    $username = $_POST['username'];
    $password = $_POST['password'];

    $user = new User();
    $user->login($username, $password);

    header('Content-Type: application/json');
    echo json_encode($user);
})();
