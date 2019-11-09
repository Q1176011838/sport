<?php
require_once "../tool.php";
$db = conn();



//....
//{"mailbox":"1371214566@qq.com"}
$data = file_get_contents('php://input');
$reg = '#{"mailbox":"(?<mailbox>.*?)"}#';
preg_match($reg,$data,$mat);
$account = $mat[1];
//....



$sql = 'select id from account where account = :account';
$stmt = $db->prepare($sql);
$stmt->execute([':account'=>$account]);
$res = $stmt->fetchAll();
$data = $res[0];

//...........
$id = $data['id'];
//.........

$sql = 'select name from userdata where id = :id';
$stmt = $db->prepare($sql);
$stmt->execute([':id'=>$id]);
$res = $stmt->fetchAll();
$data = $res[0];
$message['name'] = $data['name'];
//..........

$sql = 'select pic from headpic where id = :id';
$stmt = $db->prepare($sql);
$stmt->execute([':id'=>$id]);
$res = $stmt->fetchAll();
$data = $res[0];
$message['pic'] = $data['pic'];
echo json_encode($message);
?>