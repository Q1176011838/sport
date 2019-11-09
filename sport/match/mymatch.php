<?php

//

$data = file_get_contents('php://input');
$reg = '#{"mailbox":"(?<mailbox>.*?)"}#';
preg_match($reg,$data,$mat);

$message['mailbox'] = $mat[1];





//测试专用数据


//$message['mailbox'] = "150377";

//.....................
require_once "../tool.php";
$db = conn();


//获取该邮箱id
$message['id'] = reid($message['mailbox']);






//先判断是否已经匹配成功
$sql = 'select count(*) from sumatch where id = :id';
$stmt = $db->prepare($sql);
$stmt->execute([':id'=>$message['id']]);
$res = $stmt->fetch();
if($res[0]==0){
	
	//获取信息
	$sql  = 'select * from matchs where userid = :id';
	$stmt = $db->prepare($sql);
	$stmt->execute([':id'=>$message['id']]);
	$res = $stmt->fetch();

	$message['ball'] = $res['ball'];
	$message['level'] = $res['sort'];
	$message['num'] = $res['num'];
	
	//进行匹配
		$sql = 'select count(*) from matchs where ball = :ball and sort = :level and num = :num';
		$stmt = $db->prepare($sql);
		$stmt->execute([':ball'=>$message['ball'],':level'=>$message['level'],':num'=>$message['num']]);
		$res = $stmt->fetch();
		$num = $res[0];
		if($num<$message['num']){
			//没有匹配成功，返回已经匹配到的人
			$sql = 'select userid from matchs where ball = :ball and sort = :level and num = :num';
			$stmt = $db->prepare($sql);
			$stmt->execute([':ball'=>$message['ball'],':level'=>$message['level'],':num'=>$message['num']]);
			$res = $stmt->fetchAll();
			for($i=0;$i<$num;$i++){
				$data = $res[$i];
				//$data[0]组成返回的数组
				if($data[0]==$message['id']){
					continue;
				}
				//获取头像
				$sql = 'select pic from headpic where id = :id';
				$stmt = $db->prepare($sql);
				$stmt->execute([':id'=>$data[0]]);
				$ress = $stmt->fetch();
				$pic[$i] = $ress[0];
				
				//获取名字
				$sql = 'select name from userdata where id = :id';
				$stmt = $db->prepare($sql);
				$stmt->execute([':id'=>$data[0]]);
				$ress = $stmt->fetch();
				$name[$i] = $ress[0];
				
				//获取邮箱
				$sql = 'select account from account where id = :id';
				$stmt = $db->prepare($sql);
				$stmt->execute([':id'=>$data[0]]);
				$ress = $stmt->fetch();
				$account[$i] = $ress[0];
			}
			$ans['pic'] = $pic;
			$ans['name'] = $name;
			$ans['account'] = $account;
			echo json_encode($ans) ;
		}
		else{	
			$sql = 'select id from matchs where userid = :id';
			$stmt = $db->prepare($sql);
			$stmt->execute([':id'=>$message['id']]);
			$res = $stmt->fetch();
			$mark = $res[0];
			
			//匹配成功，保存到成功的表，
			$sql = 'select userid from matchs where ball = :ball and sort = :level and num = :num';
			$stmt = $db->prepare($sql);
			$stmt->execute([':ball'=>$message['ball'],':level'=>$message['level'],':num'=>$message['num']]);
			$res = $stmt->fetchAll();
			//print_r($res);
			
			for($i=0;$i<$message['num'];$i++){
				$data = $res[$i];
				$sql = 'insert into sumatch(id,sunum) values(:id,:sunum)';
				$stmt = $db->prepare($sql);
				$stmt->execute([':id'=>$data[0],':sunum'=>$mark]);
				
				//返回头像和名字..................
				if($data[0]==$message['id']){
					//删除在表matchs中的数据
					$sql = 'delete from matchs where userid = :id';
					$stmt = $db->prepare($sql);
					$stmt->execute([':id'=>$data[0]]);
					continue;
				}
				//获取头像
				$sql = 'select pic from headpic where id = :id';
				$stmt = $db->prepare($sql);
				$stmt->execute([':id'=>$data[0]]);
				$ress = $stmt->fetch();
				$pic[$i] = $ress[0];
				
				//获取名字
				$sql = 'select name from userdata where id = :id';
				$stmt = $db->prepare($sql);
				$stmt->execute([':id'=>$data[0]]);
				$ress = $stmt->fetch();
				$name[$i] = $ress[0];
				
				//获取邮箱
				$sql = 'select account from account where id = :id';
				$stmt = $db->prepare($sql);
				$stmt->execute([':id'=>$data[0]]);
				$ress = $stmt->fetch();
				$account[$i] = $ress[0];
				
				//删除在表matchs中的数据
				$sql = 'delete from matchs where userid = :id';
				$stmt = $db->prepare($sql);
				$stmt->execute([':id'=>$data[0]]);
				
			}
			$ans['pic'] = $pic;
			$ans['name'] = $name;
			$ans['account'] = $account;
			echo json_encode($ans);
		}
	
}








//
else{
	
	$sql  = 'select sunum from sumatch where id = :id';
	$stmt = $db->prepare($sql);
	$stmt->execute([':id'=>$message['id']]);
	$res = $stmt->fetch();
	$mark = $res[0];
	
	//获取人数
	$sql = 'select count(*) from sumatch where sunum = :sunum';
	$stmt = $db->prepare($sql);
	$stmt->execute([':sunum'=>$mark]);
	$res = $stmt->fetch();
	$message['num'] = $res[0];
	
	
	$sql = 'select id from sumatch where sunum = :sunum';
	$stmt = $db->prepare($sql);
	$stmt->execute([':sunum'=>$mark]);
	$res = $stmt->fetchAll();
	for($i=0;$i<$message['num'];$i++){
		$data = $res[$i];
		//$data[0]组成返回的数组
		if($data[0]==$message['id']){
			continue;
		}
		//获取头像
		$sql = 'select pic from headpic where id = :id';
		$stmt = $db->prepare($sql);
		$stmt->execute([':id'=>$data[0]]);
		$ress = $stmt->fetch();
		$pic[$i] = $ress[0];
		//获取名字
		$sql = 'select name from userdata where id = :id';
		$stmt = $db->prepare($sql);
		$stmt->execute([':id'=>$data[0]]);
		$ress = $stmt->fetch();
		$name[$i] = $ress[0];
		//获取邮箱
		$sql = 'select account from account where id = :id';
		$stmt = $db->prepare($sql);
		$stmt->execute([':id'=>$data[0]]);
		$ress = $stmt->fetch();
		$account[$i] = $ress[0];
	}
	$ans['pic'] = $pic;
	$ans['name'] = $name;
	$ans['account'] = $account;
	echo json_encode($ans) ;
}












?>