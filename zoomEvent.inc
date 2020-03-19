<?php
$dataFolder = "./data/";
$eventFileNames = array($dataFolder."zoomEvent1.txt", $dataFolder."zoomEvent2.txt");

function lockFile($fname, $block=true){
	$fh = fopen($fname . ".lock", "w");
	$flags = $block ? LOCK_EX : LOCK_EX|LOCK_NB;
	if (flock($fh, $flags)){
		return $fh;
	}else{
		fclose($fh);
		return NULL;
	}
}
function unlockFile($fh){
	flock($fh, LOCK_UN);
	fclose($fh);
}
?>
