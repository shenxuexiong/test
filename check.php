<?php
ini_set("display_errors", 0);
date_default_timezone_set ('Asia/Shanghai');
$data_temp = addslashes($_POST["data"]);
///////////////////////////////////////////////////////
$adminkey = md5('100');			//管理密码
$dbhost = 'localhost';			//数据库主机
$dbname = '';					//数据库
$dbuser = '';					//数据库用户名
$dbpass = '';					//数据库密码
$tbname = 'new_check';			//表名

$rc4key = '23ce2d9b73a6725e8d446eed903f0a00';
$paskey = 8086;
$pagesize = 100;				//每页条数
///////////////////////////////////////////////////////连接数据库
$con = mysql_connect($dbhost, $dbuser, $dbpass);
if (!$con){
	exit(mysql_error());
}

$db_selected = mysql_select_db($dbname,$con);
$temp = explode('|',$data_temp);//拆开原始数据
if($temp[2] != $adminkey){
	$data_temp = strtr($data_temp,' ',"+");
}

$temp = explode('|',$data_temp);//拆开原始数据
if(count($temp)>10){
	exit('Cracked shameful.');
}
session_start();
$rand = $paskey*rc4($rc4key,base64_decode($temp[1]))/2;
switch ($temp[0])
{
case 'D839DL'://管理登陆
	if($temp[2] == $adminkey)
	{
		exit(stringToHex(rc4($rc4key,'1|'."$rand".'|登陆成功')));
	}else{
		exit(stringToHex(rc4($rc4key,'0|'."$rand".'|登陆失败,密码错误')));
	}
case 'OPZXVV'://心跳检查
	$check_num = (int)rc4($rc4key,base64_decode($temp[2]));
	if ($check_num == $_SESSION[check_num])
	{
		$userid = addslashes(base64_decode($temp[3]));
		$loginkey = addslashes(base64_decode($temp[4]));
		$logintime = addslashes(base64_decode($temp[5]));
		$sql="SELECT `CHECKTIME`,`ENDTIME`,`LOGINKEY`,`USERTYPE` FROM `".$tbname."_USER` WHERE `ID`=$userid";
		$result = mysql_query($sql,$con);
		$row = mysql_fetch_row($result);

		$sql="SELECT `SYSCON`,`FREETIMEA`,`FREETIMEB` FROM `".$tbname."_SYS` WHERE `ID`=1";//读取配置
		$result = mysql_query($sql,$con);
		$rows = mysql_fetch_row($result);
		if($rows[0]=='3'){
			exit(stringToHex(rc4($rc4key,'of')));//系统关闭
		}elseif ($row[3]=='1'){
			exit(stringToHex(rc4($rc4key,'nd')));//帐号被冻结
		}elseif ($rows[0]=='2'){
			if($rows[1]<=date("H") && $rows[2]>date("H")){
				exit(stringToHex(rc4($rc4key,'ok'."|"."$rand")));//正常  在免费时间内
			}elseif (strtotime("$row[0]") > strtotime(date("Y-m-d H:i:s"))){
				exit(stringToHex(rc4($rc4key,'ok'."|"."$rand")));//正常  在免费时间内
			}
		}elseif ($rows[0]=='1'){
			$nowtime = date("Y-m-d H:i:s");
			$sql = "UPDATE `".$tbname."_USER` SET `LOGINOK`='$nowtime' WHERE `ID`='$userid'";
			mysql_query($sql,$con);
			exit(stringToHex(rc4($rc4key,'ok'."|"."$rand")));//正常
		}elseif (strtotime("$row[0]") < strtotime(date("Y-m-d H:i:s"))){ 
			exit(stringToHex(rc4($rc4key,'lo'."|"."$rand")));//客户端已经过期
		}elseif ($row[1]==$logintime && $row[2]==$loginkey){
			$nowtime = date("Y-m-d H:i:s");
			$sql = "UPDATE `".$tbname."_USER` SET `LOGINOK`='$nowtime' WHERE `ID`='$userid'";
			mysql_query($sql,$con);
			exit(stringToHex(rc4($rc4key,'ok'."|"."$rand")));//正常
		}
		exit(stringToHex(rc4($rc4key,'of'."|"."$rand")));//被踢
	}else{
		exit(stringToHex(rc4($rc4key,'err')));//验证码错误
	}	
case 'F2XXV3'://用户登陆
	$check_num = (int)rc4($rc4key,base64_decode($temp[2]));
	if ($check_num == $_SESSION[check_num])
	{
		$username = addslashes(base64_decode($temp[3]));
		$password = md5(base64_decode($temp[4]));
		$tied = addslashes(base64_decode($temp[5]));
		$loginkey = addslashes(base64_decode($temp[6]));
		//exit(stringToHex(rc4($rc4key,$username)));
		if($tied=='0'){
			$sql="SELECT `ID`,`PASSWORD`,`CHECKTIME`,`USERTYPE` FROM `".$tbname."_USER` WHERE `USERNAME`='$username'";//检查帐号
		}else {
			$sql="SELECT `ID`,`PASSWORD`,`CHECKTIME`,`USERTYPE` FROM `".$tbname."_USER` WHERE `COMEKY`='$username'";//检查帐号
		}
		$result = mysql_query($sql,$con);
		$row = mysql_fetch_row($result);
		if($row==false)	
		{
			exit(stringToHex(rc4($rc4key,'nu')));//帐号不存在
		}elseif ($row[3]=='1'){
			exit(stringToHex(rc4($rc4key,'nd')));//帐号已经被冻结
		}elseif ($tied=='0'){
			if ($row[1]!=$password){
				exit(stringToHex(rc4($rc4key,'ps')));//密码错误
			}
		}
		$sql="SELECT `SYSCON`,`FREETIMEA`,`FREETIMEB` FROM `".$tbname."_SYS` WHERE `ID`=1";//读取配置
		$result = mysql_query($sql,$con);
		$rows = mysql_fetch_row($result);
		$logintime = date("Y-m-d H:i:s");
		$ip = getip();
		if($rows[0]=='3'){
			exit(stringToHex(rc4($rc4key,'of')));//系统关闭
		}elseif ($rows[0]=='2'){
			if($rows[1]<=date("H") && $rows[2]>date("H")){
				$sql = "UPDATE `".$tbname."_USER` SET `ENDTIME`='$logintime',`LOGINOK`='$logintime',`LOGINKEY`='$loginkey',`ENDIP`='$ip' WHERE `ID`='$row[0]'";
				mysql_query($sql,$con);
				exit(stringToHex(rc4($rc4key,'ok|'."$rand".'|'.$row[0].'|'.$row[2]."|".$logintime)));//OK
			}else{
				//exit(stringToHex(rc4($rc4key,'err')));//验证码错误
			}
		}
		if ($rows[0]=='1'){
			$sql = "UPDATE `".$tbname."_USER` SET `ENDTIME`='$logintime',`LOGINOK`='$logintime',`LOGINKEY`='$loginkey',`ENDIP`='$ip' WHERE `ID`='$row[0]'";
			mysql_query($sql,$con);
			exit(stringToHex(rc4($rc4key,'ok|'."$rand".'|'.$row[0].'|'.$row[2]."|".$logintime)));//OK
		}elseif (strtotime("$row[2]") < strtotime(date("Y-m-d H:i:s"))){  
			exit(stringToHex(rc4($rc4key,'lo'."|"."$rand")));//客户端已经过期
		}
		$sql = "UPDATE `".$tbname."_USER` SET `ENDTIME`='$logintime',`LOGINOK`='$logintime',`LOGINKEY`='$loginkey',`ENDIP`='$ip' WHERE `ID`='$row[0]'";
		mysql_query($sql,$con);
		exit(stringToHex(rc4($rc4key,'ok|'."$rand".'|'.$row[0].'|'.$row[2]."|".$logintime)));//OK
	}else{
		exit(stringToHex(rc4($rc4key,'err')));//验证码错误
	}	
case 'X0LMTS'://用户修改密码
	$check_num = (int)rc4($rc4key,base64_decode($temp[2]));
	if ($check_num == $_SESSION[check_num])
	{
		$username = addslashes(base64_decode($temp[3]));
		$password = md5(base64_decode($temp[4]));
		$email = addslashes(base64_decode($temp[5]));
		$newpass = md5(base64_decode($temp[6]));
		$sql="SELECT `ID`,`PASSWORD`,`EMAIL`,`USERTYPE` FROM `".$tbname."_USER` WHERE `USERNAME`='$username'";//检查帐号
		$result = mysql_query($sql,$con);
		$row = mysql_fetch_row($result);
		if($row==false)	
		{
			exit(stringToHex(rc4($rc4key,'nu')));//帐号不存在
		}elseif ($row[3]=='1'){
			exit(stringToHex(rc4($rc4key,'nd')));//帐号已经被冻结
		}elseif ($row[1]!=$password){
			exit(stringToHex(rc4($rc4key,'ps')));//密码错误
		}elseif ($row[2]!=$email){
			exit(stringToHex(rc4($rc4key,'em')));//邮箱错误
		}
		$sql = "UPDATE `".$tbname."_USER` SET `PASSWORD`='$newpass' WHERE `ID`='$row[0]'";
		if(mysql_query($sql,$con)==false)
		{
			exit(stringToHex(rc4($rc4key,'no')));//修改失败
		}
		exit(stringToHex(rc4($rc4key,'ok')));//修改成功
	}else{
		exit(stringToHex(rc4($rc4key,'err')));//验证码错误
	}	
case 'X30KAF'://用户查卡
	$check_num = (int)rc4($rc4key,base64_decode($temp[2]));
	if ($check_num == $_SESSION[check_num])
	{
		$cardname = addslashes(base64_decode($temp[3]));
		$cardpass = addslashes(base64_decode($temp[4]));
		$sql="SELECT `KEYPASS`,`VALS`,`USERNAME` FROM `".$tbname."_CARD` WHERE `USERKEY`='$cardname'";//检查卡号
		$result = mysql_query($sql,$con);
		$row = mysql_fetch_row($result);
		if($row==false)	
		{
			exit(stringToHex(rc4($rc4key,'cu')));//卡号不存在
		}elseif ($row[0]!= $cardpass){
			exit(stringToHex(rc4($rc4key,'cu')));//卡密不正确
		}elseif ($row[2]!=''){
			exit(stringToHex(rc4($rc4key,'er')));//被充值
		}
		exit(stringToHex(rc4($rc4key,"$row[1]")));
	}else{
		exit(stringToHex(rc4($rc4key,'err')));//验证码错误
	}	
case 'BP82ZV'://用户充值
	$check_num = (int)rc4($rc4key,base64_decode($temp[2]));
	if ($check_num == $_SESSION[check_num]) 
	{
		$tied = addslashes(base64_decode($temp[6]));
		$username = addslashes(base64_decode($temp[3]));
		$cardname = addslashes(base64_decode($temp[4]));
		$cardpass = addslashes(base64_decode($temp[5]));
		if($tied=='0'){
			$sql="SELECT `ID`,`CHECKTIME`,`USERTYPE` FROM `".$tbname."_USER` WHERE `USERNAME`='$username'";//检查帐号
		}else {
			$sql="SELECT `ID`,`CHECKTIME`,`USERTYPE` FROM `".$tbname."_USER` WHERE `COMEKY`='$username'";//检查帐号
		}
		$result = mysql_query($sql,$con);
		$row = mysql_fetch_row($result);
		if($row==false)	
		{
			exit(stringToHex(rc4($rc4key,'nu')));//帐号不存在
		}elseif ($row[2]=='1'){
			exit(stringToHex(rc4($rc4key,'nd')));//帐号已经被冻结
		}
		$username_id = $row[0];
		$username_time = $row[1];
		$sql="SELECT `ID`,`KEYPASS`,`VALS`,`USERNAME` FROM `".$tbname."_CARD` WHERE `USERKEY`='$cardname'";//检查卡号
		$result = mysql_query($sql,$con);
		$row = mysql_fetch_row($result);
		if($row==false)	
		{
			exit(stringToHex(rc4($rc4key,'cu')));//卡号不存在
		}elseif ($row[1]!= $cardpass){
			exit(stringToHex(rc4($rc4key,'cu')));//卡密不正确
		}elseif ($row[3]!=''){
			exit(stringToHex(rc4($rc4key,'us')));//已经被使用过
		}
		if(strtotime("$username_time")-strtotime(date("Y-m-d H:i:s"))<0){
			$username_time = date("Y-m-d H:i:s");
		}
		$temp_time = date("Y-m-d H:i:s",strtotime($username_time." +".$row[2]." days"));
		$sql = "UPDATE `".$tbname."_USER` SET `CHECKTIME`='$temp_time' WHERE `ID`='$username_id'";
		if(mysql_query($sql,$con)==false)
		{
			exit(stringToHex(rc4($rc4key,'no')));//充值失败
		}else{
			$acttime = date("Y-m-d H:i:s");
			$sql = "UPDATE `".$tbname."_CARD` SET `USERNAME`='$username',`ACTTIME`='$acttime' WHERE `ID`='$row[0]'";
			mysql_query($sql,$con);
			exit(stringToHex(rc4($rc4key,"$temp_time")));//充值成功
		}
	}else{
		exit(stringToHex(rc4($rc4key,'err')));//验证码错误
	}
case 'Y0XVVA'://获取客户端初始化信息
	$check_num = (int)rc4($rc4key,base64_decode($temp[2]));
	if ($check_num == $_SESSION[check_num]) 
	{
		$sql="SELECT `VERS`,`NEWADDRESS1`,`NEWADDRESS2`,`REMARK`,`CRACK`,`CRACKHOST`,`MD5`,`CRC`,`SHA` FROM `".$tbname."_SYS` WHERE `ID`=1";
		$result = mysql_query($sql,$con);
		while($row = mysql_fetch_row($result))
		{
			echo stringToHex(rc4($rc4key,$rand."|".$row[0]."|".$row[1]."|".$row[2]."|".$row[3]."|".$row[4]."|".$row[5]."|".$row[6]."|".$row[7]."|".$row[8]."|".$row[9]."||<br>"));
		}
		exit;
	}else{
		exit(stringToHex(rc4($rc4key,'err')));//验证码错误
	}
case 'Q82LNX'://用户注册
	$check_num = (int)rc4($rc4key,base64_decode($temp[2]));
	if ($check_num == $_SESSION[check_num]) 
	{
		$username = addslashes(base64_decode($temp[3]));
		$password = md5(base64_decode($temp[4]));
		$email = addslashes(base64_decode($temp[5]));
		$tied = addslashes(base64_decode($temp[6]));
		//exit(stringToHex(rc4($rc4key,$username)));
		if($tied=='0'){//是否绑机
			$sql="SELECT `ID` FROM `".$tbname."_USER` WHERE `USERNAME`='$username'";//检查帐号
		}else {
			$sql="SELECT `ID` FROM `".$tbname."_USER` WHERE `COMEKY`='$username'";//检查帐号
		}
		$result = mysql_query($sql,$con);
		$row = mysql_fetch_row($result);
		if($row!=false)	
		{
			exit(stringToHex(rc4($rc4key,'be')));//帐号已经存在
		}
		$sql="SELECT `REGTIME`,`OFFREG`,`IPREGNUM` FROM `".$tbname."_SYS` WHERE `ID`=1";
		$result = mysql_query($sql,$con);
		$row = mysql_fetch_row($result);
		if($row[1]==1){
			exit(stringToHex(rc4($rc4key,'off')));//已经关闭注册
		}
		$reg_tiems = $row[0];
		$reg_ip_num = $row[2];
		$ip = getip();
		$day = date("d");
		$sql="SELECT `ID`,`DAY`,`NUM`,`ALLNUM`,`IPTYPE` FROM `".$tbname."_IP` WHERE `REGIP`='$ip'";
		$result = mysql_query($sql,$con);
		$row = mysql_fetch_row($result);
		if($row==false)	
		{
			$sql = "INSERT INTO `".$tbname."_IP` (`REGIP`,`DAY`,`NUM`,`ALLNUM`) VALUES ('$ip','$day','1','1');"; 
			mysql_query($sql,$con);
			
		}else {
			if($row[4]=='1'){//IP被封
				exit(stringToHex(rc4($rc4key,'seal')));//IP被封
			}elseif ($row[1]==$day && $row[2]==$reg_ip_num){
				exit(stringToHex(rc4($rc4key,'max')));//IP注册数已经最大
			}elseif ($row[1]==$day){
				$row[2]++;
			}else {
				$row[2]=1;
			}
			$row[3]++;
			$sql = "UPDATE `".$tbname."_IP` SET `DAY`='$day',`NUM`='$row[2]',`ALLNUM`='$row[3]' WHERE `ID`='$row[0]'";
			mysql_query($sql,$con);
		}
		$ip_len = get_IP_($ip);
		$sql="SELECT `ID`,`ALLNUM`,`IPTYPE` FROM `".$tbname."_IP` WHERE `REGIP`='$ip_len'";//检测IP段
		$result = mysql_query($sql,$con);
		$row = mysql_fetch_row($result);
		if($row!=false)	
		{
			if($row[2]=='1'){//IP被封
				exit(stringToHex(rc4($rc4key,'seal')));//IP被封
			}
			$row[1]++;
			$sql = "UPDATE `".$tbname."_IP` SET `ALLNUM`='$row[1]' WHERE `ID`=$temp[0]";
			mysql_query($sql,$con);
		}//rc4($rc4key,base64_decode($temp[2])) 网络验证码
		$checktime = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")." +".$reg_tiems." hours"));
		$regtime = date("Y-m-d H:i:s");
		if($tied=='0'){//是否绑机
			$sql = "INSERT INTO `".$tbname."_USER` (`USERNAME`,`PASSWORD`,`CHECKTIME`,`EMAIL`,`REGTIME`,`USERTYPE`,`REGIP`) VALUES ('$username','$password','$checktime','$email','$regtime','0','$ip');"; 
		}else {
			$sql = "INSERT INTO `".$tbname."_USER` (`COMEKY`,`CHECKTIME`,`EMAIL`,`REGTIME`,`USERTYPE`,`REGIP`) VALUES ('$username','$checktime','$email','$regtime','0','$ip');"; 
		}
		if(mysql_query($sql,$con)==false)
		{
			exit(stringToHex(rc4($rc4key,'no')));//注册失败
		}else{
			exit(stringToHex(rc4($rc4key,'ok')));//注册成功
		}
	}else{
		exit(stringToHex(rc4($rc4key,'err')));//验证码错误
	}
case 'ADDUSE'://添加用户
	if ($temp[2] == $adminkey)//用户名 
	{
		$check_time = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")." +".$temp[5]." days"));
		$reg_time = date("Y-m-d H:i:s");
		$reg_ip = getip();
		if($temp[3]=='YOFOUF'){//机器码
			$sql="SELECT `ID` FROM `".$tbname."_USER` WHERE `COMEKY`='$temp[4]'";
			$result = mysql_query($sql,$con);
			$row = mysql_fetch_row($result);
			if($row==false)	
			{
				$sql = "INSERT INTO `".$tbname."_USER` (`COMEKY`,`CHECKTIME`,`EMAIL`,`REGTIME`,`USERTYPE`,`REGIP`) VALUES ('$temp[4]','$check_time','$temp[6]','$reg_time','0','$reg_ip');"; 
			}else {
				exit('NO');//已经存在这个机器码
			}
		}elseif ($temp[3]=='YOFOUS'){//用户
			$sql="SELECT `ID` FROM `".$tbname."_USER` WHERE `USERNAME`='$temp[4]'";
			$result = mysql_query($sql,$con);
			$row = mysql_fetch_row($result);
			if($row==false)	
			{
				$sql = "INSERT INTO `".$tbname."_USER` (`USERNAME`,`PASSWORD`,`CHECKTIME`,`EMAIL`,`REGTIME`,`USERTYPE`,`REGIP`) VALUES ('$temp[4]','$temp[7]','$check_time','$temp[6]','$reg_time','0','$reg_ip');"; 
			}else {
				exit('NO');//已经存在这个用户
			}
		}
		if(mysql_query($sql,$con)==false)
		{
			exit('7');//更改失败
		}else{
			exit('8');//更改成功
		}
	}else{
		exit('err');//失败
	}
case 'DJUSER'://冻结用户
	if ($temp[2] == $adminkey)
	{
		if($temp[4]=='LYZLYF'){//冻结
			$sql = "UPDATE `".$tbname."_USER` SET `USERTYPE`='1' WHERE `ID`=$temp[3]";
		}elseif ($temp[4]=='YOZFYL'){//解冻
			$sql = "UPDATE `".$tbname."_USER` SET `USERTYPE`='0' WHERE `ID`=$temp[3]";
		}
		if(mysql_query($sql,$con)==false)
		{
			exit('7');//更改失败
		}else{
			exit('8');//更改成功
		}
	}else{
		exit('err');//失败
	}
case 'DELUSE'://删除用户
	if ($temp[2] == $adminkey)
	{
		if($temp[4]=='YOFOUF')//删除一般卡
		{
			$sql = "DELETE FROM  `".$tbname."_USER` WHERE `ID` in($temp[3])";
		}elseif ($temp[4]=='YOFOU2'){//删除带日期
			$sql = "DELETE FROM  `".$tbname."_USER` WHERE `REGTIME` LIKE '$temp[3]%'";
		}elseif ($temp[4]=='YOFOU3'){//删除未登陆
			$sql = "DELETE FROM  `".$tbname."_USER` WHERE `ENDTIME` is null";
		}elseif ($temp[4]=='YOFO33'){//删除带日期--未登陆
			$sql = "DELETE FROM  `".$tbname."_USER` WHERE `ENDTIME` is null and `REGTIME` LIKE '$temp[3]%'";
		}elseif ($temp[4]=='YOFOUS'){//删除所有卡
			$sql = "TRUNCATE `".$tbname."_USER`";
		}
		if(mysql_query($sql,$con)==false)
		{
			exit('7');//更改失败
		}else{
			exit('8');//更改成功
		}
	}else{
		exit('err');//失败
	}
case 'UPUSER'://更改用户信息
	if ($temp[2] == $adminkey)
	{
		$sql = "UPDATE `".$tbname."_USER` SET `PASSWORD`='$temp[4]',`EMAIL`='$temp[5]',`CHECKTIME`='$temp[6]',`REMARK`='$temp[7]' WHERE `ID`=$temp[3]";
		if(mysql_query($sql,$con)==false)
		{
			exit('7');//更改失败
		}else{
			exit('8');//更改成功
		}
	}else{
		exit('err');//失败
	}
case 'USER0G'://用户显示第一页
	if ($temp[2] == $adminkey)
	{
		$temp_txt=base64_decode($temp[3]);
		$rs=mysql_query("select count(*) from `".$tbname."_USER` ".$temp_txt,$con); 
		$myrow = mysql_fetch_array($rs);
		$numrows=$myrow[0];//总记录数
		$pages=intval($numrows/$pagesize)+1;//总页数
		exit($numrows.'|'.$pages);
	}else{
		exit('err');//失败
	}
case 'ZUSERL'://显示用户
	if ($temp[2] == $adminkey)
	{
		$offset=$pagesize*($temp[4] - 1);
		$temp_txt=base64_decode($temp[3]);
		$sql="SELECT `ID`,`USERNAME`,`PASSWORD`,`COMEKY`,`CHECKTIME`,`EMAIL`,`REGTIME`,`ENDTIME`,`ENDIP`,`REMARK`,`USERTYPE`,`LOGINOK` FROM `".$tbname."_USER` ".$temp_txt." ORDER BY `ID` DESC LIMIT $offset,$pagesize";
		$result = mysql_query($sql,$con);
		while($row = mysql_fetch_row($result))
		{
			echo $row[0]."|".$row[1]."|".$row[2]."|".$row[3]."|".$row[4]."|".$row[5]."|".$row[6]."|".$row[7]."|".$row[8]."|".$row[9]."|".$row[10]."|".$row[11]."||<br>";
		}
		exit;
	}else{
		exit('err');//失败
	}
case 'UPDCAR'://更改卡号备注
	if ($temp[2] == $adminkey)
	{
		$temp_txt=base64_decode($temp[3]);
		$sql = "UPDATE `".$tbname."_CARD` SET `REMARK`='$temp[4]' WHERE `ID` in ($temp_txt)";
		if(mysql_query($sql,$con)==false)
		{
			exit('7');//更改失败
		}else{
			exit('8');//更改成功
		}
	}else{
		exit('err');//失败
	}
case 'YO05FA'://刷新系统设置
	if($temp[2] == $adminkey)
	{
		$sql="SELECT `REGTIME`,`OFFREG`,`SYSCON`,`FREETIMEA`,`FREETIMEB`,`CARDTOP`,`IPREGNUM` FROM `".$tbname."_SYS` WHERE `ID`=1";
		$result = mysql_query($sql,$con);
		$row = mysql_fetch_row($result);
		exit($row[0].'|'.$row[1].'|'.$row[2].'|'.$row[3].'|'.$row[4].'|'.$row[5].'|'.$row[6]);
	}else{
		exit('err');//失败
	}
case 'O56WF3'://更改系统设置
	if($temp[2] == $adminkey)
	{
		$sql = "UPDATE `".$tbname."_SYS` SET `REGTIME`='$temp[3]',`OFFREG`='$temp[4]',`SYSCON`='$temp[5]',`FREETIMEA`='$temp[6]',`FREETIMEB`='$temp[7]',`CARDTOP`='$temp[8]',`IPREGNUM`='$temp[9]' WHERE `ID`=1";
		if(mysql_query($sql,$con)==false)
		{
			exit('7');//更改失败
		}else{
			exit('8');//更改成功
		}
	}else{
		exit('err');//失败
	}
case 'P89QW0'://获取更新设置
	if($temp[2] == $adminkey)
	{
		$sql="SELECT `VERS`,`NEWADDRESS1`,`NEWADDRESS2`,`REMARK` FROM `".$tbname."_SYS` WHERE `ID`=1";
		$result = mysql_query($sql,$con);
		$row = mysql_fetch_row($result);
		exit($row[0].'|'.$row[1].'|'.$row[2].'|'.$row[3]);
	}else{
		exit('err');//失败
	}
case 'YWO208'://公告更新
	if($temp[2] == $adminkey)
	{
		$sql = "UPDATE `".$tbname."_SYS` SET `VERS`='$temp[3]',`NEWADDRESS1`='$temp[4]',`NEWADDRESS2`='$temp[5]',`REMARK`='$temp[6]' WHERE `ID`=1";
		if(mysql_query($sql,$con)==false)
		{
			exit('7');//更改失败
		}else{
			exit('8');//更改成功
		}
	}else{
		exit('err');//失败
	}
case 'V8ZW02'://防破更新读取
	if($temp[2] == $adminkey)
	{
		$sql="SELECT `CRACK`,`CRACKHOST`,`MD5`,`CRC`,`SHA` FROM `".$tbname."_SYS` WHERE `ID`=1";
		$result = mysql_query($sql,$con);
		$row = mysql_fetch_row($result);
		exit($row[0].'|'.$row[1].'|'.$row[2].'|'.$row[3].'|'.$row[4]);
	}else{
		exit('err');//失败
	}
case 'WQ093M'://设置防破
	if($temp[2] == $adminkey)
	{
		$sql = "UPDATE `".$tbname."_SYS` SET `CRACK`='$temp[3]',`CRACKHOST`='$temp[4]',`MD5`='$temp[5]',`CRC`='$temp[6]',`SHA`='$temp[7]' WHERE `ID`=1";
		if(mysql_query($sql,$con)==false)
		{
			exit('7');//更改失败
		}else{
			exit('8');//更改成功
		}
	}else{
		exit('err');//失败
	}
case 'VLE28D'://IP基本设置
	if($temp[2] == $adminkey)
	{
		$sql="SELECT `ID`,`REGIP`,`ALLNUM`,`IPTYPE`,`REMARK` FROM `".$tbname."_IP` ORDER BY `ALLNUM` DESC LIMIT 0,100";
		$result = mysql_query($sql,$con);
		while($row = mysql_fetch_row($result))
		{
			echo $row[0]."|".$row[1]."|".$row[2]."|".$row[3]."|".$row[4]."||<br>";
		}
		exit;
	}else{
		exit('err');//失败
	}
case 'DELIPS'://删除IP
	if($temp[2] == $adminkey)
	{
		$sql="DELETE FROM `".$tbname."_IP` WHERE `ID` in(".$temp[3].")";
		$result = mysql_query($sql,$con);
		if(mysql_query($sql,$con))
		{
			exit('1');
		}
		exit('2');

	}else{
		exit('err');//失败
	}
case 'ALTER8'://添加修改IP
	if($temp[2] == $adminkey)
	{
		if($temp[3]=="1")//修改
		{
			$sql = "UPDATE `".$tbname."_IP` SET `IPTYPE`='$temp[5]',`REMARK`='$temp[6]' WHERE `ID`='$temp[4]'";
			if(mysql_query($sql,$con)==false)
			{
				exit('7');//更改失败
			}else{
			exit('8');//更改成功
			}
		}else{
			$sql = "INSERT INTO `".$tbname."_IP` (`REGIP`,`IPTYPE`,`REMARK`) VALUES ('$temp[4]','$temp[5]','$temp[6]');"; 
			if(mysql_query($sql,$con)==false)
			{
				exit('7');//更改失败
			}else{
				exit('8');//更改成功
			}
		}

	}else{
		exit('err');//失败
	}
case 'WO89ZL'://卡号显示第一页
	if ($temp[2] == $adminkey)
	{
		$temp_txt=base64_decode($temp[3]);
		$rs=mysql_query("select count(*) from `".$tbname."_CARD` ".$temp_txt,$con); 
		$myrow = mysql_fetch_array($rs);
		$numrows=$myrow[0];//总记录数
		$pages=intval($numrows/$pagesize)+1;//总页数
		exit($numrows.'|'.$pages);
	}else{
		exit('err');//失败
	}
case 'ZPS2FL'://显示卡号
	if ($temp[2] == $adminkey)
	{
		$offset=$pagesize*($temp[4] - 1);
		$temp_txt=base64_decode($temp[3]);
		$sql="SELECT * FROM `".$tbname."_CARD` ".$temp_txt." ORDER BY `ID` DESC LIMIT $offset,$pagesize";
		$result = mysql_query($sql,$con);
		while($row = mysql_fetch_row($result))
		{
			echo $row[0]."|".$row[1]."|".$row[2]."|".$row[3]."|".$row[4]."|".$row[5]."|".$row[6]."||<br>";
		}
		exit;
	}else{
		exit('err');//失败
	}
case 'YO01FA'://更改卡号信息
	if ($temp[2] == $adminkey)
	{
		$sql = "UPDATE `".$tbname."_CARD` SET `VALS`='$temp[4]',`USERNAME`='$temp[5]',`REMARK`='$temp[6]' WHERE `ID`='$temp[3]'";
		if(mysql_query($sql,$con)==false)
		{
			exit('7');//更改失败
		}else{
			exit('8');//更改成功
		}
	}else{
		exit('err');//失败
	}
case 'DELCAR'://删除卡号
	if ($temp[2] == $adminkey)
	{
		if($temp[4]=='YOFOUF')//删除一般卡
		{
			$sql = "DELETE FROM  `".$tbname."_CARD` WHERE `ID` in($temp[3])";
		}elseif ($temp[4]=='YOFOU2'){//删除使用过了的卡
			$sql = "DELETE FROM  `".$tbname."_CARD` WHERE `USERNAME`<>''";
		}elseif ($temp[4]=='YOFOU3'){//删除未使用过的卡
			$sql = "DELETE FROM  `".$tbname."_CARD` WHERE `USERNAME` is null";
		}elseif ($temp[4]=='YOFOU4'){//删除所有卡
			$sql = "TRUNCATE `".$tbname."_CARD`";
		}
		if(mysql_query($sql,$con)==false)
		{
			exit('7');//更改失败
		}else{
			exit('8');//更改成功
		}
	}else{
		exit('err');//失败
	}
case 'NEWCAR'://生成充值卡
	if ($temp[2] == $adminkey)
	{
		$sql="SELECT `CARDTOP` FROM `".$tbname."_SYS` WHERE `ID`=1";
		$result = mysql_query($sql,$con);
		$row = mysql_fetch_row($result);
		$card_top=$row[0];

		$sql="SELECT `USERKEY` FROM `".$tbname."_CARD` WHERE `USERKEY` LIKE '$card_top%' ORDER BY `USERKEY` DESC LIMIT 1";
		$result = mysql_query($sql,$con);
		$row = mysql_fetch_row($result);
		if($row==false)	
		{
			$temp_i.=1;
		}else{
			$temp_i.=get_num($row[0])+1;
		}
		if($temp_i>=99999)
		{
			exit('5');//必须更换卡头
		}
		$num_temp=$temp_i+$temp[4]-2;
		for ($temp_i;$temp_i<=$num_temp;$temp_i++)
		{
			$txt1=randStr(4);
			$txt2=randStr(4);
			$txt3=randStrNum(8);
			if($temp_i>=99999)
			{
				exit('5');//必须更换卡头
			}elseif (strlen($temp_i)==1){
				$txt4='0000'.$temp_i;
			}elseif (strlen($temp_i)==2){
				$txt4='000'.$temp_i;
			}elseif (strlen($temp_i)==3){
				$txt4='00'.$temp_i;
			}elseif (strlen($temp_i)==4){
				$txt4='0'.$temp_i;
			}elseif (strlen($temp_i)==5){
				$txt4=$temp_i;
			}
			if($temp[3]>=9001){
				$temp[3]=9000;
			}
			$sql = "INSERT INTO `".$tbname."_CARD` (`USERKEY`,`KEYPASS`,`VALS`) VALUES ('$card_top$txt4-$txt1-$txt2','$txt3','$temp[3]');"; 
			mysql_query($sql,$con);
		}
		exit('8');//生成完毕
	}else{
		exit('err');//失败
	}
case 'YO8D2N'://建表
	if ($temp[2] == $adminkey)
	{
		$sql = 'CREATE TABLE `'.$tbname.'_USER` (ID int NOT NULL AUTO_INCREMENT,PRIMARY KEY(ID),`USERNAME` varchar(16),`PASSWORD` varchar(32),`COMEKY` varchar(32),`CHECKTIME` varchar(19),`EMAIL` varchar(50),`REGTIME` varchar(19),`ENDTIME` varchar(19),`ENDIP` varchar(17),`REMARK` varchar(255),`USERTYPE` varchar(1),`LOGINOK` varchar(19),`LOGINKEY` varchar(5),`REGIP` varchar(17))';	
		mysql_query($sql,$con);
		//字段:ID |USERNAME|PASSWORD|COMEKY|CHECKTIME|EMAIL|REGTIME| ENDTIME| ENDIP |REMARK|USERTYPE| LOGINOK | LOGINKEY |REGIP 
		//字段:ID | 用户名    |  密码      | 机器   |到期时间    |邮箱   |注册时间|最后登陆| 最后IP|  备注  |用户状态 | 是否登陆  |  登陆KEY |注册IP 
		//生成充值卡表
		$sql = 'CREATE TABLE `'.$tbname.'_CARD` (ID int NOT NULL AUTO_INCREMENT,PRIMARY KEY(ID),`USERKEY` varchar(50),`KEYPASS` varchar(8),`VALS` varchar(8),`USERNAME` varchar(32),`ACTTIME` varchar(19),`REMARK` varchar(255))'; 	
		mysql_query($sql,$con);
		//字段:ID |USERKEY|KEYPASS| VALS |USERNAME|ACTTIME |REMARK
		//字段:ID | 卡号      | 卡密     | 面值    | 用户名    |充值时间 |备注
		$sql = 'CREATE TABLE `'.$tbname.'_IP` (ID int NOT NULL AUTO_INCREMENT,PRIMARY KEY(ID),`REGIP` varchar(17),`DAY` varchar(2),`NUM` varchar(8),`ALLNUM` varchar(8),`IPTYPE` varchar(1),`REMARK` varchar(255))'; 	
		mysql_query($sql,$con);
		//字段:ID |  REGIP   |DAY| NUM |ALLNUM|IPTYPE|REMARK
		//字段:ID |  注册IP  | 天 | 天次 |总次数 |IP状态|备注
		$sql = 'CREATE TABLE `'.$tbname.'_SYS` (ID int NOT NULL AUTO_INCREMENT,PRIMARY KEY(ID),`REGTIME` varchar(5),`OFFREG` varchar(1),`SYSCON` varchar(1),`FREETIMEA` varchar(2),`FREETIMEB` varchar(2),`CARDTOP` varchar(5),`IPREGNUM` varchar(5),`VERS` varchar(10),`NEWADDRESS1` varchar(255),`NEWADDRESS2` varchar(255),`REMARK` varchar(255),`CRACK` varchar(1),`CRACKHOST` varchar(255),`MD5` varchar(32),`CRC` varchar(8),`SHA` varchar(40))'; 
		//字段:ID |REGTIME |OFFREG |SYSCON  |FREETIMEA|FREETIMEB|CARDTOP|IPREGNUM|VERS|NEWADDRESS1|NEWADDRESS2|REMARK|CRACK|CRACKHOST|MD5|CRC|SHA
		//字段:ID |注册赠时 |关闭注册|系统模式|免费时间1 |免费时间2|卡头         |IP注册数 |版本 |更新地址1   |更新地址2  |公告      |防破   |防破HOST |
		if (mysql_query($sql,$con)==false)
		{
			exit('建表失败,请不要重复建表,闲的蛋疼');
		}else{
			$sql = "INSERT INTO `".$tbname."_SYS` (`REGTIME`,`OFFREG`,`SYSCON`,`FREETIMEA`,`FREETIMEB`,`CARDTOP`,`IPREGNUM`,`VERS`,`CRACK`,`CRACKHOST`) VALUES ('2','0','0','20','22','SKY','10','1','0','google.com');"; 
			mysql_query($sql,$con);//初始化配置数据
			exit('建表成功');
		}
	}else{
		exit('0');//失败
	}
case 'LO91VS'://获取验证码
	for($i=0; $i<2; $i++){
	$rands.= dechex(rand(0,9));
	}
	$_SESSION[check_num]=$rands; 
	exit(stringToHex(rc4($rc4key,$rands)));
}//接口判断结束
exit("<center>八零验证系统V1.0<br/>BY:Blruce  QQ:100233622  Email:javq@163.com<br/>-|".date("Y-m-d H:i:s")."|-</center>");

function rc4 ($pwd, $data)//$pwd密钥　$data需加密字符串
{  
	$key[] ="";
	$box[] ="";
	$pwd_length = strlen($pwd);
	$data_length = strlen($data);
	for ($i = 0; $i < 256; $i++)
	{
		$key[$i] = ord($pwd[$i % $pwd_length]);
		$box[$i] = $i;
	}
	for ($j = $i = 0; $i < 256; $i++)
	{
		$j = ($j + $box[$i] + $key[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}
	for ($a = $j = $i = 0; $i < $data_length; $i++)
	{
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;

		$k = $box[(($box[$a] + $box[$j]) % 256)];
		$cipher .= chr(ord($data[$i]) ^ $k);
	}
	return $cipher;
   }
function stringToHex ($s) 
{
    $r = "";
    $hexes = array ("0","1","2","3","4","5","6","7","8","9","a","b","c","d","e","f");
    for ($i=0; $i<strlen($s); $i++) {$r .= ($hexes [(ord($s{$i}) >> 4)] . $hexes [(ord($s{$i}) & 0xf)]);}
    return $r;
}
function getip(){
	if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
		$onlineip = getenv('HTTP_CLIENT_IP');
	} elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
		$onlineip = getenv('HTTP_X_FORWARDED_FOR');
	} elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
		$onlineip = getenv('REMOTE_ADDR');
	} elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
		$onlineip = $_SERVER['REMOTE_ADDR'];
	}
	if(strpos($onlineip,',')==false){
		return $onlineip;
	}else{
		$onlineip=explode(',',$onlineip);
		return $onlineip[0];
	}
}
function get_num($str_)
{
	return trim(eregi_replace("[^0-9]","",$str_));   
}
function randStr($length)//随机卡号
{
	$pattern = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	for($i=0;$i<$length;$i++)
	{
		$key .= $pattern{mt_rand(0,25)};
	}
	return $key;
}
function randStrNum($length)//随机密码
{
	for ($a = 0; $a < $length; $a++) 
	{
		$output .= rand(0,9);
	}
	return $output;
 }
 function get_IP_($ips){
  return  substr($ips,0,strrpos($ips,".")).".*";
}
?>

