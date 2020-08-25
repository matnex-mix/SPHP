<?php

namespace SPhp\Framework;

class RouteManager {

	public function init(){

		if( !isset($GLOBALS['ROUTES']) || !is_array($GLOBALS['ROUTES']) ){

			$GLOBALS['ROUTES'] = [];

		}

	}

	public function keep( $key, $url ){

		RouteManager::init();

		$GLOBALS['ROUTES'][$key] = $url;

	}

	public function show( $key, ...$args ){

		RouteManager::init();

		if( isset($GLOBALS['ROUTES'][$key]) ){
			$r = $GLOBALS['ROUTES'][$key];
			foreach ( $args as $value ) {
				$r = preg_replace( '/\*/', $value, $r, 1 );
			}
			return $r;
		} else {
			Error::die([[
				0,
				'trying to show an undefined route',
				'ROUTE_UNDEFINED'
			]]);
		}

	}

}