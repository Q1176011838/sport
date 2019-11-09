<?php

//0.图片不合法 1.成功 2.失败 

require_once "../tool.php";
$db = conn();
//$message['mailbox'] = '1371214566@qq.com';


$file = $_FILES['file'];
$name = $file['name'];

$message['mailbox'] = post('mailbox');



$pic = 'https://www.achaonihao.com/weixin/mingjie/shopping/picture/'.$name;

$type = strtolower(substr($name,strrpos($name,'.')+1));
$allow_type = array('jpg','png','gif','webp');
if(!in_array($type,$allow_type)){
	echo 0;
}
else{
	
	$message['id'] = reid($message['mailbox']);
	
	//更改图片地址
	$sql = 'update headpic set pic = "https://www.achaonihao.com/sport/picture/'.$message['id'].'.'.$type.'" where id = :id';
	$stmt = $db->prepare($sql);
	$stmt->execute([':id'=>$message['id']]);
		
	
	$upload_path = "../picture/".$message['id'].".".$type;
	echo $$upload_path;
	if(move_uploaded_file($_FILES['file']['tmp_name'],$upload_path)){
		echo 1;
	}
	else{
		echo 2;
	}
	
	
}








?>