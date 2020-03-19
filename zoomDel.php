<?php
$url=$_SERVER['REQUEST_URI'];
if (@$_POST["delete"]){
	$array = explode("\n", $_POST["text"]);
	$array = array_map('trim', $array);
	//	zoom APIで会議を削除する
	$curl_opt = array(
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 60,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "DELETE",
	  CURLOPT_HTTPHEADER => array(
	    "authorization: Bearer ここに、zoomのJWT(JSON Web Token)を書く",
	    "content-type: application/json"
	  )
	);

	$curl = curl_init();
	curl_setopt_array($curl, $curl_opt);
	
	//	会議の作成
	foreach($array as $id){
		echo "Delete $id <br>";
		curl_setopt($curl, CURLOPT_URL, "https://api.zoom.us/v2/meetings/". $id);
		$response = curl_exec($curl);
		$err = curl_error($curl);
		echo "res:<br>";
		var_dump($response);
	}
}
?>
<form enctype="multipart/form-data" method="POST" action="<?php echo $url;?>">
<textarea name="text" rows=30 cols=40><?php echo @$_POST["text"]; ?></textarea><br>
<input type=submit name="delete" value="  ミーティングを削除  "><br>
</form>
