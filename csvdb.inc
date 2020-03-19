<?php
//	設定
require("csvdbconf.inc");

//	mbstringの設定
mb_language("ja");
mb_internal_encoding("UTF-8");
mb_regex_encoding("UTF-8");
setlocale(LC_ALL, 'ja_JP.UTF-8');

//	dataFolder作成
if (!file_exists($dataFolder)){
	mkdir($dataFolder, "0700");
	file_put_contents("$dataFolder/.htaccess", "deny from all\n");
}

//-----------------------------------------------------------------------------
//	関数定義部。
  /**
     * ファイルポインタから行を取得し、CSVフィールドを処理する
     * @param resource handle
     * @param int length
     * @param string delimiter
     * @param string enclosure
     * @return ファイルの終端に達した場合を含み、エラー時にFALSEを返します。
     */
function fgetcsv_reg (&$handle, $length = null, $d = ',', $e = '"') {
    $d = preg_quote($d);
    $e = preg_quote($e);
    $_line = "";
    $eof = false;
    while (($eof != true)and(!feof($handle))) {
        $_line .= (empty($length) ? fgets($handle) : fgets($handle, $length));
        $itemcnt = preg_match_all('/'.$e.'/', $_line, $dummy);
        if ($itemcnt % 2 == 0) $eof = true;
    }
    $_csv_line = preg_replace('/(?:\\r\\n|[\\r\\n])?$/', $d, trim($_line));
    $_csv_pattern = '/('.$e.'[^'.$e.']*(?:'.$e.$e.'[^'.$e.']*)*'.$e.'|[^'.$d.']*)'.$d.'/';
    preg_match_all($_csv_pattern, $_csv_line, $_csv_matches);
    $_csv_data = $_csv_matches[1];
    for($_csv_i=0;$_csv_i<count($_csv_data);$_csv_i++){
        $_csv_data[$_csv_i]=preg_replace('/^'.$e.'(.*)'.$e.'$/s','$1',$_csv_data[$_csv_i]);
        $_csv_data[$_csv_i]=str_replace($e.$e, $e, $_csv_data[$_csv_i]);
    }
    return empty($_line) ? false : $_csv_data;
}

//	SJISかBOM付きUTF-8のCsvをロードする。UTF-8に変換して2次元配列にして返す。
function loadCsv($fname){
	$sjis = @file_get_contents($fname);
	if (substr($sjis, 0,3) == "\xEF\xBB\xBF"){
		//	utf8 with bom
		$fp = fopen($fname, "r");
		fgetc($fp); fgetc($fp); fgetc($fp);	//	bom
		while ($data = fgetcsv_reg($fp)) {
			$csv[] = $data;
		}
	}else{
		$buf = mb_convert_encoding($sjis, "UTF-8", "sjis");
		$fp = tmpfile();
		fwrite($fp, $buf);
		rewind($fp);
		$csv=null;
		while ($data = fgetcsv_reg($fp)) {
			$csv[] = $data;
		}
	}
	return $csv;
}

//	2次元配列をUTF-8BOM付きのCsvにセーブする。
function saveCsv($fname, $csv){
	$fpSave = fopen($fname, "w");
	fputs($fpSave, "\xEF\xBB\xBF");	//	BOM
	foreach($csv as $r) fputcsv($fpSave, $r);
	fclose($fpSave);
/*
	//	まずUTF-8で保存
	$utf8name = "$fname".".save.utf8.csv";
	$fpSave = fopen($utf8name, "w");
	fputs($fpSave, "\xEF\xBB\xBF");	//	BOM
	foreach($csv as $r) fputcsv($fpSave, $r);
	fclose($fpSave);
	//	SJISに変換。
	$save = mb_convert_encoding(file_get_contents($utf8name), "SJIS", "UTF-8");
	file_put_contents($fname, $save);
*/
}

//	アップロードされたファイルを保管する関数
function upload($type){
	global $record, $dataFolder;
	if (isset($_FILES[$type])){
		//	printArray($_FILES[$type]);
		$ext = strrchr($_FILES["$type"]["name"], ".");
		$base = "$dataFolder/" . $record["oid"]. $type;
		$tmp = "$dataFolder/" . "tmp_". $record["oid"]. $type;
		$name = $base . $ext;
		$old = glob("$base.*");
		foreach($old as $f) unlink($f);
		move_uploaded_file($_FILES["$type"]["tmp_name"], $tmp);
		if (file_exists($tmp)){
			rename($tmp, $name);
			return true;
		}
	}
	return false;
}

//	「現在のファイル」というファイルへのリンクを表示する関数。
function linkToFile($type){
	global $record, $dataFolder;
	if (sizeof(glob("$dataFolder/".$record["oid"].$type.".*"))>0){
		?>
		<a href="<?php echo $_SERVER['REQUEST_URI']?>&file=<?php echo $type?>">現在のファイル</a>
		<?php
	}
}

//	配列の中身を表示する関数。デバッグ用
function printArray($a){
	if (is_array($a)){
		foreach($a as $k=>$v){
			echo "$k ";
			printArray($v);
		}
	}else{
		echo " => $a<br>\n";
	}
}

//	oidに対応するcidを得る。
function getCid($oid){
	global $cidSalt;
	return str_replace(".", "_", crypt($oid, $cidSalt));
}
//	CSVの一行目からKeyを探してカラム位置を返す。
function getPosFromKey($key, $csv){
	if(!is_array(@$csv[0])) return -1;
	foreach($csv[0] as $k=>$v){
		if($v==$key) return $k;
	}
	return -1;
}
//	cidと等しいレコードを返す。
function getRecord($cid, $csv){
	$oidPos = getPosFromKey("oid", $csv);
	$record=NULL;
	$row=-1;
	if(!is_array(@$csv)) return NULL;
	foreach($csv as $key => $r){
		$c = getCid($r[$oidPos]);
		if ($c == $cid){
			if ($record){
				//	oidが異なってもcidが同じになる危険性がないことは、checkcid.phpで確認しておく。
				echo "同一のIDを持つレコードが複数存在しました。実行委員にご連絡ください。<br>";
				exit();
			}
			$record = $r;
			$row = $key;
		}
		//	次のコメントを解除すると、cidの一覧を表示します。
		//	echo $r[$oidPos]. " => $cid<br>\n";
	}
	if (!$record) return NULL;
	$rv = array();
	foreach($csv[0] as $k => $v){
		$rv[$v] = $record[$k];
	}
	$rv['row'] = $row;
	return $rv;
}
function saveRecord(&$csv, $record){
	$row = $record['row'];
	foreach($csv[0] as $col => $key){
		$csv[$row][$col] = $record[$key];
		//	echo "csv[$row][$col] = ". $record[$key]. "<br>";
	}
}
//	ロック・アンロック
function lock(){
	global $fpLock, $dataFolder;
	$fpLock = @fopen("$dataFolder/lock.txt", "w");
	$count = 0;
	while(!$fpLock){
		//	echo 'lock() .. fopen("$dataFolder/lock.txt", "w") failed.<br>\n';
		usleep(100*1000);
		$fpLock = @fopen("$dataFolder/lock.txt", "w");
		$count ++;
		if ($count++ > 50){	//	5秒待つ
			?>
				<html>
				<meta http-equiv="content-type" content="text/html; charset=EUC-JP">
				<body>
					現在、サーバが混雑しているため更新ができません。<br>
					しばらく（5秒程度）待ってから、
					Webブラウザの「再読み込み」または「更新」ボタンを押してみてください。
				</body>
				</html>
			<?php
			exit();
		}
	}
	flock($fpLock, LOCK_EX);
}
function unlock(){
	global $fpLock;
	if ($fpLock){
		flock($fpLock, LOCK_UN);
		fclose($fpLock);
	}
	$fpLock = NULL;
}
?>
