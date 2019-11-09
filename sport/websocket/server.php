<?php
error_reporting(E_ALL ^ E_NOTICE);
ob_implicit_flush();
header("Content-Type: text/html;charset=utf-8");
//地址与接口，即创建socket时需要服务器的IP和端口
  $sk=new Sock('192.168.1.172',9100);
//对创建的socket循环进行监听，处理数据
$sk->run();
 
//下面是sock类
class Sock{
    public $sockets; //socket的连接池，即client连接进来的socket标志
    public $users;   //所有client连接进来的信息，包括socket、client名字等
    public $master;  //socket的resource，即前期初始化socket时返回的socket资源
     
    private $sda=array();   //已接收的数据
    private $slen=array();  //数据总长度
    private $sjen=array();  //接收数据的长度
    private $ar=array();    //加密key
    private $n=array();
     
    public function __construct($address, $port){
 
        //创建socket并把保存socket资源在$this->master
        $this->master=$this->WebSocket($address, $port);
 
        //创建socket连接池
        $this->sockets=array($this->master);
    }
     
    //对创建的socket循环进行监听，处理数据
    function run(){
        //死循环，直到socket断开
        while(true){
            $changes=$this->sockets;
            $write=NULL;
            $except=NULL;
             
            /*
            //这个函数是同时接受多个连接的关键，我的理解它是为了阻塞程序继续往下执行。
            socket_select ($sockets, $write = NULL, $except = NULL, NULL);
 
            $sockets可以理解为一个数组，这个数组中存放的是文件描述符。当它有变化（就是有新消息到或者有客户端连接/断开）时，socket_select函数才会返回，继续往下执行。
            $write是监听是否有客户端写数据，传入NULL是不关心是否有写变化。
            $except是$sockets里面要被排除的元素，传入NULL是”监听”全部。
            最后一个参数是超时时间
            如果为0：则立即结束
            如果为n>1: 则最多在n秒后结束，如遇某一个连接有新动态，则提前返回
            如果为null：如遇某一个连接有新动态，则返回
            */
            socket_select($changes,$write,$except,NULL);
            foreach($changes as $sock){
                 
                //如果有新的client连接进来，则
                if($sock==$this->master){
 
                    //接受一个socket连接
                    $client=socket_accept($this->master);
 
                    //给新连接进来的socket一个唯一的ID
                    $key=uniqid();
                    $this->sockets[]=$client;  //将新连接进来的socket存进连接池
                    $this->users[$key]=array(
                        'socket'=>$client,  //记录新连接进来client的socket信息
                        'shou'=>false       //标志该socket资源没有完成握手
                    );
                //否则1.为client断开socket连接，2.client发送信息
                }else{
                    $len=0;
                    $buffer='';
                    //读取该socket的信息，注意：第二个参数是引用传参即接收数据，第三个参数是接收数据的长度
                    do{
                        $l=socket_recv($sock,$buf,1000,0);
                        $len+=$l;
                        $buffer.=$buf;
                    }while($l==1000);
 
                    //根据socket在user池里面查找相应的$k,即健ID
                    $k = $this->search($sock);
 
                    //如果接收的信息长度小于7，则该client的socket为断开连接
                    if($len<7){
                        //给该client的socket进行断开操作，并在$this->sockets和$this->users里面进行删除
                        $this->close($k);
                        continue;
                    }
                    //判断该socket是否已经握手
                    if(!$this->users[$k]['shou']){
                        //如果没有握手，则进行握手处理
                        $this->woshou($k,$buffer);
                    }else{
                        //走到这里就是该client发送信息了，对接受到的信息进行uncode处理
                        $buffer = $this->uncode($buffer,$k);
                        if($buffer==false){
                            continue;
                        }
                        //如果不为空，则进行消息推送操作
                        $this->begin($k,$buffer);
                    }
                }
            }
             
        }
         
    }
     
    //指定关闭$k对应的socket
    function close($k){
        //断开相应socket
        socket_close($this->users[$k]['socket']);
        //删除相应的user信息
        unset($this->users[$k]);
        //重新定义sockets连接池
        $this->sockets=array($this->master);
        foreach($this->users as $v){
            $this->sockets[]=$v['socket'];
        }
        //输出日志
		print_r($this->users);
        $this->e("key:$k close");
    }
     
    //根据sock在users里面查找相应的$k
    function search($sock){
        foreach ($this->users as $k=>$v){
            if($sock==$v['socket'])
            return $k;
        }
        return false;
    }
     
    //传相应的IP与端口进行创建socket操作
    function WebSocket($address,$port){
        $server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1);//1表示接受所有的数据包
        socket_bind($server, $address, $port);
        socket_listen($server);
        $this->e('Server Started : '.date('Y-m-d H:i:s'));
        $this->e('Listening on   : '.$address.' port '.$port);
        return $server;
    }
     
     
    /*
    * 函数说明：对client的请求进行回应，即握手操作
    * @$k clien的socket对应的健，即每个用户有唯一$k并对应socket
    * @$buffer 接收client请求的所有信息
    */
    function woshou($k,$buffer){
 
        //截取Sec-WebSocket-Key的值并加密，其中$key后面的一部分258EAFA5-E914-47DA-95CA-C5AB0DC85B11字符串应该是固定的
        $buf  = substr($buffer,strpos($buffer,'Sec-WebSocket-Key:')+18);
        $key  = trim(substr($buf,0,strpos($buf,"\r\n")));
        $new_key = base64_encode(sha1($key."258EAFA5-E914-47DA-95CA-C5AB0DC85B11",true));
         
        //按照协议组合信息进行返回
        $new_message = "HTTP/1.1 101 Switching Protocols\r\n";
        $new_message .= "Upgrade: websocket\r\n";
        $new_message .= "Sec-WebSocket-Version: 13\r\n";
        $new_message .= "Connection: Upgrade\r\n";
        $new_message .= "Sec-WebSocket-Accept: " . $new_key . "\r\n\r\n";
        socket_write($this->users[$k]['socket'],$new_message,strlen($new_message));
 
        //对已经握手的client做标志
        $this->users[$k]['shou']=true;
        return true;
         
    }
     
    //解码函数
    function uncode($str,$key){
        $mask = array(); 
        $data = ''; 
        $msg = unpack('H*',$str);
        $head = substr($msg[1],0,2); 
        if ($head == '81' && !isset($this->slen[$key])) { 
            $len=substr($msg[1],2,2);
            $len=hexdec($len);//把十六进制的转换为十进制
            if(substr($msg[1],2,2)=='fe'){
                $len=substr($msg[1],4,4);
                $len=hexdec($len);
                $msg[1]=substr($msg[1],4);
            }else if(substr($msg[1],2,2)=='ff'){
                $len=substr($msg[1],4,16);
                $len=hexdec($len);
                $msg[1]=substr($msg[1],16);
            }
            $mask[] = hexdec(substr($msg[1],4,2)); 
            $mask[] = hexdec(substr($msg[1],6,2)); 
            $mask[] = hexdec(substr($msg[1],8,2)); 
            $mask[] = hexdec(substr($msg[1],10,2));
            $s = 12;
            $n=0;
        }else if($this->slen[$key] > 0){
            $len=$this->slen[$key];
            $mask=$this->ar[$key];
            $n=$this->n[$key];
            $s = 0;
        }
         
        $e = strlen($msg[1])-2;
        for ($i=$s; $i<= $e; $i+= 2) { 
            $data .= chr($mask[$n%4]^hexdec(substr($msg[1],$i,2))); 
            $n++; 
        } 
        $dlen=strlen($data);
         
        if($len > 255 && $len > $dlen+intval($this->sjen[$key])){
            $this->ar[$key]=$mask;
            $this->slen[$key]=$len;
            $this->sjen[$key]=$dlen+intval($this->sjen[$key]);
            $this->sda[$key]=$this->sda[$key].$data;
            $this->n[$key]=$n;
            return false;
        }else{
            unset($this->ar[$key],$this->slen[$key],$this->sjen[$key],$this->n[$key]);
            $data=$this->sda[$key].$data;
            unset($this->sda[$key]);
            return $data;
        }
         
    }
     
    //与uncode相对
    function code($msg){
        $frame = array(); 
        $frame[0] = '81'; 
        $len = strlen($msg);
        if($len < 126){
            $frame[1] = $len<16?'0'.dechex($len):dechex($len);
        }else if($len < 65025){
            $s=dechex($len);
            $frame[1]='7e'.str_repeat('0',4-strlen($s)).$s;
        }else{
            $s=dechex($len);
            $frame[1]='7f'.str_repeat('0',16-strlen($s)).$s;
        }
        $frame[2] = $this->ord_hex($msg);
        $data = implode('',$frame); 
        return pack("H*", $data); 
    }
     
    function ord_hex($data)  { 
        $msg = ''; 
        $l = strlen($data); 
        for ($i= 0; $i<$l; $i++) { 
            $msg .= dechex(ord($data{$i})); 
        } 
        return $msg; 
    }
     
	 
	 
	 
	 
	 
    //用户加入，
    function begin($k,$msg){
        //将查询字符串解析到第二个参数变量中，以数组的形式保存如：parse_str("name=Bill&age=60",$arr)
        parse_str($msg,$g);
		print_r($g);
        if($g['type']=='login'){
            $this->users[$k]['userid'] = $this->reid($g['mailbox']);
			$this->users[$k]['mailbox'] = $g['mailbox'];
			print_r($this->users);
			//获取好友请求列表
			$this->get_fri_list($this->users[$k]['userid']);
			
			//获取历史消息列表
			$this->getlist($k);
					
			
		}
		else if($g['type']=='getlist'){
			$this->getlist($k);
		}
        else if($g['type']=='add_friend'){
			//type=add_friend&friendmail=123;
			if(isset($g['accept'])){
				//&accept=1
				$friendid = $this->reid($g['friendmail']);
				
				if($g['accept']==1){
					//数据库添加好友
					$this->addfriend($this->users[$k]['userid'],$friendid);
					$this->addfriend($friendid,$this->users[$k]['userid']);	
					//删除数据库里这一条好友请求
					$db = $this->conn();
					$sql = 'delete from issue_unsend where userid = :userid and sendid = :sendid and type = "friend_request"';
					$stmt = $db->prepare($sql);
					$stmt->execute([':userid'=>$this->users[$k]['userid'],':sendid'=>$friendid]);
					//返回好友请求列表
					$this->get_fri_list($this->users[$k]['userid']);
				}
			}
			else{
				//判断该用户是否已经是好友
				$friendid = $this->reid($g['friendmail']);

				$db = $this->conn();
				$sql = 'select friend from userdata where id = :userid';
				$stmt = $db->prepare($sql);
				$stmt->execute([':userid'=>$this->users[$k]['userid']]);
				$res = $stmt->fetch();

				$friends = $res[0];
				parse_str($friends,$x);
				if(array_key_exists($friendid,$x)){
					//已经是好友
					$mess['friended'] = 1;
					$this->send($this->users[$k]['userid'],$mess);
				}
				else{
					$this->send($friendid,$this->users[$k]['userid'],"friend_request");
				}
				
			}
			
        }
		else if($g['type']=='chat'){
			
			//获得对方id
			$g['friend_id'] = $this->reid($g['friend_mail']);
			//获取上次发消息的时间
			$db = $this->conn();
			$sql = 'select created_at from '.$this->users[$k]['userid'].'chat where sendid = :sendid order by id desc';
			$stmt = $db->prepare($sql);
			$stmt->execute([':sendid'=>$g['friend_id']]);
			$res = $stmt->fetch();
			$last_time = strtotime($res[0]);
			
			//存入自己和对方的数据库

			$sql = 'insert into '.$this->users[$k]['userid'].'chat(sendid,content,is_send,is_read) values(:sendid,:content,0,1)';
			$stmt = $db->prepare($sql);
			$stmt->execute([':sendid'=>$g['friend_id'],':content'=>$g['content']]);
			
			$sql = 'insert into '.$g['friend_id'].'chat(sendid,content,is_send,is_read) values(:sendid,:content,1,0)';
			$stmt = $db->prepare($sql);
			$stmt->execute([':sendid'=>$this->users[$k]['userid'],':content'=>$g['content']]);
			
			//向对方推送消息
			//判断对方是否在线
			if($this->search_k($g['friend_id'])!=0){
				echo "on line";
				$this->getlist($this->search_k($g['friend_id']));
				
				//对方在线,发送消息
				$chat = array();
				$chat['friend_mail'] = $this->users[$k]['mailbox'];
				$chat['content'] = $g['content'];
				if($time-$last_time<=180){
					
					$chat['time'] = 0;
				}
				else{
					$chat['time'] = date("H:i:s");
				}
				$chat['name'] = $this->get_name($this->users[$k]['userid']);
				$chat['pic'] = $this->get_pic($this->users[$k]['userid']);
				$chat['type'] = 'message';
				$friend_k = $this->search_k($g['friend_id']);
				$str1 = $this->code(json_encode($chat)); 
				socket_write($this->users[$friend_k]['socket'],$str1,strlen($str1));
				
			}
		}
		
		
    }

    //记录日志
    function e($str){
        //$path=dirname(__FILE__).'/log.txt';
        $str=$str."\n";
        //error_log($str,3,$path);
        //编码处理
        echo iconv('utf-8','gbk//IGNORE',$str);
    }
	//........................................................................
	//连接数据库
	function  conn(){
		$dns = "mysql:host=localhost;dbname=sport";
		return new PDO($dns, "root","root");
	}
	
	
	//返回用户id
	function reid($mailbox){
		$db = $this->conn();
		
		$sql = 'select id from account where account = :account';
		$stmt = $db->prepare($sql);
		$stmt->execute([':account'=>$mailbox]);
		$res = $stmt->fetch();
		return $res[0];
	}
	
	//返回用户昵称
	function get_name($id){
		$db = $this->conn();
		$sql = 'select name from userdata where id = :id';
		$stmt = $db->prepare($sql);
		$stmt->execute([':id'=>$id]);
		$res = $stmt->fetch();
		return $res[0];
	}
	
	
	//返回用户头像
	function get_pic($id){
		$db = $this->conn();
		
		$sql = 'select pic from headpic where id = :id';
		$stmt = $db->prepare($sql);
		$stmt->execute([':id'=>$id]);
		$res = $stmt->fetch();
		return $res[0];
	}
	
	
	//返回用户邮箱
	function get_mail($id){
		$db = $this->conn();
		$sql = 'select account from account where id = :id';
		$stmt = $db->prepare($sql);
		$stmt->execute([':id'=>$id]);
		$res = $stmt->fetch();
		return $res[0];
	}
	
	
	//添加好友
	function addfriend($userid,$friendid){
		$db = $this->conn();
		
		
		$sql = 'select friend from userdata where id = :userid';
		$stmt = $db->prepare($sql);
		$stmt->execute([':userid'=>$userid]);
		$res = $stmt->fetch();
		$friends = $res[0]."&".$friendid;
		
		$sql = 'update userdata set friend = :friends where id = :userid';
		$stmt = $db ->prepare($sql);
		$stmt->execute([':friends'=>$friends,':userid'=>$userid]);
	}
	
	function search_k($id){
		foreach ($this->users as $k=>$v){
            if($id==$v['userid']){
				 return $k;
			}
        }
        return 0;
	}
	
	function getTimeWeek($time, $i = 0) {
	  $weekarray =  array("日","一", "二", "三", "四", "五", "六");;
	  $oneD = 24 * 60 * 60;
	  return "周" . $weekarray[date("w", $time + $oneD * $i)];
	}
	
	function send($userid,$sendid,$issue){
	//	echo "userid=$userid,sendid=$sendid,issue=$issue";
		//userid 收件人，sendid 发件人
		$db = $this->conn();
		//检测是否有相同的数据已在数据库
		$sql = 'select id from issue_unsend where userid = :userid and sendid = :sendid and type = :issue';
		$stmt = $db->prepare($sql);
		$stmt->execute([':userid'=>$userid,':sendid'=>$sendid,':issue'=>$issue]);
		$res = $stmt->fetch();
		if($res[0]==NULL){
			//检测是否超过10条好友请求
			if($issue == 'friend_request'){
				$sql = 'select count(*) from issue_unsend where userid = :userid and issue = "friend_request"';
				$stmt = $db->prepare($sql);
				$stmt->execute([':userid'=>$userid]);
				$res = $stmt->fetch();
				if($res[0] ==10){
					//写到这里，超过10条的记录清零
					$sql = 'select id from issue_unsend where userid = :userid and issue = "friend_request"';
					$stmt = $db->prepare($sql);
					$stmt->execute([':userid'=>$userid]);
					$res = $stmt->fetch();
					$dele_id = $res[0];
					
					$sql = 'delete from issue_unsend where id = :dele_id';
					$stmt = $db->prepare($sql);
					$stmt->execute([':dele_id'=>$dele_id]);
					
				}
				$sql = 'insert into issue_unsend(userid,sendid,type) values(:userid,:sendid,:issue)';
				$stmt = $db->prepare($sql);
				$stmt->execute([':userid'=>$userid,':sendid'=>$sendid,':issue'=>$issue]);
			}



			
			if($this->search_k($userid)!=0){
				//在线	发送所有的好友请求
				$this->get_fri_list($userid);
			}
		}
		

	}
	
	function getlist($k){
			//连接数据库，返回所有的的消息
			$db = $this->conn();
			$sql = 'select sendid from '.$this->users[$k]['userid'].'chat order by id desc';
			$stmt = $db->prepare($sql);
			$stmt->execute();
			$res = $stmt->fetchAll();
			$sendids = array();
			foreach($res as $sendid){
				if(!in_array($sendid[0],$sendids)){
					$sendids[] = $sendid[0];
				}
			}
			$chatss = array();
			$nums = array();
			foreach($sendids as $sendid){
				$chat = array();
				
				//获取未读的消息数目
				$sql = 'select count(*) from '.$this->users[$k]['userid'].'chat where sendid = :sendid and is_read = 0';
				$stmt = $db->prepare($sql);
				$stmt->execute([':sendid'=>$sendid]);
				$res = $stmt->fetch();
				$chat['num'] = $res[0];
				if($chat['num']>99){
					$chat['num'] = "99+";
				}
				$nums[] = $res[0];
				//获取最后发消息的时间
				$sql = 'select created_at,content from '.$this->users[$k]['userid'].'chat where sendid = :sendid order by id desc ';
				$stmt = $db->prepare($sql);
				$stmt->execute([':sendid'=>$sendid]);
				$res = $stmt->fetch();
				$chat['last_time'] = $this->change_time($res[0]);
				$chat['last_message'] = mb_substr($res[1],0,5,'utf-8');
				$chat['username'] = $this->get_name($sendid);
				$chat['pic'] = $this->get_pic($sendid);
				$chat['mailbox'] = $this->get_mail($sendid);
				
				$chatss[] = $chat;
			}
			$chatss['sum_num'] = 0;
			foreach($nums as $num){
				$chatss['sum_num'] = $chatss['sum_num'] + $num;
			}
			if($chatss['sum_num']>99){
				$chatss['sum_num'] = "99+";
			}
			$chatss['type'] = 'chat_list';
			$str1 = $this->code(json_encode($chatss)); 
			socket_write($this->users[$k]['socket'],$str1,strlen($str1));
	}
	
	function change_time($ctime){
		$current_time = date("Y-m-d");
		if(strtotime($ctime)-time()<=604800){
			if(substr($ctime,0,10)==$current_time){
				return substr($ctime,-8,-3);
			}
			else{
				return $this->getTimeWeek(strtotime($ctime)).substr($ctime,-8,-3);
			}
		}
		else{
			return substr($ctime,0,-3);
		}

	}
	
	function get_fri_list($userid){
		$db = $this->conn();
		$sql = 'select * from issue_unsend where userid = :userid';
		$stmt = $db ->prepare($sql);
		$stmt->execute([':userid'=>$userid]);
		$res = $stmt->fetchAll();
		$message = array();
		foreach($res as $data){
			$mess['pic'] = $this->get_pic($data['sendid']);
			$mess['friend_mail'] = $this->get_mail($data['sendid']);
			$mess['time'] = $this->change_time($data['created_at']);
			$mess['name'] = $this->get_name($data['sendid']);
			$message[] = $mess;
		}
		$message['type'] = 'friend_list';
		$str1 = $this->code(json_encode($message)); 
		$k = $this->search_k($userid);
		socket_write($this->users[$k]['socket'],$str1,strlen($str1));
		
	}
	
	
}
?>