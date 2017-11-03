<?php
// 本类由系统自动生成，仅供测试用途
namespace Api\Controller;
use Think\Controller;
class UserController extends PublicController {

	//***************************
	//  获取用户数据
	//***************************
	public function getorder(){
		$uid = intval($_REQUEST['userId']);
		$info = M('user')->where('id='.intval($uid).' AND del=0')->find();
		if (!$info) {
			echo json_encode(array('status'=>0,'err'=>'用户信息异常！'));
			exit();
		}

		//获取会员充电电量
		$info['charge'] = floatval(M('order')->where('uid='.intval($uid))->getField('SUM(chong_time)'));

		//会员认证资料提交情况
		$userauth = M('user_auth')->where('uid='.intval($uid))->find();
		if (!$userauth) {
			$info['userauth'] = 0;
		}else{
			$info['userauth'] = intval($userauth['state']);
		}

		echo json_encode(array('status'=>1,'info'=>$info));
		exit();
	}

	//*********************************
	//  获取用户 实名认证审核状态
	//*********************************
	public function getuserauth () {
		$uid = intval($_REQUEST['userId']);
		$info = M('user')->where('id='.intval($uid).' AND del=0')->find();
		if (!$info) {
			echo json_encode(array('status'=>0,'err'=>'用户信息异常！'));
			exit();
		}

		//会员认证资料提交情况
		$userauth = M('user_auth')->where('uid='.intval($uid))->find();
		$reason = '';
		$auth = 0;
		if ($userauth) {
			$reason = $userauth['reason'];
			$auth = intval($userauth['state']);
		}

		echo json_encode(array('status'=>1,'userauth'=>$auth,'reason'=>$reason));
		exit();
	}

	//***************************
	//  获取用户信息
	//***************************
	public function userinfo(){
		$uid = intval($_REQUEST['uid']);
		if (!$uid) {
			echo json_encode(array('status'=>0,'err'=>'非法操作.'));
			exit();
		}

		$user = M("user")->where('id='.intval($uid))->field('id,name,uname,photo,tel')->find();
		if ($user['photo']) {
			if ($user['source']=='') {
				$user['photo'] = __DATAURL__.$user['photo'];
			}
		}else{
			$user['photo'] = __PUBLICURL__.'home/images/moren.png';
		}
		$user['tel'] = substr_replace($user['tel'],'****',3,4);
		echo json_encode(array('status'=>1,'userinfo'=>$user));
		exit();
	}

	//***************************
	//  用户 充电记录 接口
	//***************************
	public function charge_recode(){
		$uid = intval($_REQUEST['uid']);
		if (!$uid) {
			echo json_encode(array('status'=>0,'err'=>'登录状态异常.'));
			exit();
		}

		$page = intval($_REQUEST['page']);
		if (!$page) {
			$page = 1;
		}
		$limit = intval($page*10)-10;

		$list = M('order')->where('order_type=1 AND uid='.intval($uid))->order('addtime desc')->select();
		foreach ($list as $k => $v) {
			$list[$k]['addtime'] = date("Y-m-d H:i:s",$v['addtime']);
			// $list[$k]['cplcode'] = M('charge_pile')->where('id='.intval($v['cplid']))->getField('numbers');
			// $long_time = ceil($v['long_time']/60);
			// if ($long_time<1) {
			// 	$list[$k]['long_time'] = $v['long_time'].'分钟';
			// }else {
			// 	$list[$k]['long_time'] = $long_time.'小时';
			// }
		}

		echo json_encode(array('status'=>1,'list'=>$list));
		exit();
	}

	//***************************
	//  用户反馈接口
	//***************************
	public function feedback(){
		$uid = intval($_REQUEST['uid']);
		if (!$uid) {
			echo json_encode(array('status'=>0,'err'=>'登录状态异常.'));
			exit();
		}

		$con = $_POST['con'];
		if (!$con) {
			echo json_encode(array('status'=>0,'err'=>'请输入反馈内容.'));
			exit();
		}
		$data = array();
		$data['uid'] = $uid;
		$data['message'] = $con;
		$data['ftype'] = intval($_POST['ftype']);
		$data['contact'] = trim($_POST['contact']);
		$data['addtime'] = time();
		$res = M('fankui')->add($data);
		if ($res) {
			echo json_encode(array('status'=>1));
			exit();
		}else{
			echo json_encode(array('status'=>0,'err' => '保存失败！'));
			exit();
		}

	}

	//***************************
	//  获取用户优惠券
	//***************************
	public function voucher(){
		$uid = intval($_REQUEST['uid']);
		if (!$uid) {
			echo json_encode(array('status'=>0,'err'=>'登录状态异常！'.__LINE__));
			exit();
		}

		//获取未使用或者已失效的优惠券
		$nouse = array();$nouses = array();$offdate = array();$offdates = array();
		$vou_list = M('user_voucher')->where('uid='.intval($uid).' AND status!=2')->select();
		foreach ($vou_list as $k => $v) {
			$vou_info = M('voucher')->where('id='.intval($v['vid']))->find();
			if (intval($vou_info['del'])==1 || $vou_info['end_time']<time()) {
				$offdate['vid'] = intval($vou_info['id']);
				$offdate['full_money'] = floatval($vou_info['full_money']);
				$offdate['amount'] = floatval($vou_info['amount']);
				$offdate['start_time'] = date('Y.m.d',intval($vou_info['start_time']));
				$offdate['end_time'] = date('Y.m.d',intval($vou_info['end_time']));
				$offdates[] = $offdate;
			}elseif ($vou_info['end_time']>time()) {
				$nouse['vid'] = intval($vou_info['id']);
				$nouse['shop_id'] = intval($vou_info['shop_id']);
				$nouse['title'] = $vou_info['title'];
				$nouse['full_money'] = floatval($vou_info['full_money']);
				$nouse['amount'] = floatval($vou_info['amount']);
				if ($vou_info['proid']=='all' || empty($vou_info['proid'])) {
	                $nouse['desc'] = '店内通用';
	            }else{
	                $nouse['desc'] = '限定商品';
	            }
				$nouse['start_time'] = date('Y.m.d',intval($vou_info['start_time']));
				$nouse['end_time'] = date('Y.m.d',intval($vou_info['end_time']));
				if ($vou_info['proid']) {
					$proid = explode(',', $vou_info['proid']);
					$nouse['proid'] = intval($proid[0]);
				}
				$nouses[] = $nouse;
			}
		}

		////获取已使用的优惠券
		$used = array();$useds = array();
		$vouusedlist = M('user_voucher')->where('uid='.intval($uid).' AND status=2')->select();
		foreach ($vouusedlist as $k => $v) {
			$vou_info = M('voucher')->where('id='.intval($v['vid']))->find();
			$used['vid'] = intval($vou_info['id']);
			$used['full_money'] = floatval($vou_info['full_money']);
			$used['amount'] = floatval($vou_info['amount']);
			$used['start_time'] = date('Y.m.d',intval($vou_info['start_time']));
			$used['end_time'] = date('Y.m.d',intval($vou_info['end_time']));
			$useds[] = $used;
		}

		echo json_encode(array('status'=>1,'offdates'=>$offdates,'nouses'=>$nouses,'useds'=>$useds));
		exit();
	}

	//***************************
	// 用户资料提交审核接口
	//***************************
	public function user_auth() {
		$uid = intval($_REQUEST['uid']);
		if (!$uid) {
			echo json_encode(array('status'=>0,'err'=>'登录状态异常.'));
			exit();
		}

		//接收数据
		$truename = trim($_POST['truename']);
		$tel = trim($_POST['tel']);
		if (!$truename || !$tel) {
			echo json_encode(array('status'=>0,'err'=>'参数错误.'));
			exit();
		}

		$data = array();
		$data['uid'] = $uid;
		$data['truename'] = $truename;
		$data['tel'] = $tel;
		$data['idcard'] = trim($_POST['idcard']);
		$data['zheng'] = trim($_POST['zheng']);
		$data['fan'] = trim($_POST['fan']);
		$data['state'] = 1;
		$data['addtime'] = time();
		$check = M('user_auth')->where('uid='.intval($uid))->find();
		if ($check) {
			$res = M('user_auth')->where('id='.intval($check['id']))->save($data);
		} else {
			$res = M('user_auth')->add($data);
		}

		if ($res) {
			echo json_encode(array('status'=>1));
			exit();
		} else {
			echo json_encode(array('status'=>0,'err'=>'操作失败！'));
			exit();
		}
	}

	//***************************
	// 上传认证图片
	//***************************
	public function uploadimg(){
		$info = $this->upload_images($_FILES['img'],array('jpg','png','jpeg'),"userauth/".date(Ymd));
		if(is_array($info)) {// 上传错误提示错误信息
			$url = 'UploadFiles/'.$info['savepath'].$info['savename'];
			$xt = $_REQUEST['imgs'];
			if ($xt) {
				$img_url = "Data/".$xt;
				if(file_exists($img_url)) {
					@unlink($img_url);
				}
			}
			echo $url;
			exit();
		}else{
			echo json_encode(array('status'=>0,'err'=>$info));
			exit();
		}
	}

	//*************************
	//会员  余额充值记录管理
	//*************************
	public function amountlist() {
		$uid = intval($_REQUEST['uid']);
		if (!$uid) {
			echo json_encode(array('status'=>0,'err'=>'登录状态异常.'));
			exit();
		}

		$page = intval($_REQUEST['page']);
		if (!$page) {
			$page = 1;
		}
		$limit = intval($page*10)-10;

		//条件
		$where = '1=1 AND ctype IN (2,4) AND uid='.intval($uid);

		$list=M('recharge')->where($where)->order('addtime desc')->limit($limit.',10')->select();
		foreach ($list as $k => $v) {
			$list[$k]['addtime'] = date("Y-m-d H:i",$v['addtime']);
			$list[$k]['order_sn'] = M('order')->where('id='.intval($v['order_id']))->getField('order_sn');
			if (intval($v['ctype'])==2) {
				$list[$k]['desc'] = '余额充值';
			} elseif (intval($v['ctype'])==4) {
				$list[$k]['desc'] = '余额支付';
			} else {
				$list[$k]['desc'] = '其他';
			}
		}

		//=============
		//将变量输出
		//=============
		echo json_encode(array('status'=>1,'list'=>$list));
		exit();
	}

	/*
	*
	* 图片上传的公共方法
	*  $file 文件数据流 $exts 文件类型 $path 子目录名称
	*/
	public function upload_images($file,$exts,$path){
		$upload = new \Think\Upload();// 实例化上传类
		$upload->maxSize   =  3145728 ;// 设置附件上传大小3M
		$upload->exts      =  $exts;// 设置附件上传类型
		$upload->rootPath  =  './Data/UploadFiles/'; // 设置附件上传根目录
		$upload->savePath  =  ''; // 设置附件上传（子）目录
		$upload->saveName = time().mt_rand(100000,999999); //文件名称创建时间戳+随机数
		$upload->autoSub  = true; //自动使用子目录保存上传文件 默认为true
		$upload->subName  = $path; //子目录创建方式，采用数组或者字符串方式定义
		// 上传文件 
		$info = $upload->uploadOne($file);
		if(!$info) {// 上传错误提示错误信息
		    return $upload->getError();
		}else{// 上传成功 获取上传文件信息
			//return 'UploadFiles/'.$file['savepath'].$file['savename'];
			return $info;
		}
	}

	//获取请求充电信息
	public function getMessage(){
		$uid = intval($_REQUEST['userId']);
		$info = M('user')->where('id='.intval($uid).' AND del=0')->find();
		if (!$info) {
			echo json_encode(array('status'=>0,'err'=>'用户信息异常！'));
			exit();
		}

		//会员认证资料提交情况
		$userauth = M('user_auth')->where('uid='.intval($uid))->find();
		if (!$userauth) {
			$info['userauth'] = 0;
		}else if($userauth['state']==2 || $userauth['state']==4){
			$info['userauth'] = 1;
		}else{
			$userauth['state'] = 0;
		}

		//获取会员车库
		$carList = M('user_car')->where('uid='.$uid)->select();
		$car = array();
		foreach($carList as $k => $v){
			array_push($car,$v['car_number']);
		}
		echo json_encode(array('status'=>1,'userinfo'=>$info,'car'=>$car));
		exit();
	}

	//发起充电
	public function send_chong(){
		$uid = intval($_REQUEST['userId']);
		$sid = $_REQUEST['sid'];
		$userinfo = M('user')->where('id='.intval($uid).' AND del=0')->find();
		if (!$userinfo) {
			echo json_encode(array('status'=>0,'err'=>'用户信息异常！'));
			exit();
		}
		$order = M('order')->where('uid='.intval($_REQUEST['userId']).' AND status=10 AND back="0" AND del=0 AND order_type=1')->order('addtime desc')->getField('id');
		if($order){
			echo json_encode(array('status'=>3,'order_id'=>$order));
			exit();
		}
		$ppileid = intval($_REQUEST['ppileid']);
		if(!$ppileid){
			echo json_encode(array('status'=>0,'err'=>'充电桩信息异常！'));
			exit();
		}
		$zhuang_status = $this->getStatus($sid, $ppileid);
		if($zhuang_status == 0){
			echo json_encode(array('status'=>0,'err'=>'此桩正在充电，请换另一个桩！'));
			exit();
		}
		$car_number = trim($_REQUEST['car_number']);
		$car_id = 0;
		// $car_id = M('user_car')->where('uid='.$uid.' AND car_number="'.$car_number.'"')->getField('id');
		// if(!$car_id){
		// 	echo json_encode(array('status'=>0,'err'=>'请选择车型！'));
		// 	exit();
		// }
		$ctype = intval($_REQUEST['ctype']);
		if(!$ctype){
			echo json_encode(array('status'=>0,'err'=>'请选择是否充满！'));
			exit();
		}
		if($ctype == 1){
			$amount = 0;
		}else if($ctype == 2){
			$amount = $_REQUEST['amount'];
			$ctype = 0;
			if(!$amount){
				echo json_encode(array('status'=>0,'err'=>'请填写预充金额！'));
				exit();
			}
		}
		if($_REQUEST['time']){
			$time = $_REQUEST['time'];
			$time = strtotime($time);
		}else{
			$time = 0;
		}

		$res = M('user_auth')->where('uid='.$uid)->getField();
		if($res){
			$signin = 1;
		}else{
			$signin = 0;
			// if($userinfo['times'] >= 3){
			// 	echo json_encode(array('status'=>0,'err'=>'请先实名认证！'));
			// 	exit();
			// }
		}
		
		//发送请求
		$url = "http://www.indchina.com:7080/exdata?SID=".$sid."&OP=W";
        //指令ID 会员ID 充电桩ID 车型ID 是否实名认证 请求充电 预计提车时间  押金余额  钱包余额  是否充满  充电金额  是否可以充电
        $post_data = "12\r\nsetCommand\r\n001\r\nuserid\r\n".$uid."\r\npileid\r\n".$ppileid."\r\ncarid\r\n".$car_id."\r\nsignin\r\n".$signin."\r\nchargeinquire\r\n1\r\nleavetime\r\n".$time."\r\nguarantebalance\r\n".$userinfo['cash_money']."\r\nmoneybalance\r\n".$userinfo['amount']."\r\nfullchargetype\r\n".$ctype."\r\nmoneytypeamount\r\n".$amount."\r\nchargeadmit\r\n1";
        $headers = array();
        $headers[] = 'Content-type: text/plain;charset=UTF-8';
        $curls = curl_init();
        curl_setopt($curls, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curls, CURLOPT_URL, $url);
        curl_setopt($curls, CURLOPT_RETURNTRANSFER, 1);
        //post数据
        curl_setopt($curls, CURLOPT_POST, 1);
        //post的变量
        curl_setopt($curls, CURLOPT_POSTFIELDS, $post_data);
        $outdata = curl_exec($curls);
        curl_close($curls);
        $arr = explode("\r\n", $outdata);
        if($arr[0] == 'OK'){
        	echo json_encode(array('status'=>1,'err'=>'正在充电！'));
			exit();
        }else{
        	echo json_encode(array('status'=>0,'err'=>$arr[2],'errinfo'=>$arr));
			exit();
        }
	}

	//检验是否可以充电
	public function getStatus($sid, $ppileid){
		if(!$ppileid){
			echo json_encode(array('status'=>0,'err'=>'充电桩信息异常！'));
			exit();
		}
		$url = "http://www.indchina.com:7080/exdata?SID=".$sid."&OP=R";
        $post_data = "15\r\nppileid".$ppileid."\r\npCommand".$ppileid."\r\npuserid".$ppileid."\r\npcarid".$ppileid."\r\npleavetime".$ppileid."\r\npstart_time".$ppileid."\r\npguarante".$ppileid."\r\npbalance".$ppileid."\r\npamount".$ppileid."\r\npcurrent".$ppileid."\r\npstartpower".$ppileid."\r\npnowpower".$ppileid."\r\npmonamount".$ppileid."\r\nppowerunit".$ppileid."\r\npbithandle".$ppileid;
        //$post_data = "3\r\n桩1号outCommand\r\nCom1Error\r\nCom2Error";
        $headers = array();
        $headers[] = 'Content-type: text/plain;charset=UTF-8';
        $curls = curl_init();
        curl_setopt($curls, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curls, CURLOPT_URL, $url);
        curl_setopt($curls, CURLOPT_RETURNTRANSFER, 1);
        //post数据
        curl_setopt($curls, CURLOPT_POST, 1);
        //post的变量
        curl_setopt($curls, CURLOPT_POSTFIELDS, $post_data);
        $outdata = curl_exec($curls);
        curl_close($curls);
        $aa = explode("\r\n", $outdata);
        if($arr[0] == 'ERROR'){
        	return 0;
        	exit();
        }
        $chong_bit = decbin($aa[16]);
        $is_shou = substr($chong_bit,8,1);
        if($is_shou == 0){
        	return 1;
			exit();
        }else{
        	return 0;
        	exit();
        }
        
	}

	 /**
     * [makeqrcode 生成二维码]
     * @return [type] [description]
     */
    public function makeqrcode(){
    	//1
        $access_token=$this->_getAccessToken();
        //2
        $path="pages/index/index?ppileid=1";
        $width=430;
        $post_data='{"path":"'.$path.'","width":'.$width.'}'; 
        $url="https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=".$access_token;
        $result=$this->api_notice_increment($url,$post_data);
        // var_dump($result);
        //3
        $newFilePath='UploadFiles/zhuang/qrcode_1_'.time().'.jpg';
        if(empty($result)){
            $result=file_get_contents("php://input");
        }
        
        $newFile = fopen("Data/".$newFilePath,"w");//打开文件准备写入
        fwrite($newFile,$result);//写入二进制流到文件
        fclose($newFile);//关闭文件
        echo json_encode(array("status"=>1,"err"=>__DATAURL__.$newFilePath));
    }

    //获取充电结果
    public function getResult(){
    	$uid = intval($_REQUEST['userId']);
		$sid = $_REQUEST['sid'];
		$userinfo = M('user')->where('id='.intval($uid).' AND del=0')->find();
		if (!$userinfo) {
			echo json_encode(array('status'=>0,'err'=>'用户信息异常！'));
			exit();
		}
		$ppileid = intval($_REQUEST['ppileid']);
		if(!$ppileid){
			echo json_encode(array('status'=>0,'err'=>'充电桩信息异常！'));
			exit();
		}
		$url = "http://www.indchina.com:7080/exdata?SID=".$sid."&OP=R";
        $post_data = "15\r\nppileid".$ppileid."\r\npCommand".$ppileid."\r\npuserid".$ppileid."\r\npcarid".$ppileid."\r\npleavetime".$ppileid."\r\npstart_time".$ppileid."\r\npguarante".$ppileid."\r\npbalance".$ppileid."\r\npamount".$ppileid."\r\npcurrent".$ppileid."\r\npstartpower".$ppileid."\r\npnowpower".$ppileid."\r\npmonamount".$ppileid."\r\nppowerunit".$ppileid."\r\npbithandle".$ppileid;
        //$post_data = "3\r\n桩1号outCommand\r\nCom1Error\r\nCom2Error";
        $headers = array();
        $headers[] = 'Content-type: text/plain;charset=UTF-8';
        $curls = curl_init();
        curl_setopt($curls, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curls, CURLOPT_URL, $url);
        curl_setopt($curls, CURLOPT_RETURNTRANSFER, 1);
        //post数据
        curl_setopt($curls, CURLOPT_POST, 1);
        //post的变量
        curl_setopt($curls, CURLOPT_POSTFIELDS, $post_data);
        $outdata = curl_exec($curls);
        curl_close($curls);
        $aa = explode("\r\n", $outdata);
        if($arr[0] == 'ERROR'){
        	echo json_encode(array('status'=>15,'err'=>''));
			exit();
        }
        $chong_bit = decbin($aa[16]);
        // dump($aa[16]);
   //      $is_shou = substr($chong_bit,8,1);
   //      if($is_shou == 0){
   //      	echo json_encode(array('status'=>2,'err'=>''));
			// exit();
   //      }
        $is_end = substr($chong_bit,0,1);
        if($is_end == 0){
        	echo json_encode(array('status'=>0,'err'=>'正在充电！'));
			exit();
        }
        $arr = array();
        $arr['is_end'] = 1;
        $arr['amount'] = $aa[14];
        $arr['chong_time'] = floatval($aa[13]) - floatval($aa[11]);
        echo json_encode(array('status'=>1,'info'=>$arr));
		exit();
    }

     //扫码
    public function scan(){
    	$uid = intval($_REQUEST['userId']);
		$sid = $_REQUEST['sid'];
		$userinfo = M('user')->where('id='.intval($uid).' AND del=0')->find();
		// if (!$userinfo) {
		// 	echo json_encode(array('status'=>0,'err'=>'用户信息异常！'));
		// 	exit();
		// }
		$ppileid = intval($_REQUEST['ppileid']);
		if(!$ppileid){
			echo json_encode(array('status'=>0,'err'=>'充电桩信息异常！'));
			exit();
		}
		$url = "http://www.indchina.com:7080/exdata?SID=".$sid."&OP=R";
        $post_data = "15\r\nppileid".$ppileid."\r\npCommand".$ppileid."\r\npuserid".$ppileid."\r\npcarid".$ppileid."\r\npleavetime".$ppileid."\r\npstart_time".$ppileid."\r\npguarante".$ppileid."\r\npbalance".$ppileid."\r\npamount".$ppileid."\r\npcurrent".$ppileid."\r\npstartpower".$ppileid."\r\npnowpower".$ppileid."\r\npmonamount".$ppileid."\r\nppowerunit".$ppileid."\r\npbithandle".$ppileid;
        //$post_data = "3\r\n桩1号outCommand\r\nCom1Error\r\nCom2Error";
        $headers = array();
        $headers[] = 'Content-type: text/plain;charset=UTF-8';
        $curls = curl_init();
        curl_setopt($curls, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curls, CURLOPT_URL, $url);
        curl_setopt($curls, CURLOPT_RETURNTRANSFER, 1);
        //post数据
        curl_setopt($curls, CURLOPT_POST, 1);
        //post的变量
        curl_setopt($curls, CURLOPT_POSTFIELDS, $post_data);
        $outdata = curl_exec($curls);
        curl_close($curls);
        $aa = explode("\r\n", $outdata);
        $temp_userid = intval($aa[4]);
        //判断是否有用户在使用此桩
        if(!$temp_userid){
        	echo json_encode(array('status'=>1,'err'=>''));
			exit();
        }
        if($temp_userid != $uid){
        	echo json_encode(array('status'=>2,'err'=>'此桩正在充电，请换另一个桩！'));
			exit();
        }else{
        	$res = $this->openDoor($uid, $ppileid, $sid);
        	if($res == 1){
        		echo json_encode(array('status'=>1,'err'=>'重开门成功！'));
				exit();
        	}else{
        		echo json_encode(array('status'=>0,'err'=>'网络异常！'));
				exit();
        	}
        }
        exit();
    }

    //重开门
     public function openDoor($uid, $ppileid, $sid){
		$userinfo = M('user')->where('id='.intval($uid).' AND del=0')->find();
		if (!$userinfo) {
			echo json_encode(array('status'=>0,'err'=>'用户信息异常！'));
			exit();
		}
		if(!$ppileid){
			echo json_encode(array('status'=>0,'err'=>'充电桩信息异常！'));
			exit();
		}
		//发送请求
		$url = "http://www.indchina.com:7080/exdata?SID=".$sid."&OP=W";
        //指令ID 会员ID 充电桩ID 车型ID 是否实名认证 请求充电 预计提车时间  押金余额  钱包余额  是否充满  充电金额  是否可以充电
        $post_data = "12\r\nsetCommand\r\n003\r\nuserid\r\n".$uid."\r\npileid\r\n".$ppileid."\r\ncarid\r\n0\r\nsignin\r\n0\r\nchargeinquire\r\n1\r\nleavetime\r\n0\r\nguarantebalance\r\n0\r\nmoneybalance\r\n0\r\nfullchargetype\r\n0\r\nmoneytypeamount\r\n0\r\nchargeadmit\r\n0";
        $headers = array();
        $headers[] = 'Content-type: text/plain;charset=UTF-8';
        $curls = curl_init();
        curl_setopt($curls, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curls, CURLOPT_URL, $url);
        curl_setopt($curls, CURLOPT_RETURNTRANSFER, 1);
        //post数据
        curl_setopt($curls, CURLOPT_POST, 1);
        //post的变量
        curl_setopt($curls, CURLOPT_POSTFIELDS, $post_data);
        $outdata = curl_exec($curls);
        curl_close($curls);
        $arr = explode("\r\n", $outdata);
        if($arr[0] == 'OK'){
        	return 1;
			exit();
        }else{
        	return 0;
			exit();
        }
    }

    //充电结束重开门
    public function openDoor2(){
    	$uid = intval($_REQUEST['userId']);
		$sid = $_REQUEST['sid'];
		$ppileid = intval($_REQUEST['ppileid']);
		$res = $this->openDoor($uid, $ppileid, $sid);
        if($res == 1){
    		echo json_encode(array('status'=>1,'err'=>'重开门成功！'));
			exit();
    	}else{
    		echo json_encode(array('status'=>0,'err'=>'网络异常！'));
			exit();
    	}
    }

     //***************************
    //  公共请求远程链接 接口
    //***************************
    public function get_result($url,$data) {
        $headers = array();
        $headers[] = 'Content-type: text/plain;charset=UTF-8';
        $curls = curl_init();
        curl_setopt($curls, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curls, CURLOPT_URL, $url);
        curl_setopt($curls, CURLOPT_RETURNTRANSFER, 1);
        //post数据
        curl_setopt($curls, CURLOPT_POST, 1);
        //post的变量
        curl_setopt($curls, CURLOPT_POSTFIELDS, $data);
        $outdata = curl_exec($curls);
        curl_close($curls);
        $arr = explode("\r\n", $outdata);
        return $arr;
    }

    //获取充电详情
    public function getCurrent(){
		$sid = $_REQUEST['sid'];
		$ppileid = intval($_REQUEST['ppileid']);
		if(!$ppileid){
			echo json_encode(array('status'=>0,'err'=>'充电桩信息异常！'));
			exit();
		}
		$url = "http://www.indchina.com:7080/exdata?SID=".$sid."&OP=R";
        $post_data = "15\r\nppileid".$ppileid."\r\npCommand".$ppileid."\r\npuserid".$ppileid."\r\npcarid".$ppileid."\r\npleavetime".$ppileid."\r\npstart_time".$ppileid."\r\npguarante".$ppileid."\r\npbalance".$ppileid."\r\npamount".$ppileid."\r\npcurrent".$ppileid."\r\npstartpower".$ppileid."\r\npnowpower".$ppileid."\r\npmonamount".$ppileid."\r\nppowerunit".$ppileid."\r\npbithandle".$ppileid;
        //$post_data = "3\r\n桩1号outCommand\r\nCom1Error\r\nCom2Error";
        $headers = array();
        $headers[] = 'Content-type: text/plain;charset=UTF-8';
        $curls = curl_init();
        curl_setopt($curls, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curls, CURLOPT_URL, $url);
        curl_setopt($curls, CURLOPT_RETURNTRANSFER, 1);
        //post数据
        curl_setopt($curls, CURLOPT_POST, 1);
        //post的变量
        curl_setopt($curls, CURLOPT_POSTFIELDS, $post_data);
        $outdata = curl_exec($curls);
        curl_close($curls);
        $aa = explode("\r\n", $outdata);
        if($arr[0] == 'ERROR'){
        	echo json_encode(array('status'=>15,'err'=>''));
			exit();
        }
        // $chong_bit = decbin($aa[16]);
        // $is_shou = substr($chong_bit,8,1);
        $current = $aa[14];
        $start = $aa[13];
        $dianliu = $aa[12];
        echo json_encode(array('status'=>1,'current'=>$current,'start'=>$start,'dianliu'=>$dianliu));
		exit();
    }

}