<?php

define('GAME_STATUS_WAITING_PLAYERS', 'waitingForPlayers');
define('GAME_STATUS_RUNNING', 'running');
define('GAME_STATUS_ENDED', 'ended');

define('GSTATE_FIELD_HOST', 'host');
define('GSTATE_FIELD_CURRENT_PLAYER', 'currentPlayer');
define('GSTATE_FIELD_BOARD', 'board');

define('PSTATE_FIELD_READY', 'ready');
define('PSTATE_FIELD_HAND', 'hand');

global $LOCAL_CONFIG;
if (!isset($LOCAL_CONFIG)) {
    $LOCAL_CONFIG = require(dirname(__FILE__) . '/.config.php');
}

include_once dirname(__FILE__) . '/model/game.php';
include_once dirname(__FILE__) . '/model/player.php';
include_once dirname(__FILE__) . '/model/session.php';
include_once dirname(__FILE__) . '/model/user.php';

function db()
{
    global $LOCAL_CONFIG;

    $serverName = $LOCAL_CONFIG['db_server'];
    $username = $LOCAL_CONFIG['db_username'];
    $password = $LOCAL_CONFIG['db_password'];
    $name = $LOCAL_CONFIG['db_name'];

    $conn = new mysqli($serverName, $username, $password, $name);

    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }

    return $conn;
}

model\Session::initialize();
