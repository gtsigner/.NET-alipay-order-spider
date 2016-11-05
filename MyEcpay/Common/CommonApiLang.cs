using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace Ecpay.Common
{
    class CommonApiLang
    {
        /// <summary>
        /// 登录请求地址
        /// </summary>
        public static readonly String Login_Url = "http://115.28.85.183/ecpays/index.php/Index/login";

        public static readonly String Ecpay_Host_Url = "http://115.28.85.183/ecpays";
        /// <summary>
        ///验证码地址
        /// </summary>
        public static readonly String Verify_Url = "http://115.28.85.183/ecpays/index.php/Index/validateCode?";

        /// <summary>
        /// 登录
        /// </summary>
        public static String Alipay_Login_Url = "https://my.alipay.com/portal/i.htm";

        /// <summary>
        /// 今日转账
        /// </summary>
        public static  String Alipay_Today_ZhuanZhang_Url = "https://consumeprod.alipay.com/record/advanced.htm?dateRange=today&status=success&tradeType=TRANSFER&_input_charset=utf-8";

        /// <summary>
        /// 切换版本的Url
        /// </summary>
        public static readonly String Alipay_Switch_Url = "https://consumeprod.alipay.com/record/switchVersion.htm";

        #region 用户接口

        public static  String User_Interface_Url = "http://localhost:8080/test/easypayNotify.php";

        #endregion
    }
}
