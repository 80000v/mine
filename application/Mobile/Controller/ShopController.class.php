<?php
namespace Mobile\Controller;
use Common\Controller\AppController;
class ShopController extends AppController{

    protected $pid;
    protected $app_id;
    protected $rsaPrivateKey;
    protected $alipayrsaPublicKey;
    protected $alipayPublicKey;
    protected $notify_url;
    protected $dotname;

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
        $this->dotname="http://ios.lehouse.cc";
        $this->pid = "2088521408711454";  //pid
        $this->app_id = "2017020305501701";  //app_id
        //异步回调地址
        $this->notify_url=$this->dotname."/App/shop/alipay_callback";
        //应用私钥
        $this->rsaPrivateKey = 'MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBALOAicUPRZ0Hru8w43A7DIEZjVSrqTrRJoaYojr5hXgdsEobqeFCL31alKqz8KMtS9gxO2gkZtBsj1GsajYFiIrz3FUAeOSh6xxPOZCS82aqIxGmeBUUUcHtgvS2dyIva1Zt9S6vdBF4TNWFE2m9tvrqfENsUjoN6HdBdPIkD8+3AgMBAAECgYAsxreXLIQU88GzcOKLMG+iFJmosVl5joqpsJFnXK7qk51SHyx1QGlQP7QuEMzKJ5Zvy3giNlJfU3U8zmGAMEkq1ONS08/JVmLMndLxiRaWfnES76eUz01Y6ZxZC4YpaWsxzDleVrh2h57rRb63qiRhXLdNi5GrJw0DMQKgN/YCYQJBAOpI4Lhjjv59+xbehxya5MMUgstbZf2YQZwVf90V7P46QqVxKsLd09mHyoliCSM6IlhB89r406TU6vl/y006dYsCQQDEI8aZ2N8IBSq7NmdrEau6dzQ1NUsS7r1n9RaE1NJ7WF0ECNRhfMpNkZJ1PcnyThA9J8wb6n0i+3XDMzTUWwwFAkAzy8Lq4Q/nEcEmUDI8z73Np0Y3YVCOHVA8CsDHBybrGcRMQVW72UER8aSEdQkiIaMgMgyQl7xqz6vXVzqCK297AkBLQ18mEe4jabgn9oxgrXs0JiHGeRjBvxK3HXjyp6fM5O9saOb2Mah/c2i7zGX9sK7SiL7tx2EVV2Cs8q1G/1jxAkEAsQCC62m1yS0ije/gBwfOx7M9U3J8a1vpxPzH408TprtOz4xxGAjZU7D05v6FQUi/g5Z7LhFQaiVfuQXyPDpGDg==';
        //阿里公钥
        //$this->alipayrsaPublicKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCnxj/9qwVfgoUh/y2W89L6BkRAFljhNhgPdyPuBV64bfQNN1PjbCzkIM6qRdKBoLPXmKKMiFYnkd6rAoprih3/PrQEB/VsW8OoM8fxn67UDYuyBTqA23MML9q1+ilIZwBC2AQ2UBVOrFXfFl75p6/B5KsiNG9zpgmLCUYuLkxpLQIDAQAB';
        $this->alipayrsaPublicKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB';
        $this->alipayPublicKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDI6d306Q8fIfCOaTXyiUeJHkrIvYISRcc73s3vF1ZT7XN8RNPwJxo8pWaJMmvyTn9N4HQ632qJBVHf8sxHi/fEsraprwCtzvzQETrNRwVxLO5jVmRGi60j8Ue1efIlzPXV9je9mkjzOmdssymZkh2QhUrCmZYI/FCEa3/cNMW0QIDAQAB';
        //$this->alipayPublicKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCnxj/9qwVfgoUh/y2W89L6BkRAFljhNhgPdyPuBV64bfQNN1PjbCzkIM6qRdKBoLPXmKKMiFYnkd6rAoprih3/PrQEB/VsW8OoM8fxn67UDYuyBTqA23MML9q1+ilIZwBC2AQ2UBVOrFXfFl75p6/B5KsiNG9zpgmLCUYuLkxpLQIDAQAB';
    }

    //商品列表
    public function  goods_list(){
        $list = D('Shop')->getGoods();
        if(empty($list)){
            $this->exitJson(3000);
        }else{
            $this->exitJson(0,$list);
        }
    }

    //商品列表（新）
    public function goods_list_new(){
        $w['is_sale']=1;
        $w['coin']=0;
        $w['del']=0;

        //分类
        $type=I('post.type'); //专区
        $classify=I('post.classify'); //类型
        switch ($type){
            case '0':$w['type']=0;break;
            case '1':$w['type']=1;break;
            default:;
        }
        if($classify){
            $w['classify']=$classify;
        }


        $data=M("goods")->where($w)->field("id,name,sale_price,title,type,num,img")->select();
        foreach($data as $k=>$v){
            $data[$k]['sale_volume'] = getSaleVolume($v['id']);  //销量
            $sale_volume_arr[$k] = $data[$k]['sale_volume'];
            $sale_price_arr[$k]=$v['sale_price'];
            $data[$k]['stock'] = $v['num']- $data[$k]['sale_volume'];
            if($data[$k]['stock']<0){
                $data[$k]['stock'] = "0";
            }else{
                $data[$k]['stock']=(string)$data[$k]['stock'];
            }
        }

        //排序
        $sortord=I('post.sortord');
        switch ($sortord){
            case 1:array_multisort($sale_volume_arr,SORT_DESC,$data);break;
            case 2:array_multisort($sale_price_arr,SORT_ASC,$data);break;
            case 3:array_multisort($sale_price_arr,SORT_DESC,$data);break;
            default:;
        }
        $this->exitJson(0,$data);
//        if(empty($list)){
//            $this->exitJson(3000);
//        }else{
//
//        }
    }

    //商城首页
    public function shop_main(){
        //更新：横幅
        $banner=M('shop_banner')->field('img')->select();
        if(empty($banner)){
            $banner=array();
        }
        $data['banner']=$banner;

        //最新公告标题
        $article =D('Article')->getArticleList(2);
        if(!$article){
            $data['news']='';
        }else{
//            foreach ($article as $vo)
            $data['news']=$article[0]['post_title'];
        }

        //最新商品列表
        $gl=M('goods')->where(array('coin'=>0,'is_sale'=>1,'del'=>0,'recommend'=>1))->field("id,name,sale_price,title,type,num,img")->order('add_time desc')->select();
        foreach($gl as $k=>$v){
            $gl[$k]['sale_volume'] = getSaleVolume($v['id']);  //销量
            $gl[$k]['stock'] = $v['num']-$gl[$k]['sale_volume'];

            if($gl[$k]['stock']<0){

                $gl[$k]['stock'] = "0";
            }
        }
        $data['goods_list']=$gl;
        //更新
        $ex = M("exchange")->where(array("delete"=>"0"))->select();
        if(empty($ex)){
            $ex = array();
        }
        $data['exchange'] = $ex;

        $this->exitJson(0,$data);

    }


    //商品详情
    public function goods_info(){
        $id = I("id");
        $uid = $this->uid;
        $uinfo = getUserInfoById($uid);
        $info =D("Shop")->get_goods_info($id);
        //更新
        $info['user_score'] = $uinfo['score'];
        if(empty($info['user_score'])){
            $info['user_score'] = "0";
        }else{
            $info["user_score"] = (string)$uinfo["score"];
        }
        if($info['type']==0){
            $info["cc"] = $uinfo["cc"];
        }else{
            $info["cc"] = $uinfo["coin"]+$uinfo["temp_score"];  //推广钱包和收益钱包和
        }

        if(empty($uinfo["cc"])){
            $uinfo["cc"] = "0";
        }else{
            $uinfo["cc"] = (string)$uinfo["cc"];
        }
        if(empty($info)){
            $this->exitJson(3000);
        }else{
            $this->exitJson(0,$info);
        }
    }

    //获取默认收货地址。请求接口 $id为用户id
    public function button_order(){
        $uid = $this->uid;
        $address=D('Shop')->get_address($uid);
        if($address){
            $this->exitJson(0,$address);
        }else{
            $this->exitJson(7004);

        }
    }

    //添加收货地址
    public function add_address(){
        $data['name']=trim(I("post.name"));
        $data['province']=trim(I("post.province"));
        $data['city']=trim(I("post.city"));
        $data['area']=trim(I("post.area"));
        $data['detail']=trim(I("post.detail"));
        $data['order_mobile']=trim(I("post.order_mobile"));
        $r=D('Shop')->deal_address($this->uid,$data);
        if($r=="ok"){
            $this->exitJson(0,"地址添加成功");
        }elseif($r=="exceed"){
            $this->exitJson(7025);
        }else{
            $this->exitJson(7022,$r);
        }
    }

    //删除收货地址
    public function del_address(){
        $aid=trim(I("post.aid"));
        $r=D('Shop')->del_address($this->uid,$aid);
        if($r=="ok"){
            $this->exitJson(0,"删除成功");
        }else{
            $this->exitJson(7022,$r);
        }
    }

    //修改收货地址
    public function change_address(){
        $aid=trim(I("post.aid"));
        $data['name']=trim(I("post.name"));
        $data['province']=trim(I("post.province"));
        $data['city']=trim(I("post.city"));
        $data['area']=trim(I("post.area"));
        $data['detail']=trim(I("post.detail"));
        $data['order_mobile']=trim(I("post.order_mobile"));
        $r=D('Shop')->deal_address($this->uid,$data,$aid);
        if($r=="ok"){
            $this->exitJson(0,"修改成功");
        }else{
            $this->exitJson(7022,$r);
        }
    }

    //获取收货地址列表
    public function get_address_list(){
        $r= D("Shop")->get_address_list($this->uid);
        $this->exitJson(0,$r);
    }

    //设置默认收货地址
    public function set_default_address(){
        $aid=trim(I("post.aid"));
        if(empty($aid)){
            $this->exitJson(7020);
        }
        if(D("Shop")->set_default_address($this->uid,$aid)){
            $this->exitJson(0);
        }else{
            $this->exitJson(7020);
        }
    }

    //增加订单
    public function add_order(){
//        $id = I("post.id");//订单id
//        if($id){
//            $po=M("order")->where(array("id"=>$id))->find();
//            if($po && $po['uid'] == $this->uid){
//                $good=M("goods")->where(array("id"=>$po['goods_id']))->find();
//                if(!$good){
//                    $this->exitJson(7003);//系统繁忙，请稍后再试
//                }
//            }else{
//                $this->exitJson(7101);//该订单异常，无法继续
//            }
//        }
        $post['goods_id'] = I('post.goods_id');
        $post['num'] = I('post.num');
        $the_address=I('post.address');
        if(empty($post['goods_id'])){
            $this->exitJson(7010);//商品id不能为空
        }
        $user_info = $this->userinfo;
        $user_level = $user_info['level'];
        $good=M("goods")->where(array("id"=>$post['goods_id']))->find();
        //更新
//        if($good['coin']==1){
//            $this->exitJson(7039);
//        }
        //
        if($user_level < $good['level_limit']){
            $this->exitJson(7035);//您的等级暂不符合购买条件
        }
        if($good['buy_limit']>0){
            $order_count = M("order")->where(array("uid"=>$this->uid,"goods_id"=>$post['goods_id']))->count();
            $order_count_zong = $order_count + $post['num'];
            if($order_count_zong >= $good['buy_limit']){
                $this->exitJson(7037);//超出该商品所能购买的件数,
            }
        }
        if($good["is_sale"]==0){
            $this->exitJson(7029);//该商品已下架，暂时无法购买
        }
        if(empty($post['num'])){
            $this->exitJson(7011);//数量不能为空
        }
        if($post["num"]<=0){
            $this->exitJson(7011);//数量不能为空
        }
        $info=M("goods")->where(array("id"=>$post['goods_id']))->find();
        $stock = $info['num'] - getSaleVolume($info['id']);
        if($stock < 1){
            $this->exitJson(7021);//"当前商品剩余量不足",
        }
        $post['goods_price']=D('Shop')->total_price($post['goods_id'],$post['num']);
        if ($post["goods_price"] == -1) {
            $this->exitJson(7021);//"当前商品剩余量不足",
        }
        if ($post["goods_price"] < 0) {
            $this->exitJson(7003);//系统未知错误
        }
        //收货地址处理
        $address=M('address')->where(array("id"=>$the_address,"uid"=>$this->uid))->find();
        if(!$address){
            $this->exitJson(7024);//请选择正确的收货地址
        }
        $post['goods_name'] = M("goods")->where(array("id"=>$post['goods_id']))->getField("name");
        $post['name'] = $address["name"];
        $post['province'] = $address["province"];
        $post['city'] = $address["city"];
        $post['area'] = $address["area"];
        $post['detail'] = $address["detail"];
        $post['order_mobile'] = $address["order_mobile"];
        $post['order_price'] = $post['goods_price'];
        $post['order_sn']=D('shop')->getOrderSn().sprintf("%08d", $this->uid).rand(10,99);
        $post['uid']=$this->uid;
        $post['order_status'] = "1";
        $post['add_time']=date('Y-m-d H:i:s',time());
        $u_cc = M("users")->where(array("id"=>$this->uid))->getField("cc");
        $u_coin = ($user_info['coin']>0)?$user_info['coin']:0;//推广钱包
        $u_temp_score = ($user_info['temp_score']>0)?$user_info['temp_score']:0;//收益钱包
        //更新
        $u_score = ($user_info['score']>0)?$user_info['score']:0;//金币
        if($good['coin'] == 1){
            if($u_score<$post['order_price']){
                $this->exitJson(7036);//当前对应钱包积分不足，无法购买
            }
            $post['pay_type'] = "sc"; //金币  支付标识
            $post['coin'] = 1;
            $do = addMoney($this->uid,$post['order_price'],2,"购买商品消费",1);
            if($do){
                $result = M('order')->add($post);
                if($result){
                    //增加赠送钱包
                    addMoney($this->uid,$post['goods_price'],1,"购买商品",3);
                    $this->exitJson(0,"购买成功");
                }else{
                    $this->exitJson(7003);
                }
            }else{
                $this->exitJson(7003);
            }
        }
        if($good['type']>0 ){
            //总积分数判断
            if(($u_coin+$u_temp_score)<$post['order_price']){
                $this->exitJson(7036);//当前对应钱包积分不足，无法购买
            }
            $post['pay_type'] = "ct"; //消费积分和收益积分  支付标识

            //推广积分扣款计算
            if($u_coin >= $post['order_price']){
                $coin_use=$post['order_price'];
            }else{
                $coin_use=(int)$u_coin;
                if($coin_use<0){
                    $coin_use=0;
                }
            }

            //收益钱包扣款计算
            $temp_score_use=$post['order_price']-$coin_use;
            if($coin_use>0){
                if($temp_score_use>0){
                    $do=addMoney($this->uid,$coin_use,2,"购买商品消费",2);
                }else{
                    $do=addMoney($this->uid,$post['order_price'],2,"购买商品消费",2);
                }
            }

            if($temp_score_use >0){
                $do=addMoney($this->uid,$temp_score_use,2,"购买商品消费",4);
            }

            if($do){
                $result = M('order')->add($post);
                if($result){
                    $this->exitJson(0,"购买成功");
                }else{
                    $this->exitJson(7003);
                }
            }else{
                $this->exitJson(7003);
            }
        }else{
            if(empty($u_cc)){
                $u_cc = "0";
            }
            if($u_cc<$post['order_price']){
                $this->exitJson(7027);//当前积分不足，无法购买
            }
            $post['pay_type'] = "cc";
            $do=addMoney($this->uid,$post['order_price'],2,"购买商品消费",5);
            if($do){
                $result = M('order')->add($post);
                if($result){
                    $this->exitJson(0,"购买成功");
                }else{
                    $this->exitJson(7003);
                }
            }else{
                $this->exitJson(7003);
            }
        }
    }


    //增加订单&&再次支付
    public function add_order1(){
        $id=I("post.id");
        $type=I("post.type");
        switch ($type){
            //case 1:$t="zfb";$type=1;break;
            case 3:$t="jf";$type=3;break;//积分兑换
            //case 4:$t="xx";$type=4;break;//线下转款
            case 5:$t="mobao";$type=5;break;//mobao
            default:$this->exitJson(16006);
        }
        $coupon_id=I("post.coupon_id");
        if($id){
            $po=M("order")->where(array("id"=>$id))->find();
            if($po && $po["uid"]==$this->uid){
                $good=M("goods")->where(array("id"=>$po['goods_id']))->find();
                if(!$good){
                    $this->exitJson(7003);
                }elseif($good["is_exchange"]=="1" && $type!=3){
                    $this->exitJson(7003);
                }
                $data['id']=(string)$id;
                $data['type']=(string)$type;
                $data['order_price']=(string)$po['order_price'];

                if($type==1){
                    $arr['app_id']=$this->app_id;
                    $arr['method']="alipay.trade.app.pay";
                    $arr['charset']="utf-8";
                    $arr['sign_type']="RSA";
                    $arr['timestamp']=date("Y-m-d H:i:s");
                    $arr['version']="1.0";
                    $arr['notify_url']=$this->notify_url;
                    $arr['biz_content']="{'subject':'".$po['goods_name']."','out_trade_no':'".$po['order_sn']."','total_amount':'".$po['order_price']."','seller_id':'".$this->pid."','product_code':'QUICK_MSECURITY_PAY'}";

                    $c = new AopClient;
                    $c->gatewayUrl = "https://openapi.alipay.com/gateway.do";
                    $c->appId = $this->app_id;
                    $c->rsaPrivateKey = $this->rsaPrivateKey;
                    $c->format = "json";
                    $c->charset = "UTF-8";
                    $c->signType= "RSA";
                    $arr['sign'] = $c->rsaSign($arr);
                    $data["alipay_parm"]=$c->getSignContentUrlencode($arr);
                    $this->exitJson(0,$data);
                }elseif ($type==5){
                    $data["pay_parm"]=$this->dotname.U("pay/mpay",array("id"=>$id));
                    $this->exitJson(0,$data);
                }elseif ($type==4){
                    $this->exitJson(0,$data);
                }else{
                    $this->exitJson(7003);
                }

            }else{
                $this->exitJson(7101);
            }
        }

        $post['goods_id'] = I('post.goods_id');
        $post['num'] = I('post.num');
        $post['note'] = trim(I('post.note'));
        $the_address=I('post.address');
        if(mb_strlen($post['note'])>200){
            $this->exitJson(7023);
        }

        if(empty($post['goods_id'])){
            $this->exitJson(7010);//商品id不能为空
        }

        $good=M("goods")->where(array("id"=>$post['goods_id']))->find();
        if($good["is_sale"]==0){
            $this->exitJson(7029);
        }
        if(empty($post['num'])){
            $this->exitJson(7011);//数量不能为空
        }
        if($post["num"]<=0){
            $this->exitJson(7011);//数量不能为空
        }
        if($good["is_exchange"]!="1" && $post["num"]>1){
            $this->exitJson(7003);
        }
        if($good["is_exchange"]=="1" && $post["num"]<100){
            $this->exitJson(7032);
        }

        $post['goods_price']=D('Shop')->total_price($post['goods_id'],$post['num']);
        if ($post["goods_price"] == -1) {
            $this->exitJson(7021);//"当前商品剩余量不足",
        }
        if ($post["goods_price"] < 0) {
            $this->exitJson(7003);//系统未知错误
        }

        //代金券使用
        if(!empty($coupon_id)){
            if($good["is_exchange"]=="1"){
                $this->exitJson(31003);
            }
            $ci=M("coupon")->where(array(
                "id"=>$coupon_id,
                "uid"=>$this->uid,
                "oid"=>array("EXP"," is NULL ")
            ))->find();
            if(!$ci){
                $this->exitJson(31001);
            }elseif ($post["goods_price"]<=$ci['money']){
                $this->exitJson(31002);
            }else{
                $post['order_price']=$post["goods_price"]-$ci['money'];
                $post["goods_price"]=$post["goods_price"];
                $change_coupon=1;
            }
        }else{
            $post['order_price']=$post["goods_price"];
        }


        //收货地址处理
        $address=M('address')->where(array("id"=>$the_address,"uid"=>$this->uid))->find();
        if(!$address && $type!=3){
            $this->exitJson(7024);
        }
        $post['goods_name'] = M("goods")->where(array("id"=>$post['goods_id']))->getField("name");
        if($good["is_exchange"]!="1"){
            $post['name'] = $address["name"];
            $post['province'] = $address["province"];
            $post['city'] = $address["city"];
            $post['area'] = $address["area"];
            $post['detail'] = $address["detail"];
            $post['order_mobile'] = $address["order_mobile"];
        }else{
            $post['name'] = "";
            $post['province'] = "";
            $post['city'] = "";
            $post['area'] = "";
            $post['detail'] = "";
            $post['order_mobile'] = "";
        }


        $post['order_sn']=D('shop')->getOrderSn().sprintf("%08d", $this->uid).rand(10,99);
        $post['uid']=$this->uid;
        $post['add_time']=date('Y-m-d H:i:s',time());
        $post['goods_ax']=D('shop')->getAX($post['goods_id']);
//        if(D('shop')->maxNum($post['goods_id'],$post['num'],$this->uid)){
//            $this->exitJson(7018);//超过购买限额
//        }
        $result = M('order')->add($post);
        if($result){
            if($change_coupon==1){
                M("coupon")->where(array(
                    "id"=>$coupon_id,
                    "uid"=>$this->uid,
                ))->save(array("oid"=>$result,"user_time"=>time()));
            }
            M("goods")->where(array("id"=> $post['goods_id']))->setInc("sale_volume",$post['num']);

           // $data['goods_price']=$post['goods_price'];
            $data['order_price']=$post['order_price'];
            $data['goods_name']=$post['goods_name'];
            $data['type']=(string)$type;
            $data['id']=(string)$result;
            if($type==1){
                $arr['app_id']=$this->app_id;
                $arr['method']="alipay.trade.app.pay";
                $arr['charset']="utf-8";
                $arr['sign_type']="RSA";
                $arr['timestamp']=date("Y-m-d H:i:s");
                $arr['version']="1.0";
                $arr['notify_url']=$this->dotname."/App/shop/alipay_callback";
                $arr['biz_content']="{'subject':'".$post['goods_name']."','out_trade_no':'".$post['order_sn']."','total_amount':'".$post['order_price']."','seller_id':'".$this->pid."','product_code':'QUICK_MSECURITY_PAY'}";

                $c = new AopClient;
                $c->gatewayUrl = "https://openapi.alipay.com/gateway.do";
                $c->appId = $this->app_id;
                $c->rsaPrivateKey = $this->rsaPrivateKey;
                $c->format = "json";
                $c->charset = "UTF-8";
                $c->signType= "RSA";
                // $c->alipayrsaPublicKey = $this->alipayrsaPublicKey;
                $arr['sign'] = $c->rsaSign($arr);

                $data["alipay_parm"]=$c->getSignContentUrlencode($arr);

            }elseif ($type==5){
                $data["pay_parm"]=$this->dotname.U("pay/mpay",array("id"=>$result));
            }elseif($type==3){
                $u_score=M()->query("select score from huayu_users where id=".$this->uid);
                if(!$u_score[0]){
                    M("order")->where(array("id"=>$result))->delete();
                    $this->exitJson(7003);
                }
                $u_score=$u_score[0]['score'];
                if($u_score<$post['order_price']){
                    M("order")->where(array("id"=>$result))->delete();
                    $this->exitJson(7027);
                }

                $data_ec["phone"]=$this->userinfo['mobile'];
                $data_ec['ec']=$post['num'];
                $data_ec['token']=$this->get_token($data_ec);
                $url="http://www.wanbi360.cn/market/hyyss_exchange";
                $re=request_post($url,$data_ec);
                if($re=="2"){
                    M("order")->where(array("id"=>$result))->delete();
                    $this->exitJson(7033);
                }elseif($re=='4'){
                    $do=addMoney($this->uid,$post['order_price'],2,"购买商品消费","score");
                    if($do){
                        D('Shop')->order_get_enter($post['order_sn'],$do,"jf",2);
//                        M("order")->where(array("id"=>$result))->save(array(
//                            "order_status"=>2,
//                            "pay_type"=>"jf",
//                            "pay_trade_no"=>$do,
//                            "pay_time"=>time()
//                        ));
                        $this->exitJson(0,"购买成功");
                    }else{
                        M("order")->where(array("id"=>$result))->delete();
                        $this->exitJson(7003);
                    }
                }else{
                    M("order")->where(array("id"=>$result))->delete();
                    $this->exitJson(7003);
                }
            }
            $this->exitJson(0,$data);
        }else{
            $this->exitJson(7003);//系统未知错误
        }
    }


    //全部订单列表
    public function order_list(){
        $mobile = I("post.mobile");
        $uid = getId($mobile);
        $order = D('Shop')->getOrder($uid);
        if($order){
            foreach ($order as $k=>$v){
                if($v["order_status"]==0 && $v["pay_status"]==1){
                    $order[$k]["is_check"]="1";
                }else{
                    $order[$k]["is_check"]="0";
                }
            }
            $this->exitJson(0,$order);
        }else{
            $this->exitJson(7014);//暂时没有订单
        }
    }


    //未完成订单列表

    public function order_list_no(){
        $mobile = I("post.mobile");
        $uid = getId($mobile);
        $ol=M('order')->where(array('uid'=>$uid))->select();
        foreach ($ol as $k=>$v){
            $sp=M("goods")->where(array('id'=>$v['goods_id']))->getField('sale_price');
            $ps=$sp*$v['num'];
            if($v['goods_price']>$ps){
                M("order")->where(array('id'=>$v['id']))->save(array('goods_price'=>$ps));
            }
        }

        $order = D('Shop')->getOrder_no($uid);
        if($order){
            $this->exitJson(0,$order);
        }else{
            $this->exitJson(7014);//暂时没有订单
        }
    }

    //已完成订单列表
    public function order_list_ed(){
//        $this->detection();
        $mobile = I("post.mobile");
        $uid = getId($mobile);
        $order = D('Shop')->getOrder_ed($uid);
        if($order){
            $this->exitJson(0,$order);
        }else{
            $this->exitJson(7014);//暂时没有订单
        }
    }

    //待付款订单列表
    public function order_list_no_pay(){
        $mobile = I("post.mobile");
        $uid = getId($mobile);
        $order = D('Shop')->getOrderNoPay($uid);
        if($order){
            foreach ($order as $k=>$v){
                if($v["order_status"]==0 && $v["pay_status"]==1){
                    $order[$k]["is_check"]="1";
                }else{
                    $order[$k]["is_check"]="0";
                }
            }
            $this->exitJson(0,$order);
        }else{
            $this->exitJson(7014);//暂时没有订单
        }
    }

    //待发货订单列表
    public function order_list_no_send(){
        $this->detection();
        $mobile = I("post.mobile");
        $uid = getId($mobile);
        $order =D('Shop')->getOrderNoSend($uid);
        if($order){
            $this->exitJson(0,$order);
        }else{
            $this->exitJson(7014);//暂时没有订单
        }
    }

    //待评价订单列表
    public function order_list_no_assess(){
        $this->detection();
        $mobile = I("post.mobile");
        $uid = getId($mobile);
        $order = D('Shop')->getOrderNoAssess($uid);
        if($order){
            $this->exitJson(0,$order);
        }else{
            $this->exitJson(7014);//暂时没有订单
        }
    }

    //确认收货
    public function enter_get(){
        $oid=trim(I("post.id"));
        $o_info=M("order")->where(array("id"=>$oid,"uid"=>$this->uid))->find();
        if(!$o_info){
            $this->exitJson(7003);//系统错误
        }
        if($o_info["order_status"]!="2"){
            $this->exitJson(7038);//操作失败,请重试
        }
        if($o_info["order_status"]>2 ){
            $this->exitJson(7019);//订单已确认，请返回刷新查看
        }
        if(M("order")->where(array("id"=>$oid))->save(array("order_status"=>"3","get_time"=>date("Y-m-d H:i:s")))){
            $this->exitJson(0);
        }else{
            $this->exitJson(7020);//操作失败,请重试
        }
    }

    //查看物流
    public function logistics(){
        $oid = I("post.id");
        $result = M("order")->where(array("id"=>$oid))->find();
        $data["logistics"] = $result['logistics'];
        $data["logistics_sn"] = $result['logistics_sn'];
        $data["order_sn"] = $result['order_sn'];
        if(empty($data['logistics'])){
            $data['logistics'] = "";
        }
        if(empty($data['logistics_sn'])){
            $data['logistics_sn'] = "";
        }
        $this->exitJson(0,$data);
    }

    //单个商品所有评价
    public function reviews_lists(){
        $goods_id = I("post.goods_id");
        $result = M("goods_reviews")->where(array("goods_id"=>$goods_id))->order("time DESC")->select();
        foreach($result as $key=>$value){
            $a = getUserInfoById($value["uid"]);
            $result[$key]["avatar"] = (string)$a["avatar"];
            $result[$key]["user_nicename"] = $a["user_nicename"];
            $result[$key]["mobile"] = $a["mobile"];
            unset($a);
        }
        if(empty($result)){
            $this->exitJson(7030);//该商品暂无评价
        }else{
            $this->exitJson(0,$result);
        }
    }

    //对商品进行评价
    public function add_reviews(){
        $info['order_id'] = I("post.order_id");
        $oi=M('order')->where(array('uid'=>$this->uid,'id'=>$info['order_id'],'order_status'=>3))->find();
        if(!$oi){
            $this->exitJson(7031);
        }
        $info['goods_id'] = $oi['goods_id'];
        $info['status'] = I("post.status"); //等级1-5
        if($info['status']<=0 || $info['status']>5){
            $info['status']=5;
        }
        $info['contents'] = trim(I("post.contents")); //评价内容
        $info['time'] = date("Y-m-d H:i:s",time());
        $info['uid'] = $this->uid;
        $result = M("goods_reviews")->add($info);
        $rst = M("order")->where(array("id"=>$info['order_id']))->save(array("order_status"=>"4"));
        if($result && $rst){
            $this->exitJson(0,"评价成功");
        }else{
            $this->exitJson(7031);
        }
    }


    //支付宝异步回调
    public function alipay_callback(){
      //  $this->set();
        $post = $_POST;

//        $s="begin:\n";
//        foreach ($post as $k=>$v){
//            $s.="\n$k=>$v";
//        }
//        M("v")->add(array("v"=>$s));
//        exit;
        $pid = $this->pid;  //pid

        $c = new AopClient();
        $c->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $c->appId = $this->app_id;
        $c->rsaPrivateKey = $this->rsaPrivateKey;
        $c->format = "json";
        $c->charset = "UTF-8";
        $c->signType= "RSA";
        $c->alipayrsaPublicKey = $this->alipayrsaPublicKey;
//        $c->alipayPublicKey=$this->alipayPublicKey;
        $response = $c->rsaCheckV1($post,"");
        if($response){
            $order = M("order")->where(array("order_sn"=>$post["out_trade_no"]))->find();
            if(!$order || $post['auth_app_id']!=$this->app_id
                || $order['goods_price']!=$post["total_amount"]
                || $post["trade_status"]!="TRADE_SUCCESS"){

                $s="begin:\n";
                foreach ($post as $k=>$v){
                    $s.="\n$k=>$v";
                }
                M("v")->add(array("v"=>$s));
//                exit;
                die("1001");  //验证信息错误
            }else{
//                $save["order_status"]=1;
//                $save["pay_type"]="alipay";
//                $save["pay_trade_no"]=$post['trade_no'];
                //if(M("order")->where(array("order_sn"=>$post["out_trade_no"]))->save($save)){
                if(D('Shop')->order_pay_enter($post["out_trade_no"],$post['trade_no'],"alipay")){
//                    $oid=M("order")->where(array("order_sn"=>$post["out_trade_no"]))->getField("id");
//                    $this->sc_list_add($oid);
                    die("success");
                }
            }

        }else{
            $s="b:\n";
            foreach ($post as $k=>$v){
                $s.="\n$k=>$v";
            }
            M("v")->add(array("v"=>$s));
                exit;
        }
    }
    //查询支付宝订单交易情况
//    public function alipay_status(){
//        $sn=I("sn");
//       // $this->set();
//        $aop = new AopClient ();
//        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
//        $aop->appId = $this->app_id;
//        $aop->rsaPrivateKey = $this->rsaPrivateKey;
//        $aop->alipayrsaPublicKey=$this->alipayrsaPublicKey;
//        $aop->apiVersion = '1.0';
//        $aop->signType = 'RSA';
//        $aop->postCharset='UTF-8';
//        $aop->format='json';
//        $request = new AlipayTradeQueryRequest ();
//        $request->setBizContent("{" .
//            "    \"out_trade_no\":\"".$sn."\"" .
//            "  }");
//        $result = $aop->execute ( $request);
//        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
//        $resultCode = $result->$responseNode->code;
//        print_r($result->$responseNode->sub_msg);
//        exit();
//        if(!empty($resultCode)&&$resultCode == 10000){
//            echo "成功";
//        } else {
//            echo "失败";
//        }
//    }

    //获取未使用的代金券列表
    public function coupon_list(){
        $this-> detection();
        $c=M("coupon")->where(array("use_uid"=>$this->uid,"oid"=>array('EXP'," is NULL ")))->select();
        $r["arr"]=array();
        $i=0;
        foreach ($c as $k=>$v){
            $a["id"]=$v['id'];
            $a["name"]=$v['name'];
            $a["money"]=$v['money'];
            if($v["get_uid"]==$v['use_uid']){
                $a["type"]="0";
            }else{
                $a["type"]="1";
            }
            $r["arr"][]=$a;
            $i++;
            unset($a);
        }
        $r["num"]=$i;
        $this->exitJson(0,$r);
    }

    //获取已使用的代金券列表
    public function coupon_list_ed(){
        $this-> detection();
        $c=M("coupon")->where(array("use_uid"=>$this->uid,"oid"=>array('EXP'," is not NULL ")))->select();
        $r["arr"]=array();
        $i=0;
        foreach ($c as $k=>$v){
            $a["id"]=$v['id'];
            $a["name"]=$v['name'];
            $a["money"]=$v['money'];
            if($v["get_uid"]==$v['use_uid']){
                $a["type"]="0";
            }else{
                $a["type"]="1";
            }
            $r["arr"][]=$a;
            $i++;
            unset($a);
        }
        $r["num"]=$i;
        $this->exitJson(0,$r);
    }

    /**
     * 赠送代金券
     * @author v
     */
    public function send_coupon(){
        $this->detection();
        $mobile=trim(I("post.user_mobile"));
        $name=trim(I("post.name"));
        $pwd=trim(I("post.pwd"));
        $cid=trim(I("post.id"));
        if(empty($mobile)){
            $this->exitJson(14001);
        }
        if(empty($name)){
            $this->exitJson(14002);
        }
        if(empty($pwd)){
            $this->exitJson(14003);
        }
        $ci=M("coupon")->where(array("use_uid"=>$this->uid,"id"=>$cid,"user_time"=>array('EXP'," is NULL ")))->find();
        if(!$ci){
            $this->exitJson(14005);
        }
        $ui=getUserInfoByMobile($mobile);
        if(!$ui || $ui['user_nicename']!=$name){
            $this->exitJson(14004);
        }
        if($ui['id']==$this->uid ){
            $this->exitJson(14008);
        }
        if($ui['parent_id']!=$this->uid && $this->userinfo['parent_id']!=$ui['id'] ){
            $this->exitJson(14009);
        }
        if(sp_password($pwd)!=$this->userinfo['user_pass']){
            $this->exitJson(14006);
        }
        if(M("coupon")
            ->where(array("use_uid"=>$this->uid,"id"=>$cid,"user_time"=>array('EXP'," is NULL ")))
            ->save(array("use_uid"=>$ui['id']))){
            M("coupon_log")->add(array(
                "put_uid"=>$this->uid,
                "get_uid"=>$ui['id'],
                "time"=>time(),
                "coupon_id"=>$cid
            ));
            $this->exitJson(0);
        }else{
            $this->exitJson(14007);
        }

    }


    /**
     * 获取代金券赠送记录
     * @author v
     */
    public function coupon_record(){
        $this->detection();
        $p = I("post.p");
        if(empty($p)){
            $p = 1;
        }
        $re=M("coupon_log")->where("(get_uid=".$this->uid."  && put_uid!=0) || put_uid=".$this->uid."")
            ->order("time desc")->page($p.",20")->select();
        $count=M("coupon_log")->where("(get_uid=".$this->uid."  && put_uid!=0) || put_uid=".$this->uid."")->count();
        foreach($re as $k=>$v){
            $r[$k]['date']=date("Y-m-d H:i:s",$v["time"]);
            if($v["get_uid"]==$this->uid){
                $r[$k]['type']="1"; //获得
                $ui=getUserInfoById($v['put_uid']);
            }else{
                $r[$k]['type']="2"; //赠送
                $ui=getUserInfoById($v['get_uid']);
            }
            $r[$k]["mobile"]=$ui["mobile"];
            $r[$k]["name"]=$ui["user_nicename"];
            $r[$k]["money"]=(string)M("coupon")->where(array('id'=>$v['coupon_id']))->getField('money');
            unset($ui);
        }
        if(empty($r)){
            $data["list"] = array();
        }else{
            $data["list"]=$r;
        }
        $data["count"]=$count;
        $this->exitJson(0,$data);
    }

    /**
     * 删除订单
     * @author v
     */
    public function delete_order(){
        $this->detection();
        $order_id=I("post.id");
        $oi=M("order")->where(array("uid"=>$this->uid,"id"=>$order_id))->find();
        if(!$oi){
            $this->exitJson(7102);
        }elseif($oi['order_status']!=0){
            $this->exitJson(7103);
        }
        $ci=M("coupon")->where(array("uid"=>$this->uid,"oid"=>$order_id))->find();
        if($ci){
            if(M("coupon")->where(array("id"=>$ci["id"]))->save(array("user_time"=>NULL,"oid"=>NULL))){
                sendMessage($this->uid,"由于删除商城订单，您的".$ci['money']."元代金券已返还");
            }
        }
        if(M("order")->where(array("uid"=>$this->uid,"id"=>$order_id))->delete()){
            $this->exitJson(0,"成功删除该订单");
        }else{
            $this->exitJson(7101);
        }

    }



    //购买须知 大标题7个空格，下级加1个
    public function introduction(){
        $data = M("setting")->where(array("name"=>"notice"))->find();
//        $data = array(
//            "str1" => "       本产品为华宇云商（武汉）实业有限公司（以下简称本平台）所销售，消费者成功购买本产品后即自动成为华宇云商平台会员，并在享受平台提供的福利的同时履行会员应尽的相关义务，现将具体细节告知如下：",
//            "str2" => "        1、购买者必须严格按照本平台要求进行实名认证注册，并填写真实有效的个人信息，如填写虚假信息一经查实，本平台有权取消该会员资格及相关福利。",
//            "str3" => "        2、消费者在商城订购产品，确认收货并好评后，平台将于次日起每日向该会员赠送所订购产品金额1%价值的永恒积分（如订购金额为1万元，平台每日赠送该会员约等值于100元人民币的永恒积分，送满365天为止，节假日照常赠送，依此类推），每日最多赠送约等值于500元人民币的永恒积分。",
//            "str4" => "        3、平台自购买方付款后的第8天起开始赠送永恒积分，如在7天内收货并好评的会员赠送时间可提前，会员须登录本平台APP点击签到方可领取，如当天没有签到，视为自动放弃当天永恒积分福利。",
//            "str5" => "        4、平台向会员所赠永恒积分的性质为无偿赠送，不视为会员订购产品或服务所支付的对价所得，不属于平台应尽的义务，仅为该会员的可期待利益，本平台对此不做任何保证性承诺。",
//            "str6" => "        5、消费者根据自身需求在本平台订购相应的产品，自购买方付款后的第二日开始计算，购买方享有7日的犹豫期，在犹豫期内，购买方可通过平台系统申请退款。针对已收到产品在犹豫期想要退款的消费者须自行承担产品快递费用。",
//            "str7" => "        6、在犹豫期过后，除因产品本身质量问题，购买方不得以任何理由要求平台退换。且购买方一旦以任何理由申请退货，平台有权取消其会员资格，无法享受平台的相关福利。",
//        );
        $this->exitJson(0,$data);
    }

    //收款账户
    public function receivable_account(){
        $this->detection();
        $name = M("setting")->where(array("name"=>"receipt_name"))->getField("value");
        $account = M("setting")->where(array("name"=>"receipt_account"))->getField("value");
        $address = M("setting")->where(array("name"=>"receipt_address"))->getField("value");
        $data = array(
            "name" => $name,
            "account" => $account,
            "address" => $address,
        );
        $this->exitJson(0,$data);
    }

    //转账
    public function  transfer(){
        $this->detection();
        $name = I("post.name");
        $account = I("post.account");
        if(empty($name)){
            $this->exitJson(7200);
        }
        if(empty($account)){
            $this->exitJson(7201);
        }
        if(strlen($account)<16 || strlen($account)>19){
            $this->exitJson(7202);
        }
        $data["uid"] = $this->uid;
        $data["name"] = $name;
        $data["account"] = $account;
        $data["time"] = time();
        $result = M("transfer")->add($data);
        if($result){
            $this->exitJson(0);
        }else{
            $this->exitJson(7203);
        }
    }

    //可退款订单列表
    public function refundable_list(){
        $this->detection();
        $p = I("post.p");
        if(empty($p)){
            $p = 1;
        }
        $time = time();
        $count = M("order")->where(array("uid"=>$this->uid))->where("pay_time + 604800>$time")->where("order_status=1 || order_status=2")->count();
        $list = M("order")
            ->where(array("uid"=>$this->uid))
            ->where("order_status=1 || order_status=2")
            ->where("pay_time + 604800>$time")
            ->order("id desc")
            ->page($p.',20')
            ->select();
        foreach($list as $k=>$v){
            $id = $list[$k]["goods_id"];
            $list[$k]["img"] = M("goods")->where(array("id"=>$id))->getField("img");
            unset($id);
        }
        if(empty($count)){
            $count = "0";
        }
        if(empty($list)){
            $list = array();
        }
        $data['count'] = $count;
        $data['list'] = $list;
        if($data){
            $this->exitJson(0,$data);
        }
    }

    //申请退货
    public function refundable(){
        $this->detection();
        $oid = I("post.id");
        $result = M("order")->where(array("id"=>$oid))->setField("refundable_status","1");
        if($result){
            $this->exitJson(0,"申请成功");
        }else{
            $this->exitJson(7104);
        }
    }

    //线下转账
    public function offline_pay(){
        $this->detection();
         $bank_number=I("post.bank_number");
         $bank_name=trim(I("post.bank_name"));
         $name=I("post.name");
         $order_id=I("post.id");

         $oi= M("order")
             ->where(array("uid"=>$this->uid,"order_status"=>0,'id'=>$order_id,"pay_status"=>0))
             ->find();
         if(!$oi){  
             $this->exitJson(16002);
         }
         if(empty($bank_number) || empty($name) || empty($bank_name)){
            $this->exitJson(16001);
         }
        if(!preg_match('!^\d{16,19}$!i',$bank_number)){
            $this->exitJson(16003);
        }
        if(!preg_match('/^[\x{4e00}-\x{9fa5}]+$/u',$name)){
            $this->exitJson(16004);
        }

         $add["bank_number"]=$bank_number;
         $add["bank_name"]=$bank_name;
         $add["name"]=$name;
         $add["time"]=time();
         $a=M("bank_log")->add($add);
         if($a){
             M("order")->where(array("id"=>$order_id,"uid"=>$this->uid))->save(array(
                 "pay_status"=>"xx",
                 "pay_trade_no"=>$a,
                 "pay_status"=>0
             ));
             $this->exitJson(0,"成功提交,请等待后台审核");
         }else{
             $this->exitJson(16005);
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

}