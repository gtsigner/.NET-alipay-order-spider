<?php
header("Content-type: text/html; charset=utf-8"); 
/************************************
Author		:  大猿人软件科技 
Time		: 2015.11.9
Version		: V1.0
Description : 支付宝自动交易异步通知接口文件.
************************************/

$key = '123';//通信秘钥
$validate = $_POST['validate'];//签名
$tradeNo = $_POST['tradeNo'];//交易号
$desc = $_POST['desc'];//交易名称（付款说明）
$time = $_POST['time'];//付款时间
$username = $_POST['username'];//客户名称
$userid = $_POST['userid'];//客户id
$amount = $_POST['amount'];//交易额
$status = $_POST['status'];//交易状态


if(strtoupper(md5("$tradeNo|$time|$key")) == $validate){

	/*
	 * 下面做业务处理，例如充值、开通订单等
	 * 务必注意：必须做重复交易号检测，防止重复充值、开通业务
	 */
	

	echo "OK";
}
else{
	echo "签名错误";
}

?>