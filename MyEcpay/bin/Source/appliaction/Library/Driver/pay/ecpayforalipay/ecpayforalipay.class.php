<?php
/**
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
libfile('pay_abstract');

class ecpayforalipay extends pay_abstract
{
    //https://shenghuo.alipay.com/send/payment/fill.htm?
    //optEmail=15760079693&
    //memo=123123&
    //title=%E6%94%AF%E4%BB%98&
    //smsNo=15760079693&
    //payAmount=200
    private $url = "https://shenghuo.alipay.com/send/payment/fill.htm";

    public function __construct($config = array())
    {
        if (!empty($config)) $this->set_config($config);
        $this->config['pay_url'] = $this->url;
        /*方法*/
        $this->config['pay_method'] = 'POST';
    }

    public function getpreparedata()
    {
        /* 编码字符集 */
        $prepare_data['_input_charset'] = CHARSET;
        /* 交易费用 */
        $prepare_data['payAmount'] = $this->product_info['total_fee'];
        /* 交易订单号  说明 */
        $prepare_data['title'] = $this->product_info['trade_sn'];

        /*备注*/
        $prepare_data['memo'] = "Do not change declare!!!!";

        /* 卖家支付宝账号 */
        $prepare_data['optEmail'] = $this->config['account'];

        return $prepare_data;
    }

    public function _delivery()
    {
        return TRUE;
    }

    public function _return()
    {
        $params = $this->filterParameter($_GET);
        $sign = build_mysign($params, $this->config['key'], 'MD5');
        $result = array();
        if (($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') && $sign == $_GET['sign']) {
            $result['result'] = 'success';
            $result['pay_code'] = 'alipay';
            $result['trade_no'] = $_GET['trade_no'];
            $result['out_trade_no'] = $_GET['out_trade_no'];
            $result['out_trade_no'] = $_GET['out_trade_no'];
            return $result;
        } else {
            Log::record($param['method'] . ": illegality notice : flase", 'ALERT', TRUE);
            return FALSE;
        }
    }

    private function check_money($order_sn, $money)
    {
        $model = new OrderModel();
        $msg = $model->detail($order_sn);
        if ($money == $msg['real_amount']) {
            return true;
        } else {
            return false;
        }
    }

    private function check_order_staut($order_sn)
    {

    }

    /**
     * POST接收数据
     *
     */
    public function _notify()
    {
        $validate = $_POST['validate']; //签名
        $tradeNo = $_POST['tradeNo']; //交易号
        $desc = $_POST['desc']; //交易名称（付款说明）
        $time = $_POST['time']; //付款时间
        $username = $_POST['username']; //客户名称
        $userid = $_POST['userid']; //客户id
        $money = $_POST['amount']; //交易额
        $status = $_POST['status']; //交易状态
        //获取交易信息，取订单编号
        $desc = str_replace("cz", "", $desc);
        $arr = explode("-", $desc);
        $order_no_number = "";
        if (count($arr) >= 2) {
            $order_no_number = $arr[1];
        }
        //找到支付密钥
        $key = $this->config['key'];
        //验证签名
        if (strtoupper(md5("$tradeNo|$time|$key")) == $validate) {
            /******请多次验证，防止订单重复******/
            //校验金额是否一致
            if (!$this->check_money($order_no_number, $money)) {
                echo "订单信息非法请求！" . $order_no_number;
                exit();
            } else {
                /* 改变订单状态 */
                $result['result'] = 'success';
                $result['pay_code'] = 'ecpayforalipay';
                $result['trade_no'] = $tradeNo;//交易号
                $result['trade_sn'] = $order_no_number;
                $result['out_trade_no'] = $order_no_number;
                return $result;
            }
        } else {
            echo "验证签名失败!";
            return false;
        }
    }


    /**
     * 相应服务器应答状态
     * @param $result
     */
    public function response($result)
    {
        if (FALSE == $result) echo 'fail';
        else echo 'success';
    }

    /**
     * 返回字符过滤
     * @param $parameter
     */
    private function filterParameter($parameter)
    {
        $para = array();
        foreach ($parameter as $key => $value) {
            if ('sign' == $key || 'sign_type' == $key || '' == $value || 'm' == $key || 'a' == $key || 'c' == $key || 'code' == $key || 'method' == $key || 'page' == $key) continue;
            else $para[$key] = $value;
        }
        return $para;
    }

    public function getCodeUrl()
    {
        return $this->url;
    }
}