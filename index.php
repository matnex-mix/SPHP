<?php
	require_once 'includes/SPhp/autoload.php';
	require_once 'middleware.php';

	use SPhp\Framework\Framework;
	use SPhp\Framework\Analytics;
	use SPhp\Framework\Response;
	use SPhp\Framework\Recovery;

	define( '__CONTEXT', 'app' );
	Framework::boot( __FILE__ );
	require_once 'props.php';

	$GLOBALS['Response'] = new Response();
	global $Response;
	session_start();

	Framework::secure();
	DB::connect(0);
	Recovery::check();
	Framework::crossURLMessages();
	Framework::manage( $_SERVER['REQUEST_URI'] );
	$Response->show();
	Analytics::push();
	DB::close();