<?php
namespace Mobile\Controller;
use Common\Controller\AppController;
class MessageController extends AppController {
    private function detection(){
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

    //留言列表显示
    public function message_list(){
//        $this->detection();
        $mobile = I("post.mobile");
        $uid = getId($mobile);
        $list = D('Users')->get_message($uid);
        if($list){
            $this->exitJson(0,$list);
        }else{
            $this->exitJson(6105);//没有系统反馈
        }
    }

    //财务留言列表显示
    public function message_list_money(){
        $this->detection();
        $mobile = I("post.mobile");
        $uid = getId($mobile);
        $list = D('Users')->get_message_money($uid);
        if($list){
            $this->exitJson(0,$list);
        }else{
            $this->exitJson(6105);//没有系统反馈
        }
    }


    //反馈类型
    public function message_type(){
        $this->detection();
        $result =M("message_type")->order("id")->select();
        $this->exitJson(0,$result);
    }

//    //财务反馈
//    public function message_type_money(){
//        $this->detection();
//        $result =M("message_type")->where(array("id"=>1))->order("id")->select();
//        $this->exitJson(0,$result);
//    }

    //留言类型
    public function message_shop_type(){
//        $this->detection();
        $result =M("message_shop_type")->order("id")->select();
        $this->exitJson(0,$result);
    }
    //留言
    public function message_add(){
//        $this->detection();
        $mobile = I("post.mobile");
        $uid=getId($mobile);
        $content = I('post.content');

//        $rest = M("blackmessage")->where(array("mobile"=>$mobile))->find();
//        if($rest){
//            $this->exitJson(6109);//您已被禁止留言
//        }
        $type =I("post.type");
        if(empty($content)){
            $this->exitJson(6106);//内容不能为空
        }
        if(empty($type)){
            $this->exitJson(6107);//反馈不能为空
        }

        //过滤脏话
        $allergicWord = array('日你','干','草','操你','肏','艹','鸡','尼玛','你妈','智障','傻逼','SB','sb','死全家','fuck','妈逼','老子','骗子','忽悠');

        for ($i=0;$i<count($allergicWord);$i++){
            $rst = substr_count($content, $allergicWord[$i]);
            if($rst>0){
                $info = $rst;
                break;
            }
        }
        if($info>0){
            $this->exitJson(4005);//存在敏感词，请重新输入
        }

        $a=M("message")->where(array("uid"=>$uid,"content"=>$content))->find();
        if($a){
            $this->exitJson(7016);
        }

        $last_time=M("message")->where(array("uid"=>$uid))->order("add_time DESC")->getField("add_time");
//        if($last_time && (strtotime($last_time)+120)>=time()){
//            $this->exitJson(7017);
//        }

        $result =D('Users')->message_add($uid,$content,$type);
        if($result){
            $this->exitJson(0);
        }else{
            $this->exitJson(7003);//系统错误
        }
    }


    //留言回复列表
    public function reply_info(){
//        $this->detection();
        $id=I('post.id');
        $message =D('Users')->message_info($id);
        $this->exitJson(0,$message);
    }


    //留言回复详情
    public function user_reply(){
//        $this->detection();
        $mobile=I("post.mobile");
        $parent_id = I('post.parent_id');
        $content = I('post.content');
        $date = date("Y-m-d H:i:s",time());
        $message['uid'] = getId($mobile);
        $message['content'] = $content;
        $message['add_time'] = $date;
        $message['parent_id'] = $parent_id;
        M("message")->where('id='."$parent_id")->save(array("status"=>0,"add_time"=>$date));
        $result = M('message')->add($message);
        if($result){
            $this->exitJson(0);
        }else{
            $this->exitJson(8100);
        }
    }


    //商城留言
    public function message_shop_list(){
        $mobile=I("post.mobile");
        $uid=getId($mobile);
        $list = D('Users')->get_message_shop($uid);
        if($list){
            $this->exitJson(0,$list);
        }else{
            $this->exitJson(6105);//没有系统反馈
        }
    }


        //提交商城留言
    public function message_shop_add(){
        $mobile = I("mobile");
        $uid=getId($mobile);
        $content = empty($_POST['content']) ? '' : I('post.content');$mobile = I("post.mobile");
//        $rest = M("blackmessage")->where(array("mobile"=>$mobile))->find();
//        if($rest){
//            $this->exitJson(6109);//您已被禁止留言
//        }
        $type =I("post.type");
        if(empty($content)){
            $this->exitJson(6106);//反馈不能为空
        }
        if(empty($type)){
            $this->exitJson(6107);//类型不能为空
        }
        //过滤脏话
        $allergicWord = array('日你','干','草','操你','肏','艹','鸡','尼玛','你妈','智障','傻逼','SB','sb','死全家','fuck','妈逼','老子','骗子','忽悠');

        for ($i=0;$i<count($allergicWord);$i++){
            $rst = substr_count($content, $allergicWord[$i]);
            if($rst>0){
                $info = $rst;
                break;
            }
        }
        if($info>0){
            $this->exitJson(4005);//存在敏感词，请重新输入
        }

        $a=M("message_shop")->where(array("uid"=>$uid,"content"=>$content))->find();
        if($a){
            $this->exitJson(7016);
        }

        $last_time=M("message_shop")->where(array("uid"=>$uid))->order("add_time DESC")->getField("add_time");
//        if($last_time && (strtotime($last_time)+120)>=time()){
//            $this->exitJson(7017);
//        }

        $result =D('Users')->message_shop_add($uid,$content,$type);
        if($result){
            $this->exitJson(0);
        }else{
            $this->exitJson(7003);//系统错误
        }
    }

    //商城留言回复列表

    public function reply_info_shop(){
        $id=I('id');
        $message =D('Users')->message_shop_info($id);
        $this->exitJson(0,$message);
    }


    //商城用户回复详情
    public function user_reply_shop(){
//        $this->detection();
        $mobile = I("post.mobile");
        $parent_id = I('post.parent_id');
        $content = I('post.content');
        $date = date("Y-m-d H:i:s",time());
        $message['uid'] = getId($mobile);
        $message['content'] = $content;
        $message['add_time'] = $date;
        $message['parent_id'] = $parent_id;
        M("message_shop")->where('id='."$parent_id")->save(array("status"=>0,"add_time"=>$date));
//        M("message_shop")->where('id='."$parent_id")->setField('status','0');
        $result = M('message_shop')->add($message);
        if($result){
            $this->exitJson(0);
        }else{
            $this->exitJson(8100);
        }
    }

    //常见问题
    public function common_problem_list(){
//        $this->detection();
//        $re = M("common_problem")->where(array("post_status"=>1))->select();
//        foreach ($re as $k=>$v){
//            $data[$k]['id'] = $v['id'];
//            $data[$k]['title'] = $v['title'];
//        }

        $result =D('Article')->getArticleList(1);
        if($result){
            foreach($result as $k=>$v){
                $result[$k]['title']=$v['post_title'];
            }
            $this->exitJson(0,$result);
        }else{
            $this->exitJson(61102);//没有常见问题
        }

       // $this->exitJson(0,$result);
    }

    //常见问题详情
    public function common_problem_advice(){
//        $id = I("post.id");
//        $data['content']['post_title'] = M('common_problem')->where(array("id"=>$id))->getField("title");
//        $data['content']['post_date'] = M('common_problem')->where(array("id"=>$id))->getField("time");
//        $data['content']['post_content'] = M('common_problem')->where(array("id"=>$id))->getField("content");
//        $data['next_id']="1";
//        $this->exitJson(0,$data);
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


}