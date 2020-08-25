<?php

namespace SPhp\Database;

class Seeder {
	protected $name;
	protected $data;

	public function __construct( $name, $data ){
		$this->name = $name;
		$this->data = $data;
	}

	public function repeat( $len ){
		for ($i=0; $i < $len; $i++) {
			DB::insert( $this->name, $this->new( $this->data ), TRUE );
			echo "[".Date('Y-m-d H:i:s')."]&nbsp;&nbsp;&nbsp;Seeded `$this->name` with ".($i+1)." entrie(s)<br/>";
		}

		return $this;
	}

	public function new( $data ){
		$new_data = [];
		foreach ($data as $key => $value) {
			if( method_exists($value, 'run') ){
				$new_data[$key] = $value->run();
			} else {
				$new_data[$key] = $value;
			}
		}

		return $new_data;
	}

	public function swap( $arr ){
		$this->data = array_merge( $this->data, $arr );
		return $this;
	}
}