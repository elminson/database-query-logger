<?php
namespace Elminson\DQL;

use PDO;
use PDOStatement;
use ReturnTypeWillChange;

class PDOStatementWrapper extends PDOStatement {
	protected $boundParams = [];

	protected function __construct() {}

	#[ReturnTypeWillChange]
	public function bindParam($param, &$value, $type = PDO::PARAM_STR, $length = null, $options = null) {
		$this->boundParams[$param] = $value;
		return parent::bindParam($param, $value, $type);
	}

	#[ReturnTypeWillChange]
	public function bindValue($param, $value, $type = PDO::PARAM_STR) {
		$this->boundParams[$param] = $value;
		return parent::bindValue($param, $value, $type);
	}

	public function getBoundParams() {
		return $this->boundParams;
	}
}
