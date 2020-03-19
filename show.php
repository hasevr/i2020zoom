<?php

include("csvdb.inc");
$presens = loadCsv("$dataFolder/presens.csv");		//	発表一覧
$links = loadCsv("$dataFolder/links.csv");			//	リンク情報
for($i=0; $i < 10; ++$i){
	$dispPos[$i] = getPosFromKey("disp$i", $links);
	$linkPos[$i] = getPosFromKey("link$i", $links);
}
$commentPos = getPosFromKey("comment", $links);
$pidPosInL = getPosFromKey("pid", $links);

$pidPosInP = getPosFromKey("pid", $presens);
$titlePos = getPosFromKey("title", $presens);
$starPos = getPosFromKey("star", $presens);
$namePos = getPosFromKey("name", $presens);
$orgPos = getPosFromKey("org", $presens);
$authorPos = getPosFromKey("author", $presens);
$start_urlPos = getPosFromKey("start_url", $presens);
$join_urlPos = getPosFromKey("join_url", $presens);
$roomPos = getPosFromKey("room", $presens);

?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<meta http-equiv="refresh" content="30; URL=<?php echo $_SERVER['REQUEST_URI']; ?>"/>
<style>
a {
	text-decoration: none;
}
</style>
</head>
<body>
<?php

foreach($presens as $presen){
	if ( $_GET["day"] != substr($presen[$pidPosInP], 0, 1) ) continue;
	foreach($links as $link){
		if ($link[$pidPosInL] == $presen[$pidPosInP]){
			break;
		}
	}
	if ($link[$pidPosInL] != $presen[$pidPosInP]){
		$link=array();
		foreach($links[0] as $v){
			array_push($link, "");
		}
	}
	echo '<p>';
	echo '<strong>(', $presen[$pidPosInP];
	echo ($presen[$starPos] ? '<span style="color: #ffd700;">★</span>)' : ')') . "&nbsp;";
	echo $presen[$titlePos]. "</strong><br>";
	//echo $presen[$namePos], " : ";
	$authors = str_replace($presen[$namePos], "◎".$presen[$namePos], $presen[$authorPos]);
	echo '&nbsp;&nbsp;' . $authors . "<br>\n";
	echo '&nbsp;&nbsp;<a href="' . $presen[$join_urlPos] . '" target="zoom">'. $presen[$roomPos]. '室に入る</a>&nbsp;&nbsp;';
	$pidRoom = substr($presen[$pidPosInP],1,1);
	if ("A" <= $pidRoom && $pidRoom <= "P"){
		echo '<a href="http://www.interaction-ipsj.org/proceedings/2020/data/pdf/'. $presen[$pidPosInP] .'.pdf' .'", target="pdf">予稿</a>';
	}
	for($i=0; $i<10; ++$i){
		if ($link[$dispPos[$i]]){
			echo '&nbsp;<a href="'. $link[$linkPos[$i]] .'" target="link">'. $link[$dispPos[$i]] .'</a>';
		}
	}
	if($link[$commentPos]){
		echo '&nbsp;&nbsp;<span color="red">'. $link[$commentPos] . '</span>';
	}
	echo '</p>';
}

?>
</body>
</html>
