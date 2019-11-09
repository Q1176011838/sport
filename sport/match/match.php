
<?php
header('Content-Type:text/html;Charset=utf-8');
//................................................
//输出0，表示已经在匹配队列中，输出1表示输入匹配信息成功


$data = file_get_contents('php://input');




$reg = '#{"mailbox":"(?<mailbox>.*?)","ball":(?<ball>.*?),"level":(?<level>.*?),"num":(?<num>.*?)}#';
preg_match($reg,$data,$mat);

$message['mailbox'] = $mat[1];
$message['ball'] = $mat[2];
$message['level'] = $mat[3];
$message['num'] = $mat[4];




//............
//测试数据
/*
$message['mailbox'] = '150377';
$message['ball'] = 2;
$message['level'] = 1;
$message['num'] = 3;
*/












require_once "../tool.php";
$db = conn();


//获取该邮箱id
$message['id'] = reid($message['mailbox']);
	//检测用户是否已在匹配成功队列中
	$sql = 'select count(*) from sumatch where id = :id';
	$stmt = $db->prepare($sql);
	$stmt->execute([':id'=>$message['id']]);
	$res = $stmt->fetch();
	if($res[0]!=0){
		echo 0;
	}
	else{
		//检验该用户是否已经在匹配序列中
		$sql = 'select count(*) from matchs where userid  = :id';
		$stmt = $db->prepare($sql);
		$stmt->execute([':id'=>$message['id']]);
		$res = $stmt->fetch();
		if($res[0]!=0){
			echo 0;
		}
		else{
			//将该用户信息存入数据库
			$sql = 'insert into matchs(userid,ball,sort,num,flag) values(:id,:ball,:sort,:num,1)';
			$stmt = $db->prepare($sql);
			$stmt->execute([':id'=>$message['id'],':ball'=>$message['ball'],':sort'=>$message['level'],':num'=>$message['num']]);
			echo 1;
		}
	}
	
	

?>