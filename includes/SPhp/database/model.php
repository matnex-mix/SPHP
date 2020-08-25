<?php

/*
 * Main Model Abstract Class
 * Makes adding Models flexible
 *
 */

use SPhp\Framework\Error;

class Model {
	
	public $id;
	protected $table_name;

	protected $show_errors = true;

	static final function Load( $name ){

		$file = __BASE.'\\models\\'.$name.'.php';
		
		if( file_exists($file) ){

			include_once($file);
			if( !class_exists( $name ) ){
				Error::die([[
					1,
					"could not load model ($name), make sure the file-name matche the class-name",
					'MODEL_NOTFOUND'
				]]);
			}

		} else {
			Error::die([[
				1,
				"model file not found ($file)",
				'MODEL_NOTFOUND'
			]]);
		}
	}

	public function __construct( $array=[] ){

		foreach ($array as $key => $value) {
			$this->$key = $value;
		}

	}

	public function get(){

		$self = new static;
		return DB::table( $self->table_name )
			->model( get_called_class() );

	}

	public function column( $name, $value ){
		
		$self = new static;
		return DB::table( $self->table_name )
			->where( $name, $value )
			->model( get_called_class() )
			->show();

	}

	public function find( $id ){
		$r = self::column( 'id', $id );
		return end( $r );
	}

	/*
	 * Deprecated
	 *
	public function withColumns( $array ){
		
		$qry = DB::table( $this->table_name );

		foreach ($array as $key => $value) {
			$qry = $qry->where( $key, $value );
		}

		return $qry
			->model( get_class( $this ) )
			->show();

	}
	 */

	public function _delete( $array ){

		$qry = DB::delete( $this->table_name );

		foreach ($array as $key => $value) {
			$qry = $qry->where( $key, $value );
		}

		return $qry
			->run();

	}

	public function _getAll(){
		$r = get_object_vars($this);
		
		unset($r['table_name']);
		unset($r['show_errors']);

		return $r;
	}

	public function delete( $multiple=false ){

		if( $multiple ){
			$self = new static;
			return DB::delete( $self->table_name );
		} else if ( $this->id ) {
			return $this->_delete(array(
				'id' => $this->id,
			));
		}

	}

	public function save(){
		if( $this->id ){

			return DB::update( $this->table_name, $this->_getAll(), $this->show_errors )
				->where( 'id', $this->id )
				->run();

		} else {
			return DB::insert( $this->table_name, $this->_getAll(), $this->show_errors );
		}
	}

}