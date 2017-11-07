<?php
namespace User\Controller;
use Common\Controller\HomebaseController;
class ShowController extends HomebaseController
{
    //检查登录
    public function show_login($mobile,$user_pass){
        $user = getUserInfoByMobile($mobile);
        if (empty($user)) {
            $this->error = '无此用户';
            return false;
        }
        if ($user["user_pass"] != sp_password($user_pass)) {
            $this->error = '账号或密码不正确';
            return false;
        }
        session('uid',getId($mobile));
        return true;
    }

    //检查管理员登录
    public function show_admin_login($user_login,$password){
        $user = M("users")->where(array("user_login"=>$user_login))->find();
        if(empty($user)){
            $this->error = '无此用户';
            return false;
        }
        if ($user["user_pass"] != sp_password($password)) {
            $this->error = '账号或密码不正确';
            return false;
        }
        $uid = $user['id'];
        session("uid",$uid);
        return true;
    }

    //是否登录
    public function is_login()
    {
        $uid = session("uid");
        if (empty($uid)) {
            $this->redirect(U("Show/login"));
        } else {
            return $uid;
        }
    }

    //是否登录
    public function is_admin_login()
    {
        $uid = session("uid");
        if (empty($uid)) {
            $this->redirect(U("Show/admin_login"));
        } else {
            return $uid;
        }
    }


    public function login()
    {
        $uid = session('uid');
        if (!empty($uid)) {
            $this->redirect(U("Show/user_center"));
        }
        $this->display();
    }

    public function do_login()
    {
        $mobile = I("post.mobile");
        $password = I("post.password");
        $verify = I("post.verify");
        if(!sp_check_verify_code()){
            $this->error("验证码错误！");
        }
        if (empty($mobile) || empty($password) || empty($verify)) {
            $this->error("所填内容不能为空");
        }
        $result = $this->show_login($mobile, $password);
        if ($result == true) {
            $this->redirect(U("Show/user_center"));
        } else {
            $this->error("账号密码错误");
        }
    }

    //退出登录
    public function outlogin()
    {
        session_destroy();
        $this->redirect(U("Show/login"));
    }

    //用户中心
    public function user_center(){

        $uid = $this->is_login();
        $fx = M("out_point")->where(array("uid"=>$uid,"type"=>2,"status"=>1))->sum("point");
        if($fx <= 0 || empty($fx)){
            $fx = 0;
        }
        $sy = M("out_point")->where(array("uid"=>$uid,"type"=>4,"status"=>1))->sum("point");
        if($sy <= 0 || empty($sy)){
            $sy = 0;
        }
        $zs = M("out_point")->where(array("uid"=>$uid,"type"=>3,"status"=>1))->sum("point");
        if($zs <= 0 || empty($zs)){
            $zs = 0;
        }
        $bank = M("zcbank")->where(array("uid"=>$uid))->find();
        if(empty($bank)){
            $bank = "8";
        }
        $name = getUserNameById($uid);
        $info = M("pi")->where(array("uid"=>$uid))->select();
        foreach ($info as $k=>$v){
            if($v['out_time']==0){
                $info[$k]['out_time1'] = "未出场";
            }else{
                $info[$k]['out_time1'] = date("Y-m-d H:i:s",$v['out_time']);
            }
        }
        $this->assign("fx",$fx);
        $this->assign("sy",$sy);
        $this->assign("zs",$zs);
        $this->assign("info",$info);
        $this->assign("name",$name);
        $this->assign("uid",$uid);
        $this->assign("bank",$bank);
        $this->display();
    }

    //添加银行卡信息
    public function bank_add(){
        $uid = $this->is_login();
        $puid = I("post.uid");
        $name = I("post.name");
        $num = I("post.bank_num");
        $address = I("post.address");
        if($uid != $puid){
            $this->error("系统错误");
        }
        $bank = M("zcbank")->where(array("uid"=>$uid))->find();
        if($bank){
            $this->error("银行卡信息已添加，请勿重复提交");
        }
        if(empty($name) || empty($num) || empty($address)){
            $this->error("所填内容不能为空");
        }
        $save['time'] = time();
        $save['uid'] = $uid;
        $save['name'] = $name;
        $save['num'] = $num;
        $save['address'] = $address;
        $result = M("zcbank")->add($save);
        if($result){
            $this->success("添加成功",U("Show/user_center"));
        }else{
            $this->error("添加失败");
        }
    }

    //删除银行卡信息
    public function bank_delete(){
        $uid = $this->is_login();
        $guid = I("get.uid");
        if($uid != $guid){
            $this->error("系统错误");
        }
        $result = M("zcbank")->where(array("uid"=>$uid))->delete();
        if($result){
            $this->success("删除成功");
        }else{
            $this->error("删除失败");
        }
    }

    //管理员登录
    public function admin_login(){
        $this->display();
    }

    //检查管理员登录
    public function do_admin_login(){
        $user_login = I("post.user_login");
        $password = I("post.password");
        if (empty($user_login) || empty($password)) {
            $this->error("所填内容不能为空");
        }
        $result = $this->show_admin_login($user_login, $password);
        if ($result == true) {
            $this->redirect(U("Show/admin_center"));
        } else {
            $this->error("账号密码错误");
        }
    }

    public function admin_center(){
        $this->is_admin_login();
        $this->assign("aa","8");
        $this->display();
    }

    //查询团队总人数
    public function count1($uid,$result=array()){
        global $result;
        $list = M("users")->where(array("parent_id"=>$uid))->select();
        if(!empty($list)){
            foreach ($list as $k=>$v){
                $result[] = $v['id'];
                $this->count1($v['id'],$result);
            }
        }
        return $result;
    }

    //查询详情
    public function search(){
        $this->is_admin_login();
        $mobile = I("post.key");
        $uid = getId($mobile);
        if(empty($uid)){
            $this->error("请输入正确的手机号");
        }
        $info = M("users")->where(array("parent_id"=>$uid))->select();
        foreach ($info as $k=>$v){
            $puid = $v['id'];
            $info[$k]['fx'] = M("out_point")->where(array("uid"=>$puid,"type"=>2,"status"=>1))->sum("point");
            $info[$k]['sy'] = M("out_point")->where(array("uid"=>$puid,"type"=>4,"status"=>1))->sum("point");
            $info[$k]['zs'] = M("out_point")->where(array("uid"=>$puid,"type"=>3,"status"=>1))->sum("point");
            if(empty($info[$k]['fx'])){
                $info[$k]['fx']=0;
            }
            if(empty($info[$k]['sy'])){
                $info[$k]['sy']=0;
            }
            if(empty($info[$k]['zs'])){
                $info[$k]['zs']=0;
            }
            $bj1 = M("order")->where(array("uid"=>$puid,"coin"=>1))->sum("goods_price");

            if(empty($bj1)){
                $bj1=0;
            }
            $xf = M("order")->where(array("uid"=>$puid,"coin"=>0))->sum("goods_price");
            if(empty($xf)){
                $xf = 0;
            }
            $info[$k]['xf'] = $xf;
            $info[$k]['bj'] = $bj1;
            $info[$k]['yj'] = $bj1- $info[$k]['fx']-$info[$k]['sy']-$info[$k]['zs']-$xf;
            if($info[$k]['yj']<0){
                $info[$k]['yj']=0;
            }
        }

        $count2 = $this->count1($uid);
        $count1 = count($count2);
        $money3=0;
        for ($i=0;$i<=$count1;$i++){
//            $count2['m'] = M('users')->where(array("id"=>$count2[$i],"user_type"=>2))->sum("score");
//            $count2['a'] = M('pi')->where(array("uid"=>$count2[$i],"status"=>0))->sum("point");
            $count3 = M("order")->where(array("uid"=>$count2[$i],"coin"=>1))->sum("goods_price");
            $money3 += $count3;
        }
        $name = getUserNameById($uid);
        $data = $name."的团队总人数：".$count1.",团队总业绩：".$money3;
        $this->assign("username",$name);
        $this->assign("uid",$uid);
        $this->assign("data",$data);
        $this->assign("info",$info);
        $this->display("admin_center");
    }

    //订单导出
    public function order_push(){
        $uid = I("uid");
        $username = getUserNameById($uid);
        $info = M("users")->where(array("parent_id"=>$uid))->select();
        foreach ($info as $k=>$v){
            $puid = $v['id'];
            $fx = M("out_point")->where(array("uid"=>$puid,"type"=>2,"status"=>1))->sum("point");
            $sy = M("out_point")->where(array("uid"=>$puid,"type"=>4,"status"=>1))->sum("point");
            $zs = M("out_point")->where(array("uid"=>$puid,"type"=>3,"status"=>1))->sum("point");
            if(empty($fx)){
                $fx=0;
            }
            if(empty($sy)){
                $sy=0;
            }
            if(empty($zs)){
                $zs=0;
            }
            $bj1 = M("order")->where(array("uid"=>$puid,"coin"=>1))->sum("goods_price");

            if(empty($bj1)){
                $bj1=0;
            }
            $xf = M("order")->where(array("uid"=>$puid,"coin"=>0))->sum("goods_price");
            if(empty($xf)){
                $xf = 0;
            }
            $yj = $bj1-$fx-$sy-$zs-$xf;
            if($yj<0){
                $yj=0;
            }

            $orders[$k]['mobile'] = $v['mobile'];
            $orders[$k]['user_nicename'] = $v['user_nicename'];
            $orders[$k]['level'] = level_string($v['level']);
            $orders[$k]['bj'] = $bj1;
            $orders[$k]['fx'] = $fx;
            $orders[$k]['sy'] = $sy;
            $orders[$k]['zs'] = $zs;
            $orders[$k]['xf'] = $xf;
            $orders[$k]['yj'] = $yj;
        }
        $order_header=array(
            "手机号","姓名","等级","本金","已兑换的分享钱包","已兑换的收益钱包","已兑换的赠送钱包","消费积分购买金额","应急处置额"
        );
        array_unshift($orders,$order_header);
        $file_name = $username."的一级团队详情";
        push_excel($orders,$file_name);
    }

    //查询无限代
    public function wxd($uid,$result=array()){
        global $result;
        $list = M("users")->where(array("parent_id"=>$uid))->select();
        if(!empty($list)){
            foreach ($list as $k=>$v){
                $result[] = $v['id'];
                $this->wxd($v['id'],$result);
            }
        }
        return $result;
    }

    public function www(){
        $uid = I("post.uid");
        $username = getUserNameById($uid);
        $r = $this->wxd($uid);
        foreach ($r as $k=>$v){
            $mobile = getMobile($v);
            $name = getUserNameById($v);

            $fx = M("out_point")->where(array("uid"=>$v,"type"=>2,"status"=>1))->sum("point");
            $sy = M("out_point")->where(array("uid"=>$v,"type"=>4,"status"=>1))->sum("point");
            $zs = M("out_point")->where(array("uid"=>$v,"type"=>3,"status"=>1))->sum("point");
            if(empty($fx)){
                $fx=0;
            }
            if(empty($sy)){
                $sy=0;
            }
            if(empty($zs)){
                $zs=0;
            }
            $bj1 = M("order")->where(array("uid"=>$v,"coin"=>1))->sum("goods_price");

            if(empty($bj1)){
                $bj1=0;
            }
            $xf = M("order")->where(array("uid"=>$v,"coin"=>0))->sum("goods_price");
            if(empty($xf)){
                $xf = 0;
            }
            $yj = $bj1-$fx-$sy-$zs-$xf;
            if($yj<0){
                $yj=0;
            }

            $re[$k]['id'] = $v;
            $re[$k]['mobile'] = $mobile;
            $re[$k]['name'] = $name;
            $re[$k]['yj'] = $yj;
        }

        $order_header=array("ID","手机号","姓名","应急处置额");
        array_unshift($re,$order_header);
        $file_name = $username."的团队人员";
        push_excel($re,$file_name);
    }

    //导出银行卡
    public function yh(){
        $result = M("zcbank")->select();
        foreach ($result as $k=>$v){
            $mobile = getMobile($v['uid']);
            $name = getUserNameById($v['uid']);
            $order[$k]['uid'] = $v['uid'];
            $order[$k]['mobile'] = $mobile;
            $order[$k]['name'] = $name;
            $order[$k]['yh_name'] = $v['name'];
            $order[$k]['num'] = $v['num'];
            $order[$k]['address'] = $v['address'];
        }
        $order_header=array("ID","漫香账号","姓名","持卡人姓名","银行卡号","开户行地址");
        array_unshift($order,$order_header);
        $file_name = "收款信息";
        push_excel($order,$file_name);
    }
}