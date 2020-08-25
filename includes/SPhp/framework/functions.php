<?php

namespace SPhp\Framework;

class Functions {
	public function static( $path ){
		return __URL.'/static/'.preg_replace('/^(\\\|\/)/', '', $path);
	}
	public function ago($date2, $date1=null){
		if(!$date1) $date1 = Date('Y-m-d H:i:s');
		$date_diff = date_diff(date_create($date2), date_create($date1));
		$str = "";

		if($date_diff->y > 0) $str = $date_diff->y." Years";
		else if($date_diff->m > 0) $str = $date_diff->m." Months";
		else if($date_diff->d > 0) $str = $date_diff->d." Days";
		else if($date_diff->h > 0) $str = $date_diff->h." Hours";
		else if($date_diff->i > 0) $str = $date_diff->i." Mins";
		else if($date_diff->s > 0) $str = $date_diff->s." Secs";

		return $str." ago";
	}
	public function sroute( $path ){
		return __URL.preg_replace('/(\?.*|\/$)/', '', __SEEK).'/'.preg_replace('/^(\\\|\/)/', '', $path);
	}
	public function croute( $path ){
		return self::sroute('!/'.preg_replace('/^(\\\|\/)/', '', $path));
	}
	public function route( $path ){
		return __URL.'/'.preg_replace('/^(\\\|\/)/', '', $path);
	}
	public function url( $var ){
		return urlencode( htmlspecialchars_decode($var) );
	}
	public function sessnoton($name, $url_or_callable) {
		if(empty($_SESSION[$name])) {
			if( is_callable($url_or_callable) ){
				return $url_or_callable();
			} else {
				header('Location: '.$url_or_callable);
				die();
			}
		}
	}
	public function sesson($name, $url_or_callable) {
		if(!empty($_SESSION[$name])) {
			if( is_callable($url_or_callable) ){
				return $url_or_callable();
			} else {
				header('Location: '.$url_or_callable);
				die();
			}
		}
	}
	public function uploads( $path ){
		return __URL.'/uploads/'.preg_replace('/^(\\\|\/)/', '', $path);
	}

	public function sess( $name ){
		if( !isset($_SESSION[$name]) ){
			return '';
		}

		return $_SESSION[$name];
	}

	public function post( $name ){
		if( !isset($_POST[$name]) ){
			return '';
		}

		return $_POST[$name];
	}

	public function get( $name ){
		if( !isset($_GET[$name]) ){
			return '';
		}
		
		return $_GET[$name];
	}

	public function group_param( $param1 ){
		$grouped1 = array();

		foreach ($_GET as $key => $value) {
			if( strpos($key, $param1.'_')===0 ){
				$grouped1[] = $value;
			}
		}

		return $grouped1;
	}

	public function group_post_param( $param1 ){
		$grouped1 = array();

		foreach ($_POST as $key => $value) {
			if( strpos($key, $param1.'_')===0 ){
				$grouped1[] = $value;
			}
		}

		return $grouped1;
	}

	public function message( $msg, $count, $type=1, $key=null ){

		if( !isset($_SESSION['MESSAGES']) ){
			$_SESSION['MESSAGES'] = array();
		}
		if( !isset($_SESSION['ERRORS']) ){
			$_SESSION['ERRORS'] = array();
		}
		if( !isset($_SESSION['PARAMS']) ){
			$_SESSION['PARAMS'] = array();
		}

		if( $type===2 ) {
			$_SESSION['PARAMS'][$key] = array( 'text' => $msg, 'count' => $count );
		} elseif( $type===1 ) {
			$_SESSION['MESSAGES'][$key] = array( 'text' => $msg, 'count' => $count );
		} elseif ( $type===0 ) {
			$_SESSION['ERRORS'][$key] = array( 'text' => $msg, 'count' => $count );
		} else {
			Error::die([[
				1,
				'Invalid message type',
				'SPHP_MESSAGE_TYPE'
			]]);
		}
	}

	public function success( $msg, $count=1 ){
		Functions::message( $msg, $count );
	}

	public function error( $msg, $count=1 ){
		Functions::message( $msg, $count, 0 );
	}

	public function getmessage( $type=1, $last=true ){

		if( !isset($_SESSION['MESSAGES']) ){
			$_SESSION['MESSAGES'] = array();
		}
		if( !isset($_SESSION['ERRORS']) ){
			$_SESSION['ERRORS'] = array();
		}
		if( !isset($_SESSION['PARAMS']) ){
			$_SESSION['PARAMS'] = array();
		}

		if( $type===2 ) {
			if( $last ){
				return end( $_SESSION['PARAMS'] )['text'];
			}

			return $_SESSION['PARAMS'];
		} elseif( $type===1 ) {
			if( $last ){
				return end( $_SESSION['MESSAGES'] )['text'];
			}

			return $_SESSION['MESSAGES'];
		} elseif ( $type===0 ) {
			if( $last ){
				return end( $_SESSION['ERRORS'] )['text'];
			}

			return $_SESSION['ERRORS'];
		} else {
			Error::die([[
				1,
				'Invalid message type',
				'SPHP_MESSAGE_TYPE'
			]]);
		}
	}

	public function get_success(){
		return Functions::getmessage();
	}

	public function get_error(){
		return Functions::getmessage(0);
	}

	public function return_param( $param, $value ){
		Functions::message( $value, 1, 2, $param );
	}

	public function get_param( $param ){
		$haystack = Functions::getmessage(2, false);
		if( isset($haystack[$param]) ){
			return $haystack[$param]['text'];
		}

		return '';
	}
}