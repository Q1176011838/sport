<?php
require_once "tool.php";
$db = conn();
//1.用户名为空	2.密码不一致 3.密码为空 4.用户名已存在 5.成功


//获取信息
$data = file_get_contents('php://input');
$reg = '#{"mailbox":"(?<mailbox>.*?)","password1":"(?<password1>.*?)","password2":"(?<password2>.*?)","idname":"(?<idname>.*?)"}#';
preg_match($reg,$data,$mat);

$message['username0'] = $mat[1];
$message['password1'] = $mat[2];
$message['password2'] = $mat[3];
$message['name'] = $mat[4];



//测试专用信息
/*
$message['username0'] = 'root10';
$message['password1'] = 'root';
$message['password2'] = 'root';
$message['name'] = '123';
*/









//............................
if($message['username0']==null){
	echo "1";
}
else if($message['password1']!=$message['password2']){
	echo "2";
}
else if($message['password1']==null){
	echo "3";
}
else{
	$sql = 'select count(*) from account where account =:account';

	$stmt = $db->prepare($sql);

	$stmt->execute([':account'=>$message['username0']]);
	$res = $stmt->fetch();
	if($res[0]==1){
		echo "4";
	}
	else{
		$sql = 'insert into account(account,password) values(:account,:password)';
		$stmt = $db->prepare($sql);
		$stmt->execute([':account'=>$message['username0'],':password'=>md5($message['password1'])]);
		//个人信息创建
		//............
		
		//提取id
		$message['id'] = reid($message['username0']);
		
		//提取id结束
		$sql = 'insert into userdata(id,sex,level,introduce,name) values(:id,0,0,"这个人超勤快，但是什么都没留下",:name)';
		$stmt = $db->prepare($sql);
		$stmt->execute([':id'=>$message['id'],':name'=>$message['name']]);
		
		//设置头像	
		$sql = 'insert into headpic(id,pic) values(:id,"https://www.achaonihao.com/sport/picture/headimg.jpg")';
		$stmt = $db->prepare($sql);
		$stmt->execute([':id'=>$message['id']]);
		
		//创建个人聊天的表
		$sql = 'create table '.$message['id'].'chat (id int primary key auto_increment not null,sendid int,content varchar(255),created_at timestamp,is_send int ,is_read int)';
		$stmt = $db->prepare($sql);
		$stmt->execute();
		
		//............
		//个人信息创建完毕
		echo "5";
	}
}

