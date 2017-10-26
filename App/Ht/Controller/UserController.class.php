<?php
namespace Ht\Controller;
use Think\Controller;
class UserController extends PublicController{

	//*************************
	// 普通会员的管理
	//*************************
	public function index(){
		$type=$_GET['type'];
		$id=(int)$_GET['id'];
		$tel = trim($_REQUEST['tel']);
		$name = trim($_REQUEST['name']);

		$names=$this->htmlentities_u8($_GET['name']);
		//搜索
		$where="1=1 AND del=0";
		$name!='' ? $where.=" and name like '%$name%'" : null;
		$tel!='' ? $where.=" and tel like '%$tel%'" : null;

		define('rows',20);
		$count=M('user')->where($where)->count();
		$rows=ceil($count/rows);

		$page=(int)$_GET['page'];
		$page<0?$page=0:'';
		$limit=$page*rows;
		$userlist=M('user')->where($where)->order('id desc')->limit($limit,rows)->select();
		$page_index=$this->page_index($count,$rows,$page);
		foreach ($userlist as $k => $v) {
			$userlist[$k]['addtime']=date("Y-m-d H:i",$v['addtime']);
			$userlist[$k]['authtime']=date("Y-m-d H:i",$v['authtime']);
		}
		//====================
		// 将GET到的参数输出
		//=====================
		$this->assign('name',$name);
		$this->assign('tel',$tel);

		//=============
		//将变量输出
		//=============
		$this->assign('page_index',$page_index);
		$this->assign('page',$page);
		$this->assign('userlist',$userlist);
		$this->display();	
	}

	//*************************
	// 普通会员的管理
	//*************************
	public function delindex(){
		$type=$_GET['type'];
		$id=(int)$_GET['id'];
		$tel = trim($_REQUEST['tel']);
		$name = trim($_REQUEST['name']);

		//搜索
		$where="1=1 AND del=1";
		$name!='' ? $where.=" and name like '%$name%'" : null;
		$tel!='' ? $where.=" and tel like '%$tel%'" : null;

		define('rows',20);
		$count=M('user')->where($where)->count();
		$rows=ceil($count/rows);

		$page=(int)$_GET['page'];
		$page<0?$page=0:'';
		$limit=$page*rows;
		$userlist=M('user')->where($where)->order('id desc')->limit($limit,rows)->select();
		$page_index=$this->page_index($count,$rows,$page);
		foreach ($userlist as $k => $v) {
			$userlist[$k]['del_time']=date("Y-m-d H:i",$v['del_time']);
			$userlist[$k]['authtime']=date("Y-m-d H:i",$v['authtime']);
		}
		//====================
		// 将GET到的参数输出
		//=====================
		$this->assign('name',$name);
		$this->assign('tel',$tel);

		//=============
		//将变量输出
		//=============
		$this->assign('page_index',$page_index);
		$this->assign('page',$page);
		$this->assign('userlist',$userlist);
		$this->display();	
	}

	//*************************
	//会员  押金充值记录管理
	//*************************
	public function cashlist() {
		$where = '1=1 AND ctype IN (1,3)';

		define('rows',20);
		$count=M('recharge')->where($where)->count();
		$rows=ceil($count/rows);

		$page=(int)$_GET['page'];
		$page<0?$page=0:'';
		$limit=$page*rows;
		$userlist=M('recharge')->where($where)->order('addtime desc')->limit($limit,rows)->select();
		$page_index=$this->page_index($count,$rows,$page);
		foreach ($userlist as $k => $v) {
			$userlist[$k]['addtime'] = date("Y-m-d H:i",$v['addtime']);
			$userlist[$k]['uname'] = M('user')->where('id='.intval($v['uid']))->getField('name');
		}

		//=============
		//将变量输出
		//=============
		$this->assign('page_index',$page_index);
		$this->assign('page',$page);
		$this->assign('userlist',$userlist);
		$this->display();
	}

	//*************************
	//会员  余额充值记录管理
	//*************************
	public function amountlist() {
		$where = '1=1 AND ctype IN (2,4)';

		define('rows',20);
		$count=M('recharge')->where($where)->count();
		$rows=ceil($count/rows);

		$page=(int)$_GET['page'];
		$page<0?$page=0:'';
		$limit=$page*rows;
		$userlist=M('recharge')->where($where)->order('addtime desc')->limit($limit,rows)->select();
		$page_index=$this->page_index($count,$rows,$page);
		foreach ($userlist as $k => $v) {
			$userlist[$k]['addtime'] = date("Y-m-d H:i",$v['addtime']);
			$userlist[$k]['uname'] = M('user')->where('id='.intval($v['uid']))->getField('name');
		}

		//=============
		//将变量输出
		//=============
		$this->assign('page_index',$page_index);
		$this->assign('page',$page);
		$this->assign('userlist',$userlist);
		$this->display();
	}

	//*************************
	//会员 车库管理
	//*************************
	public function carindex(){
		$where = '1=1';

		define('rows',20);
		$count=M('user_car')->where($where)->count();
		$rows=ceil($count/rows);

		$page=(int)$_GET['page'];
		$page<0?$page=0:'';
		$limit=$page*rows;
		$list=M('user_car')->where($where)->order('addtime desc')->limit($limit,rows)->select();
		$page_index = $this->page_index($count,$rows,$page);
		foreach ($list as $k => $v) {
			$list[$k]['addtime'] = date("Y-m-d H:i",$v['addtime']);
			$list[$k]['uname'] = M('user')->where('id='.intval($v['uid']))->getField('name');
			$list[$k]['bname'] = M('brand')->where('id='.intval($v['bid']))->getField('name');
		}
		
	    //=============
		//将变量输出
		//=============
		$this->assign('list',$list);
		$this->display();
	}

	public function del()
	{
		$id = intval($_REQUEST['did']);
		$info = M('user')->where('id='.intval($id))->find();
		if (!$info) {
			$this->error('会员信息错误.'.__LINE__);
			exit();
		}

		$data=array();
		$data['del'] = $info['del'] == '1' ?  0 : 1;
		$data['del_time'] = time();
		$up = M('user')->where('id='.intval($id))->save($data);
		if ($up) {
			$this->redirect('User/index',array('page'=>intval($_REQUEST['page'])));
			exit();
		}else{
			$this->error('操作失败.');
			exit();
		}
	}

	//*****************************
	//会员 实名认证 待审核管理
	//*****************************
	public function auditlist() {
		$tel = trim($_REQUEST['tel']);
		$name = trim($_REQUEST['name']);

		//搜索
		$where="1=1 AND state=1";
		$name!='' ? $where.=" and truename like '%$name%'" : null;
		$tel!='' ? $where.=" and tel like '%$tel%'" : null;

		define('rows',20);
		$count=M('user_auth')->where($where)->count();
		$rows=ceil($count/rows);

		$page=(int)$_REQUEST['page'];
		$page<0 ? $page=0:'';
		$limit=$page*rows;
		$userlist=M('user_auth')->where($where)->order('addtime desc')->limit($limit,rows)->select();
		$page_index=$this->page_index($count,$rows,$page);
		foreach ($userlist as $k => $v) {
			$userlist[$k]['addtime'] = date("Y-m-d H:i",$v['addtime']);
			$userlist[$k]['uname'] = M('user')->where('id='.intval($v['uid']))->getField('name');
		}
		//====================
		// 将GET到的参数输出
		//=====================
		$this->assign('name',$name);
		$this->assign('tel',$tel);

		//=============
		//将变量输出
		//=============
		$this->assign('page_index',$page_index);
		$this->assign('page',$page);
		$this->assign('userlist',$userlist);
		$this->display();
	}

	//*****************************
	//会员 实名认证管理 已审核
	//*****************************
	public function audited() {
		$tel = trim($_REQUEST['tel']);
		$name = trim($_REQUEST['name']);

		//搜索
		$where="1=1 AND state>1";
		$name!='' ? $where.=" and truename like '%$name%'" : null;
		$tel!='' ? $where.=" and tel like '%$tel%'" : null;

		define('rows',20);
		$count=M('user_auth')->where($where)->count();
		$rows=ceil($count/rows);

		$page=(int)$_REQUEST['page'];
		$page<0 ? $page=0:'';
		$limit=$page*rows;
		$userlist=M('user_auth')->where($where)->order('addtime desc')->limit($limit,rows)->select();
		$page_index=$this->page_index($count,$rows,$page);
		foreach ($userlist as $k => $v) {
			$userlist[$k]['addtime'] = date("Y-m-d H:i",$v['addtime']);
			$userlist[$k]['uname'] = M('user')->where('id='.intval($v['uid']))->getField('name');
		}
		//====================
		// 将GET到的参数输出
		//=====================
		$this->assign('name',$name);
		$this->assign('tel',$tel);

		//=============
		//将变量输出
		//=============
		$this->assign('page_index',$page_index);
		$this->assign('page',$page);
		$this->assign('userlist',$userlist);
		$this->display();
	}

	//*************************
	//会员 实名认证 资料查看
	//*************************
	public function audit(){
		//获取传递过来的id
		$id = intval($_REQUEST['id']);
		if(!$id) {
			$this->error('系统错误.');
			exit();
		}

		//根据订单id获取订单数据还有商品信息
		$info = M('user_auth')->where('id='.intval($id))->find();
		if (!$info) {
			$this->error('会员信息异常.');
			exit();
		}
		
		$info['addtime'] = date("Y-m-d H:i",$info['addtime']);
		$info['uname'] = M('user')->where('id='.intval($info['uid']))->getField('name');

		$this->assign('info',$info);
		$this->display();
	}

	//*************************
	//会员 实名认证 审核
	//*************************
	public function shenhe(){
		$id = intval($_POST['id']);
		$check = M('user_auth')->where('id='.intval($id))->find();
		if (!$check) {
			$this->error('认证信息异常！');
			exit();
		}

		$audit = intval($_POST['audit']);
		$reason = trim($_POST['reason']);
		if (!$reason) {
			$reason = '无';
		}

		$up = array();
		$up['state'] = $audit;
		$up['reason'] = $reason;
		$res = M('user_auth')->where('id='.intval($id))->save($up);
		if ($res) {
			$this->success('操作成功！');
			exit();
		}else{
			$this->error('操作失败！');
			exit();
		}
	}

	public function delauth()
	{
		$id = intval($_REQUEST['did']);
		$info = M('user_auth')->where('id='.intval($id))->find();
		if (!$info) {
			$this->error('认证信息错误.'.__LINE__);
			exit();
		}

		$up = M('user_auth')->where('id='.intval($id))->delete();
		if ($up) {
			$this->redirect('auditlist',array('page'=>intval($_REQUEST['page'])));
			exit();
		}else{
			$this->error('操作失败.');
			exit();
		}
	}

	/*
	*
	*  点击查看图片
	*/
	public function getimg(){
		$type = intval($_REQUEST['type']);
		$id = intval($_REQUEST['id']);
		if ($type == 1) {
			//身份证正面照
			$img = M('user_auth')->where('id='.intval($id))->getField('zheng');
		}elseif ($type==2) {
			//身份证反面照
			$img = M('user_auth')->where('id='.intval($id))->getField('fan');
		}
		$this->assign('img',$img);
		$this->display();
	}
}