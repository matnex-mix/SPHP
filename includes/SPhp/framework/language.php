<?php

namespace SPhp\Framework;

class Lang {
	public function set( $key, $value ){
		if( !isset($GLOBALS['LANG']) ){
			$GLOBALS['LANG'] = array();
		}

		if( !is_string($key) || !is_string($value) ){
			Error::die([[
				1,
				'Invalid data type(s) required (`string`, `string`) received (`'.gettype($key).'`, `'.gettype($value).'`)',
				'LANG_INVALID_TYPE'
			]]);
		}

		$GLOBALS['LANG'][$key] = $value;
	}
	public function get( $key ){
		$dt = '******';
		if( !empty($GLOBALS['LANG'][$key]) ){
			$dt = $GLOBALS['LANG'][$key];
		}
		return $dt;
	}
	public function load( $file, $show_error=false ){
		$is_default = __LANG == $file;
		$file = __BASE.'/langs/'.$file.'.php';

		if( !file_exists($file) ){
			if( !$is_default ){
				Lang::load( __LANG );
			}

			if( $show_error ){
				Error::die([[
					0,
					'The file ('.$file.') does not exist',
					'LANG_NOT_FOUND'
				]]);
			}

			return;
		}

		include $file;
	}
}