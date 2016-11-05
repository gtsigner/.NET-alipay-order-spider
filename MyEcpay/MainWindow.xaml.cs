using CsharpHttpHelper;
using Ecpay.Common;
using Ecpay.DAL;
using Ecpay.Model;
using Ecpay.ParseModule;
using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Linq;
using System.Net;
using System.Text;
using System.Text.RegularExpressions;
using System.Timers;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Documents;
using System.Windows.Forms;
using System.Windows.Input;
using System.Windows.Media;
using System.Windows.Media.Imaging;
using System.Windows.Navigation;
using System.Windows.Shapes;
using System.Windows.Threading;

namespace MyEcpay
{
    delegate void DelegateShowMessage(string msg);

    /// <summary>
    /// MainWindow.xaml 的交互逻辑
    /// </summary>
    public partial class MainWindow : Window
    {
        #region 支付宝
        private System.Windows.Forms.WebBrowser webbro_alipay;
        private System.Windows.Forms.Integration.WindowsFormsHost host;
        private AlipayParse alipay;
        private System.Timers.Timer timer;
        #endregion

        /// <summary>
        /// 面板刷新
        /// </summary>
        private int griddata_ref_time = 10;
        /// <summary>
        /// 数据库访问对象
        /// </summary>
        private SQLiteDatabase sqldb;

        /// <summary>
        /// 最小化托盘
        /// </summary>
        private NotifyIcon notifyIcon;
        public MainWindow()
        {

            InitializeComponent();
            this.Title = SystemLang.APP_NAME + " " + SystemLang.APP_VERSION;
            host = new System.Windows.Forms.Integration.WindowsFormsHost();

            #region 支付宝浏览器
            webbro_alipay = new System.Windows.Forms.WebBrowser();
            host.Child = webbro_alipay;
            host.Margin = new Thickness(0);
            this.Grid_Webbor_alipay.Children.Add(host);
            alipay = new AlipayParse(this.webbro_alipay);
            alipay.DataUpdateEvent += alipay_DateUpdate;//数据更新事件
            alipay.TimerErrorStop += alipay_TimerErrorStop;
            #endregion


            /**/
            HttpHelper her = new HttpHelper();
            HttpResult res = her.GetHtml(new HttpItem
                        {
                            URL = "http://115.28.85.183/ecpays/1.html",
                            Method = "get",
                        });

            if (res.Html.Trim() != "1")
            {
                //System.Windows.Application.Current.Shutdown();
            }


            /*#region 登录检测
            LoginWindow lw = new LoginWindow();
            lw.ShowDialog();
            if (lw.DialogResult != true)
            {
                System.Windows.Application.Current.Shutdown();

            }
            #endregion*/
            sqldb = SQLiteDatabase.GetInstance();

            ///欢迎消息

            // System.Windows.MessageBox.Show(SystemLang.APP_WELCOME_STRING);

            #region 定时器初始化
            timer = new System.Timers.Timer();
            timer.Interval = 1000;
            timer.Elapsed += timer_Elapsed;
            #endregion


            this.btn_start_ref.IsEnabled = true;
            this.btn_stop_ref.IsEnabled = false;
            this.timer.Start();

            #region   托盘图标
            this.IconShow();

            #endregion
        }

        #region  支付宝功能
        /// <summary>
        /// 支付宝异常终止
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        void alipay_TimerErrorStop(object sender, EventArgs e)
        {
            System.Windows.MessageBox.Show((String)sender);
        }

        /// <summary>
        /// 解析成功事件，这边来处理http
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        void alipay_DateUpdate(object sender, EventArgs e)
        {

        }

        /// <summary>
        /// 更新ui
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        void timer_Elapsed(object sender, ElapsedEventArgs e)
        {
            String datetimenow = DateTime.Now.ToString();

            this.Dispatcher.Invoke(new Action(() =>
            {
                this.lbl_msg.Content = "当前时间：" + datetimenow + "    订单刷新倒计时：" + alipay.GetNextRefTime() +
                "秒       数据库刷新倒计时：" + griddata_ref_time + "秒";
                /*更新面板数据*/
                if (griddata_ref_time <= 0)
                {
                    UpdateDataGrid();
                    griddata_ref_time = 20;
                }
                else
                {
                    griddata_ref_time--;
                }
            }));

        }


        private void Window_Loaded(object sender, RoutedEventArgs e)
        {


        }

        private void btn_start_ref_Click(object sender, RoutedEventArgs e)
        {
            UpdateDataGrid();
            String str = @"[a-zA-z]+://[^\s]*";
            Regex reg = new Regex(str);
            Match match = reg.Match(tb_infer_url.Text);
            if (match.ToString().Trim() == "")
            {
                System.Windows.MessageBox.Show("请检查接口地址是否填写正确！");
                return;
            }
            Ecpay.Common.CommonApiLang.User_Interface_Url = tb_infer_url.Text.Trim();
            alipay.Start(tb_infer_url.Text, tb_inter_miyao.Text);
            this.btn_start_ref.IsEnabled = false;
            this.btn_stop_ref.IsEnabled = true;
            this.tab_Control.Items.Remove(this.webbro_alipay);
            this.tab_Control.SelectedIndex = 2;
            this.tb_infer_url.IsEnabled = false;
            this.tb_inter_miyao.IsEnabled = false;
        }

        private void btn_stop_ref_Click(object sender, RoutedEventArgs e)
        {
            alipay.Stop();
            this.btn_start_ref.IsEnabled = true;
            this.btn_stop_ref.IsEnabled = false;
            this.tb_infer_url.IsEnabled = true;
            this.tb_inter_miyao.IsEnabled = true;
        }

        /// <summary>
        /// 更新数据面板
        /// </summary>
        private void UpdateDataGrid()
        {
            List<AlipayModel> models = new List<AlipayModel>();

            String sql = String.Format("select *from {0} where order_time>='{1}'", Ecpay.Common.SystemLang.APP_BASE_DATA_TABLE_NAME, DateTime.Now.ToString("yyyy-M-d"));
            DataTable table = sqldb.GetDataTable(sql).Tables[0];
            foreach (DataRow row in table.Rows)
            {
                AlipayModel model = new AlipayModel();
                //order_id,order_name,time,trade_name,money,trade_state,http_notify
                if (row != null)
                {
                    //订单号
                    if (row["order_id"] != null && row["order_id"].ToString() != "")
                    {
                        model.Order_id = row["order_id"].ToString();
                    }
                    //订单姓名
                    if (row["order_name"] != null && row["order_name"].ToString() != "")
                    {
                        model.Order_name = row["order_name"].ToString();
                    }
                    ///时间
                    if (row["order_time"] != null && row["order_time"].ToString() != "")
                    {
                        model.Order_time = row["order_time"].ToString();
                    }
                    //
                    if (row["trade_name"] != null && row["trade_name"].ToString() != "")
                    {
                        model.Trade_name = row["trade_name"].ToString();
                    }
                    //钱
                    if (row["money"] != null && row["money"].ToString() != "")
                    {
                        model.Money = row["money"].ToString();
                    }
                    //标记
                    if (row["trade_state"] != null && row["trade_state"].ToString() != "")
                    {
                        model.Trade_state = row["trade_state"].ToString();
                    }
                    //http请求
                    if (row["http_notify"] != null && row["http_notify"].ToString() != "")
                    {
                        model.Http_notify = row["http_notify"].ToString();
                    }
                    //是否发了请求
                    if (row["is_http_request"] != null && row["is_http_request"].ToString() != "")
                    {
                        model.Is_http_request = Boolean.Parse(row["is_http_request"].ToString());
                    }
                    //类别
                    if (row["order_type"] != null && row["order_type"].ToString() != "")
                    {
                        model.Order_type = row["order_type"].ToString();
                    }

                }
                models.Add(model);
            }
            Data_grid.ItemsSource = models;
        }

        /// <summary>
        /// 重发http
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void ReSendHttp_Click(object sender, RoutedEventArgs e)
        {
            if (Data_grid.SelectedItem != null)
            {
                foreach (Object obj in Data_grid.SelectedItems)
                {
                    AlipayModel model = (AlipayModel)obj;
                    SendaNewHttp(model);
                }
                UpdateDataGrid();
            }
        }

        /// <summary>
        /// 复制交易号
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void CopyOrder_Id_Click(object sender, RoutedEventArgs e)
        {

        }

        private void CopyOrderAll_Click(object sender, RoutedEventArgs e)
        {

        }

        /// <summary>
        /// 发送一个http请求
        /// </summary>
        /// <param name="model"></param>
        private void SendaNewHttp(AlipayModel model)
        {
            HttpHelper newH = new HttpHelper();
            String vfit = HttpHelper.MD5PHP(model.Order_id + "|" + model.Order_time + "|" + this.tb_inter_miyao.Text).ToUpper();
            //签名，订单号，交易备注，订单时间，对方，客服id，钱，交易状态
            String data = String.Format(Ecpay.Common.HttpConfigLang.Alipay_Interface_Postdata_String, vfit, model.Order_id, model.Trade_name, model.Order_time, model.Order_name, model.Order_name, model.Money, model.Trade_state);

            HttpItem httpitemInter = new HttpItem()
            {
                ContentType = Ecpay.Common.HttpConfigLang.ContentType_Post_Setting_String,
                Method = "Post",
                URL = this.tb_infer_url.Text,
                Encoding = Encoding.UTF8,
                Postdata = data,
            };
            HttpResult httpres = newH.GetHtml(httpitemInter);
            if (httpres.StatusCode != HttpStatusCode.OK)
            {
                /*请求不成功！*/
                return;
            }
            if (httpres.Html.Length >= 200)
            {
                //截取190
                httpres.Html = httpres.Html.Substring(0, 190);
            }
            model.Is_http_request = true;
            model.Http_notify = httpres.Html.ToString();
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
            sqldb.Update("ecpay_transfer", dic, "order_id='" + model.Order_id + "'");
        }

        /// <summary>
        /// 刷新
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void RefDataGrid_Click(object sender, RoutedEventArgs e)
        {
            UpdateDataGrid();
        }

        #endregion

        #region 托盘事件

        private void OnNotifyIconDoubleClick(object sender, EventArgs e)
        {
            QPan_OpenFromTuoPan();
        }

        /// <summary>
        /// 窗体状态改变
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void Window_StateChanged(object sender, EventArgs e)
        {
            //最小化托盘
            QPan_MiniMizedToTuoPan();
        }
        public void QPan_MiniMizedToTuoPan()
        {
            //是否最小化
            if (WindowState == WindowState.Minimized)
            {
                this.Visibility = Visibility.Hidden;
                this.ShowInTaskbar = false;
                this.notifyIcon.Visible = true;
                //气球提示           
                this.notifyIcon.ShowBalloonTip(3, SystemLang.APP_NAME + "提示", "程序后台为您监控支付中.....", ToolTipIcon.Info);
            }

        }
        public void QPan_OpenFromTuoPan()
        {
            this.Visibility = Visibility.Visible;
            this.ShowInTaskbar = true;
            this.WindowState = WindowState.Normal;
            this.notifyIcon.Visible = true;
            //设置顶层
            this.Topmost = true;
        }
        public void IconShow()
        {
            this.notifyIcon = new NotifyIcon();
            //设置悬浮名称
            this.notifyIcon.BalloonTipText = SystemLang.APP_NAME + " 核心接口驱动.." + SystemLang.APP_VERSION;
            this.notifyIcon.Text = SystemLang.APP_NAME + " 核心接口驱动..." + SystemLang.APP_VERSION;
            this.notifyIcon.ShowBalloonTip(2000);
            //this.notifyIcon.Icon = new System.Drawing.Icon("AIM54.ico");
            this.notifyIcon.Visible = false;

            //打开菜单项
            System.Windows.Forms.MenuItem open = new System.Windows.Forms.MenuItem("Open");
            open.Click += open_Click;
            //退出菜单项
            System.Windows.Forms.MenuItem exit = new System.Windows.Forms.MenuItem("Exit");
            exit.Click += exit_Click;
            //关联托盘控件
            System.Windows.Forms.MenuItem[] childen = new System.Windows.Forms.MenuItem[] { open, exit };
            notifyIcon.ContextMenu = new System.Windows.Forms.ContextMenu(childen);

            //鼠标双击
            notifyIcon.MouseDoubleClick += OnNotifyIconDoubleClick;
            notifyIcon.MouseClick += notifyIcon_MouseClick;
        }

        /// <summary>
        /// 退出程序按钮
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        void exit_Click(object sender, EventArgs e)
        {
            System.Windows.Application.Current.Shutdown();
        }
        /// <summary>
        /// 打开程序按钮
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        void open_Click(object sender, EventArgs e)
        {
            QPan_OpenFromTuoPan();
        }

        void notifyIcon_MouseClick(object sender, System.Windows.Forms.MouseEventArgs e)
        {
            /*是否是右键*/
            if (e.Button == MouseButtons.Right)
            {

            }
        }

        #endregion

        #region 菜单

        /// <summary>
        /// 添加QQ群
        /// </summary>
        /// <param name="sender"></param>
        /// <param name="e"></param>
        private void MenuItem_Click(object sender, RoutedEventArgs e)
        {
            System.Diagnostics.Process.Start(new System.Diagnostics.ProcessStartInfo("http://shang.qq.com/wpa/qunwpa?idkey=5d31bf240d31c8ba90504e7c31a0efb423039acd42d847648013de9728cca9fd"));
        }


        private void menu_service_1_Click(object sender, RoutedEventArgs e)
        {
            System.Diagnostics.Process.Start(new System.Diagnostics.ProcessStartInfo("https://shop120117620.taobao.com"));
        }

        #endregion




    }
    class InterRe
    {
        public string code { get; set; }
        public object msg { get; set; }
    }
}
