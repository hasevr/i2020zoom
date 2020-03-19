<?php
$webhook_url = 'http://localhost/i2020/int/zoomEvent.php';

if (@$_POST["send"]){
	$options = array(
		'http' => array(
		  'method' => 'POST',
		  'header' => 'Content-Type: application/json',
		  'content' => $_POST["msg"],
		)
	);
	//echo $options["http"]["content"], "<br>";
	$response = file_get_contents($webhook_url, false, stream_context_create($options));
	echo "res for test.php (", $response, ")<br>";
}
?>
<form enctype="multipart/form-data" method="POST" action="<?php echo $_SERVER["PHP_SELF"];?>">
<textarea name="msg" cols=120 rows=40>
<?php
	echo @$_POST["msg"];
?>
</textarea><br>
<input type=submit name="send" value="  WebHookを送信  "><br>

</form>
