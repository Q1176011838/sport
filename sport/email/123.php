<?php
header('Content-Type:text/html;Charset=utf-8');  
require_once("tool.php");
$addre = file_get_contents('php://input');
$addre = substr($addre,12,-2);
$addre = str_replace("%40","@",$addre);


$number=GetRandStr(6);
$content = 'baller:'.'<br/>'.'您的六位验证码为：'.$number.'<br/>如果这不是您的操作，请忽略此邮件';
$flag = sendMail($addre,'验证码',$content);


if($flag){
    echo json_encode($number);
}else{
    echo "不能直接访问，请提供address...";
}