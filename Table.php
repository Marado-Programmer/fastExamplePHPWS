<?php


namespace ExampleLeo;

abstract class Table
{
    public static string $table;

    final public function __construct(public array $data) {$this->setTable();}

    protected abstract function setTable();
}

