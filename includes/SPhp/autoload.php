<?php

require_once 'framework/error.php';
require_once 'framework/framework.php';
require_once 'framework/analytics.php';
require_once 'framework/response.php';
require_once 'framework/functions.php';
require_once 'framework/template.php';
require_once 'framework/page.php';
require_once 'framework/recovery.php';
require_once 'framework/language.php';
require_once 'framework/hooks.php';
require_once 'framework/routemanager.php';

require_once 'database/db.php';
require_once 'database/seeder.php';
require_once 'database/data.php';
require_once 'database/filter.php';
require_once 'database/model.php';

require_once 'file/file.php';

/*
 * Define some functions to allow quick access
 *
 *
 */

use SPhp\Framework;
use SPhp\Database;

class Template extends Framework\Template {}
class Page extends Framework\Page {}

class DB extends Database\DB {}
class Factory extends Database\Data {}

class Functions extends Framework\Functions {}
class App extends Functions {}

class Lang extends Framework\Lang {}
class L extends Lang {}

class Hooks extends Framework\Hooks {}

class RouteManager extends Framework\RouteManager {}
class R extends RouteManager {}

class File extends SPhp\File\File {}

/*
 *	Extra functions
 */

function conf( $key ){
	return @__CONFIG[$key];
}

/*
 * Info functions
 */

function sPhp_info(){
	$ver = sPhp_version();
	$License = '';

	if( file_exists( __BASE.'/LICENSE' ) ){
		$License = file_get_contents( __BASE.'/LICENSE' );
	}

	echo "
<!DOCTYPE html>
<html>
	<head>
		<title>sPHP $ver - Info</title>
		<link rel='icon' href='".__URL."/uploads/.trash/sphp.png' />
	</head>
	<body>
		<style>
			#sphp .head {
				padding: 6px;
				background: tomato;
				font-size: .85em;
				border: 1px solid #444;
			}

			#sphp .content {
				padding: 8px;
				background: #eee;
				font-size: small;
				border: 1px solid #444;
			}

			#sphp .table {
				width: 100%;
				max-width: 700px;
				border-collapse: collapse;
				border: 1px solid #444;
				margin: auto;
			}

			#sphp *{
				box-sizing: border-box;
				text-align: center;
			}
		</style>
		<div id='sphp' style='font-family: Helvetica; text-align: center;'>
			<img src='".__URL."/uploads/.trash/sphp.png' width='100' /><br/>
			<h1 style='margin: 30px auto;'>sPhp Info</h1>
			<table class='table' cellspacing='0'>
				<tr>
					<th class='head'>Group</th>
				</tr>
				<tr>
					<td class='content'>Jolaosho Abdulmateen (Matnex Mix), Diyaolu Abdulmalik, Abubakar Lawal, Jolaosho Abdulbateen</td>
				</tr>
			</table>

			<table class='table' cellspacing='0' style='margin-top: 25px;' >
				<tr>
					<th class='head'>Build</th>
				</tr>
				<tr>
					<td class='content'>Version: $ver, Build number: 2020.0.0.9.115, Date: 19/04/2020.</td>
				</tr>
			</table>

			<table class='table' cellspacing='0' style='margin-top: 25px;' >
				<tr>
					<th class='head'>Website</th>
				</tr>
				<tr>
					<td class='content'><a href='https://sphp.github.io'>https://sphp.github.io</a></td>
				</tr>
			</table>

			<table class='table' cellspacing='0' style='margin-top: 25px;' >
				<tr>
					<th class='head' colspan='2'>Package</th>
				</tr>
				<tr>
					<th class='head'>Requirements</th>
					<td class='content'>PHP > 5.6, mysql</td>
				</tr>
				<tr>
					<th class='head'>Error Manager</th>
					<td class='content'>Version: 0.0.1</td>
				</tr>
			</table>

			<table class='table' cellspacing='0' style='margin-top: 25px;' >
				<tr>
					<th class='head'>License</th>
				</tr>
				<tr>
					<td class='content'><pre>$License</pre></td>
				</tr>
			</table>
		</div>
	</body>
</html>
	";

}

function sPhp_credits(){
	$ver = sPhp_version();

	echo "
<!DOCTYPE html>
<html>
	<head>
		<title>sPHP $ver - Credits</title>
		<link rel='icon' href='".__URL."/uploads/.trash/sphp.png' />
	</head>
	<body>
		<style>
			#sphp .head {
				padding: 6px;
				background: tomato;
				font-size: .85em;
			}

			#sphp .content {
				padding: 8px;
				background: #eee;
				font-size: small;
			}

			#sphp .table {
				width: 100%;
				max-width: 700px;
				border-collapse: collapse;
				border: 1px solid #444;
				margin: auto;
			}

			#sphp *{
				box-sizing: border-box;
				text-align: center;
			}
		</style>
		<div id='sphp' style='font-family: Helvetica; text-align: center;'>
			<img src='".__URL."/uploads/.trash/sphp.png' width='100' /><br/>
			<h1 style='margin: 30px auto;'>sPhp Credits</h1>
			<table class='table' cellspacing='0'>
				<tr>
					<th class='head'>Group</th>
				</tr>
				<tr>
					<td class='content'>Jolaosho Abdulmateen (Matnex Mix), Diyaolu Abdulmalik, Abubakar Lawal, Jolaosho Abdulbateen</td>
				</tr>
			</table>

			<table class='table' cellspacing='0' style='margin-top: 25px;' >
				<tr>
					<th class='head'>Framework Design & Concept</th>
				</tr>
				<tr>
					<td class='content'>Matnex Mix</td>
				</tr>
			</table>
		</div>
	</body>
</html>
	";
}

function sPhp_version(){
	return "1.0.0";
}