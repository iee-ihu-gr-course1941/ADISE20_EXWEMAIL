<?php

define('GAME_STATUS_WAITING_PLAYERS', 'waitingForPlayers');
define('GAME_STATUS_RUNNING', 'running');
define('GAME_STATUS_ENDED', 'ended');

define('GSTATE_FIELD_HOST', 'host');
define('GSTATE_FIELD_CURRENT_PLAYER', 'currentPlayer');
define('GSTATE_FIELD_BOARD', 'board');
define('GSTATE_FIELD_REMAINING_BONES', 'remainingBones');
define('GSTATE_FIELD_SEATS', 'seats');

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

include_once dirname(__FILE__) . '/dominoz/board.php';
include_once dirname(__FILE__) . '/dominoz/movements.php';

function db()
{
    global $LOCAL_CONFIG;

    $serverName = $LOCAL_CONFIG['db_server'];
    $username = $LOCAL_CONFIG['db_username'];
    $password = $LOCAL_CONFIG['db_password'];
    $name = $LOCAL_CONFIG['db_name'];

    $conn = new mysqli($serverName, $username, $password, $name);

    if ($conn->connect_error) {
        error_response('Connection failed', 501, $conn->connect_error);
    }

    return $conn;
}

function db_statement($db, $params)
{
    if (!isset($params['sql'])) {
        error_response('No query');
    }

    $message = 'SQL error';
    if (isset($params['error'])) {
        $message = $params['error'];
    }

    $status = 501;
    if (isset($params['status'])) {
        $status = $params['status'];
    }

    $stmt = $db->prepare($params['sql']);
    if (!$stmt) {
        error_response($message, $status, $db->error);
    }

    if (isset($params['bind_param'])) {
        $stmt->bind_param(...$params['bind_param']);
    }

    if (!$stmt->execute()) {
        error_response($message, $status, $stmt->error);
    }

    if (isset($params['bind_result'])) {
        $stmt->bind_result(...$params['bind_result']);
        $stmt->store_result();
        $stmt->fetch();
        $stmt->close();
    }

    if (isset($params['return'])) {
        if ($params['return'] === 'result') {
            $result = $stmt->get_result();
            if (!$result) {
                error_response($message, $status, $db->error);
            }
            $result = $result->fetch_assoc();
        } else {
            $result = $stmt->{$params['return']};
        }

        $stmt->close();
        return $result;
    }

    return $stmt;
}

function error_response($message, $status = 501, $details = null)
{
    header('Content-Type: application/json');
    http_response_code($status);
    die(json_encode([
        'status' => $status,
        'message' => $message,
        'details' => $details
    ]));
}

model\Session::initialize();
