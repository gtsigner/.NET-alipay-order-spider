using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace Ecpay.Common
{
    class HttpConfigLang
    {

        public static String ContentType_Post_Setting_String = "application/x-www-form-urlencoded";

        /// <summary>
        /// 1.  $validate = $_POST['validate'];//签名
        /// 2.  $tradeNo = $_POST['tradeNo'];//交易号
        /// 3.  $desc = $_POST['desc'];//交易名称（付款说明）此数据一般设置为您系统里的唯交易号
        /// 4.  $time = $_POST['time'];//付款时间
        /// 5.  $username = $_POST['username'];//客户名称
        /// 6.  $userid = $_POST['userid'];//客户id
        /// 7.  $amount = $_POST['amount'];//交易额
        /// 8.  $status = $_POST['status'];//交易状态
        /// </summary>
        public static String Alipay_Interface_Postdata_String = "validate={0}&tradeNo={1}&desc={2}&time={3}&username={4}&userid={5}&amount={6}&status={7}";

    }
}
