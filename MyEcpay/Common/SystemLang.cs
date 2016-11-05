using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

/////////
/////////
/////////
/////////
/////////


/////////
/////////
/////////
/////////

namespace Ecpay.Common
{
    class SystemLang
    {
        
        /// <summary>
        /// 程序基础模块
        /// </summary>
        public static readonly String APP_NAME = "Ecpay易支付";//app名称

        public static readonly String APP_VERSION = "  v1.4";//app版本

        public static readonly String APP_BASE_DATA_DATABASE_NAME = "OrderDB.db";//app的数据库

        public static readonly String APP_BASE_DATA_TABLE_NAME = "ecpay_transfer";

        public static readonly String DB_CREATE_DATA_TABLE_SQL_STRING = @"CREATE TABLE IF NOT EXISTS [ecpay_transfer] (
  [order_id] NVARCHAR(100) NOT NULL ON CONFLICT FAIL, 
  [order_name] VARCHAR(20), 
  [order_time] DATETEXT NOT NULL, 
  [order_type] VARCHAR(20), 
  [trade_name] NVARCHAR(20), 
  [money] MONEY(1000000), 
  [trade_state] NVARCHAR(20), 
  [http_notify] NVARCHAR(200), 
  [id] CHAR(10), 
  [is_http_request] NVARCHAR(4) COLLATE NOCASE DEFAULT (0));";//app的创建表的语句


        /// <summary>
        /// 程序基础模块 2
        /// </summary>
        public static readonly String Timer_Refresh = "Timer_Refresh";

        public static readonly String Group_Name = "大猿人软件科技/网络科技";

        public static readonly String[] Developer_Names = { "廖强", "赵俊" };


        public static readonly String APP_WELCOME_STRING = @"
            欢迎使用Ecpay  v1.4！！
             请先登录支付宝,我们保证不会窃取您的任何信息
          程序将会自动获取数据,并发送订单信息到你的接口,
          请您在您的接口处验证并进行你的逻辑代码。
    
                如需定制自己网站的接口，请联系！！

                 From： 大猿人软件科技

                程序问题联系QQ：1716771371 / 1204887277

                 APP_Author：Coder老司机 / 大强偶吧
        
";


        /// <summary>
        /// 程序基础模块 3
        /// </summary>



    }
}
