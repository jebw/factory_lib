<?php

/**
 * This project is licensed under GPLv3.0
 */

class Factory {
	
	protected static $counter = array() ;
	public $data = null ;
	public static $factory_data = array() ;
	private $table_name = null ;
	
	public function __construct($table_name, $overrides = array()) {
		$this->table_name = $table_name ;
		
		if (!isset(self::$counter[$this->table_name]))
			self::$counter[$this->table_name] = 1 ;
		else
			self::$counter[$this->table_name]++ ;
		
		$this->data = array_merge(self::$factory_data[$this->table_name], $overrides) ;
		
		foreach($this->data as $key => $value)
			if (is_string($value))
				$this->data[$key] = str_replace('{{counter}}', self::$counter[$this->table_name], $value) ;
		
		return $this ;
	}
	
	public function store() {
		$rowdata = $this->array_to_sql($this->data) ;

		$sql = "INSERT INTO `{$this->table_name}` SET {$rowdata}" ;

		if (mysql_query($sql)) return mysql_insert_id() ;

		if ($GLOBALS['show_db_errors'])
			die("UNABLE TO INSERT DATA IN store()\n".mysql_error()."\n$sql") ;
		else
			return false ;
	}
	
	public function set($varname, $value) {
		$this->date[$varname] = $value ;
		return $this ;
	}
	
	public function get($varname) {
		return array_key_exists($varname, $this->data) ? $this->data[$varname] : null ;
	}
	
	private function escape_column($coldata) {
		if (is_null($coldata))
			return 'NULL' ;
		elseif ($coldata === true)
			return 'true' ;
		elseif ($coldata === false)
			return 'false' ;
		elseif ($coldata == 'NOW()' || $coldata == 'UNIX_TIMESTAMP()')
			return $coldata ;
		else
			return "'" . mysql_real_escape_string($coldata) . "'" ;
	}
	
	private function join_col_data($col, $data) {
		return '`' . $col . '` = ' . $this->escape_column($data) ;
	}

	private function array_to_sql($rowdata, $where_clause = false) {
		if ($this->is_assoc_array($rowdata)) {
			$mapped = array() ;
			foreach($rowdata as $key => $value)
				$mapped[] = $this->join_col_data($key, $value) ;
			return implode(', ', $mapped) ;
		} else
			return implode(', ', $rowdata) ;
	}
	
	private function is_assoc_array($arr) {
		return !(array_keys($arr) === range(0, count($arr) - 1, 1)) ;
	}
	
	public static function reset_counter($counter_name = null) {
		if (is_null($counter_name))
			self::$counter = array() ;
		else
			unset(self::$counter[$counter_name]) ;
	}
	
	public static function create($tablename, $overrides = array()) {
		$p = new Factory($tablename, $overrides) ;
		if ($p->store())
			return $p ;
		else
			return false ;
	}
	
	public static function hash($tablename, $overrides = array()) {
		$p = new Factory($tablename, $overrides) ;
		return $p->data ;
	}
	
	public static function truncate($tablename) {
		if (mysql_query("TRUNCATE `$tablename`")) 
			return true ;
		
		if ($GLOBALS['show_db_errors'])
			die("UNABLE TO INSERT DATA IN store()\n".mysql_error()."\n$sql") ;
		else
			return false ;
	}
	
}

?>
