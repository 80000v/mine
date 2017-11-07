<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class MessageController extends AdminbaseController{

    //系统留言下的财务留言，type=3
    public function message(){

        $d=I("get.d");
        $p = I("get.p");
        if(empty($p)){
            $p = 1;
        }
        if($d=="1"){  //已处理
            $w["status"]=1;
        }elseif($d=="2"){  //未处理
            $w["status"]=0;
        }
        $w["parent_id"] = "0";
        $w["type"] =3;
        $this->assign("d",$d);
        $count = M('message')->where($w)->count();
        $page = $this->page($count,20);
        $message_list = M('message')
            ->where($w)
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($message_list as $k=>$v){
            $message_list[$k]["name"]=getUserNameById($v["uid"]);
            $message_list[$k]["mobile"]=getMobile($v["uid"]);
        }
        $show=$page->show("admin");
        $this->assign("show",$show);
        $this->assign('page',$page->show('Admin'));
        $this->assign('message_list',$message_list);
        $this->assign("u",U("Message/message"));
        $this->display();
    }

    //系统留言下的商城留言 type=2
    public function message_s(){

        $d=I("get.d");
        $p = I("get.p");
        if(empty($p)){
            $p = 1;
        }
        if($d=="1"){  //已处理
            $w["status"]=1;
        }elseif($d=="2"){  //未处理
            $w["status"]=0;
        }
        $w["parent_id"] = "0";
        $w["type"] =2;
        $this->assign("d",$d);
        $count = M('message')->where($w)->count();
        $page = $this->page($count,20);
        $message_list = M('message')
            ->where($w)
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($message_list as $k=>$v){
            $message_list[$k]["name"]=getUserNameById($v["uid"]);
            $message_list[$k]["mobile"]=getMobile($v["uid"]);
        }
        $show=$page->show("admin");
        $this->assign("show",$show);
        $this->assign('page',$page->show('Admin'));
        $this->assign('message_list',$message_list);
        $this->assign("u",U("Message/message_s"));
        $this->display();
    }

    //系统留言下的财务留言的搜索 type=3
    public function message_search(){
        $mobile = I("mobile");
        $uid=getId($mobile);
        if(empty($uid)){
            $this->error("请输入正确的号码");
        }
        $result=M("message")->where(array("uid"=>$uid,"parent_id"=>"0","type"=>"3"))->select();
        foreach($result as $k=>$v){
            $result[$k]["name"]=getUserNameById($uid);
            $result[$k]["mobile"]=getMobile($v["uid"]);
        }
        $this->assign("search",$result);
        $this->assign("u",U("Message/message"));
        $this->display();
    }

    //系统留言下的商城留言的搜索 type=2
    public function message_s_search(){
        $mobile = I("mobile");
        $uid=getId($mobile);
        if(empty($uid)){
            $this->error("请输入正确的号码");
        }
        $result=M("message")->where(array("uid"=>$uid,"parent_id"=>"0","type"=>"2"))->select();
        foreach($result as $k=>$v){
            $result[$k]["name"]=getUserNameById($uid);
            $result[$k]["mobile"]=getMobile($v["uid"]);
        }
        $this->assign("search",$result);
        $this->assign("u",U("Message/message_s"));
        $this->display();
    }

    public function message_shop_search(){
        $mobile = I("mobile");
        $uid=getId($mobile);
        if(empty($uid)){
            $this->error("请输入正确的电话！");
        }
        $result=M("message_shop")->where(array("uid"=>$uid,"parent_id"=>"0"))->select();
        foreach($result as $k=>$v){
            $result[$k]["name"]=getUserNameById($uid);
            $result[$k]["mobile"]=getMobile($v["uid"]);
        }
        $this->assign("search",$result);
        $this->display();

    }

    //商城留言

    public function message_shop(){
        $count = M('message_shop')->where(array("parent_id"=>"0"))->count();
        $page = $this->page($count,20);
        $message_shop_list = M('message_shop')
            ->where("parent_id=0")
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        foreach($message_shop_list as $k=>$v){

            $message_shop_list[$k]["name"]=getUserNameById($v["uid"]);
        }



        $this->assign('page',$page->show('Admin'));
        $show=$page->show("admin");
        $this->assign('message_shop_list',$message_shop_list);
        $this->assign("show",$show);

        $this->display();


    }

    //系统回复留言
    public function info_message(){
        $id=I("id");
        $message=M("message")->where('id='."'$id'")->find();
        $message2=M("message")->where('parent_id='."'$id'")->select();
        $message['nicename']=getUserNameById($message['uid']);
        $this->assign('message',$message);
        $this->assign('message2',$message2);
        $this->display();
    }

    //系统回复留言
    public function info_reply(){

        $id=I("post.id");
        $note=I("post.note");
        $data["content"]=$note;
        $data["add_time"]=date("Y-m-d H:i:s",time());
        $data["parent_id"]=$id;
        $data["status"]="1";
        $data["reply_type"]="0";
        $result=M("message")->add($data);
        if($result){
            M("message")->where(array("id"=>$id))->save(array("status"=>"1","isread"=>"1"));
            $this->success();
        }else{
            $this->error();
        }

    }

    //系统留言删除
    public function message_del(){
        $id=I("id");
        if(!empty($id)){
            $result=M("message")->where(array("id"=>$id))->delete();
            if($result){
                $this->success('处理成功！');
            }else{
                $this->error("删除失败！");
            }
        }

    }


    //商场留言
    public function info_message_shop(){
        $id=I("id");
        $message=M("message_shop")->where('id='."'$id'")->find();
        $message2=M("message_shop")->where('parent_id='."'$id'")->select();
        $message['nicename']=getUserNameById($message['uid']);
        $this->assign('message',$message);
        $this->assign('message2',$message2);
        $this->display();
    }

    //商城留言回复
    public function info_reply_shop(){
        $id=I("post.id");
        $note=I("post.note");
        $data["content"]=$note;
        $data["add_time"]=date("Y-m-d H:i:s",time());
        $data["parent_id"]=$id;
        $data["status"]="1";
        $data["reply_type"]="0";
        $result=M("message_shop")->add($data);
        if($result){
            M("message_shop")->where(array("id"=>$id))->save(array("staus"=>"1","isread"=>"1"));
            $this->success();
        }else{
            $this->error();
        }
    }

    //商城留言删除
    public function message_del_shop(){
        $id=I("id");
        if(!empty($id)){
            $result=M("message_shop")->where(array("id"=>$id))->delete();
            if($result){
                $this->success('处理成功！');
            }else{
                $this->error("删除失败！");
            }
        }
    }

    private function type($t)
    {
        $p=I("get.p");
        $d=I("get.d");
        if($d=="1"){  //已处理
            $w["status"]=1;
        }elseif($d=="2"){  //未处理
            $w["status"]=0;
        }
        $this->assign("d",$d);
        $type=$t;
        if(empty($p)){
            $p=1;
        }
        if(!empty($type)){
            $w["type"]=$type;
        }


        $w["parent_id"]=0;

        $count=M("message")->where($w)->count();
        $page = $this->page($count,20);
        $message = M("message")
            ->where($w)
            ->order("status,add_time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        //给留言人加上真实姓名
        //print_r($message);exit;
        foreach ($message  as $key=>$val) {
            $message[$key]['nicename']=getUserNameById($val['uid']);
//            $message[$key]['mobile']=M("message")->where(array("id"=>$val['uid']))->getField("mobile");
        }
        // $this->assign("page", $page->show('Admin'));
        $show=$page->show("admin");
        $this->assign("show",$show);
        $this->assign('message',$message);
        $this->display("message");
    }



    //其他问题
    public function t1(){
        $this->assign("u",U("Message/t1"));
        $this->assign("t",1);
        $this->type(1);
    }
    //软件问题
    public function t2(){
        $this->assign("u",U("Message/t2"));
        $this->assign("t",2);
        $this->type(2);
    }
    //财务问题
    public function t3(){
        $this->assign("u",U("Message/t3"));
        $this->assign("t",3);
        $this->type(3);
    }
    //变更问题
    public function t4(){
        $this->assign("u",U("Message/t4"));
        $this->assign("t",4);
        $this->type(4);
    }
    //操作问题
    public function t5(){
        $this->assign("u",U("Message/t5"));
        $this->assign("t",5);
        $this->type(5);
    }

    private function type_shop($t)
    {
        $p=I("get.p");
        $d=I("get.sd");
        if($d=="1"){  //已处理
            $w["status"]=1;
        }elseif($d=="2"){  //未处理
            $w["status"]=0;
        }
        $this->assign("sd",$d);
        $type=$t;
        if(empty($p)){
            $p=1;
        }
        if(!empty($type)){
            $w["type"]=$type;
        }


        $w["parent_id"]=0;

        $count=M("message_shop")->where($w)->count();
        $page = $this->page($count,20);
        $message = M("message_shop")
            ->where($w)
            ->order("status,add_time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        //给留言人加上真实姓名
        //print_r($message);exit;
        foreach ($message  as $key=>$val) {
            $message[$key]['nicename']=getUserNameById($val['uid']);
            $message[$key]['mobile']=M("message")->where(array("id"=>$val['uid']))->getField("mobile");
        }
        // $this->assign("page", $page->show('Admin'));
        $show=$page->show("admin");
        $this->assign("show",$show);
        $this->assign('message',$message);
        $this->display("index_shop");
    }
    //产品发货，收货等问题
    public function st1(){
        $this->assign("u",U("Message/st1"));
        $this->assign("st",1);
        $this->type_shop(1);
    }
    //产品售后等问题
    public function st2(){
        $this->assign("u",U("Message/st2"));
        $this->assign("st",2);
        $this->type_shop(2);
    }
    //永恒币充值等问题
    public function st3(){
        $this->assign("u",U("Message/st3"));
        $this->assign("st",3);
        $this->type_shop(3);
    }
    //培训产品等问题
    public function st4(){
        $this->assign("u",U("Message/st4"));
        $this->assign("st",4);
        $this->type_shop(4);
    }
}