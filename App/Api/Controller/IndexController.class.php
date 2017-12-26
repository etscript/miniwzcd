<?php
namespace Api\Controller;
use Think\Controller;
class IndexController extends PublicController {
	//***************************
	//  首页数据接口
	//***************************
    public function index(){
    	/***********获取首页顶部轮播图************/
        $ggtop=M('guanggao')->where('position=1')->order('sort asc,id asc')->field('id,photo')->limit(10)->select();
        foreach ($ggtop as $k => $v) {
            $ggtop[$k]['photo']=__DATAURL__.$v['photo'];
        }
        /***********获取首页顶部轮播图 end************/

        //判断会员是否有未完成的订单
        $is_order = 0;$order_id = 0;
        // dump($_REQUEST['uid']); 
        $order = M('order')->where('uid='.intval($_REQUEST['uid']).' AND status=10 AND back="0" AND del=0 AND order_type=1')->order('addtime desc')->limit(1)->getField('id');
        if (intval($order)>0) {
            $is_order = 1;
            $order_id = intval($order);
        }

        echo json_encode(array('ggtop'=>$ggtop,'is_order'=>$is_order,'order_id'=>$order_id));
        exit();
    }

    public function login () {

        // $xlh = $_REQUEST['xlh'];
        // $pwd = $_REQUEST['pwd'];
        $xlh = '50150082794';
        $pwd = '123456A';
        // if (!$xlh || !$pwd) {
        //     echo json_encode(array('status'=>0,'err'=>'参数错误！'));
        //     exit();
        // }

        $url = "http://www.indchina.com:7080/exlog";
        $post_data = "GRM=".$xlh."&PASS=".$pwd;
        $arr = $this->get_result($url,$post_data);

        if ($arr[0]=='OK') {
            //更新数据库
            $check = M('sid')->where('xlh="'.$xlh.'"')->getField('id');
            if ($check) {
                $data = array();
                $data['sid'] = $arr[2];
                M('sid')->where('id='.intval($check))->save($data);
            }else{
                $data = array();
                $data['xlh'] = $xlh;
                $data['sid'] = $arr[2];
                M('sid')->add($data);
            }
            $arr[2] = substr($arr[2], 4);
            //更新对应序列号数据
            $this->chargedata($xlh,$arr[2]);
        }
        echo json_encode(array('status'=>1,'data'=>$arr));
        exit();
    }

    public function chargedata ($xlh,$sid) {
        $url = "http://www.indchina.com:7080/exdata?SID=".$sid."&OP=E";
        $post_data = "NTRPGC";
        $arrs = $this->get_result($url,$post_data);
        if ($arrs[0]=='OK') {
            $data = array();
            $data['regid'] = $xlh;
            $data['num'] = $arrs[1];
            $data['addtime'] = time();
            array_splice($arrs,0,2);
            $array = array();
            foreach ($arrs as $k => $v) {
                $array[] = trim($v,"$");
            }
            $data['name'] = implode("|", $array);
            $check = M('blname')->where('regid="'.$xlh.'"')->getField('id');
            if ($check) {
                M('blname')->where('id='.intval($check))->save($data);
            }else{
                M('blname')->add($data);
            }
        }

    }

    public function data () {
        $url = "http://www.indchina.com:7080/exdata?SID=3318D04D0E0D2517&OP=E";
        $post_data = "NTRPGC";
        $arrs = $this->get_result($url,$post_data);
        dump($arrs);

    }

    public function getval () {
        $url = "http://www.indchina.com:7080/exdata?SID=E9E3D53681DBF3A8&OP=R";
        $post_data = "15\r\nppileid1\r\npCommand1\r\npuserid1\r\npcarid1\r\npleavetime1\r\npstart_time1\r\npguarante1\r\npbalance1\r\npamount1\r\npcurrent1\r\npstartpower1\r\npnowpower1\r\npmonamount1\r\nppowerunit1\r\npbithandle1";
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
        dump($aa);
        die();
    }

    public function getvals () {
        $url = "http://www.indchina.com:7080/exdata?SID=987DA9731AB74BFB&OP=R";
        $post_data = "12\r\nCom1Error\r\nCom2Error\r\nCom3Error\r\nNetComError\r\nSIGNAL\r\nNetTraffic\r\nMODBUS读\r\nW2\r\nMODBUS写输出Q0\r\n短信报警Q1\r\n电话报警Q2\r\n以太网控制Q3";
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
        dump($aa);
        die();
    }

    public function upval () {
        $url = "http://www.indchina.com:7080/exdata?SID=B50111D97C353974&OP=W";
        //指令ID 会员ID 充电桩ID 车型ID 是否实名认证 请求充电 预计提车时间  押金余额  钱包余额  是否充满  充电金额  是否可以充电
        $post_data = "12\r\npCommand1\r\n001\r\npuserid1\r\n1\r\nppileid1\r\n1\r\npcarid1\r\n1\r\nsignin\r\n0\r\nchargeinquire\r\n1\r\nleavetime\r\n15:00:00\r\nguarantebalance\r\n0\r\nmoneybalance\r\n20\r\nfullchargetype\r\n1\r\nmoneytypeamount\r\n20\r\nchargeadmit\r\n1";
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
        dump($aa);
        die();
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

    public function getcode(){
        $str = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol)-1;

        for($i=0;$i<32;$i++){
            $str.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }

        echo json_encode(array('status'=>'OK','code'=>$str));
        exit();
    }

}