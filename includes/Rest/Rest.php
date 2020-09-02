<?php

namespace SPhp\Rest;

/**
 * A class to automatically convert requests to fmethods follwing the standards of the rest implementation
 */
class Endpoint {

	public $data = [];
	protected $responseFormat = [];

    public function __construct() {

    	$this->responseFormat = array_merge( [

    	], $this->responseFormat ); 
        
    }

    public static function process() {
    	$GLOBALS['a'] = null;
    	$GLOBALS['m'] = strtolower($_SERVER['REQUEST_METHOD']);

    	global $a;

    	$a = get_called_class();
    	$a = new $a;

    	Framework\Hooks::inject('ON_ERROR', function( &$Error ){
    		global $Response;

            if( conf('MODE')!='debug' )
                $Error = 0;

    		$Response->__(strip_tags(
    			json_encode([
	    			'success' => false,
	    			'response' => 'server error',
	    			'errors' => $Error->errors,
	    			'time' => time(),
	    		])
    		));

    		$Error = 0;
    		http_response_code(500);
    		header('Content-Type: application/json');
    	});

    	Framework\Page::children(array(

    		'{id}' => function( $_args ){
    			global $m, $a, $Response;
    			
    			if( $m=='get' ){

					$a->data = json_decode( json_encode($_GET) );
					$b = $a->singleGet( $_args['id'] );

				} else if( $m=='post' ){

					$a->data = json_decode( json_encode($_POST) );
					$b = $a->singlePost( $_args['id'] );

				} else if( $m=='delete' ){

					$b = $a->singleDelete( $_args['id'] );

				}

				if( gettype($b) == 'array' )
					$b = json_encode( $b );
				$Response->__( $b );

    		},

    		'' => function( $_args ){
    			global $m, $a, $Response;

    			if( !empty($Response->url_data) ){
    				
    				$a->__404();

    			} else {

    				if( $m=='get' ){

    					$a->data = json_decode( json_encode($_GET) );
    					$b = $a->get();

    				} else if( $m=='post' ){

    					$a->data = json_decode( json_encode($_POST) );
    					$b = $a->post();

    				}

					if( gettype($b) == 'array' )
						$b = json_encode( $b );
					$Response->__( $b );

    			}
    		}

    	));

    	header('Content-Type: application/json');
    }

    protected function success( $data ){
    	$this->responseForm['success'] = true;
    	$this->responseForm['response'] = $data;

    	$this->responseForm['time'] = time();
    	$this->responseForm['response_time'] = time()-$_SERVER['REQUEST_TIME'];

    	return $this->responseForm;
    }

    protected function error( $data ){
    	$this->responseForm['success'] = false;
    	$this->responseForm['response'] = $data;

    	$this->responseForm['time'] = time();
    	$this->responseForm['response_time'] = time()-$_SERVER['REQUEST_TIME'];

    	return $this->responseForm;
    }
}
