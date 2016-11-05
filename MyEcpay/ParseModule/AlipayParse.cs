using CsharpHttpHelper;
using Ecpay.Common;
using Ecpay.DAL;
using Ecpay.Model;
using HtmlAgilityPack;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Net;
using System.Text;
using System.Text.RegularExpressions;
using System.Timers;
using System.Windows;
using System.Windows.Controls;

namespace Ecpay.ParseModule
{

    class AlipayParse : BaseParse
    {

        #region 事件

        public event EventHandler<EventArgs> DataUpdateEvent;
        public event EventHandler<EventArgs> TimerErrorStop;

        #endregion

        private System.Windows.Forms.WebBrowser web_bro;
        /// <summary>
        /// 更新时间
        /// </summary>
        private Timer Update_timer;
        /// <summary>
        /// 配置刷新时间
        /// </summary>
        private int Timer_Refresh = 10;
        /// <summary>
        /// sqlite操作
        /// </summary>
        private SQLiteDatabase sqldb;
        /// <summary>
        /// 配置
        /// </summary>
        private Dictionary<String, String> BaseConfig;
        /// <summary>
        /// cookies
        /// </summary>
        private String Web_Cookies;

        private String PostKey = "";

        private String InterUrl = "";

        #region ali请求
        private HttpHelper httphelperAlipay;
        private HttpHelper httpInter;
        #endregion

        public AlipayParse(System.Windows.Forms.WebBrowser webbro, Dictionary<String, String> config = null)
        {
            this.BaseConfig = config;
            sqldb = SQLiteDatabase.GetInstance();

            #region 定时器线程
            Update_timer = new Timer();
            Update_timer.Interval = 1000;
            Update_timer.Elapsed += Update_timer_Elapsed;
            #endregion

            /*初始化浏览器    Start*/
            this.web_bro = webbro;
            webbro.DocumentCompleted += webbro_DocumentCompleted;
            this.web_bro.Navigate(Ecpay.Common.CommonApiLang.Alipay_Today_ZhuanZhang_Url);
            httphelperAlipay = new HttpHelper();
            httpInter = new HttpHelper();
        }


        /// <summary>
        /// 刷新计时器事件
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        void Update_timer_Elapsed(object sender, ElapsedEventArgs e)
        {
            this.Update_timer.Stop();
            if (Timer_Refresh <= 0)
            {
                this.StartParseAndPost();
                Timer_Refresh = Ecpay.Common.ConfigLang.Timer_Refresh_Config;
            }
            else
            {
                Timer_Refresh--;
            }
            this.Update_timer.Start();
        }

        #region  浏览器获取cookie
        /// <summary>
        /// 浏览器加载邓丽，加载完成 保存cookie数据
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        void webbro_DocumentCompleted(object sender, System.Windows.Forms.WebBrowserDocumentCompletedEventArgs e)
        {
            /*加载完成*/
            if (((System.Windows.Forms.WebBrowser)sender).ReadyState == System.Windows.Forms.WebBrowserReadyState.Complete)
            {
                this.Web_Cookies = web_bro.Document.Cookie;
            }
        }

        /// <summary>
        /// 检测版本切换
        /// </summary>
        private void SwitchVersion()
        {

            String url = web_bro.Url.ToString();
            //如果是标准版
            if (url.Contains("standard"))
            {
                web_bro.Navigate(Common.CommonApiLang.Alipay_Switch_Url);
            }

        }

        #endregion

        /// <summary>
        /// 向支付宝拿数据请求
        /// </summary>
        public void StartParseAndPost()
        {
            //检测是否是高级版
            SwitchVersion();
            HttpItem httpre = new HttpItem()
            {
                URL = Ecpay.Common.CommonApiLang.Alipay_Today_ZhuanZhang_Url,
                Method = "get",
                Cookie = this.Web_Cookies,
            };
            HttpResult res = httphelperAlipay.GetHtml(httpre);
            /*重定向到login*/
            if (res.RedirectUrl.Contains("login"))
            {
                web_bro.Navigate(CommonApiLang.Alipay_Login_Url);
                this.Stop();
                this.TimerErrorStop.Invoke("支付宝登录Cookies失效,线程终止！请重新登录支付宝,切换到高级版！", null);
                return;
            }
            /*判断是否重定向*/
            while (res.RedirectUrl.Trim() != "")
            {
                /*如果被基础班重定向了的话*/
                HttpItem newht = new HttpItem()
                {
                    URL = res.RedirectUrl,
                    Method = "get",
                    Cookie = this.Web_Cookies,
                };
                res = httphelperAlipay.GetHtml(newht);
                this.Web_Cookies = res.Cookie;
            }
            if (res.Html != null)
            {
                this.GetParse(res.Html);
            }
        }

        #region  外部接口

        /// <summary>
        /// 获取刷新时间
        /// </summary>
        /// <returns></returns>
        public int GetNextRefTime()
        {
            return this.Timer_Refresh;
        }
        public void Start(String interUrl, String Key)
        {
            SwitchVersion();
            this.InterUrl = interUrl;
            this.PostKey = Key;
            this.Update_timer.Start();
        }

        public void Stop()
        {
            this.Update_timer.Stop();
        }

        #endregion

        #region html解析


        /// <summary>
        /// 解析详细数据
        /// </summary>
        /// <param name="html"></param>
        private void GetParse(String html)
        {
            List<AlipayModel> list = new List<AlipayModel>();
            HtmlAgilityPack.HtmlDocument document = new HtmlAgilityPack.HtmlDocument();

            document.LoadHtml(html);
            /*获取table 列表*/
            HtmlNode parNode = document.GetElementbyId("tradeRecordsIndex");
            if (parNode == null)
            {
                return;
            }
            /*重新加载*/
            document.LoadHtml(parNode.InnerHtml);
            /*获取tbody 的子节点tr*/
            HtmlNodeCollection collection;
            try
            {
                collection = document.DocumentNode.SelectSingleNode("tbody").ChildNodes;
            }
            catch (Exception ex)
            {
                return;
            }
            foreach (HtmlNode node in collection)
            {
                if (node.InnerText.Trim() == "")
                {
                    continue;
                }
                /*获取Tr 的子节点td*/
                HtmlNodeCollection tdcoll = node.ChildNodes;
                AlipayModel model = new AlipayModel();
                foreach (HtmlNode itemnode in tdcoll)
                {
                    String str = itemnode.InnerText.Replace("\r", "").Replace("\n", "").Replace("\t", "").Replace(" ", "").Trim();
                    if (str.Trim() == "")
                    {
                        continue;
                    }
                    HtmlAttributeCollection attvalue = itemnode.Attributes;
                    #region 遍历属性
                    /*解析td的值*/
                    foreach (HtmlAttribute attitem in attvalue)
                    {
                        str = Regex.Unescape(str);
                        switch (attitem.Value)
                        {
                            case "time":
                                {

                                    String date = str.Substring(0, 10);
                                    String time = str.Substring(10, 5);
                                    DateTime dateTime = DateTime.Parse(date + " " + time);
                                    model.Order_time = dateTime.ToString().Replace("/", "-");
                                } break;
                            case "memo":
                                {
                                } break;
                            case "name":
                                {
                                    //交易类型
                                    model.Trade_name = str;
                                } break;
                            case "tradeNo ft-gray":
                                {

                                    if (str.Contains(":"))
                                    {
                                        str = str.Substring(str.LastIndexOf(":") + 1, str.Length - str.LastIndexOf(":") - 1);
                                    }

                                    model.Order_id = str;

                                } break;
                            case "other":
                                {
                                    model.Order_name = str;
                                } break;
                            case "amount":
                                {
                                    model.Money = str;

                                } break;
                            case "detail":
                                {

                                } break;
                            case "status":
                                {
                                    model.Trade_state = str;

                                } break;
                            case "action":
                                {

                                } break;
                            default: break;
                        }
                    }

                    #endregion

                }
                /*解析成功,触发成功事件*/
                model.Order_type = "支付宝";
                model.Is_http_request = false;
                SuccessParseData(model);
            }
        }



        #endregion

        /// <summary>
        /// 成功后请求接口
        /// </summary>
        /// <param name="model"></param>
        public void SuccessParseData(AlipayModel model)
        {
            /*判断数据库不存在，是新的数据就执行新数据事件*/
            if (!sqldb.IsExist("ecpay_transfer", new KeyValuePair<string, string>("order_id", model.Order_id)))
            {
                String vfit = HttpHelper.MD5PHP(model.Order_id + "|" + model.Order_time + "|" + this.PostKey).ToUpper();
                //签名，订单号，交易备注，订单时间，对方，客服id，钱，交易状态
                String data = String.Format(Ecpay.Common.HttpConfigLang.Alipay_Interface_Postdata_String, vfit, model.Order_id, model.Trade_name, model.Order_time, model.Order_name, model.Order_name, model.Money, model.Trade_state);

                HttpItem httpitemInter = new HttpItem()
                {
                    ContentType = Common.HttpConfigLang.ContentType_Post_Setting_String,
                    Method = "Post",
                    URL = this.InterUrl,
                    Encoding = Encoding.UTF8,
                    Postdata = data,
                };
                HttpResult httpres = httpInter.GetHtml(httpitemInter);
                /*请求成功！*/
                if (httpres.StatusCode == HttpStatusCode.OK)
                {
                    model.Is_http_request = true;
                    if (httpres.Html.Length > 200)
                    {
                        model.Http_notify = httpres.Html.Substring(0, 190);
                    }
                    else
                    {
                        model.Http_notify = httpres.Html;
                    }
                }
                /*解析json*/
                Dictionary<String, String> dic = new Dictionary<string, string>();
                dic.Add("order_id", model.Order_id);
                dic.Add("order_name", model.Order_name);
                dic.Add("order_time", model.Order_time);
                dic.Add("trade_name", model.Trade_name);
                dic.Add("order_type", model.Order_type);
                dic.Add("money", model.Money);
                dic.Add("trade_state", model.Trade_state);
                dic.Add("http_notify", model.Http_notify);
                dic.Add("is_http_request", model.Is_http_request.ToString());
                if (sqldb.Insert("ecpay_transfer", dic))
                {
                    DataUpdateEvent.Invoke(null, null);
                }
            }
        }

    }
    class InterRe
    {
        public string code { get; set; }
        public object msg { get; set; }
    }



}

