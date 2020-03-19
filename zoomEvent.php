<?php
include('zoomEvent.inc');
$json = file_get_contents("php://input");
$logName = './data/zoomEventLog.txt';

$eventFileNameEnd = end($eventFileNames);

foreach($eventFileNames as $fname){
	$fl = lockFile($fname, false);
	if (!$fl && $fname == $eventFileNameEnd){
		$fl = lockFile($fname, true);
	}
	if ($fl){	//	排他ロックに成功したら
		$res = json_decode($json, true);
		if ($res["event"] == "meeting.ended"){
			$event = array();
			$event["type"] = "end";
			$event["mid"] = $res["payload"]["object"]["id"];
			file_put_contents($fname, serialize($event) . "\n", FILE_APPEND);
		}else if ($res["event"] == "meeting.participant_joined" 
			|| $res["event"] == "meeting.participant_left"){
			//	参加者の joined or leftの追加
			$event = array();
			$event["mid"] = $res["payload"]["object"]["id"];
			$event["topic"]=$res["payload"]["object"]["topic"];
			$event["uid"] = $res["payload"]["object"]["participant"]["user_id"];
			$event["name"] = $res["payload"]["object"]["participant"]["user_name"];
			if ($res["event"] == "meeting.participant_joined"){
				$event["type"] = "joined";
				$event["time"] = strtotime($res["payload"]["object"]["participant"]["join_time"]);
			}else if ($res["event"] == "meeting.participant_left"){
				$event["type"] = "left";
				$event["time"] = strtotime($res["payload"]["object"]["participant"]["leave_time"]);
			}
			//var_dump($event);
			file_put_contents($fname, serialize($event) . "\n", FILE_APPEND);
		}
		unlockFile($fl);    // ロックを解放します
		goto finish;
	}
}
file_put_contents($logName, "Failed to lock all event files\n" . $json, FILE_APPEND);
finish:
//	keep all event in log file.
file_put_contents($logName, serialize($event) . "\n", FILE_APPEND);
//	for debug
//file_put_contents($logName, "Called:\n" . $json . "\n", FILE_APPEND);
?>
