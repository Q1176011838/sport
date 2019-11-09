<?php

require_once "../tool.php";


$data = file_get_contents('php://input');

$message = json_decode($data,true);

$id = reid($message['mailbox']);
$friend_id = reid($message['friend_mail']);


is_friend($id,$friend_id);

function is_friend($id,$friend_id){
	
	$db = conn();
	$sql = 'select friend from userdata where id = :userid';
	$stmt = $db->prepare($sql);
	$stmt->execute([':userid'=>$id]);
	$res = $stmt->fetch();
	parse_str($res[0],$g);
	$friend = array_keys($g);
	if(in_array($friend_id,$friend)){
		echo 1;
	}
	else{
		echo 0;
	}
}