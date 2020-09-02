<?php

Model::load('user');

class Api extends SPhp\Rest\Endpoint {

	public function __construct(){
		$this->responseFormat = [
			
		];

		parent::__construct();
	}

	# Create function
	public function post(){

		$a = @$this->data->name;
		$b = @$this->data->age;

		if( $a && $b ){
			$user = new User(array(
				'name' => $a,
				'age' => $b
			));

			if( $user->save()===TRUE ){
				return $this->success('User added successfully');
			} else {
				return $this->error('An unknown error ocurred');
			}
		} else {
			return $this->error('Incomplete parameters');
		}

	}

	# List all function
	public function get(){

		return $this->success( User::get()->order('id', 'DESC')->show() );
	}

	# Get single
	public function singleGet( $id ){

		return $this->success( User::get()->where('id', $id)->show()[0]??'User not found' );
	}

	# Update single
	public function singlePost( $id ){

		$user = User::get()->where('id', $id)->show()[0]??null;

		$a = @$this->data->name;
		$b = @$this->data->age;
		
		if( $user ){
			if( $a )
				$user->name = $a;

			if( $b )
				$user->age = $b;

			if( $user->save()===TRUE ){
				return $this->success('User updated successfully');
			} else {
				return $this->error('An unknown error ocurred');
			}
		}

		return $this->error('User not found');
	}

	# Delete single function
	public function singleDelete( $id ){

		$user = User::get()->where('id', $id)->show()[0]??null;

		$a = @$this->data->name;
		$b = @$this->data->age;
		
		if( $user ){
			if( $user->delete()===TRUE ){
				return $this->success('User deleted successfully');
			} else {
				return $this->error('An unknown error ocurred');
			}
		}

		return $this->error('User not found');
	}

	# Fallback function
	public function __404(){
		return $this->error('system error');
	}

}

Api::process();