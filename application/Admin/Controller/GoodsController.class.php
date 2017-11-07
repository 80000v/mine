<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class GoodsController extends AdminbaseController{

    public function goodsreview(){

        $count = M('goods_reviews')->count();
        $page = $this->page($count,20);
        $review_list = M('goods_reviews')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();


        $this->assign('page',$page->show('Admin'));
        $show=$page->show("admin");
        $this->assign('review_list',$review_list);
        $this->assign("show",$show);
        $this->display();

    }

    //删除商品评论
    public function review_delete(){
        $id=trim(I("id"));
        if(!(M("goods_reviews")->where(array("id"=>$id))->find())){
            $this->error("操作失败，请刷新重试");
        }
        if(M("goods_reviews")->where(array("id"=>$id))->delete()){
            $this->success("删除成功",U('goodsreview'));
        }

    }

    //查询商品评论
    public function review_search(){
        $uid=I("post.uid");
        $goods_id=I("goods_id");
        if(empty($uid)&&empty($goods_id)){
            $this->error("请输入正确的商品和用户！");
        }

    }



}