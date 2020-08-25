<?php

function adminPostExtension( $a ){

	if( !empty( $_GET) ){
		$b = conf("MIDDLEWARE") ?? [];

		foreach ($b as $d => $c) {
			if( $a==$c || '-'.$a==$c ){
				if( isset($_GET['enable']) ){
					$b[$d] = substr($c, 1);
				}

				elseif( isset($_GET['disable']) ){
					$b[$d] = '-'.$c;
				}
			}
		}

		$e = __CONFIG;
		$e['MIDDLEWARE'] = $b;
		file_put_contents(__BASE.'/config.json', json_encode($e, JSON_PRETTY_PRINT));
		header('Location: '.App::sroute(''));
	}

}

function adminPostFile( $a ){
	if( isset($_GET['saveFile']) ){

		$b = @$_POST['data'];
		if( $b )
			$c = @file_put_contents(__BASE.'/'.$a, htmlspecialchars_decode($b, ENT_QUOTES));

		if( !$c ){
			http_response_code(404);
		}

		die('');
	}

}

function adminTableOptions( $a ){
	if ( isset($_GET['tableOptions']) ) {
		$b = @$_POST['action'];
		if( $b ){
			unset($_POST['action']);

			if( $b=="delete" ){
				$query = DB::delete($a);

				foreach ($_POST as $c => $d) {
					$query->where( 'id', $c );
				}

				if( $query->run()===TRUE ){
					App::success('Record(s) deleted!');
				} else {
					App::error('An error ocurred');
				}
			}
		}

		header('Location: '.App::croute('tables/'.$a));
	}
}

function adminAddData( $a ){
	if( isset($_GET['addData']) ){
		if( DB::insert( $a, $_POST )===TRUE ){
			App::success('New row inserted with #id '.$GLOBALS['__DBINSTANCE']->insert_id);
		} else {
			App::error('An error ocurred');
		}
	
		header('Location: '.App::croute('tables/'.$a));
	}

	elseif( isset($_GET['editData']) ) {

		$d = $_POST['id'] ?? [];
		unset( $_POST['id'] );
		$msg = '';

		foreach ($d as $b => $c) {
			foreach ($_POST as $g => $e) {
				$f[$g] = $e[$b];
			}
			
			$h = DB::update($a, $f)
				->where('id', $c)
				->run();

			if( $h===TRUE ){
				$msg .= "Data OK and saved: #$c<br/>";
			} else {
				$msg .= "An error ocurred: #$c<br/>";
			}
		}

		if( $msg ){
			App::success($msg);
		} else {
			App::error('An error ocurred, check and try again');
		}

		header('Location: '.App::croute('tables/'.$a));
	}
}