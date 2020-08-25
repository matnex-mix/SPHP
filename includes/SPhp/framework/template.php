<?php

namespace SPhp\Framework;

class Template {

	public $name = '';
	public $Template_Data;
	protected $string = '';

	public function __construct( $name='' ){
		if( $name ){
			$this->name = $name;
			$name = __BASE."/static/templates/$name.html";
			if( !file_exists($name) ) {
				Error::die([[
					1,
					"Template file not found ($name)",
					'TEMPLATE_NOT_FOUND'
				]]);
			} else {
				$this->string = file_get_contents($name);
			}
		}

		$this->Template_Data = array(
			'config' => array(
				'base' => __BASE,
				'url' => __URL,
				'home' => __HOME
			),
		);

		$this->ifBlock = array();
		$this->forBlock = array();
		$this->whileBlock = array();
	}

	public function parse( $name_or_data, $data=array() ){
		if( !( isset($this) && get_class($this)==__CLASS__ ) ){
			$tmp = new Template( $name_or_data );
			return $tmp->parse( $data );
		}

		return $this->parse_string( $this->string, $name_or_data );
	}

	public function parse_string( $string, $data=array() ){
		if( !( isset($this) && get_class($this)==__CLASS__ ) ){
			$tmp = new Template();
			return $tmp->parse_string( $string, $data );
		}

		$this->Template_Data = array_merge( $this->Template_Data, $data );
		$this->raw_string = $string;
		$this->string = $string;
		$this->result = '';

		$this->string = preg_replace( '/{%[^%]*%}/m', '', $this->string );

		preg_match_all( '/{\([^\}]*\)}/', $this->string, $this->statements );

		if( !empty( $this->statements[0] ) ){

			$this->statements = $this->statements[0];
			$this->afterIndex = 0;

			foreach ( $this->statements as $value ) {

				$this->processing = $value;

				if( !empty($this->skip) && strpos( $value, 'end'.$this->skip )===FALSE ){
					continue;
				}

				$this->currentIndex = strpos( $this->string, $value );
				$this->before = substr( $this->string, $this->afterIndex, $this->currentIndex );
				$this->string = substr( $this->string, $this->currentIndex+strlen($value) );

				$value = $this->process( $value );
				$this->result .= $this->before.$value;

			}

			$this->result .= $this->string;

		} else {
			$this->result = $this->string;
		}

		if( !empty($this->uses) ){

			$this->result = $this->uses->parse( $this->usesBlock );

		}

		Hooks::run( 'ON_TEMPLATE_VIEW', $this->result );
		return $this->result;

	}

	private function process( $statement ){

		$statement = trim( preg_replace('/(^{\(|\)}$)/', '', $statement ) );
		$statement = preg_split( '/ /', $statement, 2 );

		if( sizeof($statement) ){

			$name = $statement[0];

			if( !method_exists( $this, $name ) ){

				return $this->_final( implode( ' ', $statement ) );

			}

			if( isset($statement[1]) ){

				return $this->$name( $this->fixArg( $statement[1] ) );

			}

			return $this->$name();

		} else {
			return '';
		}

	}

	private function fixArg( $val ){

		if( is_string($val) ) {

			$pattern = '/\'[^\']*\'|"[^"]*"/';
			preg_match_all( $pattern, $val, $matches );
			$val = preg_replace( $pattern, 'SPHP/QUOTED', $val );

			$val = preg_replace_callback( '/@([\w_.]+)/', function( $re ){
				$re[1] = str_replace(".", "']['", $re[1]);
				return "\$this->Template_Data['${re[1]}']";
			}, $val );

			foreach ( $matches[0] as $value ) {
				$val = preg_replace( '/SPHP\/QUOTED/', $value, $val, 1 );
			}

			return $val;

		}

	}

	public function __( $name, $array=array() ){
		global $Response;
		$Response->__( Template::parse($name, $array) );
	}

	public function _final( $arg ){

		if( strpos( $arg, '@' )===0 ){

			return $this->data( $arg );

		} else {

			try {
				return eval( "return $arg;" );
			} catch ( \Exception $e ) {
				$this->error( 'Invalid template `TAG` {( '.$arg.' )}' );
			} catch ( \Throwable $t ) {
				$this->error( 'Invalid template `TAG` {( '.$arg.' )}' );
			}

		}

	}

	public function error( $msg ){
		
		if( conf('MODE')!=='debug' )
			return;

		$n = strlen($this->before)-100;
		if( $n<0 ){
			$n = 0;
		}

		$before = htmlspecialchars(substr( $this->before, $n ));
		$after =  htmlspecialchars(substr( $this->string, 0, 100 ));
		$center = "...\n$before<mark style='background: tomato'>".htmlspecialchars($this->processing)."</mark>$after\n...";

		$lines = substr_count( $this->raw_string, "\n" )+1;
		$r_lines = substr_count( $this->string, "\n" );
		$line = $lines-$r_lines;

		$n_line = substr_count( $before, "\n" )+1;
		$n_line = $line-$n_line+1;

		Hooks::run( 'ON_TEMPLATE_ERROR', $msg );
		if( !$msg )
			return;

		$msg = preg_replace( '/\n/', '<br/><span class="counter">0</span>', "<div class='head'>Error in <b>$this->name.html($line)</b>: $msg</div><div class='content'><span class='counter'>$n_line</span>$center</div>" );

		die( "
<style>
	.error {
		background: pink;
		border: 1px solid black;
	}

	.head {
		background: #eee;
		padding: 1rem;
	}

	.content {
		font-family: courier;
		font-size: .85em;
		line-height: 22px;
	}

	.counter {
		display: inline-block;
		padding: 0 .7rem;
		background: #444;
		color: #eee;
		margin-right: .6rem;
		width: 20px;
		text-align: right;
	}
</style>
<div class='error'>
	$msg
</div>
<script>
	els = document.querySelectorAll('.counter');
	index = parseInt( els[0].innerHTML );
	els[0].innerHTML = 's';
	els[els.length-1].innerHTML = 'e';
	els.forEach(function(e, i){
		if( i>0 && i<els.length-1 ){
			e.innerHTML = index;
			index++;
		}
	});
</script>
		" );

	}

	/*
	 * Binded Tags
	 *
	 */

	private function data( $arg ){

		#Removed: (Support for Eval with data) $arg = $this->fixArg( str_replace( '.', '\'][\'', $arg ) );
		$arg = explode( '|', $arg );

		$_arg = trim($arg[0]);
		try {
			$return = $this->echo($_arg);
			#Removed: (Support for Eval with data) eval( "@\$return = ".$_arg.";" );
		}
		catch (\ParseError $e){
			$this->error( $e->getmessage() );
		}
		array_shift( $arg );

		foreach ( $arg as $value ) {
			$value = explode( '-', $value, 2 );
			$args = [];

			if( isset($value[1]) ){
				$args = explode( ',', $value[1] );
			}

			foreach ( $args as $key => $_value ) {
				$args[ $key ] = trim( $_value );
			}

			$value = 'filter_'.trim( $value[0] );
			$return = $this->$value( $return, $args );
		}

		return $return;

	}

	private function uses( $t_name ){

		if( !isset( $this->uses ) ){
			$this->uses = new Template( $t_name );
			$this->usesBlock = array();
		} else {
			$this->error('you cannot use more than one template');
		}

		return '';

	}

	private function include( $t_name ){

		$tmp = new Template( $t_name );
		return $tmp->parse( $this->Template_Data );

	}

	private function var( $val='' ){
		$_val = explode( '=', $val, 2 );
		if( empty($_val[0]) ){
			$this->error('you have to specify the variable name ( {(var'.$val.')} )');
		} else {
			$val = 'null';
			if( isset($_val[1]) ){
				$val = $_val[1];
			}

			$_val[0] = str_replace( '.', '\'][\'', $_val[0] );
			eval( "\$this->Template_Data['".trim($_val[0])."'] = $val;" );
		}

		return '';
	}

	private function echo( $expression ){
		try{
			return eval( "return (".$this->fixArg( $expression ).");" );
		}
		catch (\ParseError $e){
			$this->error( $e->getmessage() );
		}
	}

	private function block( $block_name ){
		if( empty($this->uses) ){
			$this->error('use {( use )} before {( block )}');
		}

		$this->inBlock = trim( $block_name );
		return '';
	}

	private function endblock( ){
		if( empty($this->uses) ){
			$this->error('use {( use )} before {( block )}');
		}

		if( empty($this->inBlock) ){
			$this->error('endblock without a starting {( block )} TAG');
		}

		$this->usesBlock['SPHP/BLOCK/'.$this->inBlock] = $this->result.$this->before;
		$this->before = '';
		$this->result = '';

		unset( $this->inBlock );
		return '';
	}

	private function yield( $block_name ){

		if( empty($this->Template_Data['SPHP/BLOCK/'.$block_name]) ){
			return 'undefined';
			#Removed(No more errors) $this->error("`$block_name` is undefined @ {( yield )}");
		}

		return $this->Template_Data['SPHP/BLOCK/'.$block_name];

	}

	private function if( $condition ){

		$this->skip = 'if';
		array_unshift( $this->ifBlock, $condition );
		return '';

	}

	private function endif( ){
		if( empty($this->ifBlock) ){
			$this->error( '{( if )} before {( endif )}' );
		}

		if( empty($this->endif) ){
			$this->endif = '';
		}

		$this->endif .= $this->before;

		$n_if = substr_count( preg_replace( '/{\(\s*if\s*[^\}]+\s*\)}/m', 'SPHP/IF', $this->endif ), 'SPHP/IF' );
		$n_endif = substr_count( preg_replace( '/{\(\s*endif\s*\)}/m', 'SPHP/END_IF', $this->endif ), 'SPHP/END_IF' );

		if( $n_if!=$n_endif ){

			$this->before = '';
			$this->endif .= '{( endif )}';
			return;

		}

		$this->endif = preg_replace( '/{\(\s*elseif\s*([^\}]+)\s*\)}/m', '{( else )}[-if-[$1]-if-]', $this->endif );
		$this->endif = preg_split( '/{\(\s*else\s*\)}/m', $this->endif );
		$result = '';

		$bool = boolval( $this->echo( $this->ifBlock[0] ) );
		if( $bool ){
			$result = $this->endif[0];
		} else {
			array_shift( $this->endif );
			foreach( $this->endif as $value ) {
				$value = explode( ']-if-]', preg_replace( '/\[-if-\[/', '', trim( $value ), 1 ), 2 );

				if( sizeof($value)>1 ){
					$bool = boolval( $this->echo( $value[0] ) );

					if( $bool ){
						$result = $value[1];
						break;
					}
				} else {
					$result = $value[0];
					break;
				}
			}
		}

		$this->before = '';
		$this->endif = '';

		unset( $this->skip );
		array_shift( $this->ifBlock );

		$tmp = new Template();
		$result = $tmp->parse_string( $result, $this->Template_Data );
		$this->Template_Data = $tmp->Template_Data;

		return $result;
	}

	private function for( $value ){
		$this->skip = 'for';
		array_unshift( $this->forBlock, $value );
		return '';
	}

	private function endfor(){
		if( empty($this->forBlock) ){
			$this->error( '{( for )} before {( endfor )}' );
		}

		if( empty($this->endbefore) ){
			$this->endbefore = '';
		}

		$this->endbefore .= $this->before;

		$n_for = substr_count( preg_replace( '/{\(\s*for\s*[^\}]+\s*\)}/m', 'SPHP/FOR', $this->endbefore ), 'SPHP/FOR' );
		$n_endfor = substr_count( preg_replace( '/{\(\s*endfor\s*\)}/m', 'SPHP/END_FOR', $this->endbefore ), 'SPHP/END_FOR' );

		if( $n_for!=$n_endfor ){

			$this->before = '';
			$this->endbefore .= '{( endfor )}';
			return '';

		}

		$this->before = $this->endbefore;

		$target = $this->forBlock[0];
		$return = '';

		if( is_numeric($target) ){
			for( $x=0; $x<intval( $target ); $x++ ){
				$tmp = new Template();
				$return .= $tmp->parse_string( $this->before, array_merge( $this->Template_Data, array( 'this' => $x, 'i' => $x ) ) );
			}
		} elseif ( is_string($target) || is_array($target) ) {

			$target = $this->data( '@'.trim( $target ) );
			if( is_numeric($target) ){
				for( $x=0; $x<intval( $target ); $x++ ){
					$tmp = new Template();
					$return .= $tmp->parse_string( $this->before, array_merge( $this->Template_Data, array( 'this' => $x, 'i' => $x ) ) );
				}
			} else {
				foreach ( $target as $key => $value ) {
					$tmp = new Template();
					$return .= $tmp->parse_string( $this->before, array_merge( $this->Template_Data, array( 'this' => $value, 'i' => $key ) ) );
				}
			}

		} else {
			$this->error('Invalid argument expected Iterable');
		}

		$this->Template_Data = $tmp->Template_Data ?? $this->Template_Data;

		$this->before = '';
		$this->endbefore = '';

		unset( $this->skip );
		array_shift( $this->forBlock );
		return $return;
	}

	private function while( $value ){
		$this->skip = 'while';
		array_unshift( $this->whileBlock, $value );
		return '';
	}

	private function endwhile(){
		if( empty($this->whileBlock) ){
			$this->error( 'Syntax Error: could not find a {( while )} tag before {( endwhile )}' );
		}

		if( empty($this->endbefore) ){
			$this->endbefore = '';
		}

		$this->endbefore .= $this->before;

		$n_while = substr_count( preg_replace( '/{\(\s*while\s*[^\}]+\s*\)}/m', 'SPHP/WHILE', $this->endbefore ), 'SPHP/WHILE' );
		$n_endwhile = substr_count( preg_replace( '/{\(\s*endwhile\s*\)}/m', 'SPHP/END_WHILE', $this->endbefore ), 'SPHP/END_WHILE' );

		if( $n_while!=$n_endwhile ){

			$this->before = '';
			$this->endbefore .= '{( endwhile )}';
			return '';

		}

		$this->before = $this->endbefore;

		$target = $this->whileBlock[0];
		$return = '';

		if ( is_string($target) || is_array($target) ) {

			#$target = $this->fixArg( $target );
			while ( $k = $this->data($target) ) {
				$tmp = new Template();
				$return .= $tmp->parse_string( $this->before, array_merge( $this->Template_Data, array( 'this' => $k ) ));
				
				$this->Template_Data = $tmp->Template_Data;
			}

		} else {
			$this->error('Invalid argument expected Iterable');
		}

		$this->before = '';
		$this->endbefore = '';

		unset( $this->skip );
		array_shift( $this->whileBlock );
		return $return;
	}

	private function rand( $value ){
		preg_match('/(?:\'[^\']*\'|"[^"]*")/mi', $value, $quoted);
		$value = explode( " ", preg_replace('/(?:\'[^\']*\'|"[^"]*")/mi', '@@QUOTED@@', $value) );
		foreach ($value as $i => $data) {
			while( strpos($data, '@@QUOTED@@')!==FALSE ){
				$data = preg_replace('/@@QUOTED@@/', $quoted[0], $data);
				array_shift($quoted);
			}
			$value[$i] = $this->echo($data);
		}

		shuffle($value);
		return $value[0] ?? null;
	}

	private function scsmsg(){
		if( isset($this->Message) ){
			$this->error('(Syntax Error) A success tag is already defined and not closed');

		} else {
			$this->skip = 'msg';
			$this->Message = true;
		}
		
		return '';
	}

	private function errmsg(){
		if( isset($this->Message) ){
			$this->error('(Syntax Error) An error tag is already defined and not closed');

		} else {
			$this->skip = 'msg';
			$this->Message = false;
		}

		return '';
	}

	private function endmsg(){
		if( !isset($this->Message) ){
			$this->error('(Syntax Error) Require {(success)}/{(error)} tag');
			return '';
		}

		$tmp = new Template();
		$d = preg_split( "/\{\(\s*errmsg\s*\)\}/mi", $this->before );

		if( $this->Message && Functions::get_success() ){
			$return = $tmp->parse_string( $d[0], array_merge($this->Template_Data, array('success' => Functions::get_success())) );
		} else if( $this->Message && Functions::get_error() ) {
			$return = $tmp->parse_string( $d[1], array_merge($this->Template_Data, array('error' => Functions::get_error())) );
		} else if( Functions::get_error() ) {
			$return = $tmp->parse_string( $d[0], array_merge($this->Template_Data, array('error' => Functions::get_error())) );
		} else {
			$tmp->Template_Data = $this->Template_Data;
			$return = '';
		}

		$this->Template_Data = $tmp->Template_Data;
		unset( $this->skip );
		$this->before = '';
		return $return;
	}

	/*
	 * Filters
	 *
	 */

	private function filter_strtoupper( $string ){
		return strtoupper( $string );
	}

	private function filter_substr( $string, $args ){
		return substr( $string, $args[0], $args[1] );
	}

	private function filter_trim( $string ){
		return trim( $string );
	}

	private function filter_addslashes( $string ){
		return addslashes( $string );
	}

	private function filter_capfirst( $string ){
		return ucfirst( $string );
	}

	private function filter_center( $string, $args ){
		$i = intval( (($args[0]+1)-strlen($string))/2 );
		#"%${args[0]}.${i}s"
		//return 
	}

}
