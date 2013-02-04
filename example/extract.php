<?php
require_once '../Zippo.class.php';
try {
    $zip = new Zippo();
	$zip->setOptions(array(
		'ROOT'		=> 'tmp/',
		'ZIP_NAME'	=> 'test',
		'TARGET'	=> 'tmp',
	));

	if($zip->decompress()) {
		echo 'The zip file was extracted.';
	}
} catch (Exception $e) {
	echo 'Error in line ' , $e->getLine() , ': ',  $e->getMessage(), "\n";
}