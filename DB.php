<?php

namespace ExampleLeo;

class DB
{
    public string $host = "";
    public string $dbName = "";
    public string $password = "";
    public string $user = "";
    public string $charset = "utf8";
    public bool $debug = true;

    public ?\PDO $pdo = null;
    public $error = "";
    public $last_id = false;

    public static ?DB $connection = null;

    private function __construct($host = null, $db_name = null, $password = null, $user = null, $charset = null, $debug = null) {
        $this->host = isset($host) ? $host : $this->host;
        $this->dbName = isset($db_name) ? $db_name : $this->dbName;
        $this->password = isset($password) ? $password : $this->password;
        $this->user = isset($user) ? $user : $this->user;
        $this->charset = isset($charset) ? $charset : $this->charset;
        $this->debug = isset($debug) ? $debug : $this->debug;

        $this->connect();
    }

    final protected function connect()
    {
        $details = "mysql:host={$this->host};dbname={$this->dbName};charset={$this->charset};";

        try {
            $this->pdo = new \PDO($details, $this->user, $this->password);

            unset(
                $this->host,
                $this->dbName,
                $this->charset,
                $this->user,
                $this->password,
                $this->debug
            );
        } catch (\PDOException $e) {
            if($this->debug) exit($e);
        }
    }

    public static function getInstance(): DB
    {
        if (!isset(self::$connection))
            self::$connection = new DB();

        return self::$connection;
    }

    public function query($stmt, $data = null) {
        $query = $this->pdo->prepare($stmt);
        $check_exec = $query->execute($data);

        if ($check_exec)
            return $query;
        else {
            $error = $query->errorInfo();
            $this->error = $error[2];
            return false;
        }
    }

    public function insert($table) {
        $cols = [];
        $placeHolders = "";
        $values = [];
        $data = func_get_args();

        if (!isset($data[1]) || !is_array($data[1])) return;

        for ($i = 1, $j = null; $i < count($data); ++$i)
            foreach ($data[$i] as $col => $val) {
                if ($i === 1)
                    $cols[] = "`$col`";
                if ($j != $i)
                    $placeHolders .= "(?";
                else $placeHolders .= ", ?";
                $values[] = $val;

                $j = $i;
            }

        $cols = implode(", ", $cols);

        $stmt = "INSERT INTO `$table`($cols) VALUES $placeHolders);";

        $insert = $this->query($stmt, $values);

        if (
            $insert
            && method_exists($this->pdo, "lastInsertId")
            && $this->pdo->lastInsertId()
        )
            $this->last_id = $this->pdo->lastInsertId();
    }

    public function update($table, $where, $value, $values)
    {
        if (empty($table) || empty($where) || empty($value))
            return;

        $stmt = "UPDATE `$table` SET";
        $set = [];
        $where = "WHERE `$where` = ? ";

        if (!is_array($values))
            return;

        $valuesKeys = array_keys($values);
        foreach ($valuesKeys as $column)
            $set[] = " `$column` = ?";

        $set = implode(", ", $set);

        $stmt .= "$set $where;";

        $values[] = $value;

        $values = array_values($values);

        $update = $this->query($stmt, $values);

        if ($update) return $update;

        return;
    }

    public function delete($table, $where, $value)
    {
        if (empty($table) || empty($where) || empty($value))
            return;

        $stmt = " DELETE FROM `$table` ";
        $where = " WHERE `$where` = ? ";
        $stmt .= $where;
        $values = [$value];

        $delete = $this->query($stmt, $values);

        if ($delete)
            return $delete;
        return;
    }
}
