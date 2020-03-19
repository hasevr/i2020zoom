<?php 
include("csvdb.inc");
$csv = loadCsv("$dataFolder/presens.csv");		//	審査員一覧のロード
$C = getKeyMap($csv);

//	以下、Webページを表示しながら処理する。
?>
<html>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<body>

<form enctype="multipart/form-data" method="POST" action="<?php echo $_SERVER['REQUEST_URI']?>">
	From：<input type=text size=80 name=from value="<?php echo @$_POST["from"] ? $_POST["from"] : $emailFrom ?>"><br>
	タイトル：<input type=text size=80 name=title value="<?php echo @$_POST["title"] ?>"><br>
	<textarea rows=20, cols=80, name=body><?php echo @$_POST["body"]?></textarea><br>
	パスワード: <input type=text name=passwd value=<?php echo @$_POST["passwd"]?> >
	<input type=submit name="check" value="  確認  "><br>
	<input type=submit name="send" value="  送信  "><br>
	置き換えルールについて：<br>
	本文とタイトルについて、次の置き換えができます。<br>
	<?php
		echo '$cid&nbsp;';
		foreach($csv[0] as $v){
			echo '$' . $v . "&nbsp;";
		}
	?>
	<br>
</form>
<?php
if (@$_POST["passwd"]!="1Bridge2Online") exit();
if (isset($_POST["send"])||isset($_POST["check"])){
	foreach($csv as $rn => $r){
		if ($rn == 0){
			$org=array('$cid');
			foreach($r as $v){
				$org[] = '$' . trim($v);
			}
			continue;
		}
		$rep=array(getCid($r[$C["oid"]]));
		foreach($r as $v){
			$rep[] = $v;
		}
		$to = $r[$C["email"]];
		$from = $_POST["from"];
		$title = str_replace($org, $rep, $_POST["title"]);
		$body = str_replace($org, $rep, $_POST["body"]);
		echo "<hr>To:", $to, "<br>";
		echo "From:", $from, "<br>";
		echo "タイトル:", $title, "<br>\n";
		echo "本文:<pre>", $body, "</per><br>\n";
		if (isset($_POST["send"])){
			if (mb_send_mail($to, $title, $body, "From: $from")){
				echo "メールを送信しました。";
			}else{
				echo "メール送信に失敗しました。";
			}
		}
	}
}
?>
</body>
</html>
