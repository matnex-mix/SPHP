<?php

namespace SPhp\Framework;

use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class Recovery {
	public function check(){
		global $Response;

		if( isset($_SESSION['recovery']) && !empty($_POST['signature']) && $_POST['signature']==__RECOVERY['signature'] ){
			unset($_SESSION['recovery']);
			Recovery::process();
		}

		if( !empty($_GET['recovery']) && $_GET['recovery']==__RECOVERY['pass'] ) {
			$_SESSION['recovery'] = TRUE;
			
			$Response->__('<font color="purple">** Welcome to Recovery Process **</font>');
			$Response->__("
	<form method='post'>
		<textarea name='signature' rows='20' cols='70'></textarea>
		<br/>
		<input type='submit' value='START RECOVERY' style='width: 200px; height: 50px'>
	</form>
			");
			$Response->show(); die();
		}
	}

	public function process(){
		global $Response;
		$file_name = 'recovery/SPHPRECOVERY_'.Date('Ymd_His').'.zip';
		Recovery::backupDatabase();

		if(!is_dir(__BASE.'/recovery')) {
			mkdir(__BASE.'/recovery');
		}

		$zip = new ZipArchive();
		$zip->open($file_name, ZipArchive::CREATE | ZipArchive::OVERWRITE);

		$files = new RecursiveIteratorIterator(
		    new RecursiveDirectoryIterator(__BASE),
		    RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ($files as $name => $file)
		{
		    if (!$file->isDir())
		    {
		        $filePath = $file->getRealPath();
		        $relativePath = substr($filePath, strlen(__BASE) + 1);

		        $zip->addFile($filePath, $relativePath);
		    }
		}

		$zip->close();
		unlink( str_replace( "zip", "sql", str_replace( "recovery/", "", $file_name) ) );

		header('Location: '.__URL.'/'.$file_name);
		die();
	}

	public function backupDatabase(){
		$mysqli = $GLOBALS['__DBINSTANCE'];
	    $mysqli->query("SET NAMES 'utf8'");
	    $queryTables = $mysqli->query('SHOW TABLES');

	    while($row = $queryTables->fetch_row()) { 
	        $target_tables[] = $row[0]; 
	    }

	    foreach($target_tables as $table) {

	        $result = $mysqli->query('SELECT * FROM '.$table);  
	        $fields_amount = $result->field_count;  
	        $rows_num = $mysqli->affected_rows;     
	        
	        $res = $mysqli->query('SHOW CREATE TABLE '.$table); 
	        $TableMLine = $res->fetch_row();
	        $content = (!isset($content) ?  '' : $content) . "\n\n".$TableMLine[1].";\n\n";

	        for ($i = 0, $st_counter = 0; $i < $fields_amount;   $i++, $st_counter=0) 
	        {
	            while($row = $result->fetch_row())  
	            { //when started (and every after 100 command cycle):
	                if ($st_counter%100 == 0 || $st_counter == 0 )  
	                {
	                        $content .= "\nINSERT INTO ".$table." VALUES";
	                }
	                $content .= "\n(";
	                for($j=0; $j<$fields_amount; $j++)  
	                { 
	                    $row[$j] = str_replace("\n","\\n", addslashes($row[$j]) ); 
	                    if (isset($row[$j]))
	                    {
	                        $content .= '"'.$row[$j].'"' ; 
	                    }
	                    else 
	                    {   
	                        $content .= '""';
	                    }     
	                    if ($j<($fields_amount-1))
	                    {
	                            $content.= ',';
	                    }      
	                }
	                $content .=")";
	                //every after 100 command cycle [or at last line] ....p.s. but should be inserted 1 cycle eariler
	                if ( (($st_counter+1)%100==0 && $st_counter!=0) || $st_counter+1==$rows_num) 
	                {   
	                    $content .= ";";
	                } 
	                else 
	                {
	                    $content .= ",";
	                } 
	                $st_counter=$st_counter+1;
	            }
	        } $content .="\n\n\n";
	    }

	    file_put_contents('SPHPRECOVERY_'.Date('Ymd_His').'.sql', $content);
	}
}