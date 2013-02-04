<?php
require_once '../Zippo.class.php';
try {
    $zip = new Zippo();
	$zip->setOptions(array(
		'ROOT'		=> 'tmp/',
		'ZIP_NAME'	=> 'test',
	));

	if(!$zip->send()) {
		echo 'The zip file wasn\'t send.';
	}
} catch (Exception $e) {
	echo 'Error in line ' , $e->getLine() , ': ',  $e->getMessage(), "\n";
}