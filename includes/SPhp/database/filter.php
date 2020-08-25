<?php

namespace SPhp\Database;

use SPhp\Framework\Error;

class Filter {
	protected $target;
	protected $props;
	protected $process;

	protected $where = "";
	protected $order = "";
	protected $limit = "";
	protected $joins = [];
	protected $prepare_data = [];

	protected $rawColumns = false;
	protected $query = "SELECT ?c FROM ?t ?j ?w ?o ?l";
	protected $model;

	protected function constructor(){}

	function __construct( $table, $columns, $filter=null ) {
		if( is_array($columns) && !$this->rawColumns ){
			$columns = implode(', ', $columns);
		}

		$this->target = $table;
		$this->props = $columns;
		$this->process = $filter;

		$this->constructor();
	}

	protected function build() {
		$stmnt = $this->query;
		$stmnt = str_replace('?c', $this->props, $stmnt);
		$stmnt = str_replace('?t', $this->target, $stmnt);
		$stmnt = str_replace('?j', implode(" ", $this->joins), $stmnt);
		$stmnt = str_replace('?w', $this->where, $stmnt);
		$stmnt = str_replace('?o', $this->order, $stmnt);
		$stmnt = str_replace('?l', $this->limit, $stmnt);

		$stmnt = DB::prepare($stmnt, $this->prepare_data);

		return $stmnt;
	}

	public function view(){
		echo $this->build();
	}

	public function where( $prop, $compare ) {

		$where_clause = "";
		$prop = explode('+', $prop);

		if( $this->where ){
			if( $prop[0]=='' ) $where_clause .= " AND";
			else $where_clause .= " OR";
		} else {
			$where_clause .= "WHERE";
		}

		$opt = '=';
		$prop = end($prop);

		# ! (!=)
		# ~ NOT LIKE
		# * LIKE
		# > (>)
		# < (<)
		# / (>=)
		# \ (<=)

		if( strpos($prop, '!')===0 ){
			$prop = substr($prop, 1);
			$opt = '!=';
		}

		if( strpos($prop, '~')===0 ){
			$prop = substr($prop, 1);
			$opt = 'NOT LIKE';
		}

		if( strpos($prop, '*')===0 ){
			$prop = substr($prop, 1);
			$opt = 'LIKE';
		}

		if( strpos($prop, '>')===0 ){
			$prop = substr($prop, 1);
			$opt = '>';
		}

		if( strpos($prop, '<')===0 ){
			$prop = substr($prop, 1);
			$opt = '<';
		}

		if( strpos($prop, '/')===0 ){
			$prop = substr($prop, 1);
			$opt = '>=';
		}

		if( strpos($prop, '\\')===0 ){
			$prop = substr($prop, 1);
			$opt = '<=';
		}

		$where_clause .= " $prop $opt ?";
		$this->prepare_data[] = $compare;
		$this->where .= $where_clause;

		return $this;
	}

	public function join( $type, $db ){
		if( gettype($db)!='object' ){
			Error::die([[
				1,
				'Requires db_object ('.gettype($db).') given',
				'INVALID_PARAMETER'
			]]);
		}

		$this->joins[] = strtoupper("$type JOIN ") . "(".$db->build().") AS $db->target";

		return $this;
	}

	public function innerJoin($db){
		return $this->join('inner', $db);
	}

	public function on( $array ){
		if( empty($this->joins) )
			return $this;

		$sql = "";
		foreach ($array as $key => $val) {
			$k = preg_replace('/^\+/', '', $key);
			$sql .= (strpos($key, "+")===0?'AND':'OR'). " $k = $val";
		}

		$sql = preg_replace('/(?:AND|OR) /', '', $sql);

		$L = sizeof($this->joins)-1;
		$this->joins[$L] .= " ON ".$sql;

		return $this;
	}

	public function braces( $mode='||' ){
		if( empty($this->braceCont) )
			$this->braceCont = [];

		if( empty($mode=array( "||" => 'OR', "&&" => 'AND' )[$mode]) ){
			Error::die([[
				1,
				'Braces accept only (||) or (&&)',
				'INVALID_ARGUMENT'
			]]);
		}

		array_unshift($this->braceCont, array(
			'before' => $this->where,
			'mode' => $mode,
		));

		$this->where = "";
		return $this;
	}

	public function close(){
		if( empty($this->braceCont) ){
			Error::die([[
				1,
				'Could not find a matching braces method',
				'INVALID_SYNTAX'
			]]);
		}

		$this->where = preg_replace('/^(WHERE)/', '', $this->where);
		$this->where = $this->braceCont[0]['before']." ".$this->braceCont[0]['mode']." ($this->where)";
		$this->where = preg_replace('/\( AND \(/', '((', $this->where);

		array_shift($this->braceCont);
		return $this;
	}

	public function order( $prop, $by='ASC' ) {
		$order_clause = "";

		if( $this->order ){
			$order_clause .= " ,";
		} else {
			$order_clause .= "ORDER BY";
		}

		$order_clause .= " `$prop` $by";
		$this->order .= $order_clause;

		return $this;
	}

	public function model( $class ){
		if( !class_exists( $class ) ){
			Error::die([[
				1,
				'Model class ('.$class.') not found',
				'DB_INVALID_MODEL'
			]]);
		}

		$this->model = $class;
		return $this;
	}

	public function parse( $result ){
		if( isset( $result->num_rows ) && $result->num_rows >= 0 ){
			$result_array = [];

			while ( $row=$result->fetch_assoc() ) {
				if( $this->process && is_callable($this->process) ){
					if( $this->process( $row )===FALSE ){
						continue;
					}
				}

				if( $this->model ){
					$row = new $this->model( $row );
				}

				$result_array[] = $row;
			}

			return $result_array;
		} else {
			return $result;
		}
	}

	public function show( $offset=0, $count=1000000000, $error=TRUE ) {
		$this->limit = "LIMIT $offset, $count";

		global $__DBINSTANCE;
		$res = $__DBINSTANCE->query( $this->build() );

		if( $error && sizeof($__DBINSTANCE->error_list) ){
			$errors = [];
			foreach ($__DBINSTANCE->error_list as $value) {
				$errors[] = [
					1,
					$value['error'],
					'SQL_ERROR_'.$value['errno']
				];
			}
			Error::die($errors);
		}

		return $this->parse( $res );
	}
}

class updateFilter extends Filter {

	protected $query = "UPDATE `?t` SET ?c ?w";
	protected $rawColumns = true;
	public $error = false;

	protected function constructor(){
		$tmp = '';

		foreach ($this->props as $key => $value) {
			$tmp .= ", `$key` = ?";
			$this->prepare_data[] = $value;
		}

		$tmp = substr($tmp, 2);
		$this->props = $tmp;
	}

	public function run(){
		return $this->show( 0, 1, $this->error );
	}

}

class DeleteFilter extends Filter {

	protected $query = "DELETE FROM `?t` ?w";
	public $error = false;

	public function run(){
		return $this->show( 0, 1, $this->error );
	}

}
