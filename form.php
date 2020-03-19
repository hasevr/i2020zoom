<?php 
$url=$_SERVER['REQUEST_URI'];
include("csvdb.inc");
if (!file_exists("run.txt")){
	echo "現在メンテナンス中です。しばらくお待ち下さい。";
	exit();
}

$presens = loadCsv("$dataFolder/presens.csv");		//	発表一覧
$presen = getRecord($_GET["cid"], $presens);
if (@count($presen) == 0){
	echo "URLが不正です。<br>長谷川晶一 hasevr@gmail.com までご連絡ください。<br>";
	exit();
}

lock();	//	リンク先保存するため、linksはロックが必要
$links = loadCsv("$dataFolder/links.csv");	//	リンク情報
for($i=0; $i < 10; ++$i){
	$dispPos[$i] = getPosFromKey("disp$i", $links);
	$linkPos[$i] = getPosFromKey("link$i", $links);
}
$pidPos = getPosFromKey("pid", $links);
$commentPos = getPosFromKey("comment", $links);

//var_dump($presen);
$pid = $presen["pid"];
if (!$pid){
	echo "発表IDが見つかりません。<br> 長谷川晶一 hasevr@gmail.com までご連絡ください。<br>";
}
for($row=0; $row < count($links); $row++){
	if ($links[$row][$pidPos] == $pid) break;
}
if (@$links[$row][$pidPos] != $pid){
	$link = array();
	foreach($links[0] as $v){
		array_push($link, "");
	}
	$link[$pidPos] = $pid;
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
		$i = substr($k, 4, 1);
		if (substr($k, 0, 4) == "disp"){
			$links[$row][$dispPos[$i]] = $v;
		}else if (substr($k, 0, 4) == "link"){
			$links[$row][$linkPos[$i]] = $v;
		}
		if ($k == "comment"){
			$links[$row][$commentPos] = $v;
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
同室を3日間使いますので、他の発表者が使用中で使えない場合もあります。会期前は
<?php
$roomCol = getPosFromKey("room", $presens);
$pidCol = getPosFromKey("pid", $presens);
$join_urlCol = getPosFromKey("join_url", $presens);
foreach($presens as $pr){
	if ($pr[$roomCol] == $presen["room"]){
		if( strncmp($pr[$pidCol], "1", 1) == 0) break;;
	}
}
?>
<a href="<?php echo $pr[$join_urlCol] ?>" target="zoom">9日の発表用の<?php echo $pr[$roomCol]?>室</a>でテストしてください。こちらは今から9日の発表まで試すことができます。<br>
zoom会議室に入ったら、ウィンドウ下部の「参加者」、右下の「ホストの要求」をクリックし、上記のホストキーを入力してホストになってください。
<!--
発表時にサインインを求められたときは、
ユーザ名 <strong><?php echo $presen["room"]?>@gs.haselab.net</strong> 、パスワード <strong>Zoom2020</strong> を用いてください。この場合、<strong>発表が終わったら、必ず https://zoom.us にサインインしたWebブラウザからアクセスし、右上の[マイアカウント]をクリック[サインアウト]をクリックでサインアウト</strong>してください。サインアウトしないと翌日の発表者の名前で聴講してしまいます。<br> -->
困ったときは<a href="https://join.slack.com/t/i2020p/shared_invite/enQtOTgyNjUwMDE4MDIxLWQ5YTU5MzFiMzUzNDAwYzVhMGIzMGVjOTZkMzY3ZTljMGVjZDEyNGUxZmIxMDUwYjk0MTlhYTFhNGIwN2E1ZmE" target="slack">発表サポートSlack</a>でご質問ください。

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
以下では<strong>半角の " </strong>は使えません。<strong>全角の ” </strong>をお使いください。<br>
<form enctype="multipart/form-data" method="POST" action="<?php echo $url;?>">
<?php
	echo 'コメント：' . '<input type="text" name="comment" ';
	if (!(array_search("comment", $errors)===FALSE)){ echo 'class="error" '; }
	echo 'size="40" value="' . $link[$commentPos] . '"><br>';
	for($i=0; $i<10; ++$i){
		echo '表示名'. ($i+1) . '：<input type="text" name="'. "disp$i" .'" ';
		if (!(array_search("disp$i", $errors)===FALSE)){ echo 'class="error" '; }
		echo 'size="2" value="' . $link[$dispPos[$i]] . '">';
		echo 'リンク先URL'. ($i+1) . '：<input type="text" name="'. "link$i" .'" ';
		if (!(array_search("link$i", $errors)===FALSE)){ echo 'class="error" '; }
		echo 'size="20" value="' . $link[$linkPos[$i]] . '"><br>';
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
