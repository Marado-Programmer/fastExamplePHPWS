<?php

namespace ExampleLeo;

class WS
{
    private $data = [];
    private $message;

    private $dbh = DB::getInstance();

    private $method = "GET";

    public function __construct()
    {
        $this->dbh = 

        $this->method = isset($_SERVER["REQUEST_METHOD"]) ? $_SERVER["REQUEST_METHOD"] : $this->method;

        $this->data = $this->loadMethod();
    }

    private function loadMethod()
    {
        $id = isset($_GET["id"]) ? intval($_GET["id"]) : null;
        $table = isset($_GET["table"]) ? $_GET["table"] : "";

        if (!$table) throw new \Error("Needs search param table");

        $table = strtolower($table);
        $table[0] = strtoupper($table[0]);

        if (!class_exists(__NAMESPACE__ . "\\$table")) throw new \Error("Unkown resource");

        $classTable = get_class_vars(__NAMESPACE__ . "\\$table")["table"];

        if (!isset($classTable)) throw new \Error("Unknown resource");

        try {
            return $this->{$this->method}($classTable, $id);
        } catch (\Error) {
            throw new \Error(405);
        } 
    }

    private function get(string $table, ?int $id)
    {
        if (!empty($_GET))
            $this->data = $_GET;

        $query = "SELECT * FROM `$table`" . ($id ? " WHERE `id` = ?" : "");

        return ($this->dbh->query($query, array_filter([$id], function ($i) { return isset($i); })))->fetchAll(\PDO::FETCH_OBJ);
    }

    private function post(string $table)
    {
        if (!empty($_POST))
            $this->data = $_POST;

        $this->dbh->insert($table, $this->data);

        return [
            "status" => 1,
            "statusText" => "$table inserted successfully"
        ];
    }

    private function put(string $table, ?int $id)
    {
        parse_str(file_get_contents("php://input"), $this->data);

        $this->dbh->update($table, "id", $id, $this->data);

        return [
            "status" => 1,
            "statusText" => "$table updated successfully"
        ];
    }

    private function delete(string $table, ?int $id)
    {
        $select = $this->prepareRes($this->get($table, $id));
    }
}

