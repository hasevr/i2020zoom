<?php
include("csvdb.inc");
$presens = loadCsv("$dataFolder/presens.csv");		//	発表一覧
$pidCol = getPosFromKey("pid", $presens);

$fnames = glob('./reports/*.json');
$rooms = array();
foreach($fnames as $fname){
	$start = strpos($fname, "_");
	if (!$start){
		//	会議のJSON
		$json = file_get_contents($fname);
		$jsonA = json_decode($json, true);
		$mts = $jsonA["meetings"];
		foreach($mts as $mt){
			$id = strval($mt["id"]);
			if (!array_key_exists($id, $rooms)){
				$rooms[$id] = array("pats" => array());
			}
			$rooms[$id]["topic"] = $mt["topic"];
		}
	}else{
		$start ++;
		$end = strpos($fname, "_", $start);
		$id = substr($fname, $start, $end-$start);
		if (!array_key_exists($id, $rooms)){
			$rooms[$id] = array("pats" => array(), "topic"=>"N/A");
		}
		$json = file_get_contents($fname);
		$jsonA = json_decode($json, true);
		$pats  = $jsonA["participants"];
		foreach($pats as $pat){
			$uid = $pat["user_id"];
			if (!array_key_exists($uid, $rooms[$id]["pats"])){
				$rooms[$id]["pats"][$uid] = array();
			}
			$rooms[$id]["pats"][$uid]["name"] = $pat["name"];
			if (!array_key_exists("times", $rooms[$id]["pats"][$uid])){
				$rooms[$id]["pats"][$uid]["times"] = array();
			}
			$rooms[$id]["pats"][$uid]["times"][] = array(
				"start" => strtotime($pat["join_time"]),
				"end" => strtotime($pat["leave_time"]),
				"duration" => $pat["duration"],
				"score" => $pat["attentiveness_score"]
			);
		}
	}
}
function nameComp($a, $b){
	return strcmp($a["topic"], $b["topic"]);
}
function timeComp($a, $b){
	return $a["start"] - $b["start"];
}
uasort($rooms, "nameComp");
foreach($rooms as $k=>$v){
	foreach($rooms[$k]["pats"] as $uid=>$v2){
		uasort($rooms[$k]["pats"][$uid]["times"], "timeComp");
	}
}

$timeStart = strtotime("2020-03-08T13:00:00") - 9*60*60;
$timeEnd = strtotime("2020-03-08T15:00:00") - 9*60*60;
$csv = "\xEF\xBB\xBF";
foreach($rooms as $room){
	$day = substr($room["topic"], 0,1);
	if (!is_numeric($day)) continue;
	$pats = array();
	foreach($room["pats"] as $pat){
		$total = 0;
		foreach($pat["times"] as $time){
			if ($time["start"] < $timeEnd+24*60*60*$day && $time["end"] > $timeStart+24*60*60*$day){
				$start = max($time["start"], $timeStart+24*60*60*$day);
				$end = min($time["end"], $timeEnd+24*60*60*$day);
				$duration = $end - $start;
				$total += $duration;
			}
		}
		if ($total) {
			$pats[] = array(
				"name" => $pat["name"], 
				"total" => ($total>60 ? (floor($total / 60)).":" : "") .sprintf("%02d",$total%60)
			);
		}
	}
	$pid = substr($room["topic"], 0,5);
	$presen = array();
	foreach($presens as $p){
		if ($p[$pidCol] == $pid){
			foreach($presens[0] as $k=>$v){
				$presen[$v] = $p[$k];
			}
			break;
		}
	}
	if (count($pats)){
		echo $room["topic"]. " (". count($pats). ")<br>";
		$csv .= $room["topic"]. "," .$presen["name"]."," .$presen["org"].",";
		$csv .= $presen["author"]."," .$presen["star"]."," . count($pats) . ",";
		foreach($pats as $pat){
			echo $pat["name"] . "(".$pat["total"].")&nbsp;&nbsp;&nbsp;";
			$csv .= $pat["name"] . "(".$pat["total"]."); ";
		}
		echo "<br>";
		$csv .= "\n";
	}
}
file_put_contents("interactive.csv", $csv);
?>
