<?php

use model\User;

return (function () {
    include dirname(__FILE__) . '/../model/user.php';

    $username = $_POST['username'];
    $password = $_POST['password'];

    $user = new User();
    $user->register($username, $password);

    header('Content-Type: application/json');
    echo json_encode($user->toArray());
})();
