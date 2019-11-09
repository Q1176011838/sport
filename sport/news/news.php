<?php

 $url = "http://sports.sina.com.cn/";
 $html = file_get_contents($url);
 
 $reg = '#backupData: {([\s\S]*?)"pic"#';
 preg_match($reg,$html,$mat);
 $data = $mat[0];
 
 $reg = '#"url":"(.*?)"[\s\S]*?"title":"(.*?)"[\s\S]*?"thumb":"(.*?)"#';
 preg_match_all($reg,$data,$mat);
 $mat[0] = "0";
 echo json_encode($mat);
?>