<?php

namespace SPhp\Database;

class Data {
	protected $function;
	protected $arguments;

	public function __construct( $f, ...$args ){
		$this->function = $f;
		$this->arguments = $args;
	}

	public function run(){
		return call_user_func_array($this->function, $this->arguments);
	}

	public function mix($mixture, $min, $max=100){
		return new Data(function($mixture, $min, $max){
			$haystack = [
				'A' => str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ'),
				'a' => str_split('abcdefghijklmnopqrstuvwxyz'),
				'N' => str_split('0123456789'),
			];
			
			$mixture = str_split( $mixture );
			$lt_rand = [];

			foreach ($haystack as $key => $value) {
				if( in_array( $key, $mixture ) ){
					$lt_rand = array_merge( $lt_rand, $value );
				}
			}

			$min_max = rand($min, $max);
			$rnd_text = "";
			for($x=0;$x<$min_max;$x++){
				$rnd_text .= $lt_rand[array_rand($lt_rand)];
			}
			return $rnd_text;
		}, $mixture, $min, $max);
	}

	public function int($min, $max=100){
		return new Data(function($min, $max){
			return rand($min, $max);
		}, $min, $max);
	}

	public function float($min, $max=100){
		return new Data(function($min, $max){
			return rand($min, $max)*0.88;
		}, $min, $max);
	}

	public function rand( ...$array ){
		return new Data(function( $array ){
			$ind = array_rand( $array );
			return $array[$ind];
		}, $array);
	}

	public function runner( $f ){
		return new Data( $f );
	}
}