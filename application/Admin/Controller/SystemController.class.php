<?php
/**
 * Created by PhpStorm.
 * User: zcy
 * Date: 2017/3/18
 * Time: 11:23
 * 系统设置
 */

namespace Admin\Controller;
use Common\Controller\AdminbaseController;

class SystemController extends AdminbaseController
{
    protected $system_model;
    public function _initialize() {
        parent::_initialize();
        $this->system_model = M("setting");
    }
    //核心设置明细
    public function system()
    {
        $data=$this->system_model->select();
        $this->assign("sys", $data );
        $this->display();

    }
    //设置处理核心制度提交
    public function system_post(){
        if(IS_POST){
            $post = I('post.');
            foreach ($post as $k=>$v){
                $data['value']=$v;
                $result=$this->system_model->where(array('name'=>$k))->save($data);
            }
            if ($result!==false) {
                $this->success("保存成功！");
            } else {
                $this->error("保存失败！");
            }
        }
    }

    //交易所设置
    public function exchange(){
        $info = M("exchange")->where(array("delete"=>"0"))->select();
        $this->assign("info",$info);
        $this->display();
    }

    //添加交易所
    public function exchange_add(){
        $name = trim(I("post.name"));
        $price = trim(I("post.price"));
        if(empty($name) || empty($price) || $price < 0){
            $this->error("请检查后再填写");
        }
        $add['name'] = $name;
        $add['price'] = $price;
        $result = M("exchange")->add($add);
        if($result){
            $this->success("添加成功",U("system/exchange"));
        }else{
            $this->error("添加失败");
        }
    }

    //编辑页面
    public function exchange_edit(){
        $id = I("get.id");
        if(empty($id)){
            $this->error("页面有误");
        }
        $info = M("exchange")->where(array("id"=>$id))->find();
        $this->assign("info",$info);
        $this->display();
    }

    //处理编辑
    public function exchange_edit_post(){
        $id = I("post.id");
        $name = trim(I("post.name"));
        $price = trim(I("post.price"));
        if(empty($name) || empty($price) || $price < 0){
            $this->error("请检查后再填写");
        }
        $save['name'] = $name;
        $save['price'] = $price;
        $result = M("exchange")->where(array("id"=>$id))->save($save);
        if($result){
            $this->success("更新成功",U("system/exchange"));
        }else{
            $this->error("更新失败");
        }
    }

    //删除交易所
    public function exchange_delete(){
        $id = I("get.id");
        if(empty($id)){
            $this->error("页面有误");
        }
        $save['delete'] = "1";
        $result = M("exchange")->where(array("id"=>$id))->save($save);
        if($result){
            $this->success("删除成功",U("system/exchange"));
        }else{
            $this->error("删除失败");
        }
    }
}