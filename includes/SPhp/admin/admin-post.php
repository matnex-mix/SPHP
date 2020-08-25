<?php

if( isset($_GET['setDB']) ){
	
	$e = App::post('e');
	$_SESSION['db_index'] = $e;

	header('Location: '.App::sroute(''));

}

else if ( isset($_GET['crd']) ) {
	die( sPhp_credits() );
}

else if ( isset($_GET['inf']) ) {
	die( sPhp_info() );
}

elseif ( isset($_GET['login']) ) {

	if( @conf("ADMIN")['username']==App::post('admin_user') && password_verify(App::post('admin_pass'), @conf("ADMIN")['password']) ){
		$_SESSION['admin'] = @conf("ADMIN")['author'];
	} else {
		App::error('Invalid username or password');
	}

	header('Location: '.App::sroute(''));
	
}

elseif ( isset($_GET['logout']) ) {
	unset($_SESSION['admin']);
}