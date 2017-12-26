<?php
// 本类由系统自动生成，仅供测试用途
namespace Api\Controller;
use Think\Controller;
class OrderController extends PublicController {
	//***************************
	//  用户获取订单信息接口
	//***************************
	public function index(){
		$uid = intval($_REQUEST['uid']);
		if (!$uid) {
			echo json_encode(array('status'=>0,'err'=>'登录状态异常'));
			exit();
		}

		$orders=M("order");
		$orderp=M("order_product");

		//按条件查询
		$condition = array();
		$condition['del'] = 0;
		$condition['back'] = '0';
		$condition['uid'] = intval($uid);
		$condition['status'] = 10;
		$order_type = trim($_REQUEST['order_type']);
		if ($order_type) {
			switch ($order_type) {
				case 'pay':
					$condition['status'] = 10;
					break;
				case 'deliver':
					$condition['status'] = 20;
					break;
				case 'receive':
					$condition['status'] = 30;
					break;
				case 'evaluate':
					$condition['status'] = 40;
					break;	
				case 'finish':
					$condition['status'] = array('IN',array(40,50));
					break;		
				default:
					$condition['status'] = 10;
					break;
			}
		}

		$order_status = array('0'=>'已取消','10'=>'待付款','20'=>'待发货','30'=>'待收货','40'=>'待评价','50'=>'交易完成','51'=>'交易关闭');

        $order = $orders->where($condition)->order('id desc')->field('id,order_sn,pay_sn,status,price,type,product_num')->limit(7)->select();
		foreach ($order as $n=>$v) {
			$order[$n]['desc'] = $order_status[$v['status']];
			$prolist = $orderp->where('order_id='.intval($v['id']))->find();
			$order[$n]['photo_x'] = __DATAURL__.$prolist['photo_x'];
			$order[$n]['pid'] = $prolist['pid'];
			$order[$n]['name'] = $prolist['name'];
			$order[$n]['price_yh'] = $prolist['price'];
			$order[$n]['pro_buff'] = $prolist['pro_buff'];
			$order[$n]['pro_count'] = $orderp->where('order_id='.intval($v['id']))->getField('COUNT(id)');
		}

        echo json_encode(array('status'=>1,'ord'=>$order));
        exit();

	}

	//***************************
	//  用户获取订单信息接口
	//***************************
	public function get_more(){
		$uid = intval($_REQUEST['uid']);
		if (!$uid) {
			echo json_encode(array('status'=>0,'err'=>'登录状态异常'));
			exit();
		}

		//分页
		$pages=intval($_REQUEST['page']);
		if (!$pages) {
			$pages=2;
		}
		$limit = $pages*7-7;

		$orders=M("order");
		$orderp=M("order_product");

		//按条件查询
		$condition = array();
		$condition['del'] = 0;
		$condition['back'] = '0';
		$condition['uid'] = intval($uid);
		$condition['status'] = 10;
		$order_type = trim($_REQUEST['order_type']);
		if ($order_type) {
			switch ($order_type) {
				case 'pay':
					$condition['status'] = 10;
					break;
				case 'deliver':
					$condition['status'] = 20;
					break;
				case 'receive':
					$condition['status'] = 30;
					break;
				case 'evaluate':
					$condition['status'] = 40;
					break;	
				case 'finish':
					$condition['status'] = array('IN',array(40,50));
					break;		
				default:
					$condition['status'] = 10;
					break;
			}
		}

		$order_status = array('0'=>'已取消','10'=>'待付款','20'=>'待发货','30'=>'待收货','40'=>'待评价','50'=>'交易完成','51'=>'交易关闭');

        $order = $orders->where($condition)->order('id desc')->field('id,order_sn,pay_sn,status,price,type,product_num')->limit($limit.',7')->select();
		foreach ($order as $n=>$v){
			$order[$n]['desc'] = $order_status[$v['status']];
			$prolist = $orderp->where('order_id='.intval($v['id']))->find();
			$order[$n]['photo_x'] = __DATAURL__.$prolist['photo_x'];
			$order[$n]['pid'] = $prolist['pid'];
			$order[$n]['name'] = $prolist['name'];
			$order[$n]['price_yh'] = $prolist['price'];
			$order[$n]['pro_buff'] = $prolist['pro_buff'];
			$order[$n]['pro_count'] = $orderp->where('order_id='.intval($v['id']))->getField('COUNT(id)');
		}

        echo json_encode(array('status'=>1,'ord'=>$order));
        exit();

	}

	//***************************
	//  用户退款退货接口
	//***************************
	public function order_refund(){
		$uid = intval($_REQUEST['uid']);
		if (!$uid) {
			echo json_encode(array('status'=>0,'err'=>'登录状态异常'));
			exit();
		}

		$orders=M("order");
		$orderp=M("order_product");

		$condition = array();
		$condition['back']=array('gt','0');

		$refund_status = array('1'=>'退款申请中','2'=>'已退款','3'=>'处理中','4'=>'已拒绝');

        $order = $orders->where($condition)->order('back_addtime desc')->field('id,price,order_sn,product_num,back,back_addtime')->limit(7)->select();
		foreach ($order as $n=>$v) {
			$order[$n]['desc'] = $refund_status[$v['back']];
			$prolist = $orderp->where('order_id='.intval($v['id']))->find();
			$order[$n]['photo_x'] = __DATAURL__.$prolist['photo_x'];
			$order[$n]['pid'] = $prolist['pid'];
			$order[$n]['name'] = $prolist['name'];
			$order[$n]['price_yh'] = $prolist['price'];
			$order[$n]['back_addtime'] = date("Y-m-d H:i",$v['back_addtime']);
			$order[$n]['pro_count'] = $orderp->where('order_id='.intval($v['id']))->getField('COUNT(id)');
		}

        echo json_encode(array('status'=>1,'ord'=>$order));
        exit();
	}

	//***************************
	//  用户退款退货接口
	//***************************
	public function get_refund_more(){
		$uid = intval($_REQUEST['uid']);
		if (!$uid) {
			echo json_encode(array('status'=>0,'err'=>'登录状态异常'));
			exit();
		}

		//分页
		$pages=intval($_REQUEST['page']);
		if (!$pages) {
			$pages=2;
		}
		$limit = $pages*6-6;

		$orders=M("order");
		$orderp=M("order_product");
		$shangchang = M('shangchang');

		$condition = array();
		$condition['back']=array('gt','0');
		//获取总页数
        $count = $orders->where($condition)->count();
        $the_page = ceil($count/6);

		$refund_status = array('1'=>'退款申请中','2'=>'已退款','3'=>'处理中','4'=>'已拒绝');

        $order = $orders->where($condition)->order('back_addtime desc')->field('id,price,order_sn,product_num,back,back_addtime')->limit($limit.',6')->select();
		foreach ($order as $n=>$v) {
			$order[$n]['desc'] = $refund_status[$v['back']];
			$prolist = $orderp->where('order_id='.intval($v['id']))->find();
			$order[$n]['photo_x'] = __DATAURL__.$prolist['photo_x'];
			$order[$n]['pid'] = $prolist['pid'];
			$order[$n]['name'] = $prolist['name'];
			$order[$n]['price_yh'] = $prolist['price'];
			$order[$n]['back_addtime'] = date("Y-m-d H:i",$v['back_addtime']);
			$order[$n]['pro_count'] = $orderp->where('order_id='.intval($v['id']))->getField('COUNT(id)');
		}

        echo json_encode(array('status'=>1,'ord'=>$order));
        exit();
	}


	//***************************
	//  用户订单编辑接口
	//***************************
	public function orders_edit(){
		
	    $orders=M("order");
	    $order_id=intval($_REQUEST['id']);
	    $type=$_REQUEST['type'];

	    $check_id = $orders->where('id='.intval($order_id).' AND del=0')->getField('id');
	    if (!$check_id || !$type) {
	    	echo json_encode(array('status'=>0,'err'=>'订单信息错误.'.__LINE__));
	    	exit();
	    }

	    $data = array();
	    if ($type==='cancel') {
	    	$data['status'] = 0;
	    }elseif ($type==='receive') {
	    	$data['status'] = 40;
	    }elseif ($type==='refund') {
	    	$data['back'] = 1;
	    	$data['back_addtime'] = time();
	    	$data['back_remark'] = $_REQUEST['back_remark'];
	    }

	    if ($data) {
	    	$result = $orders->where('id='.intval($order_id))->save($data);
	    	if($result !== false){
				echo json_encode(array('status'=>1));
	    		exit();
			}else{
				echo json_encode(array('status'=>0,'err'=>'操作失败.'.__LINE__));
	    	exit();
			}
	    }else{
	    	echo json_encode(array('status'=>0,'err'=>'订单信息错误.'.__LINE__));
	    	exit();
	    }
	}

	//***************************
	//  用户订单详情接口
	//***************************
	public function order_details(){

		$order_id = intval($_REQUEST['order_id']);
		//订单详情
		$orders=M("order");
		$orderp=M("order_product");

		$data = array();
		$order_info = $orders->where('id='.intval($order_id).' AND del=0')->find();
		if (!$order_info) {
			echo json_encode(array('status'=>0,'err'=>'订单信息错误.'));
			exit();
		}

		$data['order_sn'] = intval($order_info['order_sn']);
		$data['amount'] = floatval($order_info['amount']);

		//优惠金额
		$data['yh_price'] = floatval($order_info['amount'])-floatval($order_info['price']);

		//获取充电信息
		$pid = $orderp->where('order_id='.intval($order_info['id']))->getField('pid');
		$charge_info = M('charge_log')->where('id='.intval($pid))->find();
		if ($charge_info) {
			$data['long_time'] = $charge_info['long_time'].'分钟';
			if ($charge_info['long_time']>60) {
				$data['long_time'] = ceil($charge_info['long_time']/60).'小时';
			}
			$data['degree'] = $charge_info['degree'].'kW.h';
		}
		
        echo json_encode(array('status'=>1,'info'=>$data));
        exit();
	}

	public function details(){
		$uid = intval($_REQUEST['userid']);
		$userinfo = M('user')->where('id='.intval($uid).' AND del=0')->find();
		if (!$userinfo) {
			echo json_encode(array('status'=>0,'err'=>'用户信息异常！'));
			exit();
		}
		$info = M('order')->where('uid='.$uid.' AND status=10')->limit(1)->find();
		echo json_encode(array('status'=>1,'info'=>$info));
		exit();
	}

    //***************************
	//  用户订单评论接口
	//***************************
    public function order_comment(){
    	$id=explode(',', $_GET['id']);

    	$orderid = (int)$_GET['orderid'];
    	$order_product = M('order_product');
    	$order   = M('order');
    	$orderr  = $order->where('id='.$orderid)->select();
    	foreach ($id as $key => $value) {
    		$result[$key]  = $order_product->where('`order`='.$orderid.' and pid='.$value)->select();
    	}
        $this->assign('result',$result);
        $this->assign('orderr',$orderr);
    	$this->display();
    }

    //***************************
	//  用户订单评论接口
	//***************************
    public function addMessage(){
        $product_dp      = M('product_dp');
        $order_product   = M("order_product");
        $order       = M("order");
        //获取商品的ID
        $id              = $_POST['pid'];
        $id              = explode(",", $id);
        $data['orderid'] = $_POST['orderid'];
        $data['type']    = 1;
        $status['mstatus']=1;
        $data['uid']     = $_SESSION['ID'];
        $data['addtime'] = time();
        foreach ($id as $key => $value) {
        	$data['pid']     = $value;
            $data['concent'] = $_POST['content'.$data['pid']];
            $data['num']     = $_POST['pingfen'.$data['pid']];
            $result   = $product_dp->add($data);
        }
        /*$meresult = $product_dp->where('uid='.$_SESSION['ID'].' and orderid='.$_POST['orderid'].' and pid='.$_POST['id'])->select();
        if($meresult){
        	echo 2;
        	exit();
        }*/
        
        if($result){
            $order->where('id='.$_POST['orderid'])->save($status);
            $this->success('评价成功',U('User/orders',array('key'=>$_POST['key'])));
        }else{
        	$this->error('评价失败');
        }

    }

}