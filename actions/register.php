<?php

use model\User;
use model\Session;

return (function () {
    include dirname(__FILE__) . '/../model/session.php';
    include dirname(__FILE__) . '/../model/user.php';

    Session::initialize();

    $username = $_POST['username'];
    $password = $_POST['password'];

    $user = new User();
    $user->register($username, $password);

    header('Content-Type: application/json');
    echo json_encode($user->toArray());
})();
