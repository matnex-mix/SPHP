<?php

namespace SPhp\Framework;

class Page {
	public function child( $route_pattern, $handler ){
		global $Response;
		$_ARGS = [];

		if( $route_pattern ){

			$route_pattern = trim($route_pattern);
			if( isset($route_pattern[0]) && $route_pattern[0]=='/' ){
				$route_pattern = substr($route_pattern, 1);
			}
			if( isset($route_pattern[strlen($route_pattern)-1]) && $route_pattern[strlen($route_pattern)-1]=='/' ){
				$route_pattern = substr($route_pattern, 0, strlen($route_pattern)-1);
			}

			$route_pattern = explode('/', $route_pattern);
			if( sizeof($route_pattern)!=sizeof($Response->url_data) ){
				return;
			}
			
			foreach ($Response->url_data as $key => $value) {
				$url_key = '';

				if( isset($route_pattern[$key]) && ( preg_match('/\{(\w+)\}/', $route_pattern[$key], $url_key) || $route_pattern[$key] == $value ) ){
					if( sizeof($url_key)>1 ){
						$_ARGS[ $url_key[1] ] = $value;
					}
				} else {
					return;
				}
			}

		}

		if( gettype($handler)=='string' ){
			if( file_exists($handler) ){
				try {
					include $handler;
					return true;
				} catch( \Throwable $e ){
					Error::die([[
						1,
						$e->getMessage()." in ($handler)",
						'PAGE_HANDLER_EXCEPTION'
					]]);
				}
			} else {
				Error::die([[
					1,
					'The page handler ('.$handler.') do not exist',
					'PAGE_HANDLER_NOTFOUND'
				]]);
			}
		} else if( gettype($handler)=='object' ) {
			$handler( $_ARGS );
			return true;
		} else {
			Error::die([[
				1,
				'Expected STRING or FUNCTION, '.gettype($handler).' given',
				'PAGE_HANDLER_TYPE'
			]]);
		}
	}

	public function children( $map ){

		foreach ($map as $key => $value) {
			if( Page::child( $key, $value ) ){
				return;
			}
		}

	}

	public function singleFormSession(){
		global $Response;
		$Response->__('
<script>
	_forms = document.forms;
	for( x=0;x<_forms.length;x++ ){
		if( _forms[x].method.toLowerCase()!="post" ){
			continue;
		}
		_token = document.createElement("INPUT");
		_token.type = "hidden";
		_token.name = "no_resubmit_token";
		_token.value = "'.md5(microtime()).'";
		_forms[x].appendChild(_token);
	}
</script>
		');

		if( !empty($_POST['no_resubmit_token']) ){
			if( isset($_SESSION['no_resubmit_token']) && $_SESSION['no_resubmit_token']==$_POST['no_resubmit_token'] ){
				$_POST = [];
				header('Location: .');
			} else{
				$_SESSION['no_resubmit_token'] = $_POST['no_resubmit_token'];
			}
		} else{
			// unset($_SESSION['no_resubmit_token']);
		}
	}

	public function back(){

		$url = Framework::history();
		if( !empty($url[sizeof($url)-2]) ){
			$url = $url[sizeof($url)-2];
		} else {
			$url = '';
		}
		
		header( 'Location: '.$url );

	}
}