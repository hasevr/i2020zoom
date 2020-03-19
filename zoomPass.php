<?php
//	zoom APIでユーザを更新する
$curl_opt = array(
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 60,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "PUT",
  CURLOPT_HTTPHEADER => array(
    "authorization: Bearer ここに、zoomのJWT(JSON Web Token)を書く",
    "content-type: application/json"
  )
);

$curl = curl_init();
curl_setopt_array($curl, $curl_opt);

//	パスワード更新
for($i=1; $i <= 76 ; ++$i){
	$email = sprintf('r%02d@gs.haselab.net', $i);
	$url = "https://api.zoom.us/v2/users/". $email. "/password";
	curl_setopt($curl, CURLOPT_URL, $url);
	$data = '{"password": "Zoom2020"}';
	echo "<strong>USER" . $i . "</strong>: $url<br>";
	echo $data . "<br>";
	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	$response = curl_exec($curl);
	$err = curl_error($curl);
	echo "<br>res:<br>";
	var_dump(@$response);
	echo "<br>";
	$res = json_decode(@$response, true);
}
