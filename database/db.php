<?php return (function () {
    $config = require(dirname(__FILE__) . '/../.config.php');

    $serverName = $config['db_server'];
    $username = $config['db_username'];
    $password = $config['db_password'];
    $name = $config['db_name'];

    $conn = new mysqli($serverName, $username, $password, $name);

    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }

    return $conn;
})();
