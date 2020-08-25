<?php

namespace SPhp\Migration;

require_once __DIR__.'/../includes/SPhp/autoload.php';

use SPhp\Framework\Framework;
use SPhp\Database\DB;
use SPhp\Database\Data;

Framework::boot( __DIR__.'../' );
DB::connect(0);

DB::seeder('payments', [
	'ref' => Data::mix('AaN', 11, 11),
	'amount' => Data::float(200, 20000),
	'created_at' => Data::runner(function(){
		return Date('Y-m-d H:i:s');
	}),
])
	#->repeat(10)
	;

DB::close();