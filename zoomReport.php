<?php
set_time_limit(0);
foreach(@$_GET as $k => $v){
	echo "$k = $v<br>";
}

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
$url = "https://api.zoom.us/v2/users?page_size=300";
curl_setopt($curl, CURLOPT_URL, $url);
$response = curl_exec($curl);
$err = curl_error($curl);
$res = json_decode(@$response, true);
$emails = array();
foreach($res["users"] as $user){
	$emails[] = $user["email"];
}

$csv="";
$cmd="";

$fbases = array();
$emailFound = false;
foreach($emails as $email){
	if (!$emailFound && @$_GET["email"]){
		if ($_GET["email"] == $email){
			$emailFound = true;
		}else{
			continue;
		}
	}
	curl_setopt_array($curl, $curl_opt);
	$url = "https://api.zoom.us/v2/report/users/$email/meetings?page_size=300&from=2020-03-18&to=2020-03-18";
	curl_setopt($curl, CURLOPT_URL, $url);
	$response = curl_exec($curl);
	if (curl_getinfo($curl, CURLINFO_HTTP_CODE) != 200){
		echo "error at email = $email <br>";
		continue;
	}
	$err = curl_error($curl);
	//echo "<br>res:<br>";
	//var_dump(@$response);
	//echo "<br>";
	$room = substr($email, 0, strpos($email, "@"));
	file_put_contents("reports/" .$room. ".json", $response);
	$res = json_decode(@$response, true);
	$muuidFound = false;
	$count = 1;
	foreach($res["meetings"] as $meeting){
		$muuid = $meeting["uuid"];
		//if (strpos($muuid, "/") === FALSE) continue;
		if (!$muuidFound && @$_GET["muuid"]){
			if ($_GET["muuid"] == $muuid){
				$muuidFound = true;
			}else{
				continue;
			}
		}
		$mid = sprintf("%09d", $meeting["id"]);
		$muuidEnc = urlencode($muuid);
		$url = "https://api.zoom.us/v2/report/meetings/$muuidEnc/participants?page_size=300";
		curl_setopt($curl, CURLOPT_URL, $url);
		sleep(1);
		$response = curl_exec($curl);
		if (curl_getinfo($curl, CURLINFO_HTTP_CODE) != 200){
			echo "error: at email=$email&muuid=$muuid<br>";
			echo "?". urlencode("email=$email&muuid=$muuid"). "<br>";
			var_dump($response);
			echo "<br>";
			continue;
		}
		$err = curl_error($curl);
		$orgCh = array('¥', '/', ':', '*', '?', '"', '<', '>', '|');
		$repCh = array('￥', '／', '：', '＊', '？', '”', '＜', '＞', '｜');
		file_put_contents("reports/" .$room. "_" .$mid. "_" .$count. "_" .str_replace($orgCh, $repCh, $muuid). ".json", $response);
		$count ++;
	}
}
