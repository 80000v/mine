<?php
namespace Mobile\Controller;
use Common\Controller\AppController;
class ArticleController extends AppController {
    public function _initialize() {
        parent::_initialize();
    }
    //官方动态 term_id 为2
    public function getDynamic(){
        $result =D('Article')->getArticleList(2);
        if($result){
            $this->exitJson(0,$result);
        }else{
            $this->exitJson(61101);//没有官方动态
        }
    }

    //常见问题 term_id 为1
    public function getHelp(){
        $result =D('Article')->getArticleList(1);
        if($result){
            $this->exitJson(0,$result);
        }else{
            $this->exitJson(61102);//没有常见问题
        }
    }

    //最新动态 term_id 为4
    public function getNews(){
        $result =D('Article')->getArticleList(4);
        if($result){
            $this->exitJson(0,$result);
        }else{
            $this->exitJson(61101);//没有常见问题
        }
    }

    //文章详情展示 1-常见问题  2-公告 3-应急处置额  4-最新动态
    public function getContent(){
        $id=empty($_REQUEST['id'])?'':I('id');
        $mobile=empty($_REQUEST['mobile'])?'':I('mobile');
        if(empty($id)){
            $this->exitJson(61103);//文章id不能为空
        }
        $content = D('Article')->getContent($id);
        if($content){
            //用户查看状态更新
            $uinfo= M('Users')->where(array("mobile"=>$mobile))->find();
            $ptype=M("term_relationships")->where(array("object_id"=>$id))->getField("term_id");
            if($ptype=="2" && strtotime($content["post_date"])>$uinfo["last_get_notice"]){
                M("users")->where(array("mobile"=>$mobile))->save(array("last_get_notice"=>strtotime($content["post_date"])));
            }elseif($ptype=="1" && strtotime($content["post_date"])>$uinfo["last_get_questions"]){
                //M("users")->where(array("mobile"=>$mobile))->save(array("last_get_questions"=>strtotime($content["post_date"])));
            }elseif($ptype=="4" && strtotime($content["post_date"])>$uinfo["last_get_news"]){
               // M("users")->where(array("mobile"=>$mobile))->save(array("last_get_news"=>strtotime($content["post_date"])));
            }
            //下一跳计算
            $all=M("term_relationships")->where(array("term_id"=>$ptype,"status"=>"1"))->order("tid desc")->select();
            $i=0;
            $next_id=0;
            foreach($all as $vo){
                if($i==1){
                    $next_id=$vo["object_id"];
                    break;
                }
                if($vo["object_id"]==$id){
                    $i=1;
                }
            }
            if($next_id==0){
                $next_id=$all[0]["object_id"];
            }
            $re["content"]=$content;
            $re["next_id"]=$next_id."";
            $this->exitJson(0,$re);
        }else{
            $this->exitJson(6014);
        }
    }



    //最新公告
    public function content_new(){
//        $re=M('posts')->where(array("post_status"=>1,"term_id"=>4))->order('post_date desc')->limit(3)->field('post_title,post_date')->find();
//        $this->exitJson(0,$re);
        $result =D('Article')->getArticleList(2);
        if($result){
            $this->exitJson(0,$result);
        }else{
            $this->exitJson(61101);//没有官方动态
        }

    }

    //公告列表
    public function content_list(){
//        $re=M('posts')->where(array("post_status"=>1))->order('post_date desc')->select();
//        foreach ($re as $k=>$v){
//            $data[$k]['post_title'] = $v['post_title'];
//            $data[$k]['post_date'] = $v['post_date'];
//            $data[$k]['id'] = $v['id'];
//        }
//        $this->exitJson(0,$data);
        $result =D('Article')->getArticleList(2);
        if($result){
            foreach($result as $k=>$v){
                $result[$k]['post_date']=$v['post_modified'];
            }
            $this->exitJson(0,$result);
        }else{
            $this->exitJson(61101);//没有官方动态
        }
    }
}
