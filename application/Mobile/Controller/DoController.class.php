<?php
namespace Mobile\Controller;
use Common\Controller\AppController;
class DoController extends AppController {

    public function index(){
        $the_all=0;
        $uid_arr=array(1);  //查询人得id数组，多个用户id
        foreach ($uid_arr as $uid){
            $ui=getUserInfoById($uid);
            $buy_point = M('order')->where(array('uid'=>$uid,'coin'=>1))->sum("goods_price");  //购买积分商品总消耗
            $buy_point += $uid['score'];   //加上剩余的金币
            $out_point =M('out_point')->where(array('uid'=>$uid,'status'=>1))->sum("point");   //提现总数
            //$out_point = $out_point*0.85;
            $the_m=$buy_point-$out_point;
            echo $ui['user_nicename'].'：'.$the_m.'<br/>';
            if($the_m>0){
                $the_all+=$the_m;
            }else{
                //echo $uid.'<br/>';
            }
        }

       // echo '提现：'.$out_point_all.'<br/>投入：'.$buy_all;.
        echo $the_all;

    }


}