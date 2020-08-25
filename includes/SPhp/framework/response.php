<?php

namespace SPhp\Framework;

class Response {
	protected $responses = [];
	public $url_data = [];
	public $page_data = [];

	public function __constructor(){
		$this->responses = [];
	}

	public function url_data( $data ){
		$this->url_data[] = $data;
	}

	public function __( $body ){
		$this->responses[] = $body;
	}

	public function put( $data ){
		$this->page_data[] = $data;
	}

	public function error( $msg ){
		$this->page_data['errors'][] = $data;
	}

	public function data(){
		return $this->page_data;
	}

	public function show(){
		$r = implode('', $this->responses);
		Hooks::run( 'ON_RESPONSE', $r );

		echo $r;
	}
}