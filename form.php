<?php 
$url=$_SERVER['REQUEST_URI'];
include("csvdb.inc");
if (!file_exists("run.txt")){
	echo "現在メンテナンス中です。しばらくお待ち下さい。";
	exit();
}

$presens = loadCsv("$dataFolder/presens.csv");		//	発表一覧
$presen = getRecordByCid($_GET["cid"], $presens);
if (@count($presen) == 0){
	echo "URLが不正です。<br>";
	exit();
}

lock();	//	リンク先保存するため、linksはロックが必要
$links = loadCsv("$dataFolder/links.csv");	//	リンク情報
$C = getKeyMap($links);

//var_dump($presen);
$pid = $presen["pid"];
if (!$pid){
	echo "発表IDが見つかりません。<br>";
}

$pidCol = getColumnFromKey("pid", $links);
for($row=0; $row < count($links); $row++){
	if ($links[$row][$pidCol] == $pid) break;
}
if (@$links[$row][$pidCol] != $pid){
	$link = array();
	foreach($links[0] as $v){
		array_push($link, "");
	}
	$link[$pidCol] = $pid;
	$links[] = $link;
}
$link = $links[$row];

//	$links 更新
$errors = array();
$update = false;
if (@$_POST["update"]){
	$update = true;
	//var_dump($_POST);
	foreach($_POST as $k => $v){
		if (array_key_exists($k, $C)){
			$links[$row][$C[$k]] = $v;
		}
	}
	saveCsv("$dataFolder/links.csv", $links);
	$link = $links[$row];
}
unlock();	//	保存が済んだのでロック終了

?>
<html>
<head>
<title>インタラクティブ発表用フォーム</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<style>
.error{
	background-color:cyan;
}
</style>
</head>
<body>
<h1>インタラクティブ発表用フォーム</h1>
<?php echo "発表者名：". $presen["name"] . "(" . $presen["org"] . ")<br>"; ?>
<?php echo "発表ID：". $presen["pid"]. "<br>"; ?>
<?php echo "発表題目：". $presen["title"]. "<br>"; ?>
誤りや不具合がありましたら、東工大の長谷川晶一までご連絡ください。
 (Twitter @hasevr, Facebookメッセンジャー, Skype hase_vr, email hasevr@gmail.com) 
<br>
また、<a href="https://forms.gle/xu7fSJY5BQRKUEeq7">代替発表アンケート</a> にお答えください。
<br><br>

<h2>発表用ビデオ会議室</h2>
発表前日午後6時から使用可能なこの発表用の会議室：&nbsp;
<!-- <a href="<?php echo $presen["start_url"] ?>" target="zoom"><?php echo $presen["room"];?>室で発表する</a>
&nbsp; &nbsp; &nbsp; -->
<a href="<?php echo $presen["join_url"] ?>" target="zoom"><?php 
	echo (8+substr($presen["pid"], 0, 1)). "日の発表用の";
	echo $presen["room"];
?>室に入る</a>
&nbsp; &nbsp; &nbsp;
ホストキー: <strong><?php printf("%06d", $presen["host_key"]);?></strong>
<p>

<h2>コメント、リンク先更新フォーム</h2>
聴講者は、<a href="https://sites.google.com/view/i20202xr7fkr" target="attendee_page">参加登録者向けページ</a>からリンクされている聴講者向けプログラム」(<a href="show.php?day=1" target="prog">9日</a>、<a href="show.php?day=2" target="prog">10日</a>、<a href="show.php?day=3" target="prog">11日</a>)と<a href="https://www.interaction-ipsj.org/2020/online/int2Xr7fkR/rooms.php" target="realtimelist">リアルタイムの会議室一覧</a>を見ながら会議室に入ります。
これらには、zoomの会議室、予稿PDFへのリンクに加え、以下に記載頂いたコメントとリンクが掲載されます。
これらは、更新するとすぐに反映されますので、発表中の聴講者への連絡にお使いください。詳しくは<a href="https://docs.google.com/document/d/1KygFO6-ADIiAMZxSdxdxDiRdIvS92SzcVwcG7k-YViA/edit?usp=sharing">インタラクティブ発表マニュアル</a>、<a href="https://docs.google.com/document/d/1s_0xTaIrfuhTryebc9YTJKAwIjlPLEOv-StxG2Ksqp4/edit?usp=sharing">zoomマニュアル</a>を御覧ください<br>

<?php
if ($update){
	echo '<strong>';
	if (count($errors) > 0){	//	エラーがある場合
		echo '入力内容にエラーがあります。<span class="error">水色</span>の項目を修正の上再度「上書き更新」ボタンを押してください。';
	}else{
		echo "データがサーバに保存され、参加者向けプログラムが更新されました。";
	}
	echo '</strong><br>';
}else{
	echo '<br>';
}
?>

<form enctype="multipart/form-data" method="POST" action="<?php echo $url;?>">
<?php
	echo 'コメント：' . '<input type="text" name="comment" ';
	if (!(array_search("comment", $errors)===FALSE)){ echo 'class="error" '; }
	echo 'size="40" value="' . htmlspecialchars($link[$C["comment"]]) . '"><br>';
	for($i=0; $i<10; ++$i){
		echo '表示名'. ($i+1) . '：<input type="text" name="'. "disp$i" .'" ';
		if (!(array_search("disp$i", $errors)===FALSE)){ echo 'class="error" '; }
		echo 'size="2" value="' . addslashes($link[$C["disp$i"]]) . '">';
		echo 'リンク先URL'. ($i+1) . '：<input type="text" name="'. "link$i" .'" ';
		if (!(array_search("link$i", $errors)===FALSE)){ echo 'class="error" '; }
		echo 'size="20" value="' . $link[$C["link$i"]] . '"><br>';
	}
?>
<input type=submit name="update" value="  上書き更新  "><br>
<br>
<br>
他のウィンドウ、デバイスから更新した結果を読み出す場合は
<input type=submit name="read" value=" 変更を破棄して同期 ">
を押してしてください。
</form>
</body>
</html>
