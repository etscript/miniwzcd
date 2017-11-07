<?php
namespace Api\Controller;
use Think\Controller;
class WxpayController extends Controller{
	//构造函数
    public function _initialize(){
    	//php 判断http还是https
    	$this->http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
		vendor('wxpay.wxpay');
	}

	//***************************
	//  微信支付 接口
	//***************************
	public function wxpay(){
		$pay_sn = trim($_REQUEST['order_sn']);
		if (!$pay_sn) {
			echo json_encode(array('status'=>0,'err'=>'支付信息错误！'));
			exit();
		}

		$order_info = M('order')->where('order_sn="'.$pay_sn.'"')->find();
		if (!$order_info) {
			echo json_encode(array('status'=>0,'err'=>'没有找到支付订单！'));
			exit();
		}

		if (intval($order_info['status'])!=10) {
			echo json_encode(array('status'=>0,'err'=>'订单状态异常！'));
			exit();
		}

		//余额支付，判断余额是否足够
		if (isset($_REQUEST['paytype']) && $_REQUEST['paytype']=='amount') {
			$amount = M('user')->where('id='.intval($order_info['uid']))->getField('amount');
			if (floatval($amount)<floatval($order_info['amount'])) {
				echo json_encode(array('status'=>0,'err'=>'余额不足！'));
				exit();
			}
			if ($order_info['type']=='weixin') {
				$uppaytype = M('order')->where('id='.intval($order_info['id']))->save(array('type'=>'amount'));
				if (!$uppaytype) {
					echo json_encode(array('status'=>0,'err'=>'支付类型错误！'));
					exit();
				}
			}

			//修改会员余额
			$data = array();
			$data['amount'] = floatval($amount)-floatval($order_info['amount']);
			if (floatval($data['amount'])<0) {
				echo json_encode(array('status'=>0,'err'=>'余额不足。'));
				exit();
			}
			$upuser = M('user')->where('id='.intval($order_info['uid']))->save($data);
			if ($upuser) {
				$newdata = array();
				$newdata['order_sn'] = $order_info['order_sn'];
				$newdata['pay_type'] = 'amount';
				$newdata['trade_no'] = '';
				$newdata['total_fee'] = floatval($order_info['amount'])*100;
				$upres = $this->orderhandle($newdata);
				if (is_array($upres)) {
					echo json_encode(array('status'=>1));
					exit();
				} else {
					echo json_encode(array('status'=>0,'err'=>$upres));
					exit();
				}
			} else {
				echo json_encode(array('status'=>0,'err'=>'支付失败！'));
				exit();
			}
		}

		//①、获取用户openid
		$tools = new \JsApiPay();
		$openId = M('user')->where('id='.intval($order_info['uid']))->getField('openid');
		if (!$openId) {
			echo json_encode(array('status'=>0,'err'=>'用户状态异常！'));
			exit();
		}
		//$openId = 'oVjTt0EsH0dbqMY5bNGQQ2RsZcXA';

		//②、统一下单
		$input = new \WxPayUnifiedOrder();
		$input->SetBody("万众充电小程序订单_".trim($order_info['order_sn']));
		$input->SetAttach("万众充电小程序订单_".trim($order_info['order_sn']));
		$input->SetOut_trade_no($pay_sn);
		$input->SetTotal_fee(floatval($order_info['amount'])*100);
		$input->SetTime_start(date("YmdHis"));
		$input->SetTime_expire(date("YmdHis", time() + 3600));
		$input->SetGoods_tag("万众充电小程序订单_".trim($order_info['order_sn']));
		$input->SetNotify_url('https://gzleren.com/miniwzcd/index.php/Api/Wxpay/notify');
		$input->SetTrade_type("JSAPI");
		$input->SetOpenid($openId);
		$order = \WxPayApi::unifiedOrder($input);
		//echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
		// $jsApiParameters = $tools->GetJsApiParameters($order);
		// if (!$jsApiParameters) {
		// 	echo json_encode(array('status'=>0,'err'=>'err：订单异常！'));
		// 	exit();
		// }
		//$jsdata = json_decode($jsApiParameters,true);
		
		$arr = array();
		$arr['appId'] = $order['appid'];
		$arr['nonceStr'] = $order['nonce_str'];
		$arr['package'] = "prepay_id=".$order['prepay_id'];
		$arr['signType'] = "MD5";
		$arr['timeStamp'] = (string)time();
		$str = $this->ToUrlParams($arr);
		$jmstr = $str."&key=".\WxPayConfig::KEY;
		$arr['paySign'] = strtoupper(MD5($jmstr));
		echo json_encode(array('status'=>1,'arr'=>$arr));
		exit();
		//获取共享收货地址js函数参数
		//$editAddress = $tools->GetEditAddressParameters();
		//$this->assign('jsApiParameters',$jsApiParameters);
		//$this->assign('editAddress',$editAddress);
	}

	//***************************
	//  余额充值 接口
	//***************************
	public function chargemoney () {
		$uid = intval($_REQUEST['uid']);
		$check_user = M('user')->where('id='.intval($uid).' AND del=0')->find();
		if (!$check_user) {
			echo json_encode(array('status'=>0,'err'=>'登录状态异常.'));
			exit();
		}

		$money = floatval($_REQUEST['money']);
		if (!$money) {
			echo json_encode(array('status'=>0,'err'=>'请输入充值金额.'));
			exit();
		}
		// $check = M('userinfo_log')->where('uid='.intval($uid))->find();
		// if (!$check) {
		// 	echo json_encode(array('status'=>0,'err'=>'会员信息异常.'));
		// 	exit();
		// }

		// $level_info = M('user_level')->where('id='.intval($utype))->find();

		//下单
		$data = array();
		$data['uid']=intval($uid);
		$data['addtime']=time();
		$data['del']=0; 
		$data['type']='weixin';
		//订单状态 10未付款20代发货30确认收货（待收货）40交易关闭50交易完成
		$data['status']=10;//未付款
		$data['product_num']=intval(1);
		/*******解决屠涂同一订单重复支付问题 lisa**********/
		$data['order_sn'] = $this->build_order_no();//生成唯一订单号
		$data['order_type'] = 3;
		$data['price']=floatval($money);
	    $data['amount']=floatval($money);

		$result = M('order')->add($data);
		if(!$result){
			echo json_encode(array('status'=>0,'err'=>'下单失败.'));
			exit();
		}

		//添加产品订单表
		$date = array();
		$date['pid'] = intval($_REQUEST['utype']);
		$date['order_id'] = $result;
		$date['name'] = '会员余额充值';
		$date['price'] = $data['amount'];
		$date['photo_x'] = '';
		$date['num'] = 1;
		$date['addtime'] = time();
		$res = M('order_product')->add($date);
		if(!$res){
			echo json_encode(array('status'=>0,'err'=>'下单失败.'.__LINE__));
			exit();
		}

		//①、获取用户openid
		$tools = new \JsApiPay();
		$openId = M('user')->where('id='.intval($uid))->getField('openid');
		if (!$openId) {
			echo json_encode(array('status'=>0,'err'=>'用户状态异常！'));
			exit();
		}

		$desc = '万众充电_会员余额充值_'.trim($data['order_sn']);

		//②、统一下单
		$input = new \WxPayUnifiedOrder();
		$input->SetBody($desc);
		$input->SetAttach($desc);
		$input->SetOut_trade_no(trim($data['order_sn']));
		$input->SetTotal_fee(floatval($data['amount'])*100);
		$input->SetTime_start(date("YmdHis"));
		$input->SetTime_expire(date("YmdHis", time() + 3600));
		$input->SetGoods_tag($desc);
		$input->SetNotify_url('https://gzleren.com/miniwzcd/index.php/Api/Wxpay/notify');
		$input->SetTrade_type("JSAPI");
		$input->SetOpenid($openId);
		$order = \WxPayApi::unifiedOrder($input);
		// dump($order);
		//echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
		//printf_info($order);
		$arr = array();
		$arr['appId'] = $order['appid'];
		$arr['nonceStr'] = $order['nonce_str'];
		$arr['package'] = "prepay_id=".$order['prepay_id'];
		$arr['signType'] = "MD5";
		$arr['timeStamp'] = (string)time();
		$str = $this->ToUrlParams($arr);
		$jmstr = $str."&key=".\WxPayConfig::KEY;
		$arr['paySign'] = strtoupper(MD5($jmstr));
		echo json_encode(array('status'=>1,'arr'=>$arr));
		exit();
	}

	//***************************
	//  押金缴纳 接口
	//***************************
	public function paycashmoney () {
		$uid = intval($_REQUEST['uid']);
		$check_user = M('user')->where('id='.intval($uid).' AND del=0')->find();
		if (!$check_user) {
			echo json_encode(array('status'=>0,'err'=>'登录状态异常.'));
			exit();
		}

		$check_order = M('order')->where('uid='.intval($uid).' AND status>10 AND order_type=2')->getField('id');
		if (intval($check_order)>0) {
			echo json_encode(array('status'=>0,'err'=>'您已支付押金，无需重复支付.'));
			exit();
		}

		//下单
		$data = array();
		$data['uid']=intval($uid);
		$data['addtime']=time();
		$data['del']=0; 
		$data['type']='weixin';
		//订单状态 10未付款20代发货30确认收货（待收货）40交易关闭50交易完成
		$data['status']=10;//未付款
		$data['product_num']=intval(1);
		/*******解决屠涂同一订单重复支付问题 lisa**********/
		$data['order_sn'] = $this->build_order_no();//生成唯一订单号
		$data['order_type'] = 2;
		$data['price']=floatval(500);
	    $data['amount']=floatval(500);

		$result = M('order')->add($data);
		if(!$result){
			echo json_encode(array('status'=>0,'err'=>'下单失败.'));
			exit();
		}

		//添加产品订单表
		$date = array();
		$date['pid'] = intval($_REQUEST['utype']);
		$date['order_id'] = $result;
		$date['name'] = '会员押金支付';
		$date['price'] = $data['amount'];
		$date['photo_x'] = '';
		$date['num'] = 1;
		$date['addtime'] = time();
		$res = M('order_product')->add($date);
		if(!$res){
			echo json_encode(array('status'=>0,'err'=>'下单失败.'.__LINE__));
			exit();
		}

		//①、获取用户openid
		$tools = new \JsApiPay();
		$openId = M('user')->where('id='.intval($uid))->getField('openid');
		if (!$openId) {
			echo json_encode(array('status'=>0,'err'=>'用户状态异常！'));
			exit();
		}

		$desc = '万众充电_押金支付_'.trim($data['order_sn']);

		//②、统一下单
		$input = new \WxPayUnifiedOrder();
		$input->SetBody($desc);
		$input->SetAttach($desc);
		$input->SetOut_trade_no(trim($data['order_sn']));
		$input->SetTotal_fee(floatval($data['amount'])*100);
		$input->SetTime_start(date("YmdHis"));
		$input->SetTime_expire(date("YmdHis", time() + 3600));
		$input->SetGoods_tag($desc);
		$input->SetNotify_url('https://gzleren.com/miniwzcd/index.php/Api/Wxpay/notify');
		$input->SetTrade_type("JSAPI");
		$input->SetOpenid($openId);
		$order = \WxPayApi::unifiedOrder($input);
		//echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
		//printf_info($order);
		$arr = array();
		$arr['appId'] = $order['appid'];
		$arr['nonceStr'] = $order['nonce_str'];
		$arr['package'] = "prepay_id=".$order['prepay_id'];
		$arr['signType'] = "MD5";
		$arr['timeStamp'] = (string)time();
		$str = $this->ToUrlParams($arr);
		$jmstr = $str."&key=".\WxPayConfig::KEY;
		$arr['paySign'] = strtoupper(MD5($jmstr));
		echo json_encode(array('status'=>1,'arr'=>$arr));
		exit();
	}

	//***************************
	//  支付回调 接口
	//***************************
	public function notify(){
		/*$notify = new \PayNotifyCallBack();
		$notify->Handle(false);*/

		$res_xml = file_get_contents("php://input");
		libxml_disable_entity_loader(true);
		$ret = json_decode(json_encode(simplexml_load_string($res_xml,'simpleXMLElement',LIBXML_NOCDATA)),true);

		$path = "./Data/log/";
		if (!is_dir($path)){
			mkdir($path,0777);  // 创建文件夹test,并给777的权限（所有权限）
		}
		$content = date("Y-m-d H:i:s").'=>'.json_encode($ret);  // 写入的内容
		$file = $path."weixin_".date("Ymd").".log";    // 写入的文件
		file_put_contents($file,$content,FILE_APPEND);  // 最简单的快速的以追加的方式写入写入方法，

		$data = array();
		$data['order_sn'] = $ret['out_trade_no'];
		$data['pay_type'] = 'weixin';
		$data['trade_no'] = $ret['transaction_id'];
		$data['total_fee'] = $ret['total_fee'];
		$result = $this->orderhandle($data);
		if (is_array($result)) {
			$xml = "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg>";
			$xml.="</xml>";
			echo $xml;
		}else{
			$contents = 'error => '.json_encode($result);  // 写入的内容
			$files = $path."error_".date("Ymd").".log";    // 写入的文件
			file_put_contents($files,$contents,FILE_APPEND);  // 最简单的快速的以追加的方式写入写入方法，
			echo 'fail';
		}
	}

	//***************************
	//  订单处理 接口
	//***************************
	public function orderhandle($data){
		$order_sn = trim($data['order_sn']);
		$pay_type = trim($data['pay_type']);
		$trade_no = trim($data['trade_no']);
		$total_fee = floatval($data['total_fee']);
		$check_info = M('order')->where('order_sn="'.$order_sn.'"')->find();
		if (!$check_info) {
			return "订单信息错误...";
		}

		if ($check_info['status']<10 || $check_info['back']>'0') {
			return "订单异常...";
		}

		if ($check_info['status']>10) {
			return array('status'=>1,'data'=>$data);
		}

		$up = array();
		$up['price_h'] = sprintf("%.2f",floatval($total_fee/100));
		$up['type'] = $pay_type;
		$up['status'] = 50;
		$up['trade_no'] = $trade_no;
		$res = M('order')->where('order_sn="'.$order_sn.'"')->save($up);
		if ($res) {

			//增加 充值记录
			$add = array();
			$add['uid'] = intval($check_info['uid']);
			$add['order_id'] = intval($check_info['id']);
			$add['money'] = floatval($check_info['amount']);
			$add['addtime'] = time();
			if (intval($check_info['order_type'])==1) {
				if ($check_info['type']=='amount') {
					$add['ctype'] = 4;
				}
			} else if (intval($check_info['order_type'])==2) {
				$add['ctype'] = 1;
				//修改会员余额和押金，更新用户资料
				if (floatval($add['money'])>0) {
					$this->upUserInfo(intval($check_info['uid']));
				}
				
			} else if (intval($check_info['order_type'])==3) {
				$add['ctype'] = 2;
				//修改会员余额
				$uinfo = M('user')->where('id='.intval($check_info['uid']))->find();
				if ($uinfo && floatval($add['money'])>0) {
					$amount = floatval($uinfo['amount']);
					$udata = array();
					$udata['amount'] = floatval($add['money'])+$amount;
					M('user')->where('id='.intval($check_info['uid']))->save($udata);
				}
			}
			M('recharge')->add($add);

			return array('status'=>1,'data'=>$data);
		}else{
			return '订单处理失败...';
		}
	}

	//***************************
	//  修改会员资料 接口
	//***************************
	public function upUserInfo($uid) {
		if (!$uid) {
			return false;
		}
		//修改会员余额和押金
		$uinfo = M('user')->where('id='.intval($uid).' AND cash_state=0')->find();
		$udata = array();
		if ($uinfo) {
			// $udata['amount'] = floatval($uinfo['amount'])+100;
			$udata['cash_money'] = floatval($uinfo['cash_money'])+500;
			$udata['cash_state'] = 1;
		}

		//更新会员信息
		$authinfo = M('user_auth')->where('uid='.intval($uid).' AND state=2')->find();
		if ($authinfo) {
			$udata['truename'] = $authinfo['truename'];
			$udata['idcard'] = $authinfo['idcard'];
			$udata['tel'] = $authinfo['tel'];
			$udata['authtype'] = 1;
			$udata['authtime'] = time();
			M('user_auth')->where('id='.intval($authinfo['id']))->save(array('state'=>4));
		}

		if ($udata) {
			M('user')->where('id='.intval($uid))->save($udata);
		}
	}

	//构建字符串
	private function ToUrlParams($urlObj)
	{
		$buff = "";
		foreach ($urlObj as $k => $v)
		{
			if($k != "sign"){
				$buff .= $k . "=" . $v . "&";
			}
		}
		
		$buff = trim($buff, "&");
		return $buff;
	}

	 /**针对涂屠生成唯一订单号
	*@return int 返回16位的唯一订单号
	*/
	public function build_order_no(){
		return date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
	}
}
?>
