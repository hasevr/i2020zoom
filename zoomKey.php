<?php
//	zoom APIでユーザ一覧を取得し、ホストキーを表示する。
$curl_opt = array(
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 60,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "authorization: Bearer ここに、zoomのJWT(JSON Web Token)を書く",
    "content-type: application/json"
  )
);

$curl = curl_init();
curl_setopt_array($curl, $curl_opt);

//ユーザをリスト
$url = "https://api.zoom.us/v2/users/?page_size=300";
curl_setopt($curl, CURLOPT_URL, $url);
$response = curl_exec($curl);
$err = curl_error($curl);
$res = json_decode(@$response, true);
$users = $res["users"];
foreach($users as $user){
	$url = "https://api.zoom.us/v2/users/" . $user["id"];
	curl_setopt($curl, CURLOPT_URL, $url);
	$response = curl_exec($curl);
	$err = curl_error($curl);
	$res = json_decode(@$response, true);
	echo $user["first_name"], "&nbsp;\t";
	echo $user["last_name"], "&nbsp;\t";
	echo $user["email"], "&nbsp;\t";
	echo "key:\t", $res["host_key"];
	echo "<br>";
}
