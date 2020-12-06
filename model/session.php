<?php namespace model;

use \SessionHandlerInterface;

class Session implements SessionHandlerInterface
{
    private $db;

    public function open($savePath, $sessionName)
    {
        $this->db = require(dirname(__FILE__) . '/../database/db.php');
        return true;
    }

    public function close()
    {
        $this->db->close();
        return true;
    }

    public function read($sid)
    {
        $sql = 'SELECT * FROM sessions WHERE sid = ?';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            die('Session failed: ' . $this->db->error);
        }

        $stmt->bind_param('s', $sid);
        if (!$stmt->execute()) {
            return [];
        }

        $result = $stmt->get_result();
        $session = $result->fetch_assoc();
        $data = json_decode($session['data']);

        return $data;
    }

    public function write($sid, $data)
    {
        $sql = 'INSERT INTO sessions (sid, data) VALUES (?, ?) ON DUPLICATE KEY UPDATE data = ?';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            die('Session failed: ' . $this->db->error);
        }

        $json = json_encode($data);
        $stmt->bind_param('sss', $sid, $json, $json);
        if (!$stmt->execute()) {
            die('Session failed: ' . $this->db->error);
        }

        return true;
    }

    public function destroy($sid)
    {
        $sql = 'DELETE FROM sessions WHERE sid = ?';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            die('Session failed: ' . $this->db->error);
        }

        $stmt->bind_param('s', $sid);
        if ($stmt->execute()) {
            die('Session failed: ' . $this->db->error);
        }

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
