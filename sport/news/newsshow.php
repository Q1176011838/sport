<?php


$data = file_get_contents('php://input');
$reg = '#{"src":"(?<mailbox>.*?)"}#';
preg_match($reg,$data,$mat);

$url = $mat[1];

//http://sports.sina.com.cn/china/j/2018-10-08/doc-ihkvrhpt1251521.shtml
//$url = "http://sports.sina.com.cn/g/laliga/2018-10-08/doc-ifxeuwws2063944.shtml";
$html = file_get_contents($url);

//<!--/video-list-->([\s\S]*?)<!-- 正文 end -->
$reg = '#<div class="article"([\s\S]*?)<!-- 非定向300\*250按钮#';
preg_match($reg,$html,$mat);
//print_r($mat);

$html = '<div class="article"'.$mat[1];
//echo $html;
echo json_encode($html);
