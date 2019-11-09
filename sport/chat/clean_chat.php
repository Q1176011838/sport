<?php



header("Content-Type: text/html;charset=utf-8");
require_once "../tool.php";
$db = conn();


$data = file_get_contents('php://input');
$reg = '#{"mailbox":"(?<mailbox>.*?)","friend_mail":"(?<friend_mail>.*?)"}#';
preg_match($reg,$data,$mat);

$message['mailbox'] = $mat[1];
$message['friend_mail'] = $mat[2];

$id = reid($message['mailbox']);
$friend_id = reid($message['friend_mail']);


//消息标志为 已读

$sql = 'update '.$id.'chat set is_read = 1 where is_read = 0 and sendid = :sendid';
$stmt = $db->prepare($sql);
$stmt->execute([':sendid'=>$friend_id]);
