<?php


//....

$data = file_get_contents('php://input');
$reg = '#{"mailbox":"(?<mailbox>.*?)"}#';
preg_match($reg,$data,$mat);

$message['mailbox'] = $mat[1];












//.............................

require_once "../tool.php";
$db = conn();

//获取该邮箱id
$message['id'] = reid($message['mailbox']);

$sql = 'select count(*) from sumatch where id = :id';
$stmt = $db->prepare($sql);
$stmt->execute([':id'=>$message['id']]);
$res = $stmt->fetch();
if($res[0]==0){
	
	$sql = 'delete from matchs where userid = :id';
	$stmt = $db->prepare($sql);
	$stmt->execute([':id'=>$message['id']]);
}
else{
	$sql = 'delete from sumatch where id = :id';
	$stmt = $db->prepare($sql);
	$stmt->execute([':id'=>$message['id']]);
	
	
}







?>