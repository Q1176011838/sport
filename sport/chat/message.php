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


//$id = 30;
//$friend_id = 31;

$last_time = 0;


//获取消息内容
$sql = 'select content,created_at,is_send from '.$id.'chat where sendid = :sendid ';
$stmt = $db->prepare($sql);
$stmt->execute([':sendid'=>$friend_id]);
$res = $stmt->fetchAll();
//整理
$content = array();
$current_time = date("Y-m-d");
foreach($res as $ress){
	$mess['time'] = change_time($ress[1],$last_time);
	$last_time = strtotime($ress[1]);
	$mess['content'] = $ress[0];
	$mess['is_send'] = $ress[2];
	$content[] = $mess;
}

$chat['message'] = $content;

//获取头像
$chat['pic'] = get_pic($friend_id);
$chat['friend_mail'] = $message['friend_mail'];

//未读消息变为已读

$sql = 'update '.$id.'chat set is_read = 1 where is_read = 0 and sendid = :sendid';
$stmt = $db->prepare($sql);
$stmt->execute([':sendid'=>$friend_id]);

//print_r($chat);
echo json_encode($chat);

function getTimeWeek($time, $i = 0) {
  $weekarray =  array("日","一", "二", "三", "四", "五", "六");
  $oneD = 24 * 60 * 60;
  return "周" . $weekarray[date("w", $time + $oneD * $i)];
}

function change_time($ctime,$last_time){
	$current_time = date("Y-m-d");
	if(strtotime($ctime)-$last_time<=180){
		return 0;
	}
	else if(strtotime($ctime)-time()<=604800){
		//echo strtotime($ress[1])."<br/>";
		if(substr($ctime,0,10)==$current_time){
			return substr($ctime,-8,-3);
		}
		else{
			return getTimeWeek(strtotime($ctime)).substr($ctime,-8,-3);
		}
	}
	else{
		return substr($ctime,0,-3);
	}

}