<?php
/**
 * Created by PhpStorm.
 * User: 卓朝元
 * Date: 2016/4/4
 * Time: 19:31
 */

namespace Common\Model;

class ShopModel extends CommonModel
{
    protected $autoCheckFields = false;
    protected $trueTableName="huayu_goods";
    //获取商品列表
    public function getGoods(){
//        //热销推荐
//        $list = M("goods")->where(array("type"=>1,"is_sale"=>1,"is_exchange"=>0))->field("id,name,sale_price,title,type,list_img")->select();
//        foreach($list as $k=>$v){
//            $list[$k]['img'] = $list[$k]['list_img'];
//        }
        //全部商品
      /*  $list1 = M("goods")->where(array("is_sale"=>1,"type"=>0,"coin"=>0))->field("id,name,sale_price,title,type,num,list_img")->select();
        foreach($list1 as $k=>$v){
            $list1[$k]['stock'] = $v['num']-getSaleVolume($v['id']);
        }
        $list2 = M("goods")->where(array("is_sale"=>1,"type"=>1,"coin"=>0))->field("id,name,sale_price,title,type,num,list_img")->select();
        foreach($list1 as $k=>$v){
            $list1[$k]['stock'] = $v['num']-getSaleVolume($v['id']);
        }
        $list3 = M("goods")->where(array("is_sale"=>1,"type"=>2,"coin"=>0))->field("id,name,sale_price,title,type,num,list_img")->select();
        foreach($list1 as $k=>$v){
            $list1[$k]['stock'] = $v['num']-getSaleVolume($v['id']);
        }
        $list4 = M("goods")->where(array("is_sale"=>1,"type"=>3,"coin"=>0))->field("id,name,sale_price,title,type,num,list_img")->select();
        foreach($list1 as $k=>$v){
            $list1[$k]['stock'] = $v['num']-getSaleVolume($v['id']);
        }*/
//        $lists = array("type1"=>$list1,"type2"=>$list2,"type3"=>$list3,"type4"=>$list4);
        $lists = array(
            "type1"=>$this->getGoodsArry(0),
            "type2"=>$this->getGoodsArry(1),
            "type3"=>$this->getGoodsArry(2),
            "type4"=>$this->getGoodsArry(3),
            "type5"=>$this->getGoodsArry(4)
        );
        if($lists){
            return $lists;
        }else{
            return false;
        }

    }
    public  function getGoodsArry($type){
        $data=M("goods")->where(array("is_sale"=>1,"type"=>$type,"coin"=>0,'del'=>0))->field("id,name,sale_price,title,type,num,img")->select();
        foreach($data as $k=>$v){
            $data[$k]['stock'] = $v['num']-getSaleVolume($v['id']);
            if($data[$k]['stock']<0){
                $data[$k]['stock'] = "0";
            }
        }
        return $data;
    }
    //获取商品详情
    public function get_goods_info($id){
        $info=M("goods")->where(array("id"=>$id))->find();
        $info['sale_volume'] = getSaleVolume($info['id']);
        $info['stock'] = $info['num'] - $info['sale_volume'];
        if($info['stock']<0){
            $info['stock'] = "0";
        }
        if($info['coin'] == 1){
            $info['stock'] ='';
            $info['sale_volume']='';
        }
        if($info){
            return $info;
        }else{
            return false;
        }
    }

    //查询默认收获地址
    public function get_address($uid)
    {
        $address=M('address')->where(array("uid"=>$uid,"defaultx"=>"1"))->find();
        if(empty($address)){
            $address = array();
        }
        if($address){
            return $address;
        }
        else{
            $address=M('address')->where(array("uid"=>$uid))->find();
            if(empty($address)){
                $address =array();
            }
            if($address){
                return $address;
            }
            return array();
        }
    }

    //更新收货地址，或者增加
    public function deal_address($uid,$data,$aid="0"){
        $address['uid']=$uid;
        $address['name']=$data['name'];
        $address['province']=$data['province'];
        $address['city']=$data['city'];
        $address['area']=$data['area'];
        $address['detail']=$data['detail'];
        $address['order_mobile']=$data['order_mobile'];
        if(empty($uid) || empty($address["name"])|| empty($address["province"])|| empty($address["city"])|| empty($address["area"])
            || empty($address["detail"])|| empty($address["order_mobile"])){
            return "收获地址和联系方式不能留空";
        }
        if(empty($uid)){
            return "用户id不能为空";
        }

        if($aid!="0"){
            //更新收货地址
            if(M('address')->where(array("id"=>$aid,"uid"=>$uid))->save($address)){
                return "ok";
            }
        }else{

            $a=M('address')->where(array("uid"=>$uid))->count();
            if($a>5){
                return "exceed";
            }

            //新增加收货地址
            if(M('address')->add($address)){
                return "ok";
            }
        }
        return "操作失败，请重试";
    }

    //删除收货地址，或者增加
    public function del_address($uid,$aid){
        if(empty($uid)){
            return "用户id不能为空";
        }
        if(empty($aid)){
            return "收货地址id不能为空";
        }
        $arr["uid"]=$uid;
        $arr["id"]=$aid;
        //新增加收货地址
        if(M('address')->where($arr)->delete()){
            return "ok";
        }
        return "操作失败，请重试";
    }

    public function get_address_list($uid){
        $r=M("address")->where(array("uid"=>$uid))->select();
        return $r;
    }

    public function set_default_address($uid,$aid){
        if(!M("address")->where(array("uid"=>$uid,"id"=>$aid))->find()){
            return false;
        }else{
            M("address")->where(array("uid"=>$uid))->save(array("defaultx"=>"0"));
            M("address")->where(array("uid"=>$uid,"id"=>$aid))->save(array("defaultx"=>"1"));
            return true;
        }
    }


    //生成订单号
    public function getOrderSn(){
        $order_sn=date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        return $order_sn;
    }

    public function getAX($goods_id){
        $ax =M('goods')->where('id='."'$goods_id'")->getField('buy_price');
        return $ax;
    }

    //查询订单
    public function getOrder($uid){
        $order =M('order')->where(array("uid"=>$uid))->order("add_time desc")->select();
        foreach($order as $k=>$v){
            $order[$k]['img'] = $this->getGoodsImg($v['goods_id']);
        }
        return $order;
    }

    //查询订单
    public function getOrder_ed($uid){
        $order =M('order')->where(array("uid"=>$uid,"order_status"=>array('EGT',3)))->order("add_time desc")->select();
        foreach($order as $k=>$v){
            $order[$k]['img'] = $this->getGoodsImg($v['goods_id']);
        }
        return $order;
    }


    //未完成订单查询
    public function getOrder_no($uid){
        $order =M('order')->where(array("uid"=>$uid,"order_status"=>array('ELT',2)))->order("add_time desc")->select();
        foreach($order as $k=>$v){
            $order[$k]['img'] = $this->getGoodsImg($v['goods_id']);
        }
        return $order;
    }
    //查询待付款订单
    public function getOrderNoPay($uid){
        $order =M('order')->where(array("uid"=>$uid,"order_status"=>"0"))->order("add_time desc")->select();
        foreach($order as $k=>$v){
            $order[$k]['img'] = $this->getGoodsImg($v['goods_id']);
        }
        return $order;
    }

    //查询待发货订单
    public function getOrderNoSend($uid){
        $order =M('order')->where(array("uid"=>$uid,"order_status"=>"1"))->order("add_time desc")->select();
        foreach($order as $k=>$v){
            $order[$k]['img'] = $this->getGoodsImg($v['goods_id']);
        }
        return $order;
    }

    //查询待评价订单
    public function getOrderNoAssess($uid){
        $order =M('order')->where(array("uid"=>$uid,"order_status"=>"2"))->order("add_time desc")->select();
        foreach($order as $k=>$v){
            $order[$k]['img'] = $this->getGoodsImg($v['goods_id']);
        }
        return $order;
    }

    //获取商品图片
    protected function getGoodsImg($goods_id){
        $img =M('goods')->where('id='."'$goods_id'")->getField('img');
        return $img;
    }

    //计算商品总价
    public function total_price($goods_id,$num){
        $goods=M('goods')->where('id='."'$goods_id'")->find();
        if($goods["num"]!="0"){
            $ar=M("order")->where(array("goods_id"=>$goods_id,"order_status>0"))->sum("num");
            if(($ar+$num)>$goods["num"]){
                return -1;
            }
        }
//            if($goods["old_price"]!=0){
//                $goods_price=$goods["old_price"];
//            }else{
//                $goods_price=$goods["sale_price"];
//            }

        $goods_price=$goods["sale_price"];

        $total=$goods_price*$num;
        return $total;
    }

    //扣除爱心值
    public function deduct_score($num,$uid,$type){

    }

    //每日购买量
    public function maxNum($id,$num,$uid){
        $goods=M('goods')->where('id='."'$id'")->find();
        $an=M("order")->where(array("goods_id"=>$id,"uid"=>$uid,"add_time"=>array("EGT",date("Y-m-d 0:0:0"))))->sum("num");
        if(!$goods || $goods["max_num"]<($num+$an)){
            return true;
        }
        return false;
    }

    //确认付款
    public function order_pay_enter($order_sn,$pay_sn,$pay_type,$do=1){
        $save["pay_trade_no"]=$pay_sn;
        $save["pay_type"]=$pay_type;
        $save["order_status"]=$do;
        $save["pay_time"]=time();
        $st=M("order")->where(array("order_sn"=>$order_sn))->find();
        if(!$st && $st['order_status']!="0"){
            return false;
        }
        $do=M("order")->where(array("order_sn"=>$order_sn))->save($save);
        if($do){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 订单支付完成，签到奖金总值增加
     * @param $oid
     * @author v
     */
    public function sc_list_add($oid,$all_t=365){
        $order=M("order")->where(array("id"=>$oid))->find();
        $add["uid"]=$order['uid'];
        $add["point"]=$order["goods_price"];
        $add["order_id"]=$order["id"];
        $add["add_time"]=time();
        $add["all_t"]=$all_t;
        M("sc_list")->add($add);
        //$p=getParentInfoById();
        //count_level(getParentInfoById($order['uid']));
    }


}