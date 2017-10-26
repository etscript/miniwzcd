<?php
namespace Api\Controller;
use Think\Controller;
class CarlistController extends PublicController {
	//***************************
	//  获取会员地址数据接口
	//***************************
    public function index(){
        $user_id=intval($_REQUEST['uid']);
        if (!$user_id){
            echo json_encode(array('status'=>0,'err'=>'用户状态异常.'));
            exit();
        }

    	//所有地址
    	$carModel = M('user_car');
		$list = $carModel->where('uid='.intval($user_id))->order('is_default desc,id desc')->select();
        foreach ($list as $k => $v) {
            $list[$k]['carname'] = M('brand')->where('id='.intval($v['bid']))->getField('name');
        }

		echo json_encode(array('status'=>1,'list'=>$list));
		exit();
    }

    //***************************
    //  会员添加车型接口
    //***************************
    public function addcar(){
        $user_id=intval($_REQUEST['uid']);
        if (!$user_id){
            echo json_encode(array('status'=>0,'err'=>'登录状态异常.'));
            exit();
        }

        $bid = intval($_REQUEST['bid']);
        if (!$bid) {
            echo json_encode(array('status'=>0,'err'=>'请选择车型.'));
            exit();
        }

        //接收ajax传过来的数据
        $data = array();
        $data['car_number'] = trim($_POST['car_number']);
        $data['numb'] = trim($_POST['numb']);
        $data['bid'] = intval($bid);
        $data['uid'] = intval($user_id);
        if (!$data['car_number'] || !$data['numb']) {
            echo json_encode(array('status'=>0,'err'=>'请先完善信息后再保存.'));
            exit();
        }

        $check_id = M('user_car')->where($data)->getField('id');
        if ($check_id) {
            echo json_encode(array('status'=>0,'err'=>'该车型已经添加.'));
            exit();
        }
        $data['addtime'] = time();
        $res = M('user_car')->add($data);
        if ($res) {
            echo json_encode(array('status'=>1,'carid'=>$res));
            exit();
        } else {
            echo json_encode(array('status'=>0,'err'=>'操作失败.'));
            exit();
        }
    }


    //***************************
    //  会员删除车型接口
    //***************************
    public function del_car(){
        $user_id=intval($_REQUEST['user_id']);
        if (!$user_id){
            echo json_encode(array('status'=>0,'err'=>'网络异常.'.__LINE__));
            exit();
        }

        $id_arr = trim($_POST['id_arr'],',');
        if ($id_arr) {
            $res = M('user_car')->where('uid='.intval($user_id).' AND id IN ('.$id_arr.')')->delete();
            if ($res) {
                echo json_encode(array('status'=>1));
                exit();
            }else{
                echo json_encode(array('status'=>0,'err'=>'操作失败.'));
                exit();
            }
        }else{
            echo json_encode(array('status'=>0,'err'=>'没有找到要删除的数据.'));
            exit();
        }
    }

    //***************************
    //  获取 车品牌
    //***************************
    public function get_brand(){
        $brand=M("brand");
        $list = $brand->where('1=1')->field('id,name')->select();

        echo json_encode(array('status'=>1,'list'=>$list));
        exit();
    }

    //***************************
    //  设置默认充电车型
    //***************************
    public function set_default(){
        $uid=intval($_REQUEST['uid']);
        if (!$uid){
            echo json_encode(array('status'=>0,'err'=>'登录状态异常.'));
            exit();
        }

        $bid = intval($_REQUEST['bid']);
        if (!$bid) {
            echo json_encode(array('status'=>0,'err'=>'设置失败.'.__LINE__));
            exit();
        }

        //修改默认状态
        $check = M('user_car')->where('uid='.intval($uid).' AND is_default=1')->find();
        if ($check) {
            $up1= M('user_car')->where('uid='.intval($uid))->save(array('is_default'=>0));
            if (!$up1) {
                echo json_encode(array('status'=>0,'err'=>'设置失败.'.__LINE__));
                exit();
            }
        }
        
        $up2 = M('user_car')->where('id='.intval($bid).' AND uid='.intval($uid))->save(array('is_default'=>1));
        if ($up2) {
            echo json_encode(array('status'=>1));
            exit();
        }else{
            echo json_encode(array('status'=>0,'err'=>'设置失败.'.__LINE__));
            exit();
        }
    }

}