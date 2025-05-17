<?php

namespace Elminson\DQL;

use PDO;
use PDOStatement;
use ReturnTypeWillChange;

class PDOStatementWrapper extends PDOStatement
{
    protected array $boundParams = [];

    protected array $paramTypes = [];

    protected function __construct() {}

    #[ReturnTypeWillChange]
    public function bindParam($param, &$value, $type = PDO::PARAM_STR, $length = null, $options = null)
    {
        $this->boundParams[$param] = $value;
        $this->paramTypes[$param] = $type;

        return parent::bindParam($param, $value, $type);
    }

    #[ReturnTypeWillChange]
    public function bindValue($param, $value, $type = PDO::PARAM_STR)
    {
        $this->boundParams[$param] = $value;
        $this->paramTypes[$param] = $type;

        return parent::bindValue($param, $value, $type);
    }

    public function getBoundParams(): array
    {
        return $this->boundParams;
    }

    public function getParamTypes(): array
    {
        return $this->paramTypes;
    }

    public function getQueryString(): string
    {
        return $this->queryString;
    }
}
