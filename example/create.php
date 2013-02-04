<?php
require_once '../Zippo.class.php';
try {
    $zip = new Zippo();
	$zip->setOptions(array(
		'ROOT'		=> 'tmp/',
		'ZIP_NAME'	=> 'test',
		'ARCHIVE'	=> 'test',
		'NOT_ADD'	=> array('create.php'),
	));

	$zip->addDir('files');
	$zip->addFile('create.php');
	if($zip->compress()) {
		echo 'The zip file was created.';
	}
} catch (Exception $e) {
	echo 'Error in line ' , $e->getLine() , ': ',  $e->getMessage(), "\n";
}