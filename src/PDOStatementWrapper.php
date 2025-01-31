<?php

namespace Elminson\DQL;

use PDOStatement;
use ReturnTypeWillChange;

class PDOStatementWrapper extends PDOStatement
{
    protected $boundParams = [];

    protected function __construct() {} // Required for extending PDOStatement

    /**
     * @return void
     */
    #[ReturnTypeWillChange]
    public function bindValue($param, $value, $data_type = PDO::PARAM_STR)
    {
        $this->boundParams[$param] = $value;
        parent::bindValue($param, $value, $data_type);
    }

    public function getBoundParams()
    {
        return $this->boundParams;
    }
}
