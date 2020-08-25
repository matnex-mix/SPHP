<?php

namespace SPhp\Rest\Output;

use SPhp\Framework\Hooks;

/*
 * Hooks Usage Example

Hooks::inject( 'ON_BOOT', function( ){
	# TODO: initialize some variables
});

Hooks::inject( 'ON_REQUEST', function( &$Path ){
	# Reset the path to always be index
	$Path = '';
});

Hooks::inject( 'ON_RESPONSE', function( &$Response ){
	# Format the response to JSON
	$Response = json_encode(array(
		'response' => $Response,
		'status' => http_response_code(),
	));

	header('Content-Type: application/json');
});

Hooks::inject( 'ON_ERROR', function( &$Error ){
	$Error = 0;
});

Hooks::inject( 'ON_TEMPLATE_VIEW', function( &$Data ){
	$Data = trim($Data);
});

Hooks::inject( 'ON_TEMPLATE_ERROR', function( &$Error ){
	$Error = 0;
});

 */