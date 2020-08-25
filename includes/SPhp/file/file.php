<?php

namespace SPhp\file;

use SPhp\Framework\Error;

class File {
	const FILE_ONLY = '456';
	const FILE_ONLY_NAME = '789';
	const FILE_NAME = '012';
	const DIR_ONLY = '345';

	/*
	 * File Types
	 */

	public function audio(){
		return 'mpeg,mp3,wav';
	}

	public function document(){
		return 'doc,docx';
	}

	public function image(){
		return 'jpg,jpeg,png';
	}

	public function safe(){
		return File::audio().','.File::video().','.File::document().','.File::image().',xlsx';
	}

	public function video(){
		return 'mp4,mkv,avi,3pg';
	}

	/*
	 * File Sizing
	 */

	public function kb( $int ){
		if( !is_numeric($int) ){
			Error::die([[
				0,
				'Expected integer, `('.gettype($int).') '.print_r($int, TRUE).'` given',
				'FILE_SIZE_INTEGER_ONLY'
			]]);
		}

		return $int*1024;
	}

	public function mb( $int ){
		if( !is_numeric($int) ){
			Error::die([[
				0,
				'Expected integer, `('.gettype($int).') '.print_r($int, TRUE).'` given',
				'FILE_SIZE_INTEGER_ONLY'
			]]);
		}

		return $int*1024*1024;
	}

	public function gb( $int ){
		if( !is_numeric($int) ){
			Error::die([[
				0,
				'Expected integer, `('.gettype($int).') '.print_r($int, TRUE).'` given',
				'FILE_SIZE_INTEGER_ONLY'
			]]);
		}

		return $int*1024*1024*1024;
	}

	public function dir( $path, $filter='', $show='' ){
		if( is_dir($path) ){
			$contents = [];

			if( $dir=opendir($path) ){
				while( ($file=readdir($dir))!==FALSE ){
					if($file == ".." || $file == "."){
						continue;
					}

					if( $filter && !preg_match($filter, $file) ){
						continue;
					}

					if( $show==File::FILE_ONLY && is_dir($path.'/'.$file) ){
						continue;
					} else if( $show==File::FILE_NAME ){
						if( strpos($file, '.')!==FALSE ){
							$file = explode('.', $file);
							array_pop($file);
							$file = implode('.', $file);
						}
					} else if( $show==File::FILE_ONLY_NAME ){
						if( is_dir($path.'/'.$file) ){
							continue;
						}

						if( strpos($file, '.')!==FALSE ){
							$file = explode('.', $file);
							array_pop($file);
							$file = implode('.', $file);
						}
					} else if( $show==File::DIR_ONLY && !is_dir($path.'/'.$file) ){
						continue;
					}
					$contents[] = $file;
				}
			}
			return $contents;
		} else {
			Error::die([[
				1,
				'Unable to open dir ('.$path.')',
				'DIR_NOTFOUND'
			]]);
		}
	}

	public function flatten_dir($array, $base='') {
	  	$result = array(); 
	  	foreach ($array as $key => $value) { 
	    	if (is_array($value)) { 
	      		$result = array_merge($result, self::flatten_dir($value, ($base ? "$base\\" : '' ).$key));
	    	} 
	    	else {
	      		$result[$key] = ($base ? "$base\\" : '' )."$value";
	    	} 
	  	}
	  	return $result;
	}

	public function dir_all( $path, $filter='' ){
		$contents = File::dir( $path, $filter, File::FILE_ONLY );
		$dirs = File::dir( $path, '', File::DIR_ONLY );
		foreach ($dirs as $dir) {
			$contents[$dir] = File::dir_all( $path.'/'.$dir, $filter );
		}

		return $contents;
	}

	public function upload( $file_opt, $path=FALSE, $size=FALSE, $ext=FALSE ){
		$errors = array();
		/*
		 * Set arguments to default
		 */
		if( $path===FALSE )
			$path = __BASE.'/uploads/';
		if( $size===FALSE )
			$size = File::mb(100);
		if( $ext===FALSE )
			$ext = File::safe();

		foreach ($file_opt as $key => $value) {
			if( !isset($_FILES[$key]) || !$_FILES[$key]['tmp_name'] ){
				$errors[$key] = [FALSE, 'Empty file'];
				continue;
			}
			/*
			 * Set state arguments
			 */
			if( !isset($value['path']) ){
				$value['path'] = $path;
			}
			if( !isset($value['size']) ){
				$value['size'] = $size;
			}
			if( !isset($value['ext']) ){
				$value['ext'] = $ext;
			}

			$f_name = $_FILES[$key]['name'];
			$f_size = $_FILES[$key]['size'];
			$f_tmp_name = $_FILES[$key]['tmp_name'];
			$f_ext = pathinfo($f_name, PATHINFO_EXTENSION);

			if( strpos($value['ext'], $f_ext)===FALSE ){
				$errors[$key] = [FALSE, 'Invalid Filetype'];
				continue;
			} else if( $f_size > $value['size'] ){
				$errors[$key] = [FALSE, 'Filesize too large'];
				continue;
			}

			if( isset($value['name']) ){
				$f_name = $value['name'].'.'.$f_ext;
			}

			$upload_path = str_replace('//', '/', $value['path'].'/'.$f_name);
			move_uploaded_file($f_tmp_name, $upload_path);
			$errors[$key] = [TRUE, str_replace(__BASE, '', $upload_path)];
		}

		return $errors;
	}

	public function trashUpload( $path, $permanent=FALSE ){

		// also check if it is a sub path of `uploads`
		if( is_file($path) || is_dir($path) ){

			if( $permanent===TRUE ){
				unlink( $path );
				return true;
			}

			preg_match('/(\/|\\\)[^\/\\\]+$/', $path, $name);
			
			if( !empty($name[0]) ){
				$name = __BASE.'/uploads/.trash'.$name[0];
				rename( $path, $name );
				return true;
			}

		}

		return false;

	}
}