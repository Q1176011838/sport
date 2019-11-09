<?php


require_once "../tool.php";


$data = file_get_contents('php://input');

$message = json_decode($data,true);

//$message['mailbox'] = 'root9';
//$message['friend_mail'] = 'root10';

$id = reid($message['mailbox']);
$friend_id = reid($message['friend_mail']);


//addfriend($id,$friend_id);
//addfriend($friend_id,$id);
del_friend($id,$friend_id);
del_friend($friend_id,$id);

	function del_friend($userid,$friend_id){
		$db = conn();
		$sql = 'select friend from userdata where id = :userid';
		$stmt = $db->prepare($sql);
		$stmt->execute([':userid'=>$userid]);
		$res = $stmt->fetch();
		parse_str($res[0],$g);
		$friend = null;
		foreach($g as $key=>$content){
			if($key!=$friend_id){
				$friend = $friend."&".$key;
			}
		}
		
		$sql = 'update userdata set friend = :friend where id = :userid';
		$stmt = $db->prepare($sql);
		$stmt->execute([':friend'=>$friend,':userid'=>$userid]);
	}

	function addfriend($userid,$friend_id){
		$db = conn();
		
		
		$sql = 'select friend from userdata where id = :userid';
		$stmt = $db->prepare($sql);
		$stmt->execute([':userid'=>$userid]);
		$res = $stmt->fetch();
		$friends = $res[0]."&".$friend_id;
		
		$sql = 'update userdata set friend = :friends where id = :userid';
		$stmt = $db ->prepare($sql);
		$stmt->execute([':friends'=>$friends,':userid'=>$userid]);
	}