using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace Ecpay.Model
{
    class BaseModel
    {

        //order_id,order_name,time,trade_name,money,trade_state,http_notify
        private int id;
        private String order_id;
        private String money;
        private String order_name;
        private String order_time;
        private String trade_state;
        private String http_notify;
        private bool is_http_request = false;
        private String trade_name;
        private String order_type;
        public String Order_type
        {
            get { return order_type; }
            set { order_type = value; }
        }

        public int Id
        {
            get { return id; }
            set { id = value; }
        }

        public String Order_id
        {
            get { return order_id; }
            set { order_id = value; }
        }

        public String Order_name
        {
            get
            {
                if (this.order_name == null)
                {
                    return "";
                }
                return order_name;
            }
            set { order_name = value; }
        }

        public String Order_time
        {
            get { return order_time; }
            set { order_time = value; }
        }

        public String Trade_name
        {
            get
            {
                if (this.trade_name == null)
                {
                    return "";
                }
                return trade_name;
            }
            set { trade_name = value; }
        }

        public String Money
        {
            get { return money; }
            set { money = value; }
        }

        public String Trade_state
        {
            get
            {
                if (this.trade_state == null)
                {
                    return "";
                }
                return trade_state;
            }
            set { trade_state = value; }
        }

        public String Http_notify
        {
            get
            {
                if (this.http_notify == null)
                {
                    return "空消息";
                }
                return http_notify;
            }
            set { http_notify = value; }
        }


        public bool Is_http_request
        {
            get
            {
                return is_http_request;
            }
            set { is_http_request = value; }
        }



    }

}
