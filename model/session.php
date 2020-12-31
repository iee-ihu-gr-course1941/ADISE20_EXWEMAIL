<?php namespace model;

use \SessionHandlerInterface;

class Session implements SessionHandlerInterface
{
    private $db;

    public function open($savePath, $sessionName)
    {
        require_once(dirname(__FILE__) . '/../includes.php');

        $this->db = db();
        return true;
    }

    public function close()
    {
        $this->db->close();
        return true;
    }

    public function read($sid)
    {
        $session = db_statement($this->db, [
            'sql' => 'SELECT * FROM sessions WHERE sid = ?',
            'bind_param' => ['s', $sid],
            'return' => 'result',
            'error' => 'Session failed',
            'status' => 500
        ]);

        return json_decode($session['data']);
    }

    public function write($sid, $data)
    {
        $json = json_encode($data);
        db_statement($this->db, [
            'sql' => 'INSERT INTO sessions (sid, data) VALUES (?, ?) ON DUPLICATE KEY UPDATE data = ?',
            'bind_param' => ['sss', $sid, $json, $json],
            'error' => 'Session failed',
            'status' => 500
        ]);

        return true;
    }

    public function destroy($sid)
    {
        db_statement($this->db, [
            'sql' => 'DELETE FROM sessions WHERE sid = ?',
            'bind_param' => ['s', $sid],
            'error' => 'Session failed',
            'status' => 500
        ]);

        return true;
    }

    public function gc($maxlifetime)
    {
        return true;
    }

    private static $handler;
    public static function initialize()
    {
        if (isset(self::$handler)) {
            return;
        }

        self::$handler = new Session();
        session_set_save_handler(self::$handler, true);
        session_start();
    }
}
