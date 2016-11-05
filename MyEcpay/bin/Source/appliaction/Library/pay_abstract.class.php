<?php

/****
 *
 * 支付接口抽象类
 * Class pay_abstract
 */
abstract class pay_abstract
{
    protected $config = array();
    protected $product_info = array();

    /**
     * 设置配置信息
     * @param $config
     * @return $this
     */
    public function set_config($config)
    {
        foreach ($config as $key => $value) $this->config[$key] = $value;
        return $this;
    }

    /***
     * @param $product_info
     * @return $this
     */
    public function set_productinfo($product_info)
    {
        $this->product_info = $product_info;
        return $this;
    }

    /**
     * 获取请求
     * @return string
     */
    public function get_code()
    {
        // return $this->getAuthData();
        $prepare_data = $this->getpreparedata();
        return $this->config['gateway_url'] . http_build_query($prepare_data);

    }

    /**
     * 获取配置信息
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }


    public function get_code_url()
    {
        $code_url = $this->getCodeUrl();
        return $code_url;
    }

    protected function get_verify($url, $time_out = "60")
    {
        $urlarr = parse_url($url);
        $errno = "";
        $errstr = "";
        $transports = "";
        if ($urlarr["scheme"] == "https") {
            $transports = "ssl://";
            $urlarr["port"] = "443";
        } else {
            $transports = "tcp://";
            $urlarr["port"] = "80";
        }
        $fp = @fsockopen($transports . $urlarr['host'], $urlarr['port'], $errno, $errstr, $time_out);
        if (!$fp) {
            die("ERROR: $errno - $errstr<br />\n");
        } else {
            fputs($fp, "POST " . $urlarr["path"] . " HTTP/1.1\r\n");
            fputs($fp, "Host: " . $urlarr["host"] . "\r\n");
            fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
            fputs($fp, "Content-length: " . strlen($urlarr["query"]) . "\r\n");
            fputs($fp, "Connection: close\r\n\r\n");
            fputs($fp, $urlarr["query"] . "\r\n\r\n");
            while (!feof($fp)) {
                $info[] = @fgets($fp, 1024);
            }
            fclose($fp);
            $info = implode(",", $info);
            return $info;
        }
    }

    // 发货接口、同步接口、异步接口
    abstract public function _delivery();

    abstract public function _return();

    abstract public function _notify();

    abstract public function response($result);

    abstract public function getPrepareData();

    abstract public function getCodeUrl();
}