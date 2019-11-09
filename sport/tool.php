<?php
function get($name){
	return isset($_GET[$name])?$_GET[$name]:"";
}
function post($name){
	return isset($_POST[$name])?$_POST[$name]:"";
}
function  conn(){
	$dns = "mysql:host=localhost;dbname=sport";
	return new PDO($dns, "root","root");
}


function reid($mailbox){
	$db = conn();
	$sql = 'select id from account where account = :account';
	$stmt = $db->prepare($sql);
	$stmt->execute([':account'=>$mailbox]);
	$res = $stmt->fetch();
	return $res[0];
}

function get_name($id){
	$db = conn();
	$sql = 'select name from userdata where id = :id';
	$stmt = $db->prepare($sql);
	$stmt->execute([':id'=>$id]);
	$res = $stmt->fetch();
	return $res[0];
}
	
	
//返回用户头像
function get_pic($id){
	$db = conn();	
	$sql = 'select pic from headpic where id = :id';
	$stmt = $db->prepare($sql);
	$stmt->execute([':id'=>$id]);
	$res = $stmt->fetch();
	return $res[0];
}

?>