<?php
namespace Mobile\Controller;
use Common\Controller\AppController;
class MarketController extends AppController {

    public function _initialize() {
        parent::_initialize();
        if($this->token=="0"){
            $this->exitJson(2100);
        }
        if ($this->userinfo['token'] != $this->token) {
            $this->exitJson(2101);
        }
        if((strtotime($this->userinfo["last_login_time"])+24*15*3600)<time()){
            $this->exitJson(2101);
        }
    }

    /**
     * 我的余额（电子货币）
     * @author v
     */

    public function my_score(){
        $data['score']=$this->userinfo['score'];

//        $td=strtotime(date("Y-m-d 0:0:0"));
        $data['bjqb_point']=(string)$this->userinfo['play_score'];
//        $data['bjqb_today']=M("point_list")->where(array("uid"=>$this->uid,"type"=>3,'time'=>array("EGT",$td),"do"=>1))->sum("point");
//        $data['bjqb_today']=empty($data['bjqb_today'])?"0": (string)$data['bjqb_today'];


        $data['goods_list']=array();
//        $gl=M("goods")->where(array("coin"=>1,"is_sale"=>1))->order('sale_price desc')->select();
//        foreach ($gl as $k=>$v){
//            $data['goods_list'][$k]["name"]=$v['name'];
//            $data['goods_list'][$k]["sale_price"]=$v['sale_price'];
//            $data['goods_list'][$k]["id"]=$v['id'];
//            $data['goods_list'][$k]["img"]=$v['img'];
//            $data['goods_list'][$k]["introduction"]=$v['introduction'];
//
//        }
        //更新
        $data['goods_list']=M("goods")->where(array("coin"=>1,"is_sale"=>1))->order('sale_price desc')->field("id,name,sale_price,title,type,num,img")->select();
//        foreach ($gl as $k=>$v){
//            $data['goods_list'][$k]["name"]=$v['name'];
//            $data['goods_list'][$k]["sale_price"]=$v['sale_price'];
//            $data['goods_list'][$k]["id"]=$v['id'];
//            $data['goods_list'][$k]["img"]=$v['img'];
//            $data['goods_list'][$k]["introduction"]=$v['introduction'];
//
//        }

        foreach($data['goods_list'] as $k=>$v){
            $sale_volume_arr[$k] = $data['goods_list'][$k]['sale_volume'];
            $sale_price_arr[$k]=$v['sale_price'];
//            $data['goods_list'][$k]['stock'] = $v['num']- $data['goods_list'][$k]['sale_volume'];//库存
            $data['goods_list'][$k]['sale_volume'] = "";  //销量
            $data['goods_list'][$k]['stock'] = "";
//            if($data['goods_list'][$k]['stock']<0){
//                $data['goods_list'][$k]['stock'] = "";
//            }else{
////                $data['goods_list'][$k]['stock']=(string)$data['goods_list'][$k]['stock'];
//                $data['goods_list'][$k]['stock']="";
//            }
        }

        $this->exitJson(0,$data);
    }

//    /**
//     * 电子币购买商品
//     * @author v
//     */
//    public function score_buy(){
//        $goods_string=I("goods_string");
//        $goods_array=explode(";",$goods_string);
//        $all_point=0;
//        //实名认证
//        if(!M("realname")->where(array("uid"=>$this->uid,"status"=>1))->find()){
//            $this->exitJson(6101);
//        }
//
//        $default_address=D('Shop')->get_address($this->uid);
//        if(count($default_address)<=0){
//            $this->exitJson(7004);
//        }
//
//
//        $goods_price_add=array();
//        foreach ($goods_array as $k=>$g){
//            if(empty($g)){
//                continue;
//            }
//            $ge=explode(':',$g);
//            $goods_info=M("goods")->where(array("id"=>$ge[0],"coin"=>1,"is_sale"=>1))->find();
//            if(!$goods_info || $ge[1]<=0){
//                $this->exitJson(15004);
//            }
//            $ga[$k]["goods_info"]=$goods_info;
//            $ga[$k]["num"]=$ge[1];
//            $goods_price_add[$goods_info['id']]=$goods_info['sale_price']*$ge[1];
//            $all_point+=$goods_info['sale_price']*$ge[1];
//        }
//        if($all_point<=0){
//            $this->exitJson(7034);
//        }
//        if($all_point>30000 || $all_point<1000){
//            $this->exitJson(20001);
//        }
//        $aa=$all_point+getUserMaxPi($this->uid);
//        if($aa>30000 || $aa<1000){
//            $this->exitJson(20002);
//        }
//
//        if($this->userinfo['score'] < $all_point){
//            $this->exitJson(15002);
//        }
//        if(addMoney($this->uid,$all_point,2,"购买商品",1)){
//            $add_time=time();
//            foreach ($ga as $v){
//
//                M("order")->add(array(
//                        "uid"=>$this->uid,
//                        "goods_id"=> $v['goods_info']['id'],
//                        "order_sn"=> D('Shop')->getOrderSn().sprintf("%07d", $this->uid).rand(10,99),
//                        "goods_name"=> $v['goods_info']['name'],
//                        "goods_price"=> $goods_price_add[$v['goods_info']['id']],
//                        "num"=> $v['num'],
//                        "add_time"=>date('Y-m-d H:i:s',$add_time),
//                        "coin"=>1,
//                        "order_status"=>1,
//                        "pay_type"=>'score',
//                        "name"=>$default_address['name'],
//                        "province"=>$default_address['province'],
//                        "city"=>$default_address['city'],
//                        "area"=>$default_address['area'],
//                        "detail"=>$default_address['detail'],
//                        "order_mobile"=>$default_address['order_mobile']
//                    )
//                );
//            }
//            //增加赠送钱包
//            addMoney($this->uid,$all_point,1,"购买商品",3);
//            $this->exitJson(0,"购买完成");
//        }else{
//            $this->exitJson(15002);
//        }
//    }

    //排单
    public function put_pi(){
//        $spwd=I("post.spwd");
//        if(empty($this->userinfo['spwd'])){
//            $this->exitJson(105);
//        }
//        if(sp_password($spwd)!=$this->userinfo['spwd']){
//            $this->exitJson(104);
//        }
        if(M("pi")->where(array("uid"=>$this->uid,"status"=>0))->find()){
            $this->exitJson(17001);
        }
        if($this->userinfo['play_score']<100){
            $this->exitJson(17002);
        }

        if($this->userinfo['pi_key']<($this->userinfo['play_score']/100)){
            $this->exitJson(17006);
        }

        $add['uid']=$this->uid;
        $add['point']=$this->userinfo['play_score'];
        if($add['point']>30000){
            $add['point']=30000;
        }


        $add['time']=time();
        //扣除赠送钱包
        addMoney($this->uid,$add['point'],2,"排单扣除",3);
        //扣除排单币
        $npk=$this->userinfo['pi_key']-$add['point']/100;
        M("users")->where(array("id"=>$this->uid))->save(array("pi_key"=>$npk));
        M('activate_play')->add(array(
            'uid'=>$this->uid,
            'num'=>($add['point']/100),
            'activate_id'=>$this->uid,
            'add_time'=>time(),
            'type'=>1
        ));
        //增加排单额
        if(M("pi")->add($add)){
            if(($add['point']>=30000)){
                if(M("recommend")->where(array("uid"=>$this->uid))->find()){
                }else{
                    addMoney($this->uid,200,1,"满额奖",2);
                    addMoney($this->userinfo['parent_id'],200,1,"推荐奖",2);
                    //满额奖已拿记录
                    M("recommend")->add(array(
                        "uid"=>$this->uid,
                        "get_uid"=>$this->userinfo['parent_id'],
                        "time"=>time()
                    ));
                }

            }
            D("Users")->tg($add['uid'],$add['point']);
            $this->exitJson(0,"排单成功");
        }else{
            $this->exitJson(16005);
        }
    }

    //我的排单列表
    public function my_pi(){
        $data['list']=array();
        $pi_list=M("pi")->where(array("uid"=>$this->uid))->order('time desc')->select();
        foreach ($pi_list as $k=>$v){
            $data['list'][$k]['id']=$v['id'];
            $data['list'][$k]['date']=date('Y/m/d H:i',$v['time']);
            $data['list'][$k]['out_date']=date('Y/m/d H:i',($v['time']+config_get_vv("block_days")*86400));
            $data['list'][$k]['point']=$v['point'];
            if($v['status']==1){
                $data['list'][$k]['out_button']="2";
                if($v['out_point']==0){
                    $v['out_point']=$v['point']*0.1;
                }
            }else{

                //排单轮数

                $cc=get_pi_num($this->uid);

                if($cc<=5) {
                    $v['out_point']=$v['point']*(int)config_get_vv('pi_rate')/100;
                }else{
                    $v['out_point']=$v['point']*((int)config_get_vv('pi_rate')+2)/100;
                }

                if($v['point']<=1000){
                    $v['out_point']=$v['point']*0.15;
                }

                if(($v['time']+config_get_vv("block_days")*86400)<=time()){
                    $data['list'][$k]['out_button']="1";
                }else{
                    $data['list'][$k]['out_button']="0";
                }
            }

            $data['list'][$k]['out_point']=(string)$v['out_point'];
            if($k==0){
                $data['list'][$k]['title']="复供排单";
            }else{
                $data['list'][$k]['title']="首次排单";
            }
            //出场金额


        }
        $this->exitJson(0,$data);
    }

    //出场操作
    public function out_pi(){
        //二级密码验证
//        $spwd=I("post.spwd");
//        if(empty($this->userinfo['spwd'])){
//            $this->exitJson(105);
//        }
//        if(sp_password($spwd)!=$this->userinfo['spwd']){
//            $this->exitJson(104);
//        }

        $pi_id=I("post.id");
        if(empty($pi_id)){
            $this->exitJson(16005);
        }

        $do=D("Users")->out_pi($pi_id,$this->uid);
        if($do=="0"){
            $this->exitJson(0,'出场完成');
        }elseif($do=="2"){
            $this->exitJson(17003);
        }else{
            $this->exitJson(16005);
        }

    }

    //我的钱包
    public function my_wallet(){
        $td=strtotime(date("Y-m-d 0:0:0"));
        $data['syqb_point']=(string)$this->userinfo['temp_score'];
        $data['syqb_today']=M("point_list")->where(array("uid"=>$this->uid,"type"=>4,'time'=>array("EGT",$td),"do"=>1))->sum("point");
        $data['syqb_today']=empty($data['syqb_today'])?"0": (string)$data['syqb_today'];

        $data['tgqb_point']=(string)$this->userinfo['coin'];
//        $next_users=M("users")->where(array("parent_id"=>$this->uid,"block"=>0))->select();
//        $na=0;
//        foreach ($next_users as $v){
//            $na+= M("pi")->where(array("uid"=>$v['id']))->sum('point');
//        }
        /* ①团队直推销售业绩过2w拿一级 提成5%
             ②团队直推销售业绩过10w拿2级 提成5%3%
           推广钱包20%进入消费钱包*/
        if($this->userinfo['level']>=1){
            $data['tgqb_one']=config_get_vv('one_rate')."%";
        }else{
            $data['tgqb_one']="0%";
        }
        if($this->userinfo['level']>=2){
            $data['tgqb_two']=config_get_vv('two_rate')."%";
        }else{
            $data['tgqb_two']="0%";
        }
        if($this->userinfo['level']>=5){
            $data['tgqb_team']="0.3%";
        }elseif($this->userinfo['level']>=4){
            $data['tgqb_team']="0.2%";
        }elseif($this->userinfo['level']>=3){
            $data['tgqb_team']="0.1%";
        }else{
            $data['tgqb_team']="0%";
        }

        $data['xfqb_point']=(string)$this->userinfo['cc'];
        $data['xfqb_today']=M("point_list")->where(array("uid"=>$this->uid,"type"=>5,'time'=>array("EGT",$td),"do"=>1))->sum("point");
        $data['xfqb_today']=empty($data['xfqb_today'])?"0": (string)$data['xfqb_today'];


        $data['bjqb_point']=(string)$this->userinfo['play_score'];
        $data['bjqb_today']=M("point_list")->where(array("uid"=>$this->uid,"type"=>3,'time'=>array("EGT",$td),"do"=>1))->sum("point");
        $data['bjqb_today']=empty($data['bjqb_today'])?"0": (string)$data['bjqb_today'];

        //等级刷新
        count_level($this->uid);
        $this->exitJson(0,$data);
    }

    public function index(){
        die();
    }

    //经销商首页
    public function market(){
//        $data['mx']="芬达币交易价格：".sprintf("%.2f", config_get_vv('mx'))."   芬达币交易价格:123   神马币交易价格：321";
//        $data['mx']="芬达币交易价格：".sprintf("%.2f", config_get_vv('mx'));

        $list = M("exchange")->where(array("delete"=>"0"))->select();
        $content = "";
        foreach ($list as $k => $v){
            $content .= $v['name']."价格：".$v['price']."   ";
        }
        if(empty($content)){
            $content = "暂无交易价格";
        }
        $data['mx'] = $content;


        $data['score']=(string)$this->userinfo['score'];
        $data['wallet_point']=(string)($this->userinfo['temp_score']
            +$this->userinfo['coin']
            +$this->userinfo['cc']
            +$this->userinfo['play_score']);
        $data['recommend_num']=(string)team_num($this->uid);
        $data['pi_key']=(string)$this->userinfo['pi_key'];
        $data['key']=(string)$this->userinfo['key'];
        $pp=M("pi")->where(array("uid"=>$this->uid,"status"=>0))->sum('point');
        $data['pi_point']=(string)($this->userinfo['play_score']+$pp);
        $data['block_days']="暂无";
        $this->exitJson(0,$data);

    }

    //金额添加小数标识
    public function point_more(){
        $money=I("post.money");
        if(!preg_match('!^\d{2,10}$!i',$money)){
            $this->exitJson(16007);
        }
        $data['money']= ''.($money+rand(0,5)/10+rand(1,9)/100);
        $this->exitJson(0,$data);
    }

    //线下充值
    public function score_in_offline(){
        $bank_number=I("post.bank_number");
        $bank_name=trim(I("post.bank_name"));
        $name=I("post.name");
        $money=I("post.money");

        if(!M("realname")->where(array("uid"=>$this->uid,"status"=>1))->find()){
            $this->exitJson(6101);
        }


        if(M("offline_pay")->where(array("uid"=>$this->uid,"status"=>0,"pay_type"=>array('neq',"wx")))->find()){
            $this->exitJson(16008);
        }

        if(empty($bank_number) || empty($name)  ||  empty($money)){
            $this->exitJson(16001);
        }
        if(!preg_match('!^\d{16,19}$!i',$bank_number)){
            $this->exitJson(16003);
        }
        if(!preg_match('/^[\x{4e00}-\x{9fa5}]+$/u',$name)){
            $this->exitJson(16004);
        }
        if($money<=10){
            $this->exitJson(16007);
        }
//        if(!preg_match('!^\d{2,10}$!i',$money)){
//            $this->exitJson(16007);
//        }

        $add["bank_number"]=$bank_number;
        $add["bank_name"]=$bank_name;
        $add["name"]=$name;
        $add["money"]=$money;
        $add["add_time"]=time();
        $add["uid"]=$this->uid;
        $a=M("offline_pay")->add($add);
        if($a){
            $this->exitJson(0,"成功提交,请等待后台审核");
        }else{
            $this->exitJson(16005);
        }
    }

     //在线充值
    public function score_in_online(){
        $money=I("post.money");
        $pay_type=I("post.pay_type");

        if(empty($pay_type)){
            $this->exitJson(16001);
        }

        if($money>10000){
            $this->exitJson(16009);
        }
        if(!M("realname")->where(array("uid"=>$this->uid,"status"=>1))->find()){
            $this->exitJson(6101);
        }


//        if(M("offline_pay")->where(array("uid"=>$this->uid,"status"=>0))->find()){
//            $this->exitJson(16008);
//        }

        if(empty($money)){
            $this->exitJson(16001);
        }
//        if($money<=10){
//            $this->exitJson(16007);
//        }
//        if(!preg_match('!^\d{2,10}$!i',$money)){
//            $this->exitJson(16007);
//        }

        $add["pay_type"]='wx';
        $add["money"]=$money;
        $add["add_time"]=time();
        $add["uid"]=$this->uid;

        $a=M("offline_pay")->add($add);
        if($a){
            if($pay_type=='999'){
                $re['return_url']=U('Pay/thepay',array('id'=>$a));
            }else{
                $re['return_url']=U('Pay/thepay',array('id'=>$a));
            }
            $this->exitJson(0,$re);
        }else{
            $this->exitJson(16005);
        }
    }





    //线下充值公司账户信息
    public function score_in_offline_info(){
        $data['name']=(string)config_get_vv("offline_user_name");
        $data['bank_number']=(string)config_get_vv("offline_bank_code");
        $data['bank_name']=(string)config_get_vv("offline_bank_name");
        $data['name2']=(string)config_get_vv("offline_user_name2");
        $data['bank_number2']=(string)config_get_vv("offline_bank_code2");
        $data['bank_name2']=(string)config_get_vv("offline_bank_name2");
        $this->exitJson(0,$data);
    }

    //提出积分
    public function out_point(){
        // $this->exitJson(20006);
        $type=I("post.type");
        $spwd=I("post.spwd");
        $num=I("post.num");
        $wallet_type = I("post.wallet_type");


        //实名认证
        if(!M("realname")->where(array("uid"=>$this->uid,"status"=>1))->find()){
            $this->exitJson(6101);
        }
        $wallet_status = M("exchange")->where(array("id"=>$wallet_type))->getField("delete");
        if($wallet_status == 1){
            $this->exitJson(15010);
        }

//        if($num<=0){
//            $this->exitJson(17004);
//        }
        if(empty($this->userinfo['spwd'])){
            $this->exitJson(105);
        }
        if(sp_password($spwd)!=$this->userinfo['spwd']){
            $this->exitJson(104);
        }

        switch ($type){
            case "tg":$t='coin';$c=2;break; //推广
            case "bj":$t='play_score';$c=3;break; //赠送钱包
            case "sy":$t='temp_score';$c=4;break; //收益钱包
            default:$this->exitJson(103);
        }
        $coin=$this->userinfo[$t];
        if($c==3){
            $num=$coin;
            $bp=M("block_point")->where(array("uid"=>$this->uid))->getField("point");
            if($bp<0){
                $bp=0;
            }
            $num-=$bp;
            if($num<=0 && $bp){
                $this->exitJson(123456,'扣除您的冻结积分'.$bp.',您没有可以提现的漫香积分');
            }
            if($num<=0){
                $this->exitJson(17005);
            }
        }elseif($c==2 && $num<500){
            $this->exitJson(20003);
        }elseif($c==4 && $num<100){
            $this->exitJson(20004);
        }

        if($num<=0){
            $this->exitJson(17004);
        }

        if( ( $num % 100) !=0){
            $this->exitJson(20005);
        }

        if($coin<=0 || $coin<$num){
            $this->exitJson(17005);
        }

        $wa=M("wallet_address")->where(array("uid"=>$this->uid,"exchange_id"=>$wallet_type))->find();
        if(!$wa){
            $this->exitJson(15006);
        }

        $op_all=M("out_point")->where('uid='.$this->uid.' && (type=2 || type=4) && status=0')->sum('point');
        if(($op_all+$num)>30000){
            $this->exitJson(20007);
        }

        $add['point']=$num;
//        $add['mx']=(float)config_get_vv('mx'); //mx价格
        $add['mx'] = M("exchange")->where(array("id"=>$wallet_type))->getField("price");//mx价格


        $cc=get_pi_num($this->uid);

        if($cc<5){
            $orp=0.5;
        }else{
            $orp=(int)config_get_vv("out_point_rate")/100;
        }
        if($orp<=0){
            $orp=0.5;
        }

        $data['address']=(string)$wa['address'];
        $data['point']=(string)$add['point'];
        if($c!=3){
            $orp=0;
        }
        $data['poundage']=(string)($data['point']*$orp);
        $data['money']=(string)round((($add['point']-$data['poundage'])/$add['mx']),2);

//        $data['cn']="转化为芬达币";
        $data['cn']="转化为";
        $data['exchange_name'] = M("exchange")->where(array("id"=>$wallet_type))->getField("name");

        $this->exitJson(0,$data);

    }

    //提出积分确定
    public function out_point_enter(){
        // $this->exitJson(20006);
        $type=I("post.type");
        $spwd=I("post.spwd");
        $num=I("post.num");
        $wallet_type = I("post.wallet_type");
        //实名认证
        if(!M("realname")->where(array("uid"=>$this->uid,"status"=>1))->find()){
            $this->exitJson(6101);
        }

        $wallet_status = M("exchange")->where(array("id"=>$wallet_type))->getField("delete");
        if($wallet_status == 1){
            $this->exitJson(15010);
        }

//        if($num<=0){
//            $this->exitJson(17004);
//        }
        if(empty($this->userinfo['spwd'])){
            $this->exitJson(105);
        }
        if(sp_password($spwd)!=$this->userinfo['spwd']){
            $this->exitJson(104);
        }

        switch ($type){
            case "tg":$t='coin';$c=2;break; //推广
            case "bj":$t='play_score';$c=3;break; //赠送钱包
            case "sy":$t='temp_score';$c=4;break; //收益钱包
            default:$this->exitJson(103);
        }
//        $coin=$this->userinfo[$t];
//        if($coin<=0 || $coin<$num){
//            $this->exitJson(17005);
//        }
//
//        $num=$this->userinfo[$t];
//
//        if($num<=0){
//            $this->exitJson(17004);
//        }

        $coin=$this->userinfo[$t];
        if($c==3){
            $num=$coin;
            $bp=M("block_point")->where(array("uid"=>$this->uid))->getField("point");
            if($bp<0){
                $bp=0;
            }
            $num-=$bp;
            if($num<=0 && $bp){
                $this->exitJson(123456,'扣除您的冻结积分'.$bp.',您没有可以提现的漫香积分');
            }
            if($num<=0){
                $this->exitJson(17005);
            }
        }elseif($c==2 && $num<500){
            $this->exitJson(20003);
        }elseif($c==4 && $num<100){
            $this->exitJson(20004);
        }

        if($num<=0){
            $this->exitJson(17004);
        }

        if( ( $num % 100) !=0){
            $this->exitJson(20005);
        }

        if($coin<=0 || $coin<$num){
            $this->exitJson(17005);
        }

        $wa=M("wallet_address")->where(array("uid"=>$this->uid))->order('change_time desc')->find();
        if(!$wa){
            $this->exitJson(15006);
        }
        $add["uid"]=$this->uid;
        $add['point']=$num;

//        $add['mx']=(float)config_get_vv('mx'); //mx价格
        $add['exchange_id'] = $wallet_type;
        $add['mx'] = M("exchange")->where(array("id"=>$wallet_type))->getField("price");//mx价格

        $cc=get_pi_num($this->uid);

//        //手续费比例
//        $cc=M("pi")->where(array("uid"=>$this->uid,"status"=>1))->count();
        if($cc<5){
            $orp=0.5;
        }else{
            $orp=(int)config_get_vv("out_point_rate")/100;
        }
        if($orp<0){
            $orp=0.5;
        }

        if($c!=3){
            $orp=0;
        }

        $add['poundage']=$add['point']*$orp;
        $add['money']= round((($add['point']-$add['poundage'])/$add['mx']),2);
        $add['add_time']=time();
        $add['type']=$c;
        $add['address']=$wa['address'];

        if(M("out_point")->add($add)){
            //addSysPoint($this->uid,$add['poundage'],"用户提现手续费");
            addMoney($this->uid,$num,2,"积分提现",$c);

            if($c==3){
                addMoney($this->uid,$this->userinfo['score'],2,'提现漫香积分清空',1);//电子币清空
                addMoney($this->uid,$this->userinfo['coin'],2,'提现漫香积分清空',2);//推广清空
                addMoney($this->uid,$this->userinfo['temp_score'],2,'提现漫香积分清空',4);//收益钱包清空
                addMoney($this->uid,$this->userinfo['cc'],2,'提现漫香积分清空',5);//消费钱包清空
            }

            $this->exitJson(0,"申请提现成功，系统会在三个工作日内审核");


        }else{
            $this->exitJson(103);
        }
    }

    //    提现记录
    public function out_point_log(){
        $l=array();
        $list=M("out_point")
            ->where(array(
                "uid"=>$this->uid,
            ))->order("add_time desc")->select();
        foreach ($list as $k=>$v){
            $l[$k]['point']=$v['point'];
            $l[$k]['date']=date('Y-m-d',$v['add_time']);
            switch($v['type']){
                case 2:$t="分享钱包";break;
                case 3:$t="漫香积分";break;
                case 4:$t="收益钱包";break;
                default:$t="未知";
            }
            $l[$k]['note']=$t;
            switch ($v['status']){
                case 0:$s='1';break;
                case 1:$s='2';break;
                default:$s='3';
            }
            $l[$k]['status']=$s;
        }
        $data['list']=$l;
        $this->exitJson(0,$data);
    }

    //电子币积分变动记录
    public function score_log(){
        $list1=M("point_list")
            ->Field("do,point,note,time")
            ->where(array(
                "uid"=>$this->uid,
                "do"=>2,
                "type"=>1
            ))
            ->order("time desc")
            ->select();
        if(count($list1)==0){
            $list1=array();
        }
        $list2=M("point_list")
            ->Field("do,point,note,time")
            ->where(array(
                "uid"=>$this->uid,
                "do"=>1,
                "type"=>1
            ))->order("time desc")
            ->select();
        if(count($list2)==0){
            $list2=array();
        }
        foreach ($list1 as $k=>$v){
            $list1[$k]["time"]=date("Y-m-d",$v['time']);
        }
        foreach ($list2 as $k=>$v){
            $list2[$k]["time"]=date("Y-m-d",$v['time']);
        }
        $data['list_reduce']=$list1;
        $data['list_add']=$list2;
        $this->exitJson(0,$data);
    }

    //积分明细
    public function point_log(){
        $type=I("post.type");
        switch ($type){
            case "tg":$t='coin';$c=2;break; //推广
            case "bj":$t='play_score';$c=3;break; //赠送钱包
            case "sy":$t='temp_score';$c=4;break; //收益钱包
            case "xf":$t='cc';$c=5;break; //消费钱包
            default:$this->exitJson(103);
        }
        $list1=M("point_list")
            ->Field("do,point,note,time")
            ->where(array(
                "uid"=>$this->uid,
                "type"=>$c
            ))
            ->order("time desc")
            ->select();
        if(count($list1)==0){
            $list1=array();
        }
        foreach ($list1 as $k=>$v){
            $list1[$k]["time"]=date("Y-m-d",$v['time']);
        }
        $data['list']=$list1;
        $this->exitJson(0,$data);
    }

    //推广钱包首页
    public function coin_mian(){
        //$today=strtotime(date("Y-m-d 0:0:0"));
        $data['coin']=$this->userinfo['coin'];
        $data['one']=M("point_list")->where(array("uid"=>$this->uid,"note"=>"一级团队提成",'type'=>2))->sum("point");
        $data['one']+=M("point_list")->where(array("uid"=>$this->uid,"note"=>"推荐奖",'type'=>2))->sum("point");
        $data['one']+=M("point_list")->where(array("uid"=>$this->uid,"note"=>"满额奖",'type'=>2))->sum("point");
        if(empty($data['one'])){
            $data['one']="0";
        }else{
            $data['one']=(string)$data['one'];
        }
        $data['two']=(string)M("point_list")->where(array("uid"=>$this->uid,"note"=>"二级团队提成",'type'=>2))->sum("point");
        if(empty($data['two'])){
            $data['two']="0";
        }
        $data['team']=(string)M("point_list")->where(array("uid"=>$this->uid,"note"=>"团队分红",'type'=>2))->sum("point");
        if(empty($data['team'])){
            $data['team']="0";
        }
        $data['cultivate']="0";
        $data['num']="5";

        $this->exitJson(0,$data);
    }

    //推广记录
    public function coin_log(){
        $type=I("post.type");
        switch ($type){
            case 1:$t='note="一级团队提成" || note="推荐奖" || note="满额奖"';break; //推广
            case 2:$t=" note='二级团队提成'";break; //赠送钱包
            case 3:$t=" note='团队分红'";break; //收益钱包
            default:$t='note="一级团队提成" || note="推荐奖" || note="满额奖"';
        }
        $list1=M("point_list")
            ->Field("do,point,note,time")
            ->where(array(
                "uid"=>$this->uid,
                "type"=>2
            ))
            ->where($t)
            ->order("time desc")
            ->select();
        if(count($list1)==0){
            $list1=array();
        }
        foreach ($list1 as $k=>$v){
            $list1[$k]["time"]=date("Y-m-d",$v['time']);
        }
        $data['list']=$list1;

        $this->exitJson(0,$data);
    }




}