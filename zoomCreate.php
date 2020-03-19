<?php
if (!file_exists("run.txt")){
	echo "現在メンテナンス中です。しばらくお待ち下さい。";
	exit();
}

$url=$_SERVER['REQUEST_URI'];


include("csvdb.inc");
$presens = loadCsv("$dataFolder/presens.csv");		//	発表一覧
$links = loadCsv("$dataFolder/links.csv");			//	リンク情報

$pidPosInL = getPosFromKey("pid", $links);

$pidPosInP = getPosFromKey("pid", $presens);
$titlePos = getPosFromKey("title", $presens);
$starPos = getPosFromKey("star", $presens);
$namePos = getPosFromKey("name", $presens);
$orgPos = getPosFromKey("org", $presens);
$emailPos = getPosFromKey("email", $presens);
$start_urlPos = getPosFromKey("start_url", $presens);
$join_urlPos = getPosFromKey("join_url", $presens);
$roomPos = getPosFromKey("room", $presens);
$midPos = getPosFromKey("mid", $presens);

$rooms=array();
for($i=0; $i<=3; ++$i) array_push($rooms, array());
$nroom = array(0,0,0,0);
for($i=1; $i<count($presens); $i++){
	$presen = $presens[$i];
	$day = mb_substr($presen[$pidPosInP], 0, 1);
	$nroom[$day] ++;
	$room = array(
		"index" => $i,
		"pid" => $presen[$pidPosInP],
		"title" => $presen[$titlePos],
		"star" => $presen[$starPos],
		"name" => $presen[$namePos],
		"org" => $presen[$orgPos],
		"email" => $presen[$emailPos],
	);
	array_push($rooms[$day], $room);
}
$nroom[0] = max($nroom);
for($i=0; $i<$nroom[0]; ++$i){
	$room = array("pid" => "0Z".($i+1), "title"=>"発表準備用". ($i+1), "star"=>false, "name"=>"", "org"=>"", "email"=>"");
	$sep = "";
	for($d=1; $d <= 3; ++$d){
		if ( $i < count($rooms[$d]) ){
			$room["email"] .= $sep . $rooms[$d][$i]["email"];
			$room["name"] .= $sep . $rooms[$d][$i]["name"];
			$room["org"] .= $sep . $rooms[$d][$i]["org"];
			$sep = ",";
		}
	}
	array_push($rooms[0], $room);
}
function room_comp($a, $b){
	return strcmp($a["pid"], $b["pid"]);
}
for($d=0; $d <= 3; ++$d){
	usort($rooms[$d], "room_comp");
}

if (@$_POST["create"]){
	//	zoom APIで会議を作成する
	$curl_opt = array(
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 60,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_HTTPHEADER => array(
	    "authorization: Bearer ここに、zoomのJWT(JSON Web Token)を書く",
	    "content-type: application/json"
	  )
	);

	$curl = curl_init();
	curl_setopt_array($curl, $curl_opt);
	
	//	会議の作成
	$day = $_POST["day"];
	$ncreate = $nroom[$day];
	for($i=0; $i < $ncreate; ++$i){
		$room = $rooms[$day][$i];
		$roomName = sprintf("r%02d", $i+1);
		$email = sprintf("r%02d@gs.haselab.net", $i+1);
		curl_setopt($curl, CURLOPT_URL, "https://api.zoom.us/v2/users/". $email ."/meetings");
		$data = 
'{
  "topic": "'. $room["pid"].' '.mb_substr($room["title"], 0 ,10) .'",
  "agenda": "'. $room["title"].' '.$room["name"].'('.$room["org"].')' .'",
  "type": 2, '. /* Scheduled meeting */  '
  "start_time": "'. $_POST["start"] .'",
  "duration": '. $_POST["duration"] .',
  "timezone": "Asia/Tokyo",
  "settings": {
    "host_video": true,
    "participant_video": true,
    "cn_meeting": false,
    "in_meeting": false,
    "join_before_host": true,
    "mute_upon_entry": true,
    "watermark": true,
    "use_pmi": false,
    "approval_type": 2,'. /*no registration*/ '
    "audio": "both",
    "auto_recording": "'. ($day==0 ? "none" : "cloud") .'",
    "enforce_login": false
'.	//	"alternative_hosts": "'. $room["email"] .'"
	//	残念ながら、アカウントを持たないEmailは指定できない。
'
  }
}';
		echo "<strong>ROOM" . ($i+1) . "</strong>:". $data . "<br>";
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		$response = curl_exec($curl);
		$err = curl_error($curl);
		echo "<br>res:<br>";
		var_dump(@$response);
		echo "<br>";
		$res = json_decode(@$response, true);
		if ($day > 0){	//	会議室情報の記録
			$presens[$room["index"]][$start_urlPos] = $res["start_url"];
			$presens[$room["index"]][$join_urlPos] = $res["join_url"];
			$presens[$room["index"]][$roomPos] = $roomName;
			$presens[$room["index"]][$midPos] = $res["id"];
		}
	}
	saveCsv("$dataFolder/presens.csv", $presens);		//	発表一覧のセーブ
	echo "<strong>" . $ncreate, "室作りました。</strong><br><br>";
}else{
	echo "<br><br>";
}
?>
会議室の数：
<?php
	for($i=0; $i<=3; ++$i){
		echo "&nbsp;&nbsp;&nbsp;Day" . $i . " " . $nroom[$i] . "室";
	}
	echo "<br>\n";
?>
<form enctype="multipart/form-data" method="POST" action="<?php echo $url;?>">
どのセッション？ <input type=input name="day" value="<?php echo @$_POST["day"] ? $_POST["day"] : 1; ?>" />
	0:準備日、1:初日、2:2日目、3:3日目<br>
開始時間 <input type=input name="start" value="<?php echo @$_POST["start"] ? $_POST["start"] : "2020-03-08T18:00:00"; ?>" /><br>
継続時間 <input type=input name="duration" value="<?php echo 24*60-1; ?>" />分<br>
<input type=submit name="create" value="  ミーティングを作成  "><br>
</form>
