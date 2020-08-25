<?php

namespace SPhp\Framework;

use SPhp\Database\DB;

class Analytics {
	public function create(){
		$check = DB::query("SHOW TABLES LIKE '%sphp%';");
		if( $check->num_rows <= 0 ){
			DB::query("
				CREATE TABLE IF NOT EXISTS `sphp` (
				  `id` int(10) UNSIGNED NOT NULL,
				  `session_id` varchar(100) NOT NULL,
				  `option` varchar(100) NOT NULL,
				  `value` varchar(300) NOT NULL,
				  `extra` varchar(500) NOT NULL,
				  `time` int(10) UNSIGNED NOT NULL
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
			", TRUE);
			DB::query("
				ALTER TABLE `sphp` ADD PRIMARY KEY (`id`)
			", TRUE);
			DB::query("
				ALTER TABLE `sphp` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17
			", TRUE);
		}
	}

	public function push(){
		if( empty($GLOBALS['__DBINSTANCE']) )
			return;
		Analytics::create();

		$props = [];

		$props['value'] = json_encode([
			"uri" => $_SERVER['REQUEST_URI'],
			"ip_address" => $_SERVER['REMOTE_ADDR'],
			"user_agent" => $_SERVER['HTTP_USER_AGENT'],
		]);
		$props['time'] = intval($_SERVER["REQUEST_TIME"]);
		$props['option'] = "request";
		$props['`session_id`'] = session_id();
		$props['extra'] = json_encode([
			"reload" => 1,
			"status" => http_response_code(),
		]);

		$check = DB::table('sphp', ['id', 'extra'])
			->where( "session_id", session_id() )
			->where( "+option", "request" )
			->where( "+value", $props["value"] )
			->show();

		if( sizeof($check) ){
			$extra = json_decode($check[0]['extra'], true);
			$extra["reload"] = $extra["reload"]+1;
			$extra["status"] = http_response_code();

			DB::update('sphp', array(
				"extra"=>json_encode($extra),
			))
				->where( "id", $check[0]['id'] )
				->run();
		} else {
			DB::insert('sphp', $props);
		}
	}
	public function pull(){

	}
}