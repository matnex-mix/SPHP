<?php

namespace SPhp\Framework;

class Framework {
	public function boot( $base ){
		if( defined('__BASE') ){
			die( 'Booted Already' ); # ERROR: 1
		}
		define('__BASE', dirname( $base ));

		if( !file_exists(__BASE.'/config.json') ){
			die( 'Cannot Find Configuration File' ); # ERROR: 2
		}

		$config = json_decode( file_get_contents(__BASE.'/config.json') );

		if( JSON_ERROR_NONE !== json_last_error() ){
			die( 'Configuration File not formatted properly' ); # ERROR: 3
		} else {
			if( $config->LOCALIZATION ){
				define( '__LOCALE', json_decode(json_encode($config->LOCALIZATION), true) );
			}

			define( '__HOME', Framework::guessHome() );
			define( '__URL', Framework::guessURL() );
			define( '__MODE', $config->MODE );
			define( '__RECOVERY', json_decode(json_encode($config->RECOVERY), true) );

			if( $config->LANGUAGE ){
				define( '__LANG', $config->LANGUAGE );
				Lang::load( $config->LANGUAGE );
			}
		}

		define( '__CONFIG', json_decode(json_encode($config), TRUE) );
		define( '__CONFIGURED', true );
		
		self::loadMiddleware();
		Hooks::run( 'ON_BOOT' );
	}

	/*
	 * Load Extensions
	 */

	public function loadMiddleware(){

		$a = conf("MIDDLEWARE") ?? [];

		foreach ($a as $b) {
			if( strpos($b, "-")===0  )
				continue;

			$b = realpath(__BASE.'/'.$b.'/autoload.php');
			$GLOBALS['__MODULE_PATH'] = dirname($b);

			if( !@include_once ( $b ) ){
				Error::die([[
					1,
					"Middleware ($b) could not be loaded",
					'EXTENSION_ERROR'
				]]);
			}
		}

	}

	public function manage( $path ){

		global $Response, $PAGE_DIRS;

		Hooks::run( 'ON_REQUEST', $path );

		if( defined('__CONFIGURED') ){
			$path = str_replace(__HOME, '', $path);
			$path = preg_replace( '/\/+/', '/', $path );
			//preg_match('/(?:(?:\/)!((?:\/[^\/]*)+)\??.*)$/', $path, $not_path);
			$path = preg_split('/\?/', $path, 2)[0];
			$not_path = preg_split('/!/', $path, 2);
			if( isset($not_path[0]) ){
				$path = $not_path[0];
			}
			if( isset($not_path[1]) ){
				$not_path = explode('/', $not_path[1]);
				foreach ($not_path as $value) {
					if( $value ) $Response->url_data( $value );
				}
			}
			
			define( '__SEEK', $path );

			if( !isset($_SESSION['HISTORY']) ){
				$_SESSION['HISTORY'] = [];
			}

			if( end($_SESSION['HISTORY']) != __URL.__SEEK ){
				$_SESSION['HISTORY'][] = __URL.__SEEK;
			}

			if( $path=='/' ){
				$target_file = __BASE.'/pages/index.php';
			} else{
				$level_path = explode('/', $path);
				array_shift($level_path);

				/*
				 * Begin Localization
				 */

				$locale = @$level_path[0];

				if( defined('__LOCALE') && $locale ){
					foreach ( __LOCALE as $abbr => $lang ) {
						if( $abbr==$locale ){
							Lang::Load( $lang );
							array_shift( $level_path );
							$path = implode( '/', $level_path );
							break;
						}
					}
				}

				/*
				 * End localization
				 */

				$full_path = __BASE.'/pages'.$path;
				$target_file = self::urlToLocal($level_path, $full_path);

				if( !file_exists($target_file) ){

					$_404 = true;

					foreach ($PAGE_DIRS??[] as $a) {
						$target_file = self::urlToLocal($level_path, $a.'/pages'.$path);
						if( file_exists($target_file) ){
							$_404 = false;
							break;
						}
					}

					if( $_404 ){
						$Response->__('
<!DOCTYPE html>
<html>
<head>
</head>
<body>
	<h1>404: Not Found</h1>
	<p>
		The requested url was not found on this server. Please check your spelling and try again<br/>
		<br/><i>'.__URL.__SEEK.'</i>
	</p>
	<hr/>
	<strong>Strongly Powered by SPhp&reg;</strong>
</body>
</html>
						');
						http_response_code(404);
						return;
					}
				}
			}

			set_error_handler(function( $a, $b, $c, $d ){
				Error::die([[
					1,
					'Warning Error in '.$c.'('.$d.') <b>'.$b.'</b><br/>',
					'SCRIPT_PARSE_ERROR'
				]]);
			}, E_WARNING);
			
			try {
				require_once $target_file;
			}
			catch( \Throwable $t ){
				Error::die([[
					1,
					'Error in '.$t->getFile().'('.$t->getLine().') <b>'.$t->getMessage().'</b>',
					'SCRIPT_PARSE_ERROR'
				]]);
			}
			catch( \Exception $e ){
				Error::die([[
					1,
					'Error in '.$e->getFile().'('.$e->getLine().') <b>'.$e->getMessage().'</b>',
					'SCRIPT_PARSE_ERROR'
				]]);
			}
		}
	}

	public function urlToLocal( $path_array, $path ){
		$target_file = __BASE.'/pages/404.php';
		
		if( preg_match('/\.php$/', end($path_array)) && file_exists($path) ){
			$target_file = $path;
		} else if( end($path_array)=='' && file_exists($path.'index.php') ){
			$target_file = $path.'index.php';
		} else if( end($path_array)=='' && file_exists(substr($path, 0, strlen($path)-1).'.php') ){
			$target_file = substr($path, 0, strlen($path)-1).'.php';
		} else if( file_exists($path.'.php') ){
			$target_file = $path.'.php';
		} else if( file_exists($path.'/index.php') ){
			$target_file = $path.'/index.php';
		}

		return $target_file;
	}

	public function secure(){
		foreach ($_GET as $key => $value) {
			if( is_array($value) ){
				foreach ( $value as $key2 => $value2 ) {
					$_GET[$key][$key2] = htmlspecialchars($value2, ENT_QUOTES, 'UTF-8');
				}

				continue;
			}

			$_GET[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
		}
		foreach ($_POST as $key => $value) {
			if( is_array($value) ){
				foreach ( $value as $key2 => $value2 ) {
					$_POST[$key][$key2] = htmlspecialchars($value2, ENT_QUOTES, 'UTF-8');
				}

				continue;
			}

			$_POST[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
		}
	}

	public function crossURLMessages(){
		if( !isset($_SESSION['MESSAGES']) ){
			$_SESSION['MESSAGES'] = array();
		}
		if( !isset($_SESSION['ERRORS']) ){
			$_SESSION['ERRORS'] = array();
		}
		if( !isset($_SESSION['PARAMS']) ){
			$_SESSION['PARAMS'] = array();
		}

		foreach ($_SESSION['MESSAGES'] as $key => $value) {
			$_SESSION['MESSAGES'][$key]['count']--;
			if( empty($value['count']) ){
				unset($_SESSION['MESSAGES'][$key]);
			}
		}

		foreach ($_SESSION['ERRORS'] as $key => $value) {
			$_SESSION['ERRORS'][$key]['count']--;
			if( empty($value['count']) ){
				unset($_SESSION['ERRORS'][$key]);
			}
		}

		foreach ($_SESSION['PARAMS'] as $key => $value) {
			$_SESSION['PARAMS'][$key]['count']--;
			if( empty($value['count']) ){
				unset($_SESSION['PARAMS'][$key]);
			}
		}
	}

	public function guessHome(){

		return str_replace( $_SERVER['DOCUMENT_ROOT'], '', str_replace( '\\', '/', __BASE ) );

	}

	public function guessURL(){

		$host = $_SERVER['SERVER_NAME'];
		$port = $_SERVER['SERVER_PORT'];
		$initial = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";

		if( $port==80 || $port==443 ){
			$port = '';
		} else {
			$port = ':'.$port;
		}

		return "$initial://$host$port".__HOME;

	}

	public function history(){
		return $_SESSION['HISTORY'];
	}

	public function usePages(){
		global $PAGE_DIRS, $__MODULE_PATH;
		if( !in_array($__MODULE_PATH, $PAGE_DIRS ?? []) )
			$PAGE_DIRS[] = $__MODULE_PATH;
	}
}