<?php

namespace SPhp\Framework;

class Hooks {

	protected $ON_BOOT = 'ON_BOOT';
	protected $ON_RESPONSE = 'ON_RESPONSE';
	protected $ON_REQUEST = 'ON_REQUEST';
	protected $ON_ERROR = 'ON_ERROR';
	protected $ON_TEMPLATE_VIEW = 'ON_TEMPLATE_VIEW';
	protected $ON_TEMPLATE_ERROR = 'ON_TEMPLATE_ERROR';

	public function inject( $enum, $closure ){

		if( property_exists( 'Hooks', $enum ) ){
			$GLOBALS['HOOKS'][$enum][] = $closure;
		}

	}

	public function run( $enum, &...$args ){

		if( property_exists( 'Hooks', $enum ) ){
			if( !empty( $GLOBALS['HOOKS'][$enum] ) ){

				foreach ( $GLOBALS['HOOKS'][$enum] as $closure ) {
					call_user_func_array( $closure, $args );
				}

			}
		}

	}

}