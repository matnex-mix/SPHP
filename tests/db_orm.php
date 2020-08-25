<?php

/*print_r(
DB::table('history')
	->innerJoin(DB::table('users')
		->where( '>id', 0 ))
		->on([
			'history.user' => 'users.id'
		])
	->where( '*history.summary', '%' )
	->braces('||')
		->braces('&&')
			->where( 'history.id', 20 )
			->where( 'history.id', 21 )
		->close()
		->where( '+*history.time', '2020-08-02' )
	->close()
	#->view()
	->show());*/