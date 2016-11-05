using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace Ecpay.Model
{
    class AlipayUrlModel
    {

        private String beginDate;
        private String dateType;
        private String status;
        private String maxAmount;
        private String fundFlow;
        private String tradeType;
        private String categoryId;
        private String _input_charset;
        private String minAmount;
        private String beginTime;
        private String keyValue;
        private String bizOutNo;
        private String endDate;
        private String endTime;
        private String dateRange;


        /// <summary>
        /// 开始时间
        /// </summary>
        public String BeginTime
        {
            get { return beginTime; }
            set { beginTime = value; }
        }

        /// <summary>
        /// 结束日期
        /// </summary>
        public String EndDate
        {
            get { return endDate; }
            set { endDate = value; }
        }

        public String EndTime
        {
            get { return endTime; }
            set { endTime = value; }
        }

        public String DateRange
        {
            get { return dateRange; }
            set { dateRange = value; }
        }

        public String Status
        {
            get { return status; }
            set { status = value; }
        }

        public String BizOutNo
        {
            get { return bizOutNo; }
            set { bizOutNo = value; }
        }
     

        public String KeyValue
        {
            get { return keyValue; }
            set { keyValue = value; }
        }


        public String DateType
        {
            get { return dateType; }
            set { dateType = value; }
        }
  

        public String MinAmount
        {
            get { return minAmount; }
            set { minAmount = value; }
        }


        public String MaxAmount
        {
            get { return maxAmount; }
            set { maxAmount = value; }
        }


        public String FundFlow
        {
            get { return fundFlow; }
            set { fundFlow = value; }
        }
  


        public String TradeType
        {
            get { return tradeType; }
            set { tradeType = value; }
        }
   

        public String CategoryId
        {
            get { return categoryId; }
            set { categoryId = value; }
        }

        /// <summary>
        /// 编码
        /// </summary>
        public String Input_charset
        {
            get { return _input_charset; }
            set { _input_charset = value; }
        }

        /// <summary>
        /// 开始日期
        /// </summary>
        public String BeginDate
        {
            get { return beginDate; }
            set { beginDate = value; }
        }
    }
}
