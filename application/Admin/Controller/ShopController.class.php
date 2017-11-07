<?php
/**
 * Created by PhpStorm.
 * User: 卓朝元
 * Date: 2016/4/4
 * Time: 15:02
 * 商城管理
 */

namespace Admin\Controller;
use Common\Controller\AdminbaseController;

class ShopController extends AdminbaseController
{
    protected $shop_model;
    public function _initialize() {
        parent::_initialize();
        $this->shop_model = D("Common/Goods");
    }
//商品列表
    public function index()
    {
        $count=$this->shop_model->where(array("del"=>"0","coin"=>"0"))->count();
        $page = $this->page($count,20);
        $goods = $this->shop_model
            ->where(array("del"=>"0","coin"=>"0"))
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($goods as $k=>$v){
            $xl = M("order")->where(array("goods_id"=>$v['id']))->sum("num");
            $goods[$k]['sale_volume'] = $xl;
        }
        $this->assign("page", $page->show('Admin'));
        $this->assign('goods',$goods);
        $this->display();
    }

    //增加商品
    public function add(){
        $this->display();
    }
    //处理增加商品
    public function add_post(){
        $img=I('photos_url');
        $goods['name']   = str_replace(",","，",I('name'));
        $goods['sale_price']=I('sale_price');
        $goods['num']=I('num');
        $goods['type']=I('goods_type');
        $goods['level_limit'] = I('level_limit');
        $goods['buy_limit']=I('buy_limit');
        $goods['type']=I('type');
        $goods['classify']=I('classify');
        $goods['recommend']=I('recommend');
//        print_r($goods['is_exchange']);die;
        $upload = new \Think\Upload();// 实例化上传类
//        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =      'data/upload/shop/'; // 设置附件上传根目录
        $info = $upload->uploadOne($_FILES['img']);
//        print_r($info);die;
        if(!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        }
        $file_img= '/data/upload/shop/'.$info['savepath'].$info['savename'];
//        $file_img2= '/data/upload/shop/'.$info[1]['savepath'].$info[1]['savename'];
        $goods["img"]=$file_img;
//        $goods["list_img"]=$file_img2;
        $goods['introduction']=htmlspecialchars_decode(I('introduction'));
        $goods['add_time']=date("Y-m-d H:i:s",time());
        if(empty($goods['name'])){
            $this->error("请填写商品名称！");
        }elseif(empty($goods['sale_price']))
        {
            $this->error("请填写价格！");
        }
        $result = $this->shop_model->add($goods);
        if($result){
            $this->success("添加成功！");
        }else{
            $this->error('添加失败！');
        }
    }

    //编辑商品
    public function edit(){
        $id=intval($_GET['id']);
        $goods = $this->shop_model->where("id="."'$id'")->find();
        $this->assign('goods',$goods);
        $this->display();
    }

    //处理编辑商品
    public function edit_post(){
        $img=I('photos_url');
        $list_img=I('list_photos_url');
        $goods['id'] = I('id');
        //$goods['goods_name']=I('goods_name');
        $goods['name'] = str_replace(",","，",I('name'));
        $goods['introduction']=I('introduction');
        $goods['sale_price']=I('sale_price');
        $goods['num']=I('num');
        $goods['level_limit']=I('level_limit');
        $goods['buy_limit']=I('buy_limit');
        $goods['type']=I('type');
        $goods['classify']=I('classify');
        $goods['recommend']=I('recommend');
        $upload = new \Think\Upload();// 实例化上传类
//        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =  SITE_PATH.'data/upload/shop/'; // 设置附件上传根目录
        $info = $upload->uploadOne($_FILES['img']);
//        if(!$info) {// 上传错误提示错误信息
//            $this->error($upload->getError());
//        }
        $file_img= '/data/upload/shop/'.$info['savepath'].$info['savename'];
        if(empty($info)){
            $goods["img"] = $img;
        }else{
            $goods["img"]=$file_img;
        }
        $goods['introduction']=htmlspecialchars_decode(I('introduction'));
        $goods['add_time']=date("Y-m-d H:i:s",time());
        if(empty($goods['name'])){
            $this->error("请填写商品名称！");
        }elseif(empty($goods['sale_price']))
        {
            $this->error("请填写价格！");
        }
        $result = $this->shop_model->save($goods);
        if($result){
            $this->success("修改成功！");
        }else{
            $this->error('修改失败！');
        }
    }

    //删除商品
    public function delete(){
        $id=intval($_GET['id']);
        if ($this->shop_model->where("id=$id")->save(array("del"=>"1"))!==false) {
            $this->success("删除成功！");
        } else {
            $this->error("删除失败！");
        }
    }

    //查看商品
    public function info(){
        $id = I('id');
        $goods=$this->shop_model->where('id='."'$id'")->find();
        $this->assign('goods',$goods);
        $this->display();
    }

    //更改商品销售状态
    public function sale(){
        $status = I("get.status");
        $id = I("get.id");
        if($status=="0"){
            $rst = M("goods")->where(array("id"=>$id))->setField("is_sale","1");
            if($rst){
                $this->success("状态更改成功");
            }
        }elseif($status=="1"){
            $rst = M("goods")->where(array("id"=>$id))->setField("is_sale","0");
            if($rst){
                $this->success("状态更改成功");
            }
        }else{
            $this->error("数据处理失败");
        }
    }

    //代金券发放记录
    public function coupon_list(){
        $p = I("p");
        if(empty($p)){
            $p = 1;
        }
        $w['put_uid'] = '0';
        $count = M("coupon_log")->where($w)->count();
        $page = $this->page($count,20);
        $list =M("coupon_log")
            ->where($w)
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($list as $k=>$v){
            $uid = $v["get_uid"];
            $info = getUserInfoById($uid);
            $money = M("coupon")->where(array("id"=>$v["coupon_id"]))->getField("money");
            $list[$k]["money"] = $money;
            $list[$k]["user_nicename"] = $info["user_nicename"];
            $list[$k]["mobile"] = $info["mobile"];
            unset($uid);
            unset($info);
            unset($money);
        }
        $show=$page->show("admin");
        $this->assign ( "list", $list );
        $this->assign("show",$show);
        $this->display();
    }

    //搜索
    public function coupon_list_search(){
        $key = I("key");
        $uid = getId($key);
        if(empty($key) || empty($uid)){
            $this->error("请输入正确的手机号");
        }
        $p = I("p");
        if(empty($p)){
            $p = 1;
        }
        $w['put_uid'] = '0';
        $w['get_uid'] = $uid;
        $count = M("coupon_log")->where($w)->count();
        if(empty($count)){
            $this->error("该用户暂未获得代金券");
        }
        $page = $this->page($count,20);
        $list =M("coupon_log")
            ->where($w)
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($list as $k=>$v){
            $uid = $v["get_uid"];
            $info = getUserInfoById($uid);
            $money = M("coupon")->where(array("id"=>$v["coupon_id"]))->getField("money");
            $list[$k]["money"] = $money;
            $list[$k]["user_nicename"] = $info["user_nicename"];
            $list[$k]["mobile"] = $info["mobile"];
            unset($uid);
            unset($info);
            unset($money);
        }
        $show=$page->show("admin");
        $this->assign ( "list", $list );
        $this->assign("show",$show);
        $this->display("coupon_list");
    }

    //和发放代金券
    public function send_coupon(){
        $mobile=trim(I("post.mobile"));
        $money=trim(I("post.money"));
        if(!$mobile){
            $this->display();
            exit;
        }

        $ui=getUserInfoByMobile($mobile);
        if(!$ui){
            $this->alert("用户不存在");
        }
        if($money<=0){
            $this->alert("代金券金额必须大于0");
        }
        $add["get_uid"]=$ui['id'];
        $add["use_uid"]=$ui['id'];
        $add["name"]=$money."积分代金券";
        $add["money"]=$money;
        $add["time"]=time();
        $add["send_time"]=time();
        $cid=M("coupon")->add($add);
        if($cid){
            M("coupon_log")->add(array(
                "put_uid"=>0,
                "get_uid"=>$ui['id'],
                "time"=>time(),
                "coupon_id"=>$cid
            ));
            $this->success('"'.$add["name"].'"发放成功！');
        }else{
            $this->error("发放失败，请重试。");
        }
    }

    //购买须知
    public function notice(){
        $data = M("setting")->where(array("name"=>"notice"))->find();
        $this->assign("data",$data);
        $this->display();
    }

    //编辑购买须知
    public function edit_notice(){
        $info = I("post.notice");
        if(empty($info)){
            $this->error("购买须知不能为空");
        }
        $result = M("setting")->where(array("name"=>"notice"))->setField("value",$info);
        if($result){
            $this->success("购买须知更新成功");
        }else{
            $this->error("购买须知更新失败");
        }
    }

    //订单导出
    public function order_push(){
        $goods_id=I("id");
        $gi=M("goods")->where(array("id"=>$goods_id))->find();
        if(!$gi){
            $this->error("所选商品不存在");
        }
        if(IS_POST){
            if(I('ia')=="1"){
                $w=array();
                $ww="全部订单";
            }else{
                $w=array("logistics"=>'');
                $ww="未发货订单";
            }
            $bt=I("bt")." 0:0:0";
            $et=I("et")." 23:59:59";
            if(empty($bt) || empty($et)){
                $this->error("请检查所输入日期");
            }
            if($et<$bt){
                $this->error("请检查所输入日期");
            }
            $orders= M("order")
                ->where(array("goods_id"=>$goods_id,"add_time"=>array("between","$bt,$et"),"order_status"=>array("GT",0),"refundable_status"=>0))
                ->where($w)
                ->Field("order_sn,num,name,order_mobile,add_time,province,city,area,detail,logistics,logistics_sn")->select();
            $order_header=array(
                "订单号","数量","收货人姓名","收货人手机号","下单时间","省份","市","区","详细地址","物流公司","物流单号"
            );
//            echo count($orders);die();
            array_unshift($orders,$order_header);
//          echo "<pre>";
//            print_r($orders);
            $file_name=$gi['name'].$bt."到".$et.$ww;
              push_excel($orders,$file_name);
        }else{
            $this->assign("gi",$gi);
            $this->display();
        }
    }

    //设置APP横幅
    public function shop_banner(){
        $data = M("shop_banner")->select();
        $this->assign("data",$data);
        $this->display();
    }

    //删除横幅
    public function shop_banner_delete(){
        $id = I("get.id");
        $result = M("shop_banner")->where(array("id"=>$id))->delete();
        if($result){
            $this->success("删除成功",U("shop/shop_banner"));
        }
    }

    //新增横幅
    public function shop_banner_add(){
//        $time= date('Y-m', time());
        $upload = new \Think\Upload();// 实例化上传类
//        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =      'data/upload/shop/'; // 设置附件上传根目录
        $info = $upload->uploadOne($_FILES['img']);
//        print_r($info);die;
        if(!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        }
        $file_img= '/data/upload/shop/'.$info['savepath'].$info['savename'];
        $add["img"]=$file_img;
        $result = M("shop_banner")->add($add);
        if($result){
            $this->success("上传成功",U("shop/shop_banner"));
        }
    }

    //报单产品相关
    //报单产品列表
    public function bd_goods(){
        $count=$this->shop_model->where(array("del"=>"0","coin"=>1))->count();
        $page = $this->page($count,20);
        $goods = $this->shop_model
            ->where(array("del"=>"0","coin"=>1))
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($goods as $k=>$v){
            $xl = M("order")->where(array("goods_id"=>$v['id']))->sum("num");
            $goods[$k]['sale_volume'] = $xl;
        }
        $this->assign("page", $page->show('Admin'));
        $this->assign('goods',$goods);
        $this->display();
    }

    //增加报单商品
    public function bd_add(){
        $this->display();
    }
    //处理增加报单商品
    public function bd_add_post(){
        $img=I('photos_url');
        $goods['name']   = str_replace(",","，",I('name'));
        $goods['sale_price']=I('sale_price');
        $goods['num']=I('num');
        $goods['type']=I('goods_type');
        $goods['level_limit'] = I('level_limit');
        $goods['buy_limit']=I('buy_limit');
        $goods['type']=I('type');
//        $goods['classify']=I('classify');
//        $goods['recommend']=I('recommend');
        $goods['coin']=1;
//        print_r($goods['is_exchange']);die;
        $upload = new \Think\Upload();// 实例化上传类
//        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =      'data/upload/shop/'; // 设置附件上传根目录
        $info = $upload->uploadOne($_FILES['img']);
//        print_r($info);die;
        if(!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        }
        $file_img= '/data/upload/shop/'.$info['savepath'].$info['savename'];
//        $file_img2= '/data/upload/shop/'.$info[1]['savepath'].$info[1]['savename'];
        $goods["img"]=$file_img;
//        $goods["list_img"]=$file_img2;
        $goods['introduction']=htmlspecialchars_decode(I('introduction'));
        $goods['add_time']=date("Y-m-d H:i:s",time());
        if(empty($goods['name'])){
            $this->error("请填写商品名称！");
        }elseif(empty($goods['sale_price']))
        {
            $this->error("请填写价格！");
        }
        $result = $this->shop_model->add($goods);
        if($result){
            $this->success("添加成功！");
        }else{
            $this->error('添加失败！');
        }
    }

    //编辑报单商品
    public function bd_edit(){
        $id=intval($_GET['id']);
        $goods = $this->shop_model->where("id="."'$id'")->find();
        $this->assign('goods',$goods);
        $this->display();
    }

    //处理报单编辑商品
    public function bd_edit_post(){
        $img=I('photos_url');
        $list_img=I('list_photos_url');
        $goods['id'] = I('id');
        //$goods['goods_name']=I('goods_name');
        $goods['name'] = str_replace(",","，",I('name'));
        $goods['introduction']=I('introduction');
        $goods['sale_price']=I('sale_price');
        $goods['num']=I('num');
        $goods['level_limit']=I('level_limit');
        $goods['buy_limit']=I('buy_limit');
//        $goods['type']=I('type');
//        $goods['classify']=I('classify');
//        $goods['recommend']=I('recommend');
        $upload = new \Think\Upload();// 实例化上传类
//        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =  SITE_PATH.'data/upload/shop/'; // 设置附件上传根目录
        $info = $upload->uploadOne($_FILES['img']);
//        if(!$info) {// 上传错误提示错误信息
//            $this->error($upload->getError());
//        }
        $file_img= '/data/upload/shop/'.$info['savepath'].$info['savename'];
        if(empty($info)){
            $goods["img"] = $img;
        }else{
            $goods["img"]=$file_img;
        }
        $goods['introduction']=htmlspecialchars_decode(I('introduction'));
        $goods['add_time']=date("Y-m-d H:i:s",time());
        if(empty($goods['name'])){
            $this->error("请填写商品名称！");
        }elseif(empty($goods['sale_price']))
        {
            $this->error("请填写价格！");
        }
        $result = $this->shop_model->save($goods);
        if($result){
            $this->success("修改成功！");
        }else{
            $this->error('修改失败！');
        }
    }

}