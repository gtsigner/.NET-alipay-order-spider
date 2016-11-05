<?php
include 'config/config.php';
/************************************
Author		:  大猿人软件科技 
Time		: 2015.11.9
Version		: V1.0
Description : 支付宝自动交易异步通知接口文件.
Connect     : QQ:1716771371
************************************/




//配置MYSQL数据库连接信息
$mysql_server_name	=	DB_HOST; 	//数据库服务器名称
$mysql_username		=	DB_USER; 		// 连接数据库用户名
$mysql_password		=	DB_PASSWORD;				// 连接数据库密码
$mysql_database		=	DB_NAME; 			// 数据库的名字
//*******************************************************************


//-----------------------------------------------------------------


$key	=	"123";			//此处请修改为自己的程序设置的key
$validate = isset($_POST['validate'])?$_POST['validate']:"";		//签名
$number	=	isset($_REQUEST["tradeNo"])?$_REQUEST["tradeNo"]:"";	//支付宝交易号
$money		=	isset($_REQUEST["amount"])?$_REQUEST["amount"]:0;			//付款金额
$remark		=	isset($_REQUEST["desc"])?$_REQUEST["desc"]:"";		//付款说明，一般是网站用户名
$name		=	isset($_REQUEST["username"])?$_REQUEST["username"]:"";		//支付宝充值的姓名

$time = isset($_REQUEST["time"])?$_REQUEST["time"]:"";//付款时间
$status = isset($_REQUEST["status"])?$_REQUEST["status"]:"";//交易状态

//获取订单编号
$remarkarr = explode("-",$remark);
$remark=$remarkarr[1];

//-----------------------------------------------------------------
/*QQ: 验证签名*/
if(strtoupper(md5("$number|$time|$key")) == $validate){
		/*付款成功
		 ********************************************************************
		 会员使用支付宝付款时，可以放2个参数，分别是“付款说明”(remark)，您可以灵活使用这2个参数进行自动发货
		 UserName	=	remark	'如充值的用户名放在remark中
		 *******************************************************************
		
		 *******************************************************************
		 为了防止用户填错“付款说明”或“备注”导致充值失败，您可以先检查用户名是否存在，再决定自动发货，以解决这个问题
		//$UserNameIsExist	=	true;	//此处修改为您的检测代码,当然如果您觉得没有必要，也可以不检测
		*/
		$mysql_conn = mysql_connect($mysql_server_name, $mysql_username, $mysql_password);
		if ($mysql_conn){
			mysql_select_db($mysql_database, $mysql_conn);

			/*判断订单类型*/
			if($remark<20130000000000){

				$rs=mysql_query("Select * From ".DB_PREFIX."members Where member_id='$remark'");	//将查询sql语句的结果存到$rs变量中
				$num=mysql_num_rows($rs);											//mysql_num_rows函数的作用就是返回记录笔数.就是你的数据表中的总笔数
				if($num>0){
					$UserNameIsExist	=	true;	//该用户名存在
				}
				else{
					$UserNameIsExist	=	false;	//用户名不存在
				}
				//*******************************************************************
				
				if ($UserNameIsExist==true){		/*如果用户名存在，就自动发货*/
					/*
					 此处编写您更新数据库（自动发货）的代码
					 
					**********更新数据库事例开始*********************************************************
					*/
						/* 充值*/
						if(mysql_query("Update ".DB_PREFIX."members set advance=advance+$money Where member_id=$remark"))
							echo "success";//此处返回值（1）不能修改，当检测到此字符串时，就表示充值成功
						else 
							echo "failure";
						/* 增加充值记录 */
						mysql_query("INSERT INTO `".DB_PREFIX."advance_logs` (`member_id`, `money`, `message`, `mtime`, `payment_id`, `paymethod`, `memo`, `import_money`, `member_advance`, `shop_advance`) VALUES ($remark, $money, '$number', ".time().", '$number', 'alipay', 'alipay', '$money', '', '');");
						
						mysql_close($mysql_conn);
					 
					/* **********更新数据库事例结束********************************************************* */
				}else{
					echo "member not exist";	//当用户名不存在时，就提示此信息，并且不会自动发货
				}

			}else{
				//查询订单
				$rs=mysql_query("Select * From ".DB_PREFIX."orders Where order_id='$remark'");	//将查询sql语句的结果存到$rs变量中
				//查询订单号
				$num=mysql_num_rows($rs);											//mysql_num_rows函数的作用就是返回记录笔数.就是你的数据表中的总笔数
				if($num>0){
					$UserNameIsExist	=	true;	
				}
				else{
					$UserNameIsExist	=	false;	
				}
				//*******************************************************************
				
				if ($UserNameIsExist==true){		/*如果用户名存在，就自动发货*/
					/*
					 此处编写您更新数据库（自动发货）的代码
					**********更新数据库事例开始*********************************************************
					*/
						/* 改变订单状态 */
						if(mysql_query("Update ".DB_PREFIX."orders set pay_status='1' Where order_id=$remark"))
							echo "success";//此处返回值（1）不能修改，当检测到此字符串时，就表示充值成功
						else 
							echo "failure";
						/* 增加充值记录 */
						mysql_query("INSERT INTO `".DB_PREFIX."payments` (`payment_id`,`order_id`, `member_id`, `account`, `bank`, `currency`, `money`, `paycost`, `cur_money`, `payment`, `paymethod`, `ip`, `t_begin`, `t_end`, `memo`,`status`) VALUES ('$remark','$remark', '', '$number', 'alipay', 'CNY', '$money', 0.000, '$money', '3000', 'alipay', '', ".time().", ".time().", 'alipay','succ');");
						
						mysql_close($mysql_conn);
					 
					/* **********更新数据库事例结束********************************************************* */
				}else{
					echo "order not exist";	//当用户名不存在时，就提示此信息，并且不会自动发货
				}
			}
			//*******************************************************************
		}else{
			echo "Conect failed";		//连接数据库失败
		}
	}
else{
	 echo "key error!";
}
?>