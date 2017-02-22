<?php

    /**
    * @link https://github.com/naga3/qb
	* @author Osamu Nagayama
	* @forked by Paweł Klockiewicz
    * @license MIT
    */

    namespace Inc\Engine\Lib;

    class QueryBuilder
    {

    	protected static $db = null;

    	protected static $last_sql = '';

    	protected static $options = [];

    	protected $table = null;

    	protected $columns = [];

    	protected $joins = [];

    	protected $conditions = [];

    	protected $condition_binds = [];

    	protected $sets = [];

    	protected $set_binds = [];

    	protected $orders = [];

    	protected $limit = '';

    	protected $offset = '';

    	/**
    	* constructor
    	*
    	* @param string $table
    	*/
    	public function __construct($table = null)
    	{
    		if($table)
    			$this->table = $table;
    	}

    	/**
    	* PDO instance
    	*
    	* @return PDO
    	*/
    	public static function pdo()
    	{
    		return self::$db;
    	}

    	/**
    	* last SQL query
    	*
    	* @return string SQL
    	*/
    	public static function lastSql()
    	{
    		return self::$last_sql;
    	}

    	/**
    	* creates connection with database
    	*
    	* Qb::connect($dsn); // default user, password and options
    	* Qb::connect($dsn, $user); // default password and options
    	* Qb::connect($dsn, $user, $pass); // default options
    	* Qb::connect($dsn, $user, $pass, $options);
    	* Qb::connect($dsn, $options);
    	* Qb::connect($dsn, $user, $options);
    	*
    	* @param string $dsn
    	* @param string $user
    	* @param string $pass
    	* @param array $options
    	*   primary_key:  primary column name, default: 'id'
    	*   error_mode:   default: \PDO::ERRMODE_EXCEPTION
    	*   json_options: default: JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    	*/
    	public static function connect($dsn, $user = '', $pass = '', $options = [])
    	{
    		if(is_array($user))
    		{
    			$options = $user;
    			$user = '';
    			$pass = '';
    		}
    		else if(is_array($pass))
			{
				$options = $pass;
				$pass = '';
			}
			self::$options = array_merge([
                'primary_key'   => 'id',
                'error_mode'    => \PDO::ERRMODE_EXCEPTION,
                'json_options'  => JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT,
                ], $options);
    		self::$db = new \PDO($dsn, $user, $pass);
    		self::$db->setAttribute(\PDO::ATTR_ERRMODE, self::$options['error_mode']);
			if(strpos($dsn, 'sqlite') !== false)
            	self::$db->exec("pragma synchronous = off;");
    	}

    	/**
    	* close connection with database
    	*/
    	public static function close()
    	{
    		self::$db = null;
    	}

    	/**
    	* get or set options
    	*
    	* @param string $name
    	* @param mixed $value
    	*/
    	public static function config($name, $value = null)
    	{
    		if($value === null)
    		{
    			return self::$options[$name];
    		}
    		else
    		{
    			self::$options[$name] = $value;
    		}
    	}

    	/**
    	* SELECT
    	*
    	* select('column1')->select('column2') // SELECT column1, column2
    	* select(['column1', 'column2', ...]) // SELECT column1, column2, ...
    	* select(['alias1' => 'column1', 'column2', ...]) // SELECT column1 AS alias1, column2, ...
    	*
    	* @param string|array $columns
    	*
    	* @return object
    	*/
    	public function select($columns)
    	{
    		if(!is_array($columns))
    			$columns = array($columns);
    		foreach($columns as $alias => $column)
    		{
    			if(!is_numeric($alias))
    				$column .= " AS $alias";
    			array_push($this->columns, $column);
    		}
    		return $this;
    	}

    	/**
    	* INNER JOIN
    	*
    	* @param string $table
    	* @param string $condition
    	*
    	* @return object
    	*/
    	public function join($table, $condition)
    	{
    		array_push($this->joins, "INNER JOIN $table ON $condition");
    		return $this;
    	}

    	/**
    	* LEFT OUTER JOIN
    	*
    	* @param string $table
    	* @param string $condition
    	*
    	* @return object
    	*/
    	public function leftJoin($table, $condition)
    	{
    		array_push($this->joins, "LEFT JOIN $table ON $condition");
    		return $this;
    	}

    	/**
    	* WHERE
    	*
        * where(column, operator, value) // WHERE column (=, <, >, <=, >=, <>) value
    	* where(column, value) // WHERE column = value
    	* where(value) // WHERE id = value
    	*
    	* @param string $column
    	* @param mixed $value
    	*
    	* @return object
    	*/
    	public function where($column, $operator = null, $value = null, $ao = 'AND')
    	{
    		if($operator === null)
    		{
    			$value = $column;
    			$column = self::$options['primary_key'];
                $operator = '=';
    		}
            else if($value === null)
            {
                $value = $operator;
                $operator = '=';
            }

    		if(is_array($value))
    		{
    			$qs = '(' . implode(',', array_fill(0, count($value), '?')) . ')';
                if(empty($this->conditions))
    			    array_push($this->conditions, "$column $operator $qs");
                else
                    array_push($this->conditions, "$ao $column $operator $qs");
    			foreach($value as $v)
    			{
    				array_push($this->condition_binds, $v);
    			}
    		}
    		else
    		{
    		    if(empty($this->conditions))
    			    array_push($this->conditions, "$column $operator ?");
                else
                    array_push($this->conditions, "$ao $column $operator ?");
    			array_push($this->condition_binds, $value);
    		}
    		return $this;
    	}

        public function orWhere($column, $operator = null, $value = null)
        {
            return $this->where($column, $operator, $value, 'OR');
        }

    	/**
    	* WHERE LIKE
    	*
    	* @param string $column
    	* @param mixed $value
    	*
    	* @return object
    	*/
    	public function like($column, $value)
    	{
    		$this->where($column, 'LIKE', $value);
    		return $this;
    	}

    	/**
    	* WHERE OR LIKE
    	*
    	* @param string $column
    	* @param mixed $value
    	*
    	* @return object
    	*/
    	public function orLike($column, $value)
    	{
    		$this->where($column, 'LIKE', $value, 'OR');
    		return $this;
    	}

    	/**
    	* WHERE NOT LIKE
    	*
    	* @param string $column
    	* @param mixed $value
    	*
    	* @return object
    	*/
    	public function notLike($column, $value)
    	{
    		$this->where($column, 'NOT LIKE', $value);
    		return $this;
    	}

    	/**
    	* WHERE OR NOT LIKE
    	*
    	* @param string $column
    	* @param mixed $value
    	*
    	* @return object
    	*/
    	public function orNotLike($column, $value)
    	{
    		$this->where($column, 'NOT LIKE', $value, 'OR');
    		return $this;
    	}

    	/**
    	* WHERE IN
    	*
    	* @param string $column
    	* @param array $values
    	*
    	* @return object
    	*/
    	public function in($column, $values)
    	{
    		$this->where($column, 'IN', $values);
    		return $this;
    	}

    	/**
    	* WHERE OR IN
    	*
    	* @param string $column
    	* @param array $values
    	*
    	* @return object
    	*/
    	public function orIn($column, $values)
    	{
    		$this->where($column, 'IN', $values, 'OR');
    		return $this;
    	}

    	/**
    	* WHERE NOT IN
    	*
    	* @param string $column
    	* @param array $values
    	*
    	* @return object
    	*/
    	public function notIn($column, $values)
    	{
    		$this->where($column, 'NOT IN', $values);
    		return $this;
    	}

    	/**
    	* WHERE OR NOT IN
    	*
    	* @param string $column
    	* @param array $values
    	*
    	* @return object
    	*/
    	public function orNotIn($column, $values)
    	{
    		$this->where($column, 'NOT IN', $values, 'OR');
    		return $this;
    	}

    	/**
    	* get or set column value
    	*
    	* @param string $column
    	* @param mixed $value
    	*
    	* @return object
    	*/
    	public function set($column, $value = null)
    	{
    		if(is_array($column))
    		{
    			$sets = $column;
    		}
    		else
    		{
    			$sets = [$column => $value];
    		}
    		$this->sets += $sets;
    		return $this;
    	}

    	/**
    	* UPDATE or INSERT
    	*
    	* @param string $column
    	* @param mixed $value
    	*
    	* @return integer / boolean
    	*/
    	public function save($column = null, $value = null)
    	{
    		if($column)
    			$this->set($column, $value);
    		$st = $this->_build();
    		if($lid = self::$db->lastInsertId())
    			return $lid;
    		else
    			return $st;
    	}

    	/**
    	* UPDATE
    	*
    	* @param string $column
    	* @param mixed $value
    	*
    	* @return boolean
    	*/
    	public function update($column = null, $value = null)
    	{
    		if($column)
    			$this->set($column, $value);
    		return $this->_build(['only_update' => true]);
    	}

    	/**
    	* ORDER BY ASC
    	*
    	* @param string $column
    	*
    	* @return object
    	*/
    	public function asc($column)
    	{
    		array_push($this->orders, "$column ASC");
    		return $this;
    	}

    	/**
    	* ORDER BY DESC
    	*
    	* @param string $column
    	*
    	* @return object
    	*/
    	public function desc($column)
    	{
    		array_push($this->orders, "$column DESC");
    		return $this;
    	}

    	/**
    	* LIMIT
    	*
    	* @param integer $num
    	*
    	* @return object
    	*/
    	public function limit($num)
    	{
    		$this->limit = " LIMIT $num";
    		return $this;
    	}

    	/**
    	* OFFSET
    	*
    	* @param integer $num
    	*
    	* @return object
    	*/
    	public function offset($num)
    	{
    		$this->offset = " OFFSET $num";
    		return $this;
    	}

    	/**
    	* create array with all rows
    	*
    	* @return array
    	*/
    	public function toArray()
    	{
    		$st = $this->_build();
    		return $st->fetchAll(\PDO::FETCH_ASSOC);
    	}

    	/**
    	* create object with all rows
    	*
    	* @return object
    	*/
    	public function toObject()
    	{
    		$st = $this->_build();
    		return $st->fetchAll(\PDO::FETCH_OBJ);
    	}

    	/**
    	* create JSON array with all rows
    	*
    	* @return string
    	*/
    	public function toJson()
    	{
    		$rows = $this->toArray();
    		return json_encode($rows, self::$options['json_options']);
    	}

    	/**
    	* create array with one row
    	*
    	* @param string $column
    	* @param mixed $value
    	*
    	* @return array
    	*/
    	public function oneArray($column = null, $value = null)
    	{
    		if($column !== null)
    		{
    			$this->where($column, $value);
    		}
    		$st = $this->_build();
    		return $st->fetch(\PDO::FETCH_ASSOC);
    	}

    	/**
    	* create object with one row
    	*
    	* @param string $column
    	* @param mixed $value
    	*
    	* @return object
    	*/
    	public function oneObject($column = null, $value = null)
    	{
    		if($column !== null)
    		{
    			$this->where($column, $value);
    		}
    		$st = $this->_build();
    		return $st->fetch(\PDO::FETCH_OBJ);
    	}

    	/**
    	* create JSON array with one row
    	*
    	* @param string $column
    	* @param mixed $value
    	*
    	* @return string
    	*/
    	public function oneJson($column = null, $value = null)
    	{
    		if($column !== null)
    		{
    			$this->where($column, $value);
    		}
    		$row = $this->oneArray();
    		return json_encode($row, self::$options['json_options']);
    	}

    	/**
    	* returns rows count
    	*
    	* @return integer
    	*/
    	public function count()
    	{
    		$st = $this->_build(array('count' => true));
    		return $st->fetchColumn();
    	}

        public function lastInsertId()
        {
            return self::$db->lastInsertId();
        }

    	/**
    	* DELETE
    	*
    	* @param string $column
    	* @param mixed $value
    	*/
    	public function delete($column = null, $value = null)
    	{
    		if($column !== null)
    		{
    			$this->where($column, $value);
    		}
    		$st = $this->_build(array('delete' => true));
    		return $st->rowCount();
    	}

    	/**
    	* build SQL query
    	*
    	* @param array $params
    	*
    	* @return PDOStatement
    	*/
    	protected function _build($params = [])
    	{
    		$sql = '';
    		$sql_where = '';

    		// build conditions
    		$conditions = implode(' ', $this->conditions);
    		if($conditions)
    		{
    			$sql_where .= " WHERE $conditions";
    		}

			// if some columns have set value then UPDATE or INSERT
    		if($this->sets)
    		{
    			$insert = true;
				// if there are some conditions then UPDATE
    			if(!empty($this->conditions))
    			{
    				$insert = false;
    				$columns = implode('=?,', array_keys($this->sets)) . '=?';
    				$this->set_binds = array_values($this->sets);
    				$sql = "UPDATE $this->table SET $columns";
    				$sql .= $sql_where;
    				$st = $this->_query($sql);
    				if($st->rowCount() === 0 && empty ($params['only_update']))
    					$insert = true;
    			}
				// if there aren't conditions, then INSERT
    			if($insert)
    			{
    				$columns = implode(',', array_keys($this->sets));
    				$this->set_binds = array_values($this->sets);
    				$qs = implode(',', array_fill(0, count($this->sets), '?'));
    				$sql = "INSERT INTO $this->table($columns) VALUES($qs)";
    				$this->condition_binds = array();
    				$st = $this->_query($sql);
    			}
    		}
    		else
    		{
    			if(!empty ($params['delete']))
    			{
    			    // DELETE
    				$sql = "DELETE FROM $this->table";
    				$sql .= $sql_where;
    				$st = $this->_query($sql);
    			}
    			else
    			{
    			    // SELECT
    				$columns = implode(',', $this->columns);
    				if(!$columns)
    					$columns = '*';
    				if(!empty ($params['count']))
    					$columns = "COUNT($columns) AS count";
    				$sql = "SELECT $columns FROM $this->table";
    				$joins = implode(' ', $this->joins);
    				if($joins)
    				{
    					$sql .= " $joins";
    				}
    				$order = '';
    				if(count($this->orders) > 0)
    					$order = ' ORDER BY ' . implode(',', $this->orders);
    				$sql .= $sql_where . $order . $this->limit . $this->offset;
    				$st = $this->_query($sql);
    			}
    		}
    		return $st;
    	}

    	/**
    	* execute SQL query
    	*
    	* @param string $sql
    	*
    	* @return PDOStatement
    	*/
    	protected function _query($sql)
    	{
    		$binds = array_merge($this->set_binds, $this->condition_binds);
    		$st = self::$db->prepare($sql);
    		$st->execute($binds);
    		self::$last_sql = $sql;
    		return $st;
    	}

    }