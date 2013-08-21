<?php
//TODO: Many to many relationship

class Model
{
	protected static $tableName = null; //auto
	protected static $connName = null; //default

	public $orm;

	public static function make($className = null, $connName = null)
	{
		if (is_null($className))
		{
			$className = get_called_class();
		}

		return new $className($connName);
	}

	function __construct($connName = null)
	{
		if (!is_null(static::$tableName))
		{
			$tableName = static::$tableName;
		}
		else
		{
			$tableName = Str::lower(preg_replace(array('/\\\\/', '/(?<=[a-z])([A-Z])/', '/__/'), array('_', '_$1', '_'), ltrim(get_class($this), '\\')));
		}

		if (is_null($connName) && !is_null(static::$connName))
		{
			$connName = static::$connName;
		}

		$this->orm = ORM::make($tableName, $connName);
		$this->orm->className = get_class($this);
	}

	public function findFirst($id = null)
	{
		return $this->orm->findFirst($id);
	}

	public function find()
	{
		return $this->orm->find();
	}

	public function findRS()
	{
		return $this->orm->findRS();
	}

	private function makeHasMany($targetClassName, $foreignKey = null)
	{
		if (is_null($foreignKey))
		{
			$foreignKey = Str::camel($this->orm->tableName) . "ID";
		}

		$model = Model::make($targetClassName);
		$model->orm->whereEqual($foreignKey, $this->id);

		return $model;
	}

	public function hasOne($targetClassName, $foreignKey = null)
	{
		return $this->makeHasMany($targetClassName, $foreignKey);
	}

	public function hasMany($targetClassName, $foreignKey = null)
	{
		return $this->makeHasMany($targetClassName, $foreignKey);
	}

	public function belongsTo($targetClassName, $foreignKey = null)
	{
		if (is_null($foreignKey))
		{
			$foreignKey = Str::camel($this->orm->tableName) . "ID";
		}

		$model = Model::make($targetClassName);
		$model->orm->whereEqual("id", $this->$foreignKey);

		return $model;
	}

	public function hasManyThrough($targetClassName, $joinTableName = null, $baseKey = null, $targetKey = null)
	{
		$model = Model::make($targetClassName);

		if (is_null($joinTableName))
		{
			$tables = array($this->orm->tableName, $model->orm->tableName);
			sort($tables, SORT_STRING);
			$joinTableName = join("_", $tables);
		}

		if (is_null($baseKey))
		{
			$baseKey = Str::camel($this->orm->tableName) . "ID";
		}

		if (is_null($targetKey))
		{
			$targetKey = Str::camel($model->orm->tableName) . "ID";
		}

		return $model
					->select($model->orm->tableName . ".*")
					->join($joinTableName, array($model->orm->tableName . ".id", "=", $joinTableName . "." . $targetKey))
					->whereEqual($joinTableName . "." . $baseKey, $this->id);
	}

	public function __get($key)
	{
		return $this->orm->$key;
	}

	public function __set($key, $value)
	{
		$this->orm->$key = $value;
	}

	public function __isset($key)
	{
		return isset($this->orm->$key);
	}

	public function __call($method, $params = array())
	{
		return call_user_func_array(array($this->orm, $method), $params);
	}
}
?>