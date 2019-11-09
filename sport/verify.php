<?php

//密码正确返回1，错误返回0
require_once "tool.php";
$db = conn();



//............
$data = file_get_contents('php://input');

$reg = '#{"username":"(?<mailbox>.*?)","userpassword":"(?<password1>.*?)"}#';
preg_match($reg,$data,$mat);
$message['username0'] = $mat[1];
$message['password1'] = $mat[2];
//...........


$sql = "select * from account where account =:account";
	
$stmt = $db->prepare($sql);

$stmt->execute([':account'=>$message['username0']]);

$res = $stmt->fetch();

if($res[password]==null){
	echo '0';
}
else{
	if($res[password]!=md5($message['password1'])){
		echo '0';
	}
	else{
		echo '1';
	}
}
