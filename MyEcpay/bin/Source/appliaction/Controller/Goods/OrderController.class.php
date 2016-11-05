<?php

/**
 *      [Haidao] (C)2013-2099 Dmibox Science and technology co., LTD.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      http://www.haidao.la
 *      tel:400-600-2042
 */
class OrderController extends HomeBaseController
{
    /* 错误消息 */
    protected $error = '';
    /* 购物车商品 */
    protected $cart_goods_list = array();
    protected $norder_keys = array();
    protected $keys = array();

    public function _initialize()
    {
        parent::_initialize();
        $this->user_info = getUserInfo();
        if (!$this->user_info) {
            showmessage('请登录后操作', U('User/Public/login', array('url_forward' => urlencode(__SELF__))));
        }
        $this->pays = array(
            array('name' => '在线支付', 'description' => '即时到帐，支持绝大数银行借记卡及部分银行信用卡'),
            array('name' => '货到付款', 'description' => '送货上门后再收款，支持现金、POS机刷'),
        );
        $this->uid = $this->user_info['id'];
        $this->address_id = $this->user_info['address_id'];
        $this->order_db = model('Order');
        libfile('Cart');
        $this->keys = $this->getKey();
        $this->Cart = new Cart();
    }

    /* 购物结算 */
    public function index()
    {
        $region = model('region')->select();
        $region = list_to_tree($region, 'area_id', 'parent_id', '_child', 1);
        $norder_keys = $this->getNorderKeys();
        $goods_info = model('goods')->detail(1, 1300);
        /* 读取收货地址 */
        $sqlmap = array();
        $sqlmap['user_id'] = $this->uid;
        $user_address = model('user_address')->where($sqlmap)->getField('id, user_id, address_name, province, city, district, address, zipcode, tel, mobile');
        /* 配送方式 */
        $address_id = $norder_keys['address_id'];
        $deliverys = $this->getDelivery($address_id);
        /* 支付方式 */
        $payment = $this->pays[$norder_keys['pay_type']];

        /* 获取购物数据 */
        $rs = $this->getPromotion();
        if ($rs == FALSE) {
            showmessage($this->error, U('Goods/Cart/index'));
        }
        $cart_goods_list = $this->cart_goods_list;
        /* 可参与订单促销列表 */
        $order_promotions = $rs['order_promotion_list'];

        /* 优惠券列表 */
        $sqlmap = array();
        $sqlmap['limit'] = array("ELT", $rs['goods_count_price']);
        $coupons_ids = model('coupons')->where($sqlmap)->getField('id', TRUE);
        $coupons_list = array();
        if ($coupons_ids) {
            $sqlmap = array();
            $sqlmap['user_id'] = $this->uid;
            $sqlmap['cid'] = array("IN", $coupons_ids);
            $sqlmap['start_time'] = array("LT", NOW_TIME);
            $sqlmap['end_time'] = array("GT", NOW_TIME);
            $sqlmap['status'] = 1;
            $coupons_list = model('coupons_list')->where($sqlmap)->getField('id, sn, name,value', TRUE);
        }
        /* 发票类型 */
        $invoice_content = str2arr(getconfig('site_invoicecontent'), "\r\n");
        /* 页面参数 */
        $args = array('keys' => $_GET['keys']);
        $SEO = seo(0, "核对订单信息");
        include template('buy_order');
    }

    /* 根据参数得到订单数据 */
    public function getOrderInfo($return = 'json')
    {
        extract($_GET);
        $rs = $this->getPromotion();
        if ($rs == FALSE) showmessage($this->error);
        $result = array();
        $address = (int)$address;
        $pay_type = (int)$pay_type;
        $delivery_id = (int)$delivery_id;
        /* 第一步：配送费用 */
        $result['delivery'] = $this->getDelivery($address);
        $result['pays'] = $this->getPays($delivery_id);
        $result['p_delivery'] = 0;
        if ($delivery_id && isset($result['delivery'][$delivery_id])) {
            $weightprice = str2arr($result['delivery'][$delivery_id]['weightprice']);
            $result['p_delivery'] = (int)$weightprice[0];
        }
        $result['p_delivery'] = sprintf('%.2f', $result['p_delivery']);

        /* 第二步：订单促销 */
        $result['p_promotion'] = 0;
        $result['goods_count_price'] = $rs['goods_count_price'];//应付商品金额
        $result['goods_count_num'] = $rs['goods_count_num'];//商品总件数
        $result['p_give_point'] = 0;

        $promotion_id = (int)$promotion_id;
        if ($promotion_id) {
            $promotion_list = $rs['order_promotion_list'];
            $promotion_info = $promotion_list[$promotion_id];
            if (isset($promotion_info)) {
                $result['promotion_id'] = $promotion_id;
                $result['promotion_msg'] = $promotion_info['name'];
                foreach ($rs['order_promotion_gids'] as $k => $gid) {
                    if (in_array($gid, $promotion_info['goods_id'])) {
                        $rs['goods_promotion_price'] -= $rs['promotion_goods_price'][$gid];
                        $rs['give_point']['order_promotion_point'] -= $rs['promotion_goods_point'][$gid];
                        $rs['give_point']['goods_promotion_point'] += $rs['promotion_goods_point'][$gid];
                    } else {
                        $result['goods_count_price'] += $rs['promotion_goods_price'][$gid];
                    }
                }

                switch ($promotion_info['award_type']) {
                    // 满额打折
                    case '1':
                        $result['p_promotion'] = sprintf('%.2f', $rs['goods_promotion_price'] - ($rs['goods_promotion_price'] * $promotion_info['award_value'] / 100));
                        break;
                    // 满额减价
                    case '2':
                        $result['p_promotion'] = $promotion_info['award_value'];
                        break;
                    // 送倍数积分
                    case '3':
                        $rs['give_point']['order_promotion_point'] = $promotion_info['award_value'] * $rs['give_point']['order_promotion_point'];
                        break;
                    // 满额送券
                    case '4':
                        $result['give_coupons_id'] = $promotion_info['award_value'];
                        break;
                    // 满额免邮
                    default:
                        $result['p_delivery'] = sprintf('%.2f', 0);
                        break;
                }
            }
        }
        $result['p_give_point'] = $rs['give_point']['goods_promotion_point'] + $rs['give_point']['order_promotion_point'];
        $result['p_promotion'] = sprintf('%.2f', $result['p_promotion']);

        /* 第四步：优惠券*/
        // 所有满足条件的优惠券
        $result['p_coupons'] = $result['p_coupons_id'] = 0;
        $sqlmap = array();
        $sqlmap['sn'] = $sn;
        $sqlmap['user_id'] = $this->uid;
        $sqlmap['start_time'] = array("LT", NOW_TIME);
        $sqlmap['end_time'] = array("GT", NOW_TIME);
        $sqlmap['status'] = 1;
        $coupons = model('coupons_list')->where($sqlmap)->find();
        if ($coupons && $coupons['value'] > 0) {
            $limit = model('coupons')->getFieldById($coupons['cid'], 'limit');
            if ($limit > 0 && $limit <= $rs['goods_count_price']) {
                $result['p_coupons_id'] = $coupons['id'];
                $result['p_coupons'] = $coupons['value'];
            }
        }
        $result['p_coupons'] = sprintf('%.2f', $result['p_coupons']);
        /* 第四步：实际费用（商品价格 + 运费） - 优惠券 - 订单促销 = 实际费用 */
        $result['real_amount'] = $rs['goods_count_price'] + $result['p_delivery'] - $result['p_coupons'] - $result['p_promotion'];

        /* 第五步：计算发票税率 */
        $result['p_invoicerate'] = 0;
        if (getconfig('site_invoice') && getconfig('site_invoicerate') && $invoicerate) {
            $result['p_invoicerate'] = ($result['real_amount'] * getconfig('site_invoicerate')) / 100;
            $result['real_amount'] += $result['p_invoicerate'];
        }
        $result['real_amount'] = ($result['real_amount'] < 0) ? 0 : $result['real_amount'];
        $result['p_invoicerate'] = sprintf('%.2f', $result['p_invoicerate']);
        $result['real_amount'] = sprintf('%.2f', $result['real_amount']);
        if ($return == 'json') {
            echo json_encode($result);
            exit();
        } else {
            return $result;
        }
    }

    /**
     * 根据购物车商品获取订单促销信息
     * 返回数组
     * {
     *        goods_count_price        应付商品总额(已排除参与的单品活动)
     *        goods_count_num            所有商品件数
     *        give_point                积分赠送
     *        {
     *            goods_promotion_point:商品积分
     *            order_promotion_point:促销积分     *
     *        }
     *
     *
     *        goods_promotion_price    参与促销的商品总额
     *        goods_promotion_gids    参与单品促销的商品ID数组
     *    order_promotion_gids    参与订单促销的商品ID数组
     *    order_promotion_list    订单促销列表
     *
     * }
     */
    private function getPromotion()
    {
        $cart_goods_list = $this->Cart->getAll($this->keys);
        if (!$cart_goods_list) {
            $this->error = '购物车中没有任何商品';
            return FALSE;
        }
        $result = array();
        $result['goods_count_price'] = 0;
        $result['goods_count_num'] = 0;
        $result['goods_promotion_price'] = 0;
        $result['give_point']['goods_promotion_point'] = 0;
        $result['give_point']['order_promotion_point'] = 0;
        $result['goods_promotion_gids'] = array();
        $result['order_promotion_gids'] = array();
        $result['order_promotion_list'] = array();
        foreach ($cart_goods_list as $dateline => $goods) {
            /* 参加单品促销 */
            $goods['prom_price'] = -1;
            if ($goods['prom_id'] > 0) {
                $pro_map = array();
                $pro_map['status'] = 1;
                $pro_map['start_time'] = array("ELT", NOW_TIME);
                $pro_map['end_time'] = array("EGT", NOW_TIME);
                $promotion_goods = model('goods_promotion')->where($pro_map)->find();
                if ($promotion_goods) {
                    if ($promotion_goods['money'] <= $goods['total_price']) {
                        $result['goods_promotion_gids'][] = $goods['goods_id'];
                        switch ($promotion_goods['award_type']) {
                            // /* 直接打折 */
                            // case '1':
                            // 	$goods['prom_price'] = sprintf('%.2f', $goods['total_price'] * $promotion_goods['award_value'] / 100);
                            // 	break;
                            // /* 满额减价 */
                            // case '2':
                            // 	$goods['prom_price'] = $promotion_goods['award_value'];
                            // 	break;
                            case '1':
                            case '2':
                                $goods['prom_price'] = $goods['total_price'];
                                break;
                            /* 满送优惠券 */
                            default:
                                $goods['give_coupons_id'] = (int)$promotion_goods['award_value'];
                                $goods['prom_price'] = 0;
                                break;
                        }
                        $goods['promotion_id'] = $promotion_goods['id'];
                        $goods['prom_name'] = $promotion_goods['name'];
                    }
                }
            }
            $goods['give_point'] = $this->getGoodsPoint($goods['shop_price'], $goods['goods_num'], $goods['give_integral']);
            $promotion_goods_point[$goods['goods_id']] = $goods['give_point'];
            if ($goods['prom_price'] > -1) {
                $result['give_point']['goods_promotion_point'] += $goods['give_point'];
                $goods['total_price'] -= ($goods['total_price'] - $goods['prom_price']);//改变小计
            } else {
                $result['goods_promotion_price'] += $goods['total_price'];
                $result['order_promotion_gids'][] = $goods['goods_id'];
                $result['give_point']['order_promotion_point'] += $goods['give_point'];
                $goods['give_point'] = 0;
            }
            $result['goods_count_price'] += $goods['total_price'];
            $promotion_goods_price[$goods['goods_id']] = $goods['total_price'];
            $result['goods_count_num'] += $goods['goods_num'];
            $goods['total_price'] = sprintf('%.2f', $goods['total_price']);
            $cart_goods_list[$dateline] = $goods;
        }
        $this->cart_goods_list = $cart_goods_list;
        /* 查询出当前满足的订单促销 */
        if ($result['goods_promotion_price']) {
            $pro_map['status'] = 1;
            $pro_map['start_time'] = array("ELT", NOW_TIME);
            $pro_map['end_time'] = array("EGT", NOW_TIME);
            $pro_map['money'] = array("ELT", $result['goods_promotion_price']);
            $tmp_promotions = model('order_promotion')->where($pro_map)->select();
            $tmp_money = $result['goods_promotion_price'];
            foreach ($tmp_promotions as $key => $value) {
                if ($value['goods_id']) $value['goods_id'] = str2arr($value['goods_id']);
                foreach ($value['goods_id'] as $gid) {
                    $tmp_money -= $promotion_goods_price[$gid];
                }
                if ($tmp_money < $value['money']) continue;
                $result['order_promotion_list'][$value['id']] = $value;
            }
        }
        $result['goods_count_price'] = sprintf('%.2f', $result['goods_count_price']);
        $result['goods_promotion_price'] = sprintf('%.2f', $result['goods_promotion_price']);
        $result['promotion_goods_price'] = $promotion_goods_price;
        $result['promotion_goods_point'] = $promotion_goods_point;
        return $result;
    }

    private function getKey()
    {
        if (empty($_GET['keys'])) {
            $keys = cookie('norder_keys');
        } else {
            $keys = authcode(trim($_GET['keys']), 'ENCODE');
        }
        $this->keys = $keys;
        cookie('norder_keys', $keys);
        return $keys;
    }

    /* 获取订单结算临时数据 */
    private function getNorderKeys()
    {
        $norder_keys = ($this->user_info['norder_keys']) ? unserialize($this->user_info['norder_keys']) : array();
        if (!$norder_keys || $norder_keys['keys'] != $this->keys) {
            $norder_keys = array(
                'keys' => $this->keys,
                'address_id' => $this->address_id,
                'pay_type' => 0,
                'delivery_id' => 0,
                'invoice_title' => '',//发票抬头
                'invoice_type' => '',//发票类型
                'coupons_id' => 0,//优惠券
                'coupons_sn' => 0,//优惠券
                'promotion_id' => 0,//订单促销ID
            );
        }
        $this->updateNorderKeys(serialize($norder_keys));
        return $norder_keys;
    }

    /* 设置订单结算临时数据 */
    private function setNorderKeys($k, $v)
    {
        if (empty($k)) return FALSE;
        $norder_keys = $this->getNorderKeys();
        if (is_array($k)) {
            $norder_keys = array_merge($norder_keys, $k);
        } else {
            $norder_keys[$k] = $v;
        }
        return $this->updateNorderKeys(serialize($norder_keys));
    }

    /* 清空订单结算临时数据 */
    private function updateNorderKeys($norder_keys = '')
    {
        return model('user')->update(array('id' => $this->uid, 'norder_keys' => $norder_keys), FALSE);
    }


    /* 提交订单 */
    public function submit()
    {
        extract($_GET);
        if ($pay_type == NULL) {
            showmessage('请选择支付方式');
        }
        $_order = $this->getOrderInfo('array');
        $address = (int)$address;
        $pay_type = (int)$pay_type;
        $delivery_id = (int)$delivery_id;
        if ($address < 1) {
            showmessage('请选择收货地址');
        }
        /* 读取收货人信息 */
        $uAddress = model('UserAddress')->getById($address);
        if (!$uAddress) {
            showmessage('收货地址不存在');
        }

        if (!in_array($pay_type, array('0', '1'))) {
            showmessage('请选择支付方式');
        }

        if ($delivery_id < 1) {
            showmessage('请选择配送方式');
        }

        if (!$_order['delivery'] || !isset($_order['delivery'][$delivery_id])) {
            showmessage('当前地址不支持配送');
        }

        $order_goods = $this->Cart->getAll();
        if (!$order_goods) {
            showmessage('您的购物车中没有商品');
        }
        $source = defined('IS_MOBILE') ? 1 : 0;
        if (defined('IS_WECHAT')) {
            $source = 2;
        }
        /* 创建订单数据 */
        $info = array(
            'order_sn' => build_order_no(),
            'user_id' => $this->uid,
            'pay_code' => $pay_type,//支付方式
            'delivery_id' => $delivery_id,//配送方式ID
            'delivery_txt' => $_order['delivery'][$delivery_id]['name'],//配送方式名称
            'pay_type' => $pay_type,
            'source' => $source,//订单来源
            'pay_status' => 0,//支付状态
            'order_status' => 0,//订单状态
            'delivery_status' => 0,//配货状态
            /* 收货信息 */
            'accept_name' => $uAddress['address_name'],
            'mobile' => $uAddress['mobile'],
            'zipcode' => $uAddress['zipcode'],
            'telphone' => $uAddress['tel'],
            'province' => $uAddress['province'],
            'city' => $uAddress['city'],
            'area' => $uAddress['district'],
            'address' => $uAddress['address'],
            /* 订单价格 */
            'payable_amount' => $_order['goods_count_price'],
            'real_amount' => $_order['real_amount'],
            'payable_freight' => $_order['p_delivery'],
            'taxes' => $_order['p_invoicerate'],
            'insured' => 0,
            'discount' => 0,
            'coupons_id' => $_order['p_coupons_id'],
            'coupons' => $_order['p_coupons'],
            'integral' => 0,
            /* 优惠信息 */
            'give_point' => $_order['p_give_point'],//赠送积分
            'promotion_id' => (int)$_order['promotion_id'],//订单促销ID
            'promotion_msg' => (string)$_order['promotion_msg'],//订单促销名称
            'give_coupons_id' => (int)$_order['give_coupons_id'],//赠送优惠券ID
            /* 其它信息 */
            'postscript' => $postscript,
        );

        /* 支付状态 */
        if ($_order['real_amount'] == 0) {
            $info['pay_status'] = 1;
            $info['pay_time'] = NOW_TIME;
        }
        if (getconfig('site_invoice')) {
            $info['invoice_title'] = serialize(array($invoice_type, $invoice_title));
        }

        $order_id = $this->order_db->update($info);
        if (!$order_id) {
            showmessage($this->order_db->getError());
        }
        $cart_goods_list = $this->cart_goods_list;
        $order_goods_info = array();
        $site_integralexchange = (int)getconfig('site_integralexchange');
        foreach ($cart_goods_list as $k => $v) {
            $goods_info = model('goods')->detail($v['id']);
            $item['order_id'] = $order_id;
            $item['goods_id'] = $v['id'];
            $item['product_id'] = $v['product_id'];
            $item['thumb'] = $v['goods_img'];
            $item['barcode'] = (isset($v['products_barcode'])) ? $v['products_barcode'] : $v['barcode'];
            $item['name'] = $v['name'];
            $item['spec_array'] = !empty($v['spec_array']) ? serialize($v['spec_array']) : '';
            $item['shop_price'] = $v['shop_price'];
            $item['integral'] = 0;
            $item['shop_number'] = $v['goods_num'];
            $item['goods_number'] = $v['goods_number'];
            $item['dateline'] = NOW_TIME;
            $item['user_id'] = $this->uid;
            $item['cat_ids'] = $goods_info['cat_ids'];
            $item['brand_id'] = $goods_info['brand_id'];
            $item['give_point'] = (int)$v['give_point'];
            $item['promotion_id'] = (int)$v['promotion_id'];
            $item['give_coupons_id'] = (int)$v['give_coupons_id'];
            $order_goods_info[] = $item;
        }

        $result = model('order_goods')->addAll($order_goods_info);
        if (!$result) {
            $this->order_db->delete($order_id);
            showmessage('订单提交失败！');
        }
        /* 减少库存 */
        if (getconfig('site_inventorysetup') == 1) {
            foreach ($order_goods as $k => $v) {
                model('goods')->setDecNumber($v['id'], $v['product_id'], $v['goods_num']);
            }
        }
        /* 删除购物车数据 */

        if (empty($this->keys)) {
            $this->Cart->clear();
        } else {
            $cart_keys = explode(",", authcode($this->keys));
            foreach ($cart_keys AS $timestamp) {
                $this->Cart->delItem($timestamp);
            }
        }
        if ($info['coupons_id'] > 0) {
            $_coupons_info = array();
            $_coupons_info['id'] = $info['coupons_id'];
            $_coupons_info['status'] = 2;
            $_coupons_info['use_order'] = $info['order_sn'];
            $_coupons_info['use_time'] = NOW_TIME;
            model('coupons_list')->save($_coupons_info);
        }
        runhook('buy_submit_success', $info);
        /* 通知推送 */
        runhook('n_order_success', array('order_sn' => $info['order_sn']));
        $this->updateNorderKeys('');
        cookie('norder_keys', NULL);
        /* 最后对数据处理 */
        showmessage('订单提交成功', U('detail', array('order_sn' => $info['order_sn'])), 1, '', '', '', $info);
    }

    /***
     * 处理订单支付
     */
    public function detail()
    {
        extract($_GET);
        if (empty($order_sn)) showmessage('请勿非法访问');
        $u_info = $this->user_info;
        $rs = $this->order_db->where(array('order_sn' => $order_sn))->find();
        if (!$rs || $rs['user_id'] != $this->uid) showmessage('抱歉，您无法查看此订单');
        /*获取支付方式*/
        $payment = getcache('payment', 'pay');
        if ($payment['bank']['enabled']) {
            $banks = explode(',', $payment['bank']['config']['banks']);
        }
        if (IS_POST) {
            if ($rs['pay_status'] == 1)
                showmessage("该订单已支付");
            // 本次应付总金额
            $total_fee = round($rs['real_amount'] - $rs['balance_amount'], 2);
            if (C('balance_enable') == 1 && $user_money_check == 1 && $u_info['user_money'] > 0) {
                // 实际扣除金额
                $pay_money = ($u_info['user_money'] < $total_fee) ? $u_info['user_money'] : $total_fee;
                if ($pay_money > $u_info['user_money']) showmessage('您的余额不足以支付！');
                /* 扣除本次余额支付的金额 */
                model('user')->where(array('id' => $u_info['id']))->setDec('user_money', $pay_money);
                /* 订单余额支付的实际冻结金额 */
                $this->order_db->where(array('id' => $rs['id']))->setInc('balance_amount', $pay_money);
                // 写入财务流水
                $data = array();
                $data['user_id'] = $u_info['id'];
                $data['money'] = $pay_money;
                $data['msg'] = '余额支付(订单号:' . $rs['order_sn'] . ')';
                $data['dateline'] = NOW_TIME;
                model('user_moneylog')->add($data);
                if ($pay_money < $total_fee) {
                    // 写入冻结金额
                    model('user')->where(array('id' => $u_info['id']))->setInc('freeze_money', $pay_money);
                } else {

                    $result = model('order')->where(array('id' => $rs['id']))->save(array('pay_status' => 1, 'pay_time' => NOW_TIME));

                    // 扣除当前会员冻结的余额
                    model('user')->where(array('id' => $u_info['id']))->setDec('freeze_money', $rs['balance_amount']);
                    // 更改动态状态及日志
                    model('order_log')->update(array('order_sn' => $rs['order_sn'], 'user_id' => $u_info['id'], 'action' => '支付成功', 'msg' => '会员余额支付', 'issystem' => 1, 'dateline' => NOW_TIME), FALSE);
                    model('order_track')->update(array('order_sn' => $rs['order_sn'], 'track_msg' => '您的订单已付款(余额支付)，请等待系统确认'));
                    /* 通知推送 */
                    runhook('n_pay_success', array('order_sn' => $rs['order_sn']));
                    redirect(U('Goods/Order/pay_success', array('order_sn' => $rs['order_sn'])));
                }
                $total_fee = $total_fee - $pay_money;
            }


            if (empty($pay_code)) showmessage('请选择支付方式');
            if (!$payment[$pay_code]) showmessage('选择的支付方式不存在');
            if ($pay_bank && !in_array($pay_bank, $banks)) showmessage('选择的支付网银错误');
            $this->order_db->update(array('id' => $rs['id'], 'pay_code' => $pay_code));
            $product_info = array();
            $product_info['trade_sn'] = $rs['order_sn'];
            $product_info['total_fee'] = $total_fee;
            $product_info['subject'] = '订单号：' . $rs['order_sn'];
            $product_info['pay_bank'] = $pay_bank;
            libfile('pay_factory');
            /*获取实例*/
            $pay_factory = new pay_factory($pay_code);
            /*设置订单信息*/
            $pay_factory->set_productinfo($product_info);
            /*微信支付*/
            if ($pay_code == 'wechat_qr') {

                $code_url = $pay_factory->get_code_url();
                include template('wechat_pay');

                /*微信支付*/
            } elseif ($pay_code == 'wechat_js') {
                R('Goods/Pay/wechatWapPay', array($rs['order_sn'], $pay_code));
                /*添加ecpay验证请求*/
                /*QQ:1716771371*/
            } elseif ($pay_code == 'ecpayforalipay') {
                /*获取Urlpost*/
                $ecpay_postdata = $pay_factory->getPrepareData();
                $ecpay_config = $pay_factory->getConfig();
                echo "<form id='my_demo_post' method={$ecpay_config['pay_method']} action={$ecpay_config['pay_url']}>";
                foreach ($ecpay_postdata as $key => $value) {
                    echo "<input type='hidden' name={$key} value={$value}>";
                }
                echo "</form>";
                echo "<script>
                            var form=document.getElementById('my_demo_post');
                            form.submit();
                      </script>";
            } else {
                $pay_url = $pay_factory->get_code();
                if (defined('IS_MOBILE')) {
                    showmessage('支付请求创建成功', $pay_url, 1);
                } else {
                    redirect($pay_url);
                }
            }

        } else {


            $applie = defined('IS_MOBILE') ? 'wap' : 'pc';
            // 根据物流ID 获取该物流配置信息
            $delivery_config = unserialize(model('delivery')->getFieldById($rs['delivery_id'], 'pays'));
            foreach ($payment as $code => $pay) {
                /*1.支付开启，2.线上，3.*/
                if (!$pay['enabled'] || !$pay['isonline'] || $code == 'bank' || !preg_match("/" . $applie . "/", $pay['applies']) || !in_array($code, $delivery_config)) {
                    unset($payment[$code]);
                    continue;
                }
                $pay['config'] = json_decode($pay['config'], TRUE);
                $payment[$code] = $pay;
            }
            $banks = (in_array('bank', $delivery_config)) ? $banks : FALSE;
            $pay = $this->pays[$rs['pay_type']];
            $SEO = seo(0, '订单详情');
            if ($rs['pay_status'] == 1) {
                $SEO = seo(0, '订单支付成功');
                include template('order_success');
            } else {
                $rs['pay_type'] ? $SEO = seo(0, '订单 - 货到付款') : $SEO = seo(0, '订单支付');
                include template('order_detail');
            }
        }
    }

    /* 订单支付成功页 */
    public function pay_success($order_sn = '')
    {
        $rs = $this->order_db->where(array('order_sn' => $order_sn))->find();
        include template('order_success');
    }

    /* 获取某订单支付方式 */
    public function getOrderPayState($order_sn)
    {
        $rs = $this->order_db->where(array('order_sn' => $order_sn))->find();
        if (!$rs || $rs['user_id'] != $this->uid) {
            showmessage('抱歉，您无法查看此订单');
        } else {
            showmessage('订单查看成功', '', 1, '', '', '', $rs);
        }
    }

    /* 获取某商品应得积分 */
    private function getGoodsPoint($price, $number, $point)
    {
        $site_integralexchange = (int)getconfig('site_integralexchange');
        if ($site_integralexchange > 0) {
            if ($point == -1) {
                $give_point = $site_integralexchange;
            } elseif ($point > 0) {
                $give_point = $point;
            } else {
                $give_point = 0;
            }
        }
        return ceil($number * $price * $give_point);
    }

    /* 根据配送ID显示购物模式 */
    private function getPays($delivery_id = 0)
    {
        $delivery_id = (int)$delivery_id;
        if ($delivery_id < 1) return FALSE;
        $deliverys = model('delivery')->detail($delivery_id);
        $deliverys['type'] = unserialize($deliverys['type']);
        $result = array();
        foreach ($deliverys['type'] as $type) {
            $result[$type] = $this->pays[$type];
        }
        return $result;
    }


    /* 查找配送方式 */
    private function getDelivery($address_id, $pay_type = 0)
    {
        $region_ids = model('user_address')->field('province, city, district')->find($address_id);
        if (!$region_ids) return FALSE;
        $region_ids = array_values($region_ids);
        $map = $sqlmap = array();
        foreach ($region_ids as $region_id) {
            $map[] = "FIND_IN_SET($region_id, `region_id`)";
        }
        $sqlmap['_string'] = JOIN(" OR ", $map);
        $delivery_regions = model('delivery_region')->where($sqlmap)->group('delivery_id')->getField('delivery_id, weightprice', TRUE);
        if (!$delivery_regions) return FALSE;

        $sqlmap = array();
        $sqlmap['id'] = array("IN", array_keys($delivery_regions));
        $sqlmap['status'] = 1;
        $deliverys = model('delivery')->where($sqlmap)->getField('id, name, enname, descript', TRUE);
        foreach ($deliverys AS $delivery_id => $delivery) {
            $weightprice = explode(',', $delivery_regions[$delivery_id]);
            $delivery['weightprice'] = current($weightprice);
            $deliverys[$delivery_id] = $delivery;
        }
        return $deliverys;
    }

    /* 选择收货地址 */
    public function address()
    {
        $sqlmap = array();
        $sqlmap['user_id'] = $this->uid;
        $address_lists = model('user_address')->where($sqlmap)->getField('id, user_id, address_name, province, city, district, address, zipcode, tel, mobile');
        if (IS_POST) {
            $address_id = (int)$_GET['address_id'];
            if ($address_id < 1 || !$address_lists[$address_id]) {
                showmessage('收货地址非法');
            }
            $this->setNorderKeys(array('address_id' => $address_id, 'delivery_id' => 0));
            showmessage('收货地址选择成功', NULL, 1);
        } else {
            $address_id = $this->address_id;
            $SEO = seo(0, '收货地址');
            include template('address');
        }
    }

    /* 选择配送方式 */
    public function delivery()
    {
        $norder_keys = $this->getNorderKeys();
        if (!$norder_keys) {
            showmessage('订单参数错误', __ROOT__);
        }
        /* 支付方式 */
        $payment = $pay_map = array();
        $pay_map['enabled'] = 1;
        $pay_map['isonline'] = 1;
        $payment[0] = model('payment')->where($pay_map)->order("`isonline` ASC")->count() ? '在线支付' : '';
        $pay_map['isonline'] = 0;
        $payment[1] = model('payment')->where($pay_map)->order("`isonline` ASC")->count() ? '货到付款' : '';
        $deliverys = $this->getDelivery($norder_keys['address_id']);
        if (IS_POST) {
            if (!isset($_GET['pay_type'])) {
                showmessage('请选择支付方式');
            }
            if (!isset($_GET['delivery_id'])) {
                showmessage('请选择配送方式');
            }
            $_GET['pay_type'] = (int)$_GET['pay_type'];
            $_GET['delivery_id'] = (int)$_GET['delivery_id'];
            $this->setNorderKeys(array('pay_type' => $_GET['pay_type'], 'delivery_id' => $_GET['delivery_id']));
            showmessage('支付&配送方式选择成功', '', 1);
        } else {
            include template('delivery');
        }
    }

    /* 收货地址选择 */

    /* 选择优惠券 */
    public function coupons()
    {
        $SEO = seo(0, '选择优惠券');
        $norder_keys = $this->getNorderKeys();
        if (!$norder_keys) {
            showmessage('订单参数错误', __ROOT__);
        }
        /* 获取购物数据 */
        $rs = $this->getPromotion();
        if ($rs == FALSE) {
            showmessage($this->error, U('Goods/Cart/index'));
        }
        /* 所有优惠券 */
        $allow_lists = $not_allow_lists = array();

        $sqlmap = array();
        $sqlmap['user_id'] = $this->uid;
        $sqlmap['status'] = 1;
        $sqlmap['start_time'] = array("LT", NOW_TIME);
        $sqlmap['end_time'] = array("GT", NOW_TIME);

        $allow_map = array();
        $allow_map['value'] = array("ELT", $rs['goods_count_price']);
        $allow_map = array_merge($allow_map, $sqlmap);

        $allow_lists = model('coupons_list')->where($allow_map)->select();

        /* 不可用优惠券 */
        $not_allow_map = array();
        $not_allow_map['value'] = array("GT", $rs['goods_count_price']);
        $not_allow_map = array_merge($not_allow_map, $sqlmap);

        $not_allow_lists = model('coupons_list')->where($not_allow_map)->select();

        if (IS_POST) {
            $coupons_id = (int)$_GET['coupons_id'];
            if ($coupons_id < 0) {
                showmessage('优惠券信息非法');
            }

            $coupons_sn = model('coupons_list')->getFieldById($coupons_id, 'sn');
            $this->setNorderKeys(array('coupons_id' => $coupons_id, 'coupons_sn' => $coupons_sn));
            showmessage('优惠券选择成功', NULL, 1);
        } else {
            include template('coupons');
        }
    }

    /* 填写发票信息 */
    public function invoicerate()
    {
        if (!getconfig('site_invoice') || !getconfig('site_invoicecontent')) showmessage('系统已关闭发票功能', U('Goods/Order/index'));
        $norder_keys = $this->getNorderKeys();
        $site_invoicecontent = explode("\r\n", getconfig('site_invoicecontent'));
        if (IS_POST) {
            $invoice_title = htmlspecialchars($_GET['invoice_title']);
            $invoice_type = htmlspecialchars($_GET['invoice_type']);
            if (!in_array($invoice_type, $site_invoicecontent)) {
                showmessage('发票内容选择错误');
            }
            $this->setNorderKeys(array('invoice_title' => $invoice_title, 'invoice_type' => $invoice_type));
            showmessage('发票信息设置成功', NULL, 1);
        } else {
            $SEO = seo(0, '发票信息');
            include template('invoicerate');
        }
    }

    /* 订单促销 */
    public function promotion()
    {
        $norder_keys = $this->getNorderKeys();
        $rs = $this->getPromotion();
        $order_promotions = $rs['order_promotion_list'];
        if (IS_POST) {
            $promotion_id = (int)$_GET['promotion_id'];
            if ($promotion_id < 1 || !$order_promotions[$promotion_id]) {
                showmessage('促销信息非法');
            }
            $this->setNorderKeys(array('promotion_id' => $promotion_id));
            showmessage('促销信息选择成功', NULL, 1);
        } else {
            $SEO = seo(0, '订单促销');
            include template('promotion');
        }
    }

    public function getOrderState($order_sn)
    {
        $sqlmap = array();
        $sqlmap['order_sn'] = $order_sn;
        $pay_status = $this->order_db->where($sqlmap)->field('pay_status')->find();
        $this->ajaxReturn($pay_status);
    }
}