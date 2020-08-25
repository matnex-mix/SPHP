<?php

namespace SPhp\Framework;

use SPhp\Database\DB;
use Exception;

class Error {
	protected $template = "";
	protected $errors = [];

	public function __construct( $errors ){
		$this->errors = $errors;
	}

	public function die( $messages=array() ){
		$error = new Error( $messages );
		Hooks::run( 'ON_ERROR', $error );

		if( conf("MODE")=='debug' && $error ){
			$error->show();
			die();
		}
	}

	public function show(){
		global $__DBINSTANCE;
		$html = "
<h1>SPhp Errors</h1>
<table cellspacing='0' border='1' width='100%'>
	<tr>
		<th align='left'>READABLE</th>
		<th align='left' width='150'>ERROR</th>
	</tr>
		";
		foreach ($this->errors as $key => $value) {
			$trace = new Exception($value[1]);
			$trace_html = '<pre>'.$trace->getTraceAsString().'</pre>';

			if($__DBINSTANCE){
				DB::insert('sphp', array(
					"session_id" => session_id(),
					"option" => 'error',
					"value" => $value[1]."\n".str_replace('\\', '\\\\', $trace->getTraceAsString()),
					"time" => time(),
				));
			}
			
			$html .= "<tr>
<td><font color='". ( $value[0]==1 ? 'brown' : 'royalblue' ) ."'>${value[1]}</font><br/>$trace_html</td>
<td valign='top'>${value[2]}</td>
			</tr>";
		}
		$html .= "
</table>
<style>
	body {
		padding: 0;
		margin: 0;
		font-family: 'Tahoma';
	}
	table {
		border-spacing: 0;
		width: 100%;
		max-width: 100%;
	}
	table td, table th {
		border: none !important;
		padding: 1em !important;
	}
	table tr:nth-child(even){
		background: pink;
	}
	table tr {
		border: 1px solid #333 !important;
		padding: 0 !important;
		background: #f2f2f2;
	}
</style>
		";
		echo $html;
	}
}