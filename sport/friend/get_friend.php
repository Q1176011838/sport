<?php


$data = file_get_contents('php://input');

$reg = '#{"mailbox":"(?<mailbox>.*?)"}#';
preg_match($reg,$data,$mat);

$message['mailbox'] = $mat[1];



require_once "../tool.php";

$userid = reid($message['mailbox']);

$db = conn();

$sql = 'select friend from userdata where id = :userid';

$stmt = $db->prepare($sql);
$stmt->execute([':userid'=>$userid]);
$res = $stmt->fetch();

$friends = $res[0];


parse_str($friends,$g);


$friends = array();
$i = 0;
foreach($g as $key => $content){
//	$friend = array();
	
	//获得姓名
	$sql = 'select name from userdata where id = :id';
	$stmt = $db->prepare($sql);
	$stmt->execute([':id'=>$key]);
	$res = $stmt->fetch();
	$friend['name'] = $res[0];
	//获得头像
	$sql = 'select pic from headpic where id = :id';
	$stmt = $db->prepare($sql);
	$stmt->execute([':id'=>$key]);
	$res = $stmt->fetch();
	$friend['pic'] = $res[0];
	//获得邮箱
	$sql = 'select account from account where id = :id';
	$stmt = $db->prepare($sql);
	$stmt->execute([':id'=>$key]);
	$res = $stmt->fetch();
	$friend['mailbox'] = $res[0];
	
	$friends[$i] = $friend;
	//print_r($friend);
	$i++;
}

echo json_encode($friends);
