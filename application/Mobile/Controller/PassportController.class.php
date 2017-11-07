<?php
namespace Mobile\Controller;
use Common\Controller\AppController;
class PassportController extends AppController {

    public function index(){

    }
    //登录
    public function login() {
        $mobile = I('mobile');
        $version = I('version');
        $password = I('user_pass');
        //版本检查
        if ($version != getCore("app_version")) {
            $this->exitJson(2021);
        }

//        $blacklist=check_blacklist(1,$mobile);//检查是否在黑名单，如果在黑名单，不让注册
//        $blacklist_ip=check_blacklist(4,get_client_ip());//检查是否在黑名单，如果在黑名单，不让注册
//        if(false==$blacklist){
//            $this->exitJson(2019);
//        }
        if (empty($mobile)) {
            $this->exitJson(2005);
        }
//        if(false==$blacklist_ip){
//            $this->exitJson(2020);
//        }

        if (empty($password)) {
            $this->exitJson(2006);
        }
        //do_help($this->userinfo["id"]);
//        $user = getUserInfoByMobile($mobile);
       /* if ($user['user_pass'] != sp_password($password)) {
            $this->exitJson(2031);
        }*/

        if (true ==D('Passport')->login($mobile, $password)) {
//            $uid = getId($mobile);

            $result['mobile']=$mobile;
            $result['token']=D('Passport')->getToken();
            $parent_info=getUserInfoById($this->userinfo['parent_id']);
            $result['parent_mobile']=$parent_info["mobile"];
            $result['score']=$this->userinfo['score'];
            $result['status']=$this->userinfo['user_status'];
//            $result['level']=$this->userinfo['level'];
            $result['is_realname']=D('Users')->is_realname($this->userinfo['id']);
            $result['coin']=$this->userinfo['coin'];
           /* $ec = M("ec_address")->where(array("uid"=>$uid))->find();
            if($ec){
                $result['ec'] = "1";
            }else{
                $result['ec'] = "0";
            }*/




//            if($this->userinfo['user_status']==0){
//                $this->exitJson(0,$result);//账号被封，请联系管理员
//            }
            /*if($result['status']=="0"){
                $a=M("user_block_list")->where(array("type"=>1,"uid"=>$uid))->order("time desc")->getField("note");
                if(preg_match('!^https  ?://.+!i',$a)){
                }
                $result['block_cause']=$a;
            }else{
                $result['block_cause']="";
            }*/

            $this->exitJson(0, $result);
        } else {
            $this->exitJson(2007, D('Passport')->getError());
        }
    }

    //注册
    public function register() {
        $scode = I('post.scode');
        $data['mobile'] = I('post.mobile');
        $data['parent_mobile'] = I('post.parent_mobile');
        $data['user_nicename'] = I('post.user_nicename');
        //$blacklist=check_blacklist(1,$data['mobile']);//检查是否在黑名单，如果在黑名单，不让注册
//        if(false==$blacklist){
//            $this->exitJson(2019);
//        }

        if ( !$this->isMobile($data['mobile'] ) )
        {
            $this->exitJson(2001);
        }
        if(empty($scode)){
            $this->exitJson(2011);//验证码不正确
        }
        $randstring = session('scode');
        if($data['mobile']==$data['parent_mobile']){
            $this->exitJson(2016);
        }
        if($randstring!=$scode){
            $this->exitJson(2011);//验证码不正确
        }
        if ($data['mobile'] != session('mobile')) {
            $this->exitJson(2011);
        }

        if (getUserInfoByMobile($data['mobile'])) {
            $this->exitJson(2010); //手机号已存在
        }
        $data['user_pass'] = I('post.user_pass'); //整合UC的时候需要
        if (empty($data['user_pass']) || strlen($data['user_pass']) < 6) {
            $this->exitJson(2002);
        }
        if(!is_pwd( $data['user_pass'] )){
            $this->exitJson(2002);
        }

        (int)$max_reg_num=getCore("reg_max");
        $today_time=strtotime(date("Y-m-d 9:0:0"));
        $yesterday_time=$today_time-24*3600;
        $tomorrow_time=$today_time+24*3600;
        if(date("H")<9){
            $reg_num=M("users")->where("create_time >= '".date("Y-m-d 9:0:0",$yesterday_time)."'&& create_time < '".date("Y-m-d 9:0:0",$today_time)."'")->count();
        }else{

            $reg_num=M("users")->where("create_time >= '".date("Y-m-d 9:0:0",$today_time)."' && create_time < '".date("Y-m-d 9:0:0",$tomorrow_time)."'")->count();
        }

        if($reg_num>=$max_reg_num && $max_reg_num!=0){
            $this->exitJson(2024);
        }

        if (empty( $data['parent_mobile'])) {
            $this->exitJson(2013);//邀请人手机号不能为空
        }
        //验证邀请人手机号是否正确
        $parent_user_info=getUserInfoByMobile($data['parent_mobile']);
        if(!$parent_user_info){
            $this->exitJson(2016);
        }
        if($parent_user_info["user_status"] == "0"){
            $this->exitJson(2027);//邀请人被冻结
        }
        if (empty( $data['user_nicename'])) {
            $this->exitJson(2014);//真实姓名不能为空
        }

        $data['last_login_ip'] = get_client_ip();
        $data['create_time'] = date('Y-m-d H:i:s',time());
        $data['nickname'] =  $data['user_nicename'];

        //开始其他的判断了
        if (true == D('Passport')->register($data)) {
//            //永恒币同步注册 暂时关闭
//            $url = 'http://pay.yhb9999.cn/Home/Axb/register';
//            $post_data['mobile'] = $data['mobile'];
//            $post_data['pwd'] = $data['user_pass'];
//            $post_data['parent_mobile'] = $data['parent_mobile'];
//            $post_data['username'] = $data['user_nicename'];
//            $post_data['token'] = md5($data['mobile'].'dwfew');
//            $res = $this->request_post($url, $post_data);

//            if($res=="4"){
//                sendSMS(array(),"yhb",$data['mobile']); //注册成功 0
//            }
//            else{
//                $this->exitJson(6666,"注册成功，请返回登录(code:".$res.")");
//            }
            $this->exitJson(0);
        }
        $this->exitJson(2004, D('Passport')->getError()); //注册失败
    }

    //找回密码
    public function retrieve_password() {
        $newpwd = I('post.newpwd');
        $mobile = I('post.mobile');
        $scode =I('post.scode');
        if(empty($scode)){
            $this->exitJson(2011);//验证码不正确
        }
        $randstring = session('scode');
        if($randstring!=$scode){
            $this->exitJson(2011);//验证码不正确
        }
        if ($mobile != session('mobile')) {
            $this->exitJson(2013);
        }

        if(!is_pwd($newpwd)){
            $this->exitJson(2002);
        }
        $do=D('Passport')->uppwd($mobile,$newpwd);
        if ($do=="-1"){
            $this->exitJson(5032);
        } elseif($do=="-2"){
            $this->exitJson(2026);
        } else{
            $ds["user_nicename"] = getUserNameByMobile($mobile);
            $ds["mobile"]=$mobile;
            $ds["type"]="re_pwd";
            sendSMS($ds); //密码修改 0
            $this->exitJson(0, '修改密码成功！');
        }
        $this->exitJson(2009);
    }

    //获取短信
    public function reg_sms(){
        $mobile =I('post.mobile');
        $new =I('post.new');
        $sms_type =I('post.type');
        $sms_type=empty($sms_type)?0:$sms_type;
        if (!$this->isMobile($mobile)) {
            $this->exitJson(2001); //请输入正确的手机号
        }
        $ui=getUserInfoByMobile($mobile);
        if ($new=="1") {
            if($ui){
                $this->exitJson(2010); //手机号已存在
            }
        }else{
            if(!$ui){
                $this->exitJson(2000); //手机号已存在
            }
        }

        (int)$max_reg_num=M("core")->where(array("id"=>"2"))->getField("status");
        $today_time=strtotime(date("Y-m-d 9:0:0"));
        $yesterday_time=$today_time-24*3600;
        $tomorrow_time=$today_time+24*3600;

        if(date("H")<9){
            $reg_num=M("users")->where("create_time >= '".date("Y-m-d 9:0:0",$yesterday_time)."'&& create_time < '".date("Y-m-d 9:0:0",$today_time)."'")->count();
        }else{
            //$this->exitJson(10,date("H")."create_time >= ".date("Y-m-d 9:0:0",$today_time)."&& create_time < ".date("Y-m-d 9:0:0",$tomorrow_time));
            $reg_num=M("users")->where("create_time >= '".date("Y-m-d 9:0:0",$today_time)."' && create_time < '".date("Y-m-d 9:0:0",$tomorrow_time)."'")->count();

        }

        if($reg_num>=$max_reg_num && $max_reg_num!=0){
            $this->exitJson(2024);
        }

        $old_s=session("scode");
        if(!empty($old_s)){
           // $this->exitJson(2023);
        }

        $last_sms_time=M("sms_log")->where(array("mobile"=>$mobile))->find();
        if($last_sms_time){
            if((strtotime($last_sms_time["time"])+60)>=time()){
                //$this->exitJson(2023);
            }else{
                M("sms_log")->where(array("mobile"=>$mobile))->save(array("time"=>date("Y-m-d H:i:s")));
            }
        }else{
            M("sms_log")->add(array("mobile"=>$mobile,"time"=>date("Y-m-d H:i:s")));
        }

//        if($type=="1"){
//            sendSMS2($randstring,"",$mobile); //语音验证码
//        }else{
//            sendSMS($smsParams,"yzm_zc",$mobile); //注册验证码 0
//        }

        $sd["mobile"]=$mobile;
        $sd["type"]="reg_code";
        if(sendSMS($sd,$sms_type)){
            $this->exitJson(0);
        }else{
            $this->exitJson(103);
        }

    }


    private function isMobile($mobile){
        if ( preg_match( "/^1[34578]\d{9}$/", $mobile ) )
        {
            return true;
        }else{
            return false;
        }
    }


    //修改昵称
    //author:hhj
    public function modified_nickname(){
        $mobile = I('post.mobile');
        $nickname = I('post.nickname');
        if(empty($nickname)){
            $this->exitJson(2028);
        }else{
            $data['nickname'] = $nickname;
            M('Users')->where(array('mobile'=>$mobile))->save($data);
            $this->exitJson(0,"修改成功");
        }
    }

    //个人中心首页
    public function user_info(){
        if($this->token!="0"){
            if ($this->userinfo['token'] != $this->token) {
                $this->exitJson(2101);
            }
            if((strtotime($this->userinfo["last_login_time"])+24*15*3600)<time()){
                $this->exitJson(2101);
            }
            $data["isLogin"]="1";
            $data['nickName']=$this->userinfo['nickname']==NULL?"暂无":$this->userinfo['nickname'];
            $realname=M('realname')->where(array("uid"=>$this->userinfo['id']))->find();
            if(!$realname){
                $data['realname']="0";
            }else{
                $data['realname']="1";
            }
            $data['level']= level2_string($this->userinfo["level"]);
            $data['score']=$this->userinfo['score'];
            $data['coin']=$this->userinfo['coin'];

            $quan = M("coupon")->where(array("use_uid"=>$this->uid))->where("oid is NULL")->count();
            if(empty($quan)){
                $quan = "0";
            }
            $data['vouchers_num'] = $quan;
            $data['avatar']=$this->userinfo['avatar']==NULL?"":$this->userinfo['avatar'];
            $noread_count = M("user_message")->where(array("uid"=>$this->uid,"status"=>"0"))->count();
            if(empty($noread_count)){
                $noread_count = "0";
            }
            $data["no_read_count"] = $noread_count;

            $sys_mes_num =M("message")
                ->where(array(
                    "uid"=>$this->uid,
                    "parent_id"=>0,
                    "isread"=>"1",
                    "type"=>array('neq',1)
                ))
                ->count();
            if(empty($sys_mes_num)){
                $sys_mes_num="0";
            }
           // $data["sys_mes_num"]="".$sys_mes_num;

            $money_mes_num =M("message")
                ->where(array(
                    "uid"=>$this->uid,
                    "parent_id"=>0,
                    "isread"=>"1",
                    "type"=>array('eq',1)
                ))
                ->count();
            if(empty($money_mes_num)){
                $money_mes_num="0";
            }
           // $data["money_mes_num"]="".$money_mes_num;

            $shop_mes_num =M("message_shop")
                ->where(array("uid"=>$this->uid,
                "parent_id"=>0,
                "isread"=>"1"
                ))->count();
            if(empty($shop_mes_num)){
                $shop_mes_num="0";
            }
            //$data["shop_mes_num"]="".$shop_mes_num;
            $data["message_num"]="".($shop_mes_num+$money_mes_num+$sys_mes_num);

//            $user_mes_num1 =M("user_message")->where(array("uid"=>$this->uid,"status"=>"0"))->count();
//            $user_mes_num2 =M("message")->where(array("parent_id"=>"0","parent_user"=>$this->uid,"reply"=>"0"))->count();
//            $user_mes_num = $user_mes_num1 + $user_mes_num2;
//            if(empty($user_mes_num)){
//                $user_mes_num="0";
//            }
//            $data["user_mes_num"]="".$user_mes_num;


            $notice_list =D('Article')->getArticleList(2);
            if(count($notice_list)>0){
                $new_notice=$notice_list[0];
                if($this->userinfo['last_get_notice'] < strtotime($new_notice["post_date"])){
                    $data['news_show_tip']="1";
                }else{
                    $data['news_show_tip']="0";
                }
            }else{
                $data['news_show_tip']="0";
            }

            $data['no_pay_order'] ="".M('order')->where(array("uid"=>$this->uid,"order_status"=>"0"))->count();
            $data['no_send_order'] ="".M('order')->where(array("uid"=>$this->uid,"order_status"=>"1"))->count();
            $data['no_assess_order'] ="".M('order')->where(array("uid"=>$this->uid,"order_status"=>"2"))->count();
        }else{
            $data["isLogin"]="0";
            $data['news_show_tip']="0";
        }

        $notice_list =D('Article')->getArticleList(2);
        $notice_list[0]["post_content"]=str_replace("&nbsp;","",strip_tags($notice_list[0]["post_content"]));
        $data["the_news"]=$notice_list[0];
        $this->exitJson(0,$data);
    }


}