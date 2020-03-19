<?php
$maxExecTime = 120;
ini_set("max_execution_time", $maxExecTime);
include("csvdb.inc");
$presens = NULL;
$midCol=NULL; $pidCol=NULL; $authorCol=NULL; $join_urlCol=NULL;
$links = NULL;
$pidColInL=NULL; $dispCol=NULL; $linkCol=NULL; $commentCol=NULL;
function loadCsvFiles(){
	global $dataFolder, $presens, $midCol, $pidCol, $authorCol, $join_urlCol;
	if ($presens)return;
	
	$presens = loadCsv("$dataFolder/presens.csv");		//	発表一覧
	$midCol = getPosFromKey("mid", $presens);
	$pidCol = getPosFromKey("pid", $presens);
	$authorCol = getPosFromKey("author", $presens);
	$join_urlCol = getPosFromKey("join_url", $presens);
	
	global $links, $pidColInL, $dispCol, $linkCol, $commentCol;
	$links = loadCsv("$dataFolder/links.csv");		//	リンク先
	$pidColInL = getPosFromKey("pid", $links);
	for($i=0; $i < 10; ++$i){
		$dispCol[$i] = getPosFromKey("disp$i", $links);
		$linkCol[$i] = getPosFromKey("link$i", $links);
	}
	$commentCol = getPosFromKey("comment", $links);
}

include("zoomEvent.inc");
//---------------------------------------------------
//	ファイルの読み出し
$flgUpdate = false;
$rooms = array();
$roomsFname = "$dataFolder/rooms.txt";
foreach($eventFileNames as $fname){
	if (file_exists($fname)) $flgUpdate = true;
}
if (!$flgUpdate){
	if (file_exists($roomsFname)){
		lock();		//	更新中に読まないように、ロックする
		$rooms = unserialize(file_get_contents($roomsFname));
		unlock();
	}
}else{
	lock();	//	roomsの更新のためロック
	if (file_exists($roomsFname)){
		$rooms = unserialize(file_get_contents($roomsFname));
	}
	foreach($eventFileNames as $fname){
		if (file_exists($fname)){	//	ファイルが存在して
			$fl = lockFile($fname, false);
			if ($fl){	//	排他ロックに成功したら
				$events = file($fname);	//	ファイルを読んで
				unlink($fname);			//	削除して
				unlockFile($fl);    	//	ロックを解放
				foreach($events as $evs){
					$event = unserialize($evs);
					$ignores = array();
					if (!(array_search($event["mid"], $ignores) === FALSE)) continue;
					if ($event["type"] == "end"){
						unset($rooms[$event["mid"]]);
					}else if ($event["type"] == "joined" || $event["type"] == "left"){
						//	ミーティング情報の更新
						//echo "mid = " . $event["mid"] . "<br>";
						if (!array_key_exists($event["mid"], $rooms)){
							$join_url = "https://zoom.us/j/" . $event["mid"];
							$pid = "00-00";
							$author = "";
							loadCsvFiles();
							foreach($presens as $presen){
								echo '$presen[$midCol] = ' . $presen[$midCol] . '  event["mid"] = '.$event["mid"] . '<br>';
								if ($presen[$midCol] == $event["mid"]){
									$join_url = $presen[$join_urlCol];
									$pid = $presen[$pidCol];
									$author = $presen[$authorCol];
									// "join_url = " . $join_url . "<br>"; 
									break;
								}
							}
							$alink = array();
							foreach($links as $link){
								if ($pid == $link[$pidColInL]){
									for($i=0; $i<count($links[0]); ++$i){
										$alink[$links[0][$i]] = $link[$i];
									}
								}
							}
							$rooms[$event["mid"]] = array("topic" => "", "url" => $join_url, "pid" => $pid, 
								"author" => $author, "users" => array(), "link" => $alink);
						}
						$room = &$rooms[$event["mid"]];
						$room["topic"] = $event["topic"];
						//	ユーザ追加
						if (!array_key_exists($event["uid"], $room["users"])){
							$room["users"][$event["uid"]] = array("name" => "", "join_time" => 0, "leave_time" => 0);
						}
						$room["users"][$event["uid"]]["name"] = $event["name"];
						if ($event["type"] == "joined"){
							$room["users"][$event["uid"]]["join_time"] = $event["time"];
						}else if ($event["type"] == "left"){
							$room["users"][$event["uid"]]["leave_time"] = $event["time"];
						}
						//echo "new room:" . $event["mid"]; var_dump($room); echo "<br>";
						unset($room);
					}
				}
			}
		}
	}
	$time = time();
	$delMids = array();
	foreach($rooms as $mid => $room){
		$delUids = array();
		foreach($room["users"] as $uid => $user){
			if ($user["join_time"] && $user["leave_time"] && $user["leave_time"] - $user["join_time"] < 0){
				echo "Leave before join<br>";
				$user["leave_time"] = 0;
			}
			if ($user["leave_time"] && $time - $user["leave_time"] > 60){	//いなくなって１分
				$delUids[] = $uid;
			}
		}
		foreach($delUids as $uid){
			unset($rooms[$mid]["users"][$uid]);
		}
		if (count($rooms[$mid]["users"]) == 0){
			$delMids[] = $mid;
		}
	}
	foreach($delMids as $mid){
		unset($rooms[$mid]);
	}

	function roomComp($a, $b){
		$pidA = str_replace("X-", "!-", $a["pid"]);
		$pidB = str_replace("X-", "!-", $b["pid"]);
		//echo "comp $pidA and $pidB <br>";
		return strcmp($pidA, $pidB);
	}
	uasort($rooms, "roomComp");

	file_put_contents($roomsFname, serialize($rooms));
	unlock();	//	rooms.txt更新完了
}
//---------------------------------------------------
//	表示
//echo "<br>rooms:"; var_dump($rooms); echo "<br><br>"; 

$rooms = array_values($rooms);
$ncol = 4;

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta http-equiv="refresh" content="10; URL=<?php echo $_SERVER['REQUEST_URI']; ?>"/>

<style>
table{
  border-collapse: collapse;
  margin: 0 auto;
  width: 100%;
  table-layout: fixed;
}
th {
  border-width: 0.2em 0.2em 0 0.2em;
  border-color: white;
  border-style: solid;
  padding: 0.1em;
  text-align: center;
  width:<?php echo floor(100/$ncol);?>%;
}
td {
  border-width: 0 0.2em 0.2em 0.2em;
  border-color: white;
  border-style: solid;
  padding: 0.1em;
  text-align: center;
  width:<?php echo floor(100/$ncol);?>%;
}

th, td{
  background: #faf0e6;
}
a{
	text-decoration: none;
	color: #008080;
}
a.link, span.link{
	font-weight: normal;
}
</style>

<title>インタラクション2020 ライブ会議室一覧</title>
</head>
<body>
<h1>インタラクション2020 ライブ会議室一覧&nbsp;<span style="font-size:medium;">(<span style="color:red">赤色</span>:入ってすぐの人&nbsp; <span style="color:blue">水色</span>:出た人)</span></h1>
<a style="color:red; font-weight:bold;" href="https://forms.gle/DkPH72q3Kts6gFzY6" target="vote">インタラクティブ発表賞一般投票フォーム(11日) 16:45まで</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a style="color:red; font-weight:bold;" href="https://forms.gle/gTAm1PfJbCmLy1rh6" target="vote">インタラクション2020アンケート 皆様ご回答ください！</a>&nbsp;&nbsp;&nbsp;
<a href="https://forms.gle/xu7fSJY5BQRKUEeq7">代替発表アンケート</a>発表者の方はお答えください。
<br>
<?php
if (count($rooms) == 0){
	echo "どの会議室にも誰もいません";
}else{
	echo "<table>\n";
	$nrow = floor((count($rooms)+$ncol-1) / $ncol);

	$ndummy = $nrow * $ncol - count($rooms);
	for($i=0; $i < $ndummy; ++$i){
		array_push($rooms, array("topic"=>"", "url"=>"", "pid"=>"", "author"=>"", "users" => array(), "link" => null));
	}
	for($row = 0; $row < $nrow; $row++){
		//	topic
		echo "<tr>";
		for($col = 0; $col < $ncol; $col++){
			$i = $row*$ncol + $col;
			$title =  mb_strimwidth($rooms[$i]["topic"], 0, 60);
			if ($title != $rooms[$i]["topic"]) $title = mb_substr($title, 0, -1). "…";
			echo '<th><a href="' . $rooms[$i]['url'] . '" target="zoom" title="'. $rooms[$i]["topic"] ."\n" .
				$rooms[$i]["author"] . '">' . 
				mb_strimwidth($rooms[$i]["topic"], 0, 60) . '</a><br>';
			$pidCh2 = substr($rooms[$i]["pid"], 1,1);
			if ("A" <= $pidCh2 && $pidCh2 <= "P"){
				echo '<a class="link" ';
				echo 'href="http://www.interaction-ipsj.org/proceedings/2020/data/pdf/';
				echo $rooms[$i]["pid"] .'.pdf' .'", target="pdf">予稿</a>';
			}
			if ($rooms[$i]["link"]){
				$link = $rooms[$i]["link"];
				for($i=0; $i<10; ++$i){
					if ($link["disp$i"]){
						echo '&nbsp;<a class="link" href="'. $link["link$i"] .'" target="link">'. $link["disp$i"] .'</a>';
					}
				}
				if ($link["comment"]){
					echo '&nbsp; <span class="link">'. $link["comment"]. '</span>';
				}
			}
			echo '</th>';
		}
		echo "</tr>\n";
		//	参加者名
		echo "<tr>";
		for($col = 0; $col < $ncol; $col++){
			$i = $row*$ncol + $col;
			echo "<td>";
			$sep = "";
			foreach($rooms[$i]["users"] as $user){
				$red = 0;
				$cyan = 0;
				//	来てすぐの場合
				$color = "#FFFFFF";
				if ($user["join_time"]){
					$diff = $time - $user["join_time"];
					$duration = 60 * 3;
					if ($diff > $duration) $diff = $duration;
					$c = floor((1 - $diff/$duration) * 255);
					$color = sprintf('#%02X0000', $c);
				}
				if ($user["leave_time"]){
					$diff = $time - $user["leave_time"];
					if ($diff > 0){
						$duration = 60;
						if ($diff > $duration) $diff = $duration;
						$r = floor(($diff/$duration*0.7+0.3) * 0xfa);	//background:	faf0e6
						$g = floor(($diff/$duration*0.7+0.3) * 0xf0);	//background:	faf0e6
						$color = sprintf('#%02X%02XFF', $r, $g);
					}
				}
				echo $sep . '<span style="color:'.$color.'; white-space: nowrap;'.
					'display: inline-block; line-height:1em; border:solid 1px #008080;">' .
					 str_replace(" ", "&nbsp;", $user["name"]) . '</span>';
//				echo  . "<br>" .
//				"j:". date("H:i:s", $user["join_time"]+ 8*60*60) ."<br>" . 
//				"l:". date("H:i:s", $user["leave_time"]+ 8*60*60) . "<br>".
//				" n:" . date("H:i:s", time() + 8*60*60);
				$sep = " ";
			}
			echo "</td>";
		}
		echo "</tr>\n";
	}
	echo "</table>\n";
}
?>
</body>
</html>
