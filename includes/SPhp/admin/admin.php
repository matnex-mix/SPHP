<?php

use SPhp\Framework\Framework;
use SPhp\Framework\Response;

include __DIR__.'/../autoload.php';

define( '__CONTEXT', 'admin' );
Framework::boot( realpath(__DIR__.'/../../../index.php') );

$GLOBALS['Response'] = new Response();
global $Response;
session_start();

Framework::secure();
Framework::crossURLMessages();
Framework::manage( $_SERVER['REQUEST_URI'] );

DB::use( App::sess('db_index') ? App::sess('db_index') : 0 );

# Start Admin

http_response_code(200);

include 'admin-post.php';
include 'admin-post-functions.php';

Page::children([

	'tables/{a}/edit' => function( $_args ){

		adminAddData( $_args['a'] );

		$db = conf("DB")[ App::sess('db_index') ? App::sess('db_index') : 0 ]['name'];
		$b = DB::query("SHOW COLUMNS FROM $db.${_args['a']}");

		if( !$b || $b->num_rows < 1 ){
			header('Location: '.App::croute('404'));
		}

		unset( $_POST['action'] );
		$c = DB::table($_args['a']);
		foreach ($_POST as $d => $e) {
			$c->where('id', $d);
		}

		echo Template::parse('admin/edit_data', [
			'name' => $_args['a'],
			'columns' => $b,
			'data' => $c->show(),
		]);

	},

	'tables/{a}/insert' => function( $_args ){

		adminAddData( $_args['a'] );

		$db = conf("DB")[ App::sess('db_index') ? App::sess('db_index') : 0 ]['name'];
		$b = DB::query("SHOW COLUMNS FROM $db.${_args['a']}");

		if( !$b || $b->num_rows < 1 ){
			header('Location: '.App::croute('404'));
		}

		echo Template::parse('admin/insert', [
			'name' => $_args['a'],
			'columns' => $b,
		]);

	},

	'tables/{a}' => function( $_args ){

		$b = DB::table($_args['a'])->show();

		adminTableOptions($_args['a']);

		echo Template::parse('admin/tables', [
			'data' => $b,
			'name' => $_args['a']
		]);

	},

	'editor/{a}' => function( $_args ){

		$b = base64_decode(urldecode($_args['a']));

		if( !file_exists(__BASE.'/'.$b) ){
			header('Location: '.App::sroute('!/404'));
		}

		adminPostFile($b);

		echo  Template::parse('admin/editor', array(
			'file' => $b,
			'content' => htmlspecialchars( file_get_contents(__BASE.'/'.$b) ),
		));

	},

	'extension/{a}' => function( $_args ){

		$b = str_replace('.', '/', $_args['a']);
		adminPostExtension( $b );

		$c = (strpos( stripslashes( json_encode(conf("MIDDLEWARE") ?? []) ), '-'.$b )===FALSE) ? true : false;

		echo Template::parse('admin/extension', array(
			'title' => $b,
			'files' => File::flatten_dir( File::dir_all( __BASE.'/'.$b ) ),
			'enabled' => $c,
		));

	},

	'error_logs' => function(){

		echo Template::parse('admin/logs', array(
			'errors' => DB::table('sphp')
				->where( 'option', 'error' )
				->order('id', 'DESC')
				->show(),
		));

	},

	'{404}' => function( $_args ){

		http_response_code(404);
		echo Template::parse('admin/404');

	},

	'' => function( $_args ){

		$a = conf("DB") ?? [];
		$b = DB::query("SHOW TABLES");
		$c = File::flatten_dir( File::dir_all( __BASE.'/models', '/\.php/' ) );
		$d = File::flatten_dir( File::dir_all( __BASE.'/static/templates', '/\.html/' ) );
		$e = File::flatten_dir( File::dir_all( __BASE.'/migrations', '/\.json$/' ) );
		$f = File::flatten_dir( File::dir_all( __BASE.'/langs', '/\.php/' ) );
		$g = conf("MIDDLEWARE") ?? [];

		echo Template::parse('admin/main', array(
			'dbs' => $a,
			'tables' => $b,
			'models' => $c,
			'templates' => $d,
			'migrations' => $e,
			'langs' => $f,
			'extensions' => $g,
		));
	}

]);

# End Admin

DB::close();