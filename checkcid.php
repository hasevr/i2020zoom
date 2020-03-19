<?php
include("csvdb.inc");

$cids = array();
$i;
$stop= 30000;
for($i=0; $i<$stop; $i++){
	$cid = getCid($i);
	$pos = array_search($cid, $cids);
	if($pos===FALSE){
		$cids[] = $cid;
	}else{
		echo "$pos と $i で cid が等しい値($cid) になります。<br>";
		break;
	}
}
if ($i==$stop){
	echo "0から $stop までには、cidの重複はありません。<br>";
}

?>
