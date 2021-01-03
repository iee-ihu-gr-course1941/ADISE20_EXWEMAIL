<?php namespace DominoZ;

class Board
{
    private $db;
    private $id;

    public function __construct($id)
    {
        $this->db = db();
        $this->id = $id;
    }

    public function initialize()
    {
        $bones = [];
        for ($i = 0; $i < 7; $i++) {
            for ($j = $i; $j < 7; $j++) {
                $bones[] = [$i, $j];
            }
        }

        $playersStmt = db_statement($this->db, [
            'sql' => 'SELECT id FROM players WHERE game = ?',
            'bind_param' => ['i', $this->id],
            'error' => 'Board initialization failed',
            'status' => 500
        ]);

        $result = $playersStmt->get_result();
        $players = $result->fetch_all(MYSQLI_ASSOC);
        $playersStmt->close();

        $handField = 0;
        db_statement($this->db, [
            'sql' => 'SELECT get_enum("PSTATE_FIELD_HAND") AS hand',
            'bind_result' => [&$handField]
        ]);

        $sql = 'INSERT INTO player_state (field, player, value) VALUES ';
        $insertQuery = array();
        $insertData = array();
        foreach ($players as $playerState) {
            $playerBones = [];
            for ($i = 0; $i < 7; $i++) {
                $pick = random_int(0, count($bones) - 1);
                $playerBones[] = $bones[$pick];
                array_splice($bones, $pick, 1);
            }

            $insertQuery[] = '(?, ?, ?)';
            $insertData[] = $handField;
            $insertData[] = $playerState['id'];
            $insertData[] = json_encode($playerBones);
        }

        if (!empty($insertQuery)) {
            $sql .= implode(', ', $insertQuery);
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param(
                str_repeat('iis', count($players)),
                ...$insertData
            );
            $stmt->execute();
        }

        db_statement($this->db, [
            'sql' => 'INSERT INTO game_state (field, game, value)
                VALUES
                    (get_enum("GSTATE_FIELD_REMAINING_BONES"), ?, ?),
                    (get_enum("GSTATE_FIELD_BOARD"), ?, ?)',
            'bind_param' => ['isis',
                $this->id, json_encode($bones),
                $this->id, '[]'
            ],
            'error' => 'Board initialization failed',
            'status' => 500
        ]);
    }
}
