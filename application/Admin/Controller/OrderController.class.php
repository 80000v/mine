<?php
/**
 * Created by PhpStorm.
 * User: 卓朝元
 * Date: 2016/4/4
 * Time: 15:02
 * 订单管理
 */

namespace Admin\Controller;
use Common\Controller\AdminbaseController;

class OrderController extends AdminbaseController
{
    protected $order_model;
    public function _initialize() {
        parent::_initialize();
        $this->order_model = M("order");
    }
    //订单首页
    public function index()
    {

        $p=I("get.p");
        if(empty($p)){
            $p=1;
        }
        $count= $this->order_model->where(array("coin"=>0))->count();
        $page = $this->page($count,20);
        //$this->assign("page", $page->show('Admin'));
        $data = $this->order_model
            ->where(array("coin"=>0))
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        foreach($data as $key=>$val){
            $data[$key]['mobile']=getMobile($val['uid']);
            $data[$key]['user_nicename']=getUserNameById($val['uid']);
        }
        $show=$page->show("admin");
        $this->assign ( "order", $data );
        $this->assign("show",$show);
        $this->display ();

    }

    private function order_status_string($status){
        switch($status){
            case 1:
                $content = "待发货";
                break;
            case 2:
                $content = "待收货";
                break;
            case 3:
                $content = "待评价";
                break;
            case 4:
                $content = "已评价";
                break;
            case 5:
                $content = "全部";
                break;
            default:
                $content = "未知";
        }
        return $content;
    }

    public function order_push(){
        $bt = I("post.bt");
        $et = I("post.et");
        $status = I("post.status");
        if(empty($et)){
            $this->error("请选择时间");
        }
        if(empty($bt)){
            $bt = "1970-01-01";
        }
        $start = $bt." 0:0:0";
        $end = $et." 23:59:59";
        if($start > $end){
            $this->error("请选择正确的时间");
        }
        if($status == 5){
            $data = $this->order_model
                ->where(array("coin"=>0,"add_time"=>array("between","$start,$end")))
                ->select();
        }else{
            $data = $this->order_model
                ->where(array("coin"=>0,"add_time"=>array("between","$start,$end"),"order_status"=>$status))
                ->select();
        }
        foreach($data as $k=>$v){
            $list[$k]['order_sn'] = $v['order_sn'];
            $list[$k]['goods_name'] = $v['goods_name'];
            $list[$k]['num'] = $v['num'];
            $list[$k]['goods_price'] = $v['goods_price'];
            $list[$k]['name'] = $v['name'];
            $list[$k]['mobile'] = $v['order_mobile'];
            $list[$k]['province'] = $v['province'];
            $list[$k]['city'] = $v['city'];
            $list[$k]['area'] = $v['area'];
            $list[$k]['detail'] = $v['detail'];
            $list[$k]['time'] = $v['add_time'];
            $list[$k]['status'] = $this->order_status_string($v['order_status']);
        }
        $order_header=array(
            "订单编号","商品名","数量","积分","姓名","手机号","省","市","区","详细地址","时间","状态"
        );
        array_unshift($list,$order_header);
        $file_name=$start."到".$end."的".$this->order_status_string($status)."列表";
        push_excel($list,$file_name);
    }

    //报单订单
    public function bd_index()
    {

        $p=I("get.p");
        if(empty($p)){
            $p=1;
        }
        $count= $this->order_model->where(array("coin"=>1))->count();
        $zong = $this->order_model->where(array("coin"=>1))->sum("goods_price");
        $title = "报单总金额为：".$zong;
        $page = $this->page($count,20);
        //$this->assign("page", $page->show('Admin'));
        $data = $this->order_model
            ->where(array("coin"=>1))
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        foreach($data as $key=>$val){
            $data[$key]['mobile']=getMobile($val['uid']);
            $data[$key]['user_nicename']=getUserNameById($val['uid']);
        }
        $show=$page->show("admin");
        $this->assign ( "order", $data );
        $this->assign ( "title", $title);
        $this->assign("show",$show);
        $this->display ();

    }

    //线下支付订单列表
    public function pay_xx(){
        $p=I("get.p");
        if(empty($p)){
            $p=1;
        }
        $w["status"] = "0";
        $w["pay_type"] = "xx";
        $count= M("offline_pay")->where($w)->count();
        $page = $this->page($count,20);
        $data = M("offline_pay")
            ->where($w)
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($data as $key=>$val){
            $data[$key]['mobile']=getMobile($val['uid']);
            $data[$key]['user_nicename']=getUserNameById($val['uid']);
        }
        $show=$page->show("admin");
        $this->assign ( "order", $data );
        $this->assign("show",$show);
        $this->display ();
    }

    public function pay_xx_yes(){
        $p=I("get.p");
        if(empty($p)){
            $p=1;
        }
        $w["status"] = "1";
        $w["pay_type"] = "xx";
        $count= M("offline_pay")->where($w)->count();
        $page = $this->page($count,20);
        $data = M("offline_pay")
            ->where($w)
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($data as $key=>$val){
            $data[$key]['mobile']=getMobile($val['uid']);
            $data[$key]['user_nicename']=getUserNameById($val['uid']);
        }
        $show=$page->show("admin");
        $this->assign ( "order", $data );
        $this->assign("show",$show);
        $this->display ();
    }

    //线下支付列表搜索
    public function pay_xx_search(){
        $key=I('key');
        $uid = getId($key);
        $p=I("get.p");
        if(empty($p)){
            $p=1;
        }
        $w["status"] = "0";
        $w["pay_type"] = "xx";
        $count= M("offline_pay")->where($w)->where("uid='$uid'")->count();
        $page = $this->page($count,20);
        $data = M("offline_pay")
            ->where($w)
            ->where("uid='$uid'")
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($data as $key=>$val){
            $data[$key]['mobile']=getMobile($val['uid']);
            $data[$key]['user_nicename']=getUserNameById($val['uid']);
        }
        if(empty($data)){
            $this->error('请输入正确的手机号或订单号！');
        }
        //print_r($data);exit;
        $show=$page->show("admin");
        $this->assign("show",$show);
        $this->assign("order",$data);
        $this->display("pay_xx");
    }

    //线下支付列表搜索
    public function pay_xx_yes_search(){
        $key=I('key');
        $uid = getId($key);
        $p=I("get.p");
        if(empty($p)){
            $p=1;
        }
        $w["status"] = "1";
        $w["pay_type"] = "xx";
        $count= M("offline_pay")->where($w)->where("uid='$uid'")->count();
        $page = $this->page($count,20);
        $data = M("offline_pay")
            ->where($w)
            ->where("uid='$uid'")
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($data as $key=>$val){
            $data[$key]['mobile']=getMobile($val['uid']);
            $data[$key]['user_nicename']=getUserNameById($val['uid']);
        }
        if(empty($data)){
            $this->error('请输入正确的手机号或订单号！');
        }
        //print_r($data);exit;
        $show=$page->show("admin");
        $this->assign("show",$show);
        $this->assign("order",$data);
        $this->display("pay_xx_yes");
    }



    //在线充值
    public function pay_online(){
        $p=I("get.p");
        if(empty($p)){
            $p=1;
        }
        $w["status"] = "1";
        $w["pay_type"] = "wx";
        $count= M("offline_pay")->where($w)->count();
        $page = $this->page($count,20);
        $data = M("offline_pay")
            ->where($w)
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($data as $key=>$val){
            $data[$key]['mobile']=getMobile($val['uid']);
            $data[$key]['user_nicename']=getUserNameById($val['uid']);
        }
        $show=$page->show("admin");
        $this->assign ( "order", $data );
        $this->assign("show",$show);
        $this->display();
    }

    //在线充值查询
    public function pay_online_search(){
        $mobile=I('mobile');
        $uid = getId($mobile);
        $p=I("get.p");
        if(empty($p)){
            $p=1;
        }
        $w["status"] = "1";
        $w["pay_type"] = "wx";
        $count= M("offline_pay")->where($w)->where("uid='$uid'")->count();
        $page = $this->page($count,20);
        $data = M("offline_pay")
            ->where($w)
            ->where("uid='$uid'")
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($data as $key=>$val){
            $data[$key]['mobile']=getMobile($val['uid']);
            $data[$key]['user_nicename']=getUserNameById($val['uid']);
        }
        //print_r($data);exit;
        $show=$page->show("admin");
        $this->assign("show",$show);
        $this->assign("order",$data);
        $this->display("pay_online");
    }


    //确认收款
    public function yes_bank(){
        $oid = I("get.id");
        $jilu = M("offline_pay")->where(array("id"=>$oid,"status"=>0))->find();
        if(empty($jilu)){
            $this->error("该记录不存在");
        }
        $uid = $jilu["uid"];
        $money =(int)$jilu["money"];
        $data["status"] = 1;
        $data["do_time"] = time();
        $result = M("offline_pay")->where(array("id"=>$oid))->save($data);
        if($result){
            addMoney($uid,$money,1,"线下充值",1);
            $this->success("确认收款成功",U("order/pay_xx"));
        }else{
            $this->error("操作失败");
        }
    }

    //不通过
    public function no_bank(){
        $oid = I("get.id");
        $data["status"] = 2;
        $data["do_time"] = time();
        $result = M("offline_pay")->where(array("id"=>$oid))->save($data);
        if($result){
            $this->success("操作成功",U("order/pay_xx"));
        }else{
            $this->error("操作失败");
        }
    }

    //订单详情页
    public function info(){
        $id=intval($_GET['id']);
        $sn = $_GET['order_sn'];
        if(empty($sn)){
            $sn = 1;
        }
        $order=$this->order_model->where("id=".$id." || order_sn=".$sn)->find();
//        foreach($order as $k=>$v){
//            $info = M("address")->where(array("uid"=>$v['uid'],"defaultx"=>1))->find();
//            $order[$k]["province"] = $info['province'];
//            $order[$k]["city"] = $info['city'];
//            $order[$k]["area"] = $info['area'];
//            $order[$k]["detail"] = $info['detail'];
//            unset($info);
//        }
        $this->assign('order',$order);
        $this->display();
    }

    //删除订单
    public function delete(){
        $id=intval($_GET['id']);
        if ($this->order_model->where('id='."'$id'")->delete()!==false) {
            $this->success("删除成功！");
        } else {
            $this->error("删除失败！");
        }
    }

    //编辑商品
    public function edit(){
        $id=intval($_GET['id']);
        $order = $this->order_model->where("id="."'$id'")->find();
        $this->assign('order',$order);
        $this->display();
    }

    //处理编辑商品
    public function do_edit(){
        $id=I("post.id");
        $data = array();
        $data['province'] = I("post.province");
        $data['city'] = I("post.city");
        $data['area'] = I("post.area");
        $data['detail'] = I("post.detail");
        $data['name'] = I("post.name");
        $data['order_mobile'] = I("post.order_mobile");
        $data['order_status'] = I("order_status");
        $result = $this->order_model->where(array("id"=>$id))->save($data);
        if($result){
            $this->success();
        }else{
            $this->error("数据处理失败!");
        }
    }

    //添加物流信息
    public function logistics(){
        $key = I("key");
        $id=intval($_GET['id']);
        $order = $this->order_model->where("id="."'$id'")->find();
        $this->assign('order',$order);
        $this->assign('key',$key);
        $this->display();
    }

    public function logistics_readdress(){
        $id=intval($_GET['id']);
        $order = $this->order_model->where("id="."'$id'")->find();
        if(!empty($order['name'])){
            $this->error('该订单无法更新收货信息');exit;
        }
        $default_address=D('Shop')->get_address($order['uid']);
        if(count($default_address)<=0){
            $this->error('该用户未填写收货地址');exit;
        }
        $do=M("order")->where(array('id'=>$id))->save(array(
                "name"=>$default_address['name'],
                "province"=>$default_address['province'],
                "city"=>$default_address['city'],
                "area"=>$default_address['area'],
                "detail"=>$default_address['detail'],
                "order_mobile"=>$default_address['order_mobile']
            )
        );
        if($do){
            $this->success('更新成功');exit;
        }else{
            $this->error('更新失败');exit;
        }
    }


    //处理添加物流信息
    public function do_logistics(){
        $key = I("key");
        $mobile = getMobile($key);
        $id=I("post.id");
        $data['logistics'] = I("logistics");
        $data['logistics_sn'] = I("logistics_sn");
        $data['order_status'] = "2";
        $result = $this->order_model->where(array("id"=>$id))->save($data);
        if($result){
            $this->success("更新成功",U("Order/search",array('key'=>$mobile)));
        }else{
            $this->error("数据处理失败!");
        }
    }

    function search(){
        $key=I('key');
        $uid = getId($key);
        $p=I("get.p");
        if(empty($p)){
            $p=1;
        }

        $User=M('Order');
        $count= $User->where("goods_name like '%$key%' || order_sn like '%$key%' || uid='$uid'")->count();
//        print_r($count);die;
        $page = $this->page($count,20);
        //print_r($key);exit;
        //$map[$key] =array('like',array('%user_nicename%','%mobile%'),'OR');
        $data=$User->where("goods_name like '%$key%' || order_sn like '%$key%' || uid='$uid'")
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($data as $key=>$val){
            $data[$key]['mobile']=getMobile($val['uid']);
            $data[$key]['user_nicename']=getUserNameById($val['uid']);
        }
        if(empty($data)){
            $this->error('请输入正确的手机号或订单号或商品名！');
        }
        //print_r($data);exit;
        $show=$page->show("admin");
        $this->assign("show",$show);
        $this->assign("order",$data);
        $this->display("index");
    }

    //未支付订单首页
    public function nopay()
    {

        $p=I("get.p");
        if(empty($p)){
            $p=1;
        }
        $count= $this->order_model->where(array("order_status"=>"0","coin"=>0))->count();
        $page = $this->page($count,20);
        //$this->assign("page", $page->show('Admin'));
        $data = $this->order_model
            ->where(array("order_status"=>"0","coin"=>0))
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        foreach($data as $key=>$val){
            $data[$key]['mobile']=getMobile($val['uid']);
            $data[$key]['user_nicename']=getUserNameById($val['uid']);
        }
        $show=$page->show("admin");
        $this->assign ( "order", $data );
        $this->assign("show",$show);
        $this->display ();

    }

    //编辑商品
    public function nopayedit(){
        $id=intval($_GET['id']);
        $order = $this->order_model->where("id="."'$id'")->find();
        $this->assign('order',$order);
        $this->display();
    }

    //处理编辑商品
    public function do_nopayedit(){
        $id=I("post.id");
        $data = array();
        $data['province'] = I("post.province");
        $data['city'] = I("post.city");
        $data['area'] = I("post.area");
        $data['detail'] = I("post.detail");
        $data['name'] = I("post.name");
        $data['order_mobile'] = I("post.order_mobile");
        $data['logistics'] = I("logistics");
        $data['logistics_sn'] = I("logistics_sn");
        $data['get_time'] = I("get_time");
        $data['order_status'] = I("order_status");
        $result = $this->order_model->where(array("id"=>$id))->save($data);
        if($result){
            $this->success();
        }else{
            $this->error("数据处理失败!");
        }
    }

    //申请退款订单列表
    public function return_goods(){
        $p = I("p");
        if (empty($p)) {
            $p = 1;
        }
        $w['refundable_status'] = '1';
        $count = $this->order_model->where($w)->count();
        $page = $this->page($count,20);
        $list = $this->order_model
            ->where($w)
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($list as $key=>$val){
            $list[$key]['mobile']=getMobile($val['uid']);
            $list[$key]['user_nicename']=getUserNameById($val['uid']);
        }
        $show=$page->show("admin");
        $this->assign ( "list", $list );
        $this->assign("show",$show);
        $this->display ();
    }

    //搜索
    public function return_goods_search(){
        $p = I("p");
        if(empty($p)){
            $p = 1;
        }
        $key=I('key');
        $uid = getId($key);
        $w['refundable_status'] = '1';
        $count= $this->order_model->where($w)->where("order_sn like '%$key%' || uid='$uid'")->count();
        $page = $this->page($count,20);
        $list = $this->order_model
            ->where($w)
            ->where("order_sn like '%$key%' || uid='$uid'")
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($list as $key=>$val){
            $list[$key]['mobile']=getMobile($val['uid']);
            $list[$key]['user_nicename']=getUserNameById($val['uid']);
        }
        if(empty($list)){
            $this->error("请输入正确的订单编号或手机号");
        }
        $show=$page->show("admin");
        $this->assign ( "list", $list );
        $this->assign("show",$show);
        $this->display ("return_goods");
    }

    //通过退款申请
    public function do_return(){
        $oid = I("id");
        $do['refundable_status'] = 3;
        $do['order_status'] = 0;
        $o_sta = $this->order_model->where(array("id"=>$oid))->save($do);
        $sc_sta = M("sc_list")->where(array("order_id"=>$oid))->delete();
        if($o_sta){
            $this->success("退款申请处理成功");
        }else{
            $this->error("退款申请处理失败");
        }
    }

    //驳回申请退款
    public function no_return(){
        $oid = I("id");
        $do['refundable_status'] = 0;
        $o_sta = $this->order_model->where(array("id"=>$oid))->save($do);
        if($o_sta){
            $this->success("驳回成功");
        }else{
            $this->error("失败");
        }
    }

    //已完成退款列表
    public function return_yes(){
        $p = I("p");
        if (empty($p)) {
            $p = 1;
        }
        $w['refundable_status'] = '3';
        $count = $this->order_model->where($w)->count();
        $page = $this->page($count,20);
        $list = $this->order_model
            ->where($w)
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($list as $key=>$val){
            $list[$key]['mobile']=getMobile($val['uid']);
            $list[$key]['user_nicename']=getUserNameById($val['uid']);
        }
        $show=$page->show("admin");
        $this->assign ( "list", $list );
        $this->assign("show",$show);
        $this->display ();
    }

    //搜索已完成退款列表
    public function return_yes_search(){
        $p = I("p");
        if(empty($p)){
            $p = 1;
        }
        $key=I('key');
        $uid = getId($key);
        $w['refundable_status'] = '3';
        $count= $this->order_model->where($w)->where("order_sn like '%$key%' || uid='$uid'")->count();
        $page = $this->page($count,20);
        $list = $this->order_model
            ->where($w)
            ->where("order_sn like '%$key%' || uid='$uid'")
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($list as $key=>$val){
            $list[$key]['mobile']=getMobile($val['uid']);
            $list[$key]['user_nicename']=getUserNameById($val['uid']);
        }
        if(empty($list)){
            $this->error("请输入正确的订单编号或手机号");
        }
        $show=$page->show("admin");
        $this->assign ( "list", $list );
        $this->assign("show",$show);
        $this->display ("return_yes");

    }


}