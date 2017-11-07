<?php
namespace Mobile\Controller;
use Common\Controller\AppController;
use Foo\IBar;

class UserController extends AppController {

    public function _initialize() {
        parent::_initialize();
//        if($this->token=="0"){
//            $this->exitJson(2100);
//        }
//        if ($this->userinfo['token'] != $this->token) {
//            $this->exitJson(2101);
//        }
//        if((strtotime($this->userinfo["last_login_time"])+24*15*3600)<time()){
//            $this->exitJson(2101);
//        }
    }
    public function index(){

    }

    //修改密码
    public function edit_password() {
        $oldpwd = I('post.oldpwd');
        $newpwd = trim(I('post.newpwd'));

        if (!$oldpwd || !$newpwd) {
            $this->exitJson(2102);
        }
        if ($this->userinfo['user_pass'] != sp_password($oldpwd)) {
            $this->exitJson(2008);
        }
        if($this->userinfo['user_pass']==sp_password($newpwd)){
            $this->exitJson(2026);
        }
        if(!is_pwd($newpwd)){
            $this->exitJson(2002);
        }
        $rp=D('Passport')->uppwd($this->userinfo['mobile'],$newpwd);
        if ($rp) {
            $un = $this->userinfo['user_nicename'];
            $db["name"]=$un;
            $db["mobile"]=$this->userinfo['mobile'];
            $db["type"]="re_pwd";
            sendSMS($db);
            session("gt_code","");
            $this->exitJson(0, '修改密码成功！');
        }elseif($rp == "-1"){
            $this->exitJson(2103);
        }
        elseif($rp == "-2"){
            $this->exitJson(2026);
        }
        $this->exitJson(2009);
    }
    //实名认证
    public function realname(){
        $realname = I('post.realname');
        $idcard = I('post.idcard');
        // $users=M('realname')->where("uid="."'$this->uid'")->find();
        /*if($users){
            $this->exitJson(6005);//已认证过
        }*/
        if(empty($realname)){
            $this->exitJson(6004);
        }
        if(empty($idcard)|| strlen($idcard) != 18){
            $this->exitJson(6003);


        }
//        $y=substr($idcard,6,4);
//        $m=substr($idcard,10,2);
//        $d=substr($idcard,12,2);
//        if(strtotime(date("Y").$m.$d) < time()){
//            $data_c=date("Y")-$y;
//        }else{
//            $data_c=date("Y")-$y-1;
//        }
//        if($data_c<18 || $data_c>60){
//            $this->exitJson(6007);
//        }


        $old_idcard = M('realname')->where("idcard="."'$idcard'")->find();//验证身份证号是否被认证，如果是自己认证的，可以得继续添加
//        $black_idcard=check_blacklist(2,$idcard);//是否加入黑名单
        if(!empty($old_idcard)){
            if($this->uid!=$old_idcard['uid']){
                $this->exitJson(6002);//已重复的身份证号
            }

        }
//        if(false==$black_idcard){
//            $this->exitJson(6006);//黑名单
//        }
        $xmlstr = I('post.img');
        if(!$xmlstr){
            $this->exitJson(6001);
        }
        if (true==D('Users')->deal_realname($this->mobile,$xmlstr,$realname,$idcard,$this->uid)) {
            $this->exitJson(0, '申请认证成功，请等待管理员审核！');
        } else {
            $this->exitJson(6005);
        }
    }

    //设置二级密码
    public function second_pwd(){
        $id = $this->uid;
        $second_pwd= trim(I('post.second_pwd'));
        $second_pwd_old = M('users')->where(array("id"=>$id))->getField('spwd');
        if($second_pwd_old == sp_password($second_pwd)){
            $this->exitJson(109);
        }
//        $pwd = I('post.pwd');
//        if($this->userinfo['user_pass']!=sp_password($pwd)){
//            $this->exitJson(107);
//        }
        $scode = I('post.scode');
        if(empty($scode)){
            $this->exitJson(2011);//验证码不正确
        }
        $randstring = session('scode');
        if($randstring!=$scode){
            $this->exitJson(2011);
        }
        if(!is_pwd($second_pwd)){
            $this->exitJson(2002);
        }
        $result = M('users')->where(array("id"=>$id))->save(array("spwd"=>sp_password($second_pwd)));
        if($result){
            $this->exitJson(0,"二级密码设置成功！");
        }

    }

    //添加提现地址
    public function wallet_address(){
        $data['uid'] = $this->uid;
        $data['exchange_id'] = I("post.wallet_type");
//        $exchange = M("wallet_address")->where(array("uid"=>$data['uid'],"exchange_id"=>$data['exchange_id']))->order("change_time DESC")->find();
//        if($exchange){
//            $this->exitJson(15007);
//        }
        if(empty($data)){
            $this->exitJson(15009);
        }
        $wallet_address = trim(I('post.wallet_address'));
        //$second_pwd = I('post.second_pwd');
        $scode = I('post.scode');
        $randstring = session('scode');
        if($randstring!=$scode){
//             $this->exitJson(201145445,"your scode:".$randstring);//验证码不正确
            $this->exitJson(2011);//验证码不正确
        }
//        if($this->userinfo['spwd']!=sp_password($second_pwd)){
//            $this->exitJson(104);
//        }
        $n = strlen($wallet_address);
        if(!preg_match("/^([a-zA-Z0-9]+)$/i",$wallet_address)||$n<32||$n>34){
            $this->exitJson(108);
        }
        $data['address'] = $wallet_address;
        $data['change_time'] = date("Y-m-d H:i:s",time());
//        $find = M("wallet_address")->where(array("uid"=>$data['uid']))->order("change_time DESC")->find();
//        if($find['address'] == $wallet_address){
//            $this->exitJson(15007);
//        }
        $result = M('wallet_address')->add($data);
        if($result){
            $this->exitJson(0,"提现地址设置成功！");
        }

    }

    //提现地址列表
    public function wallet_address_lists(){
        $uid = $this->uid;
        $lists = M("wallet_address")->where(array("uid"=>$uid))->order("change_time DESC")->group("exchange_id")->select();
        foreach ($lists as $k => $v){
            $lists[$k]['name'] = M("exchange")->where(array("id"=>$v['exchange_id']))->getField("name");
        }
        if(empty($lists)){
            $lists = array();
        }
        $data["lists"] = $lists;
        $this->exitJson(0,$data);
    }

    //编辑提现地址
    public function wallet_address_edit(){
        $id = I("post.id");
        $new_address = trim(I("post.wallet_address"));
        $old_adress = M("wallet_address")->where(array("id"=>$id))->getField("address");
        $scode = I('post.scode');
        $randstring = session('scode');
        if($randstring!=$scode){
//             $this->exitJson(201145445,"your scode:".$randstring);//验证码不正确
            $this->exitJson(2011);//验证码不正确
        }
//        if($this->userinfo['spwd']!=sp_password($second_pwd)){
//            $this->exitJson(104);
//        }
        $n = strlen($new_address);
        if(!preg_match("/^([a-zA-Z0-9]+)$/i",$new_address)||$n<32||$n>34){
            $this->exitJson(108);
        }
        if($new_address == $old_adress){
            $this->exitJson(15008);
        }
        $data['change_time'] = date("Y-m-d H:i:s",time());
//        $find = M("wallet_address")->where(array("uid"=>$data['uid']))->order("change_time DESC")->find();
//        if($find['address'] == $wallet_address){
//            $this->exitJson(15007);
//        }
        $save['address'] = $new_address;
        $result = M('wallet_address')->where(array("id"=>$id))->save($save);
        if($result){
            $this->exitJson(0,"提现地址更新成功！");
        }
    }

    //删除提现地址
    public function wallet_address_delete(){
        $id = I("post.id");
        $result = M("wallet_address")->where(array("id"=>$id))->delete();
        if($result){
            $this->exitJson(0,"删除成功！");
        }

    }

    //获取实名认证信息
    public function get_realname_info(){
        $realname=M('realname')->where(array("uid"=>$this->userinfo['id']))->find();
        $wallet_address = M('wallet_address')->where(array("uid"=>$this->userinfo['id']))->order('change_time desc')->find();
        if(empty($wallet_address['address'])){
            $data["wallet_address"] = "0";
        }else{
            $data["wallet_address"] = "".$wallet_address['address'];
        }
        $second_pwd = M('users')->where(array("id"=>$this->userinfo['id']))->find();
        if(empty($second_pwd['spwd'])){
            $data['second_pwd'] = "0";
        }else{
            $data['second_pwd'] = "1";
        }
        $data["realname"]="".$realname["realname"];
        $data["status"] = "".$realname["status"];
        $data["idcard"]="".$realname["idcard"];
        $data["img"]="".$realname["img"];
        $this->exitJson(0,$data);
    }

    //个人消息(站内信)
    //站内信列表
    public function user_message(){
        $mobile = I("mobile");
        $uid = getId($mobile);
        $p = I("p");
        if(empty($p)){
            $p = 1;
        }
        $count = M("user_message")->where(array("uid"=>$uid,"del_status"=>"1"))->count();
//        $Page       = new \Think\Page($count,20);
        $list = M("user_message")
            ->where(array("uid"=>$uid,"del_status"=>"1"))
            ->order('send_time DESC')
            ->page($p.',20')
            ->select();
        $data['count'] = $count;
        $data['list'] = $list;
        if($data){
            $this->exitJson(0,$data);
        }
    }

    //个人消息详情
    public function message_info(){
        $mobile = I("mobile");
        $id = I("id");
        $uid = getId($mobile);
        $map = array("status"=>1,"see_time"=>date("Y-m-d H:i:s",time()));
        $result = M("user_message")->where(array("uid"=>$uid,"id"=>$id))->find();
        if($result){
            M("user_message")->where(array("uid"=>$uid,"id"=>$id))->setField($map);
            $this->exitJson(0);
        }else{
            $this->exitJson(6108);//消息获取失败
        }
    }

    //删除个人消息
    public function del_message(){
        $mobile = I("post.mobile");
        $id = I("post.id");
        $uid = getId($mobile);
        $result = M('user_message')->where(array("uid"=>$uid,"id"=>$id))->delete();
        if($result){
            $this->exitJson(0);
        }else{
            $this->exitJson(6110);
        }
    }

    //删除个人消息
    public function del_user_message(){
        $mid = I("post.id");
        $result = M("user_message")->where(array("id"=>$mid))->setField("del_status","0");
        if($result){
            $this->exitJson(0);
        }else{
            $this->exitJson(6110);
        }
    }

    //上传个人头像
    public function avatar(){
        $avatar = I('post.avatar');
        if(empty($avatar)){
            $this->exitJson(3003);
        }
        $jpg = base64_decode($avatar);//得到post过来的二进制原始数据
        if(strlen($jpg)>(3*1024*1024)){
            $this->exitJson(3004);
        }
        //上传头像之前，先删除
        $file_img = $this->userinfo['avatar'];
        if(file_exists($_SERVER['DOCUMENT_ROOT'] .$file_img)){
            @unlink($_SERVER['DOCUMENT_ROOT'] .$file_img);
        }
        $time= date('Y-m', time());
        $filename =C('upload_avatar_dir').$time."/".random(18).".jpg";
        if (!is_dir($_SERVER['DOCUMENT_ROOT'].C('upload_avatar_dir').$time."/")){
            mkdir($_SERVER['DOCUMENT_ROOT'].C('upload_avatar_dir').$time."/");
        }
        file_put_contents($_SERVER['DOCUMENT_ROOT'] .$filename,$jpg);
        //保存头像路径到数据库
        $result = M('users')->where('id='."'$this->uid'")->setField('avatar',$filename);
        $data['avatar']=$filename;
        if($result){
            $this->exitJson(0,$data);
        }
        else{
            $this->exitJson(7003);
        }
    }

    //用户设置页面留言消息提醒
    public function user_message_tip(){
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
        $data["sys_mes_num"]="".$sys_mes_num;

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
        $data["money_mes_num"]="".$money_mes_num;

        $shop_mes_num =M("message_shop")
            ->where(array("uid"=>$this->uid,
                "parent_id"=>0,
                "isread"=>"1"
            ))->count();
        if(empty($shop_mes_num)){
            $shop_mes_num="0";
        }
        $data["shop_mes_num"]="".$shop_mes_num;
        $this->exitJson(0,$data);
    }

    //积分列表首页积分总数和5条
    public function point_list_index(){
        $data['today_score'] = (string)getTodayIncome($this->uid);
        $data['score'] = M("users")->where(array("id"=>$this->uid))->getField("score");
        $data['coin'] = M("users")->where(array("id"=>$this->uid))->getField("coin");
        if(empty($data['coin'])){
            $data['coin'] = "0";
        }
        $data['result'] = M("point_list")
            ->where(array("uid"=>$this->uid,"type"=>"1"))
            ->limit(5)
            ->order("time DESC")
            ->select();
        foreach($data['result'] as $k=>$v){
            $data['result'][$k]["time"] = date("Y-m-d H:i:s",$data['result'][$k]["time"]);
        }
        if(empty($data['result'])){
            $data['result'] = array();
        }
        $this->exitJson(0,$data);
    }

    //积分列表
    public function point_list(){
        $type = I("post.type");
        $p = I("post.p");
        if(empty($p)){
            $p = 1;
        }
        if(!empty($type)){
            $arr=array("uid"=>$this->uid,"type"=>$type);
        }else{
            $arr=array("uid"=>$this->uid);
        }
        $count = M("point_list")->where($arr)->count();
        $list = M("point_list")
            ->where($arr)
            ->order('time DESC')
            ->page($p.',20')
            ->select();
        foreach($list as $k=>$v){
            $list[$k]["time"] = date("Y-m-d H:i:s",$list[$k]["time"]);
        }
        $data['count'] = $count;
        $data['list'] = $list;
        if($data){
            $this->exitJson(0,$data);
        }
    }

    //推广列表
    public function lower_level_list(){
        $p = I("post.p");
        if(empty($p)){
            $p = 1;
        }
        $count = M("users")->where(array("parent_id"=>$this->uid))->count();
        $list = M("users")
            ->where(array("parent_id"=>$this->uid))
            ->order("create_time DESC")
//            ->page($p.',20')
            ->select();
        foreach($list as $k=>$v){
            $lists[$k]["name"] = $list[$k]["user_nicename"];
            $lists[$k]["mobile"] = $list[$k]["mobile"];
            $time = strtotime($list[$k]["create_time"]);
            $lists[$k]["time"] = date("Y-m-d",$time);
            $lists[$k]["level"] = level_string($list[$k]["grade"]);
            unset($time);
        }
        if(empty($lists)){
            $lists = array();
        }
        if(empty($count)){
            $count = "0";
        }
        $data['count'] = $count;
        $data['list'] = $lists;
        if($data){
            $this->exitJson(0,$data);
        }
    }

    //删除账号
    public function delete_user(){
        $pwd = I("post.password");
        $pass = sp_password($pwd);
        if(empty($pwd)){
            $this->exitJson(2006);
        }
        $u_pass = $this->userinfo["user_pass"];
        if($pass != $u_pass){
            $this->exitJson(2029);
        }
        $data["user_login"] = $this->userinfo["user_login"];
        $data["user_pass"] = $this->userinfo["user_pass"];
        $data["user_nicename"] = $this->userinfo["user_nicename"];
        $data["nickname"] = $this->userinfo["nickname"];
        $data["user_email"] = $this->userinfo["user_email"];
        $data["parent_id"] = $this->userinfo["parent_id"];
        $data["last_login_ip"] = $this->userinfo["last_login_ip"];
        $data["last_login_time"] = $this->userinfo["last_login_time"];
        $data["create_time"] = $this->userinfo["create_time"];
        $data["user_status"] = $this->userinfo["user_status"];
        $data["score"] = $this->userinfo["score"];
        $data["user_type"] = $this->userinfo["user_type"];
        $data["coin"] = $this->userinfo["coin"];
        $data["mobile"] = $this->userinfo["mobile"];
        $data["level"] = $this->userinfo["level"];
        $data["grade"] = $this->userinfo["grade"];
        $data["delete_time"] = date("Y-m-d H:i:s",time());
        $parent_id = $this->userinfo["parent_id"];
        $result_update = M("users")->where(array("parent_id"=>$this->uid))->setField("parent_id",$parent_id);
        $result_add = M("users_deleted")->add($data);
        $result_del = M("users")->where(array("id"=>$this->uid))->delete();
        $result_realname_del = M("realname")->where(array("uid"=>$this->uid))->delete();
        if($result_del){
            $this->exitJson(0,"账户删除成功");
        }else{
            $this->exitJson(2030);
        }
    }

    public function extract_point(){
        //$address=I("post.address");
        $point=I("post.point");
        $pwd=I("post.pwd");
        if(empty($point)){
            $this->exitJson(15001);
        }
        if(sp_password($pwd)!=$this->userinfo['user_pass']){
            $this->exitJson(15005);
        }
        if($point>$this->userinfo['score']){
            $this->exitJson(15002);
        }

        $ec = M("ec_address")->where(array("uid"=>$this->uid))->getField("ec_address");
        if($ec){
            $address=$ec;
        }else{
            $this->exitJson(15006);
        }

        $data["phone"]=$this->userinfo['mobile'];
        $data['ec']=$point;
        $data['address']=$address;
        $data['token']=$this->get_token($data);
        $url="http://www.wanbi360.cn/market/hyys_exchange";
        $re=request_post($url,$data);
        if($re=="2"){
            $this->exitJson(15003);
        }elseif($re=='4'){
            M("extract_point")->add(array(
                "uid"=>$this->uid,
                "time"=>time(),
                "point"=>$point,
                "address"=>$address
            ));
            addMoney($this->uid,$point,2,"提取积分","score");
            sendMessage($this->uid,"您已成功提取".$point."积分至玩币网");
            $this->exitJson(0,'提取积分成功');
        }
        else{
            $this->exitJson(15004);
        }
    }

    //获取EC地址
    public function get_ec_address(){
        $ec = M("ec_address")->where(array("uid"=>$this->uid))->getField("ec_address");
        if($ec){
            $data['ec'] = $ec;
        }else{
            $data['ec'] = "";
        }
        $data['name'] = $this->userinfo["user_nicename"];
        if($data){
            $this->exitJson(0,$data);
        }
    }

    //设置EC地址
    public function set_ec_address(){
        $ec = I("post.ec_address");
        if(empty($ec)){
            $this->exitJson(2200);
        }
        if(strlen($ec) != 34){
            $this->exitJson(2201);
        }
        $vd['address']=$ec;
        $vd['phone']=$this->userinfo['mobile'];
        $url="http://www.wanbi360.cn/market/validate_address";
        $re=request_post($url,$vd);
        if($re!="2"){
            $this->exitJson(2204);
        }
        $data["uid"] = $this->uid;
        $data["ec_address"] = $ec;
        $find = M("ec_address")->where(array("uid"=>$this->uid))->find();
        if(empty($find)){
            $result = M("ec_address")->add($data);
        }else{
            if($find["ec_address"] == $ec){
                $this->exitJson(2202);
            }else{
                $result = M("ec_address")->where(array("uid"=>$this->uid))->save($data);
            }
        }
        if($result){
            $this->exitJson(0);
        }else{
            $this->exitJson(2203);
        }
    }

    /**
     * 生成token
     * @param $data 数据组
     * @param null $len
     * @return string
     * @author v
     */
    protected function get_token($data, $len = null) {
        $length = 1;
        ksort($data);
        if (is_array($data)) {
            foreach ($data as $val) {
                $length += strlen($val);
                break;
            }
            $data = http_build_query($data);
        }
        is_null($len) && $len = ($length % 5) * 2;
        $data.="93fc71be25e92ec1845a8db5068ea174";
        return substr(md5($data), $length, $length + $len);
    }

    //分销记录
    public function distribution_list(){
//        $users=M("users")->where(array("parent_id"=>$this->uid))->field("id")->select();
//        foreach ($users as $v){
//            M()
//        }
        $p = I("post.p");
        if(empty($p)){
            $p = 1;
        }
        $re=array();
        $count= M("order")
            ->where("uid in (select id from huayu_users where parent_id=".$this->uid.") && (order_status=2 || order_status=3)")
            ->count();
        $or= M("order")
            ->where("uid in (select id from huayu_users where parent_id=".$this->uid.") && (order_status=2 || order_status=3)")
            ->order("add_time desc")->page($p.",20")->select();
        foreach ($or as $k=>$v){
            $re[$k]['time']=date("Y-m-d",strtotime($v["add_time"]));
            $re[$k]['money']=$v["goods_price"];
            $re[$k]['sn']=$this->sn_short($v["order_sn"]);
        }
        $data['count']=$count;
        $data['result']=$re;
        $this->exitJson(0,$data);
    }

    /**
     * 截图订单号sn
     * @param $sn 订单号
     * @return string
     * @author v
     */
    private function sn_short($sn){
        return substr($sn,0,6)."***".substr($sn,-3);
    }

//我的推广首页

    public function tuiguang(){
        $id=$this->uid;
        $data['user_nicename'] = getUserNameById($id);
        $level = M('users')->where(array("id"=>$id))->getField('level');
        $data['level'] = level_string($level);
        $data['avatar'] = M("users")->where(array("id"=>$id))->getField("avatar");
        $p = I("p");
        if(empty($p)){
            $p = 1;
        }
        $re = M('users')
            ->where(array("parent_id"=>$id))
            ->order('create_time DESC')
            ->select();

        $num2=0;
        if(empty($re)){
            $data['member'] = array();
        }else{
            foreach ($re as $k=>$v){
                $data['member'][$k]["id"] = (string)$v["id"];
                $data['member'][$k]["user_nicename"] =(string) getUserNameById($v["id"]);
                $data['member'][$k]["mobile"] = (string)yc_phone($v["mobile"]);
                $member[$k]["level"] = (string)$v["level"];
                $data['member'][$k]["level"] = (string)level_string($member[$k]["level"]);

                $data['member'][$k]["money"] =(string) getUserMaxPi($v['id']);
                if(empty($data['member'][$k]["money"])){
                    $data['member'][$k]["money"] = "0";
                }

                $re2 = M('users')->where(array("parent_id"=>$v["id"]))->select();
                $data[$k]["num2"]= count($re2);
                $num2+= $data[$k]["num2"];
//            echo "<pre>";
//            print_r($re2);die;
                if(empty($re2)){
                    $data['member'][$k]['up']=array();
                }else{
                    foreach ($re2 as $key=>$val){
                        $data['member'][$k]['up'][$key]["id"]=(string)$val["id"];
                        $data['member'][$k]['up'][$key]["user_nicename"]=(string)getUserNameById($val["id"]);
                        $data['member'][$k]['up'][$key]["mobile"] = (string)yc_phone($val["mobile"]);
                        $member[$k]['up'][$key]["level"] = (string)$val["level"];
                        $data['member'][$k]['up'][$key]["level"] = level_string($member[$k]['up'][$key]["level"]);

                        $data['member'][$k]['up'][$key]["money"] =(string) getUserMaxPi($val['id']);
                        if(empty($data['member'][$k]["money"])){
                            $data['member'][$k]['up'][$key]["money"] = "0";
                        }

                    }
                }
            }

        }
        $data["num1"] = M('users')->where(array("parent_id"=>$id))->count();
        $data["num2"]="$num2";

        $this->exitJson(0,$data);
    }

    //添加用户获取验证码
    public function add_usersms(){
        $mobile = I("post.mobile");
        if(empty($mobile)){
            $this->exitJson();
        }
        if($mobile!==$this->userinfo['mobile']){
            $this->exitJson(2001);
        }
        $un = $this->userinfo['user_nicename'];
        $db["name"]=$un;
        $db["mobile"]=$this->userinfo['mobile'];
        $db["type"]="reg_code";
        if(sendSMS($db)){
            $this->exitJson(0);
        }
    }

    //添加成员
    //author hhj
    public function add_user(){
        $mobile = I("post.mobile");
        $xm =  I("post.xm");
        $mobile_add = I("post.mobile_add");
        $pwd_add = I("post.pwd_add");
        //实名认证
        if(!M("realname")->where(array("uid"=>$this->uid,"status"=>1))->find()){
            $this->exitJson(6101);
        }
        if($mobile!==$this->userinfo['mobile']){
            $this->exitJson(2001);
        }
        if(empty($mobile) || empty($mobile_add)){
            $this->exitJson(2003);
        }

        if(!preg_match("/^1[34578]{1}\d{9}$/",$mobile_add)){
            $this->exitJson(2001);
        }

        $user = M('users')->where(array('mobile'=>$mobile_add))->find();
        if(!empty($user)){
            $this->exitJson(2010);
        }
        if($this->userinfo['key']<1){
            $this->exitJson(2037);
        }
        $account['mobile'] = $mobile_add;
        $account['user_pass'] = sp_password($pwd_add);
        $account['parent_id'] = $this->userinfo['id'];
        $account['create_time'] = date('Y-m-d H:i:s', time());
        $account['user_type'] = 2;
        $account['user_nicename'] = $xm;
        $result = M('users')->add($account);
        if($result){
            $key = $this->userinfo['key']-1;
            $rs[] = M('users')->where(array('id'=>$this->userinfo['id']))->save(array('key'=>$key));
            //$rs[]=M('users')->where(array('id'=>$this->userinfo['id']))->setDec('key',1);
            $data['uid'] = $this->userinfo['id'];
            $data['activate_id'] = $result;
            $data['add_time'] = time();
            $data['num'] = 1;
            $data['type'] = 1;
            $rs[] = M('activate_code')->add($data);
            if(chkArr($rs)){
                $this->exitJson(0);
            }else{
                $this->exitJson(2033);
            }
        }

    }

    //个人中心首页

    public function personalcenter(){
        $id = $this->uid;
        $data['user_nicename'] = M("users")->where(array("id"=>$id))->getField("user_nicename");
        $level = M("users")->where(array("id"=>$id))->getField("level");
        $data['level'] = level_string($level);


        $parent_id = M("users")->where(array("id"=>$id))->getField("parent_id");
        $data['parent_mobile'] = M("users")->where(array("id"=>$parent_id))->getField("mobile");
        $data['avatar'] = M("users")->where(array("id"=>$id))->getField("avatar");
        // $data['news_article']=M('posts')->where(array("post_status"=>1))->order('post_date desc')->field('post_title')->limit(3)->select();
        $data['news_article'] =D('Article')->getArticleList(2,3);
        if(!$data['news_article']){
            $data['news_article']=array();
        }

//        $data['news_article_id']=M('posts')->order('post_date desc')->getField('id');
        $data['spwd'] = M("users")->where(array("id"=>$id))->getField("spwd");
        if(empty($data['spwd'])){
            $data['spwd'] = "0";
        }else{
            $data['spwd'] = "1";
        }
        $data['wallet_add'] = M("wallet_address")->where(array("uid"=>$id))->getField("address");
        if(empty($data['wallet_add'])){
            $data['wallet_add'] = "0";
        }else{
            $data['wallet_add'] = $data['wallet_add'];
        }
        $data['realname'] = M("realname")->where(array("uid"=>$id))->getField("status");
        if(empty($data['realname'])){
            $data['realname'] = "3";
        }else{
            $data['realname'] = $data['realname'];
        }

        $new_message = M('user_message')->where(array("uid"=>$this->userinfo['id'],"status"=>0))->select();
        if(!empty($new_message)){
            $data['my_news'] = "0";
        }else{
            $data['my_news'] = "1";
        }
//        $data['my_news'] = "0";//0：有消息；1：没有消息
//        $data['my_order'] = "0";
        $new_order = M('order')->where('uid='.$id.' AND (order_status=0 OR order_status=1)')->select();
//        $new_order = M('order')->where(array("uid"=>$this->userinfo['id'],"order_status"=>0))->select();
        if(!empty($new_order)){
            $data['my_order'] = "0";
        }else{
            $data['my_order'] = "1";
        }
        $data['my_service'] = "1";
        $this->exitJson(0,$data);

    }


    //发放激活码
    public function activation_code()
    {
        //  $mobile1 = I("post.membermobile");
        $member_id = I("post.member_id");
        $num = I("post.num");
        // $pwd = I("post.spwd");
        $user = M('users')->where(array('id' => $member_id))->find();
        //实名认证
        if(!M("realname")->where(array("uid"=>$this->uid,"status"=>1))->find()){
            $this->exitJson(6101);
        }
//        if ($user['parent_id'] != $this->userinfo['id']) {
//            $this->exitJson(2035);
//        }
        if ($num <= 0) {
            $this->exitJson(2012);
        }

        if ($num > $this->userinfo['key']) {
            $this->exitJson(2032);
        }
//        $pwd = sp_password($pwd);
//        $result = M('users')->where(array('mobile' => $this->userinfo['mobile'], 'spwd' => $pwd))->find();
//        if (!$result) {
//            $this->exitJson(104);
//        }
        $key1 = $user['key']+$num;
        $key2 = $this->userinfo['key']-$num;
        $rs[] = M('users')->where(array('mobile' => $this->userinfo['mobile']))->save(array('key'=>$key2));
        $rs[] = M('users')->where(array('id' => $member_id))->save(array('key'=>$key1));
        $data['uid'] = $this->userinfo['id'];
        $data['activate_id'] = $user['id'];
        $data['num'] = $num;
        $data['add_time'] = time();
        $rs[] = M('activate_code')->add($data);
        if (chkArr($rs)){
            $this->exitJson(0);
        }
    }


    //发放排单币
    public function activation_play(){
        // $mobile1 = I("post.membermobile");
        $member_id = I("post.member_id");

        $num = I("post.num");
        //  $pwd = I("post.spwd");
        $user = M('users')->where(array('id'=>$member_id))->find();
        //实名认证
        if(!M("realname")->where(array("uid"=>$this->uid,"status"=>1))->find()){
            $this->exitJson(6101);
        }
//        if($user['parent_id']!=$this->userinfo['id']){
//            $this->exitJson(2035);
//        }
        if($num<=0){
            $this->exitJson(2036);
        }
        if($num>$this->userinfo['pi_key']){
            $this->exitJson(2037);
        }
//        $pwd = sp_password($pwd);
//        $result = M('users')->where(array('mobile' => $this->userinfo['mobile'], 'spwd' => $pwd))->find();
//        if (!$result) {
//            $this->exitJson(104);
//        }
        $key1 = $user['pi_key']+$num;
        $key2 = $this->userinfo['pi_key']-$num;
        $rs[] = M('users')->where(array('mobile' => $this->userinfo['mobile']))->save(array('pi_key'=>$key2));
        $rs[] = M('users')->where(array('id' => $member_id))->save(array('pi_key'=>$key1));
        $data['uid'] = $this->userinfo['id'];
        $data['activate_id'] = $user['id'];
        $data['num'] = $num;
        $data['add_time'] = time();
        $rs[] = M('activate_play')->add($data);
        if (chkArr($rs)) {
            $this->exitJson(0);
        }
    }

    //发放电子币
    public function activation_pi(){
        //$mobile1 = I("membermobile");
        $member_id = I("post.member_id");
        $num = I("num");
        //   $pwd = I("post.spwd");
        $user = M('users')->where(array('id'=>$member_id))->find();
        //实名认证
        if(!M("realname")->where(array("uid"=>$this->uid,"status"=>1))->find()){
            $this->exitJson(6101);
        }
//        if($user['parent_id']!=$this->userinfo['id']){
//            $this->exitJson(2035);
//        }

        if(!M("realname")->where(array("uid"=>$this->uid,"status"=>1))->find()){
            $this->exitJson(6101);
        }


        if($num<=0){
            $this->exitJson(2036);
        }
        if($num>$this->userinfo['score']){
            $this->exitJson(2037);
        }
//        $pwd = sp_password($pwd);
//        $result = M('users')->where(array('mobile' => $this->userinfo['mobile'], 'spwd' => $pwd))->find();
//        if (!$result) {
//            $this->exitJson(104);
//        }
        $rs[]=addMoney($this->uid,$num,2,"下发金币",1);
        $rs[]=addMoney($user['id'],$num,1,"获得上级发放",1);

        $data['uid'] = $this->userinfo['id'];
        $data['activate_id'] = $user['id'];
        $data['num'] = $num;
        $data['add_time'] = time();
        $rs[] = M('activate_pi')->add($data);
        if(chkArr($rs)){
            $this->exitJson(0,"发放成功");
        }
    }

    //激活记录
//    public function activation_log(){
//        $p = I("p");
//        if(empty($p)){
//            $p = 1;
//        }
//        $count = M("activate_code")->where(array("uid"=>$this->userinfo['id']))->count();
//        $list = M("activate_log")
//            ->where(array("uid"=>$this->userinfo['id']))
//            ->order('add_time DESC')
//            ->page($p.',20')
//            ->select();
//        foreach ($list as $k=>$v){
//            $list[$k]['xm'] = getUserNameById($v['activate_id']);
//            $list[$k]['add_time'] = date('Y-m-d', $v['add_time']);;
//        }
//        $data['count'] = $count;
//        $data['list'] = $list;
//        if($data){
//            $this->exitJson(0,$data);
//        }
//
//    }

    //激活码发放记录
    public function code_log(){
        $p = I("p");
        if(empty($p)){
            $p = 1;
        }

        $w2["activate_id"]=$this->uid;
        $w2["type"]=0;
        $w1['uid']=$this->uid;
        $w[]=$w1;
        $w[]=$w2;
        $w['_logic'] = 'or';

        $count = M("activate_code")->where($w)->count();
        $list = M("activate_code")
            ->where($w)
            ->order('add_time DESC')
            ->page($p.',20')
            ->select();
        foreach ($list as $k=>$v){
            if($v["uid"]==$this->uid){
                $list[$k]['xm'] = getUserNameById($v['activate_id']);
                if($v['type']=="1"){
                    $list[$k]['xm'] ="激活". $list[$k]['xm'] ;
                }
                $list[$k]['do'] = "2" ;
            }else{
                if($v['uid']=='0'){
                    if($v['do']=='1'){
                        $list[$k]['xm'] = "系统发放";
                        $list[$k]['do'] = "1" ;
                    }else{
                        $list[$k]['xm'] = "系统扣除";
                        $list[$k]['do'] = "2" ;
                    }

                }else{
                    $list[$k]['xm'] = getUserNameById($v['uid']);
                    $list[$k]['do'] = "1" ;
                }

            }
            $list[$k]['add_time'] = date('Y-m-d', $v['add_time']);
        }
        $data['count'] = $count;
        $data['list'] = $list;
        if($data){
            $this->exitJson(0,$data);
        }
    }


    //电子币发放记录
    public function pi_log(){
        $p = I("p");
        if(empty($p)){
            $p = 1;
        }
        $w2["activate_id"]=$this->uid;
        $w1['uid']=$this->uid;
        $w[]=$w1;
        $w[]=$w2;
        $w['_logic'] = 'or';
//        $w['uid']=$this->uid;
        $count = M("activate_pi")->where($w)->count();
        $list = M("activate_pi")
            ->where($w)
            ->order('add_time DESC')
            ->page($p.',20')
            ->select();
        foreach ($list as $k=>$v){
            if($v["uid"]==$this->uid){
                $list[$k]['do']=$v['do'];
                $list[$k]['xm'] = getUserNameById($v['activate_id']);
            }else{
                 $list[$k]['do']= $v['do'];
                if($v['uid']=="0"){
                    $list[$k]['xm']='系统操作';
                }else{
                    $list[$k]['xm'] = getUserNameById($v['uid']);
                }

            }

            $list[$k]['add_time'] = date('Y-m-d', $v['add_time']);;
        }
        $data['count'] = $count;
        $data['list'] = $list;
        if($data){
            $this->exitJson(0,$data);
        }
    }

    //排单币记录
    public function play_log(){
        $p = I("p");
        if(empty($p)){
            $p = 1;
        }

        $w2["activate_id"]=$this->uid;
        $w2["type"]=0;
        $w1['uid']=$this->uid;
        $w[]=$w1;
        $w[]=$w2;
        $w['_logic'] = 'or';

        $count = M("activate_play")->where($w)->count();
        $list = M("activate_play")
//            ->where(array("uid"=>$this->userinfo['id']))
            ->where($w)
            ->order('add_time DESC')
            ->page($p.',20')
            ->select();
        foreach ($list as $k=>$v){
            if($v["uid"]==$this->uid){
                $list[$k]['do']="2";
                if($v['type']=="1"){
                    $list[$k]['xm'] = "排单使用" ;
                }else{
                    $list[$k]['xm'] = getUserNameById($v['activate_id']);
                }
            }else{
                $list[$k]['do']="1";
                if($v['uid']=='0'){
                    $list[$k]['xm'] = "系统发放";
                }else{
                    $list[$k]['xm'] = getUserNameById($v['uid']);
                }

            }

            $list[$k]['add_time'] = date('Y-m-d ', $v['add_time']);;
        }
        $data['count'] = $count;
        $data['list'] = $list;
        if($data){
            $this->exitJson(0,$data);
        }
    }

    //个人消息
    public function sys_message(){
        $p = I("p");
        if(empty($p)){
            $p = 1;
        }
//        $new_message = M('user_message')->where(array("uid"=>$this->userinfo['id'],"status"=>0))->select();
//        if(!empty($new_message)){
//            $data['new_message'] = "0";
//        }else{
//            $data['new_message'] = "1";
//        }
        $count = M("user_message")->where(array("uid"=>$this->userinfo['id'],"del_status"=>1))->count();
        $list = M("user_message")
            ->where(array("uid"=>$this->userinfo['id'],"del_status"=>1))
            ->order('send_time DESC')
            ->page($p.',20')
            ->select();
        $data['count'] = $count;
        $data['list'] = $list;
        if($data){
            $this->exitJson(0,$data);
        }
    }

    //获取说明
    public function get_state(){
        // $a=M("e")->where(array("id"=>1))->find();
        $a=D("Article")->getContent(6);
        if($a){
            $data['state']=$a['post_content'];
            $this->exitJson(0,$data);
        }else{
            $this->exitJson(103);
        }
    }





}