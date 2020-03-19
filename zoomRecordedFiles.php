<?php
set_time_limit(0);

$token = 'ここに、zoomのJWT(JSON Web Token)を書く';

//	zoom APIで録画ファイルをリストする
$curl_opt = array(
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 60,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "authorization: Bearer " . $token,
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
sort($emails);

$csv="";
$cmd="";


function mcomp($a, $b){
	$c = strcmp($a["topic"], $b["topic"]);
	if ($c == 0){
		$c = strcmp($a["start_time"], $b["start_time"]);
	}
	return $c;
}

$fbases = array();
$wroteHeader = false;
foreach($emails as $email){
	curl_setopt_array($curl, $curl_opt);
	$url = "https://api.zoom.us/v2/users/$email/recordings?page_size=300&from=2020-03-06&to=2020-03-12";
	curl_setopt($curl, CURLOPT_URL, $url);
	$response = curl_exec($curl);
	$err = curl_error($curl);
	//echo "<br>res:<br>";
	//var_dump(@$response);
	//echo "<br>";
	$res = json_decode(@$response, true);
	$meetings = $res["meetings"];
	if (!$meetings) continue;
	usort($meetings, "mcomp");
	
	if (!$wroteHeader){
		$sep="";
		foreach($meetings[0] as $k => $v){
			$csv .=  $sep. $k;
			$sep=",";
		}
		$csv .=  "\n";
		$wroteHeader = true;
	}

	foreach($meetings as $meeting){
		$sep="";
		foreach($meeting as $k => $v){
			if ($k != "recording_files"){
				$csv .=  $sep. $v;
			}else{
				$fbase = str_replace( array('&', '"', '/', '\\', '[', ']', ':', ';', '|', '='), 
					array('＆', '”', '／', '￥＾', '［', '］', '：', '；', '｜', '＝'), 
					$meeting["topic"]);
				if (in_array($fbase, $fbases)){
					for($i=1; in_array($fbase.$i, $fbases); $i++);
					$fbase = $fbase.$i;
				}
				//	ファイル名確定、追加
				$fbases[] = $fbase;
				$csv .=  $sep . $fbase;
				foreach($v as $rec){
					$fn = $fbase . '.' . $rec["file_type"];
					$url = $rec["download_url"];
					$cmd .= 'wget -O"' . $fn . '" ' . $url . '?access_token=' . $token . "\n";
				}
			}
			$sep = ",";
		}
		$csv .= "\n";
	}
}
file_put_contents("record.csv", $csv);
file_put_contents("record.bat", $cmd);
