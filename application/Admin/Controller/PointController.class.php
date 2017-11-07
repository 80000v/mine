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

class PointController extends AdminbaseController
{
    protected $order_model;
    public function _initialize() {
        parent::_initialize();
    }
    //财产明细
    public function index()
    {

        $p=I("get.p");
        if(empty($p)){
            $p=1;
        }
        $count= $this->order_model->count();
        $page = $this->page($count,20);
        //$this->assign("page", $page->show('Admin'));
        $data = $this->order_model
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

    //激活码中心
    public  function key(){
        $p = I("get.p");
        if(empty($p)){
            $p=1;
        }
        $mobile = I('post.mobile');
        //print_r($mobile);exit;
        if(!empty($mobile)){
           $id = getId($mobile);
            if(empty($id)){
                $this->error('请输入正确的手机号！');
            }else{
                $condition['uid'] = $id;
                $condition['activate_id'] = $id;
                $condition['_logic'] = 'OR';
            }
        }
        $count= M('Activate_code')->where($condition)->count();
        $page = $this->page($count,20);
        $data = M('Activate_code')
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->where($condition)
            ->select();
        if(empty($data)){
            $this->error('数据暂无！');
        }
        foreach($data as $key=>$val){
            $data[$key]['mobile']=getMobile($val['uid']);
            $data[$key]['add_time']=date('Y-m-d H:i:s', $val['add_time']); ;
            $data[$key]['activate_mobile']=getMobile($val['activate_id']);
        }
        $show=$page->show("admin");
        $this->assign ( "code", $data );
        $this->assign("show",$show);
        $this->display ();

    }

    //管理员发放记录
    public function key_log(){
        $p = I("get.p");
        if(empty($p)){
            $p=1;
        }
        $key = I("key");
        if($key){
            $uid = getId($key);
            if(empty($uid)){
                $this->error("请输入正确的手机号");
            }
            $w['activate_id'] = $uid;
            $w['uid'] = 0;
        }else{
            $w['uid'] = 0;
        }
        $count = M("activate_code")->where($w)->count();
        $page = $this->page($count,20);
        $data = M('Activate_code')
            ->where($w)
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($data as $k=>$v){
            $info = getUserInfoById($v['activate_id']);
            $data[$k]["name"] = getUserNameById($v['activate_id']);
            $data[$k]["mobile"] = $info['mobile'];
        }
        $show=$page->show("admin");
        $this->assign ( "code", $data );
        $this->assign("show",$show);
        $this->display();
    }

    //发放激活码页面
    public function key_send(){
        $this->display();
    }

    //发放激活码
    public function key_send_do(){
        $mobile = I("mobile");
        $type = I("type");
        $num = I("num");
        if(empty($mobile)||empty($type)||empty($num)){
            $this->error("请填写完整资料");
        }
        $num_id = getId($mobile);
        if(empty($num_id)){
            $this->error("请输入正确的手机号");
        }
        $num_key = M("users")->where(array("id"=>$num_id))->getField('key');
        if($type=="1"){
            $num_result["key"] = $num_key+$num;
            $add["uid"] = "0";
            $add["num"] = $num;
            $add["activate_id"] = $num_id;
            $add["add_time"] = time();
            $add["type"] = 0;
            $add["do"] = 1;
            M("activate_code")->add($add);
            $result = M("users")->where(array("id"=>$num_id))->save($num_result);
        }elseif($type=="2"){
            if($num>$num_key){
                $this->error("激活码数量不足");
            }else{
                $num_result["key"] = $num_key-$num;
                $add["uid"] = "0";
                $add["num"] = $num;
                $add["activate_id"] = $num_id;
                $add["add_time"] = time();
                $add["type"] = 0;
                $add["do"] = 2;
                M("activate_code")->add($add);
                $result = M("users")->where(array("id"=>$num_id))->save($num_result);
            }
        }
        if($result){
            $this->success("操作成功");
        }
    }

    //排单记录
    public function pi_list(){
        $p=I("get.p");
        if(empty($p)){
            $p=1;
        }
        $count= M('pi')->count();
        $page = $this->page($count,20);
        $data = M('pi')
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        foreach($data as $key=>$val){
            $data[$key]['mobile']=getMobile($val['uid']);
            $data[$key]['user_nicename']=getUserNameById($val['uid']);
        }
        $today=strtotime(date("Y-m-d 0:0:0"));
        $total=M('pi')->where("time>=$today")->sum('point');
        $this->assign('total',$total);
        $show=$page->show("admin");
        $this->assign ( "order", $data );
        $this->assign("show",$show);
        $this->display ();
    }

    //已删除排单记录
    public function pi_list_delete(){
        $p=I("get.p");
        if(empty($p)){
            $p=1;
        }
        $count= M('pi_delete')->count();
        $page = $this->page($count,20);
        $data = M('pi_delete')
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        foreach($data as $key=>$val){
            $data[$key]['mobile']=getMobile($val['uid']);
            $data[$key]['user_nicename']=getUserNameById($val['uid']);
        }
        $today=strtotime(date("Y-m-d 0:0:0"));
        $total=M('pi')->where("time>=$today")->sum('point');
        $this->assign('total',$total);
        $show=$page->show("admin");
        $this->assign ( "order", $data );
        $this->assign("show",$show);
        $this->display ();
    }

    public function  pi_search(){
        $mobile = I('mobile');
        $date = I('date');
        $p=I("get.p");
        $total='';
        if(empty($p)){
            $p=1;
        }
        if(!empty($mobile)){
            $count= M('pi')->where(array('uid'=>getId($mobile)))->count();
            $page = $this->page($count,20);
            $condition['uid'] = getId($mobile);
            $data = M('pi')
                ->order("id DESC")
                ->limit($page->firstRow . ',' . $page->listRows)
                ->where($condition)
                ->select();
            if(empty($data)){
                $this->error('数据暂无！');
            }
            foreach($data as $key=>$val){
                $data[$key]['mobile']=$mobile;
                $data[$key]['user_nicename']=getUserNameById($val['uid']);
            }

        }
        if(!empty($date)){
            $date=strtotime($date);
            $end_time=$date+24*3600;
            $search_total=M('pi')->where("time>=$date and time<$end_time")->sum('point');
            $count= M('pi')->where("time>=$date and time<$end_time")->count();
            $page = $this->page($count,20);
            $data = M('pi')
                ->order("id DESC")
                ->limit($page->firstRow . ',' . $page->listRows)
                ->where("time>=$date and time<$end_time")
                ->select();
            if(empty($data)){
                $this->error('数据暂无！');
            }
            foreach($data as $key=>$val){
                $data[$key]['mobile']=getMobile($val['uid']);
                $data[$key]['user_nicename']=getUserNameById($val['uid']);
            }
            $this->assign('search_time',I('date'));
            $this->assign('search_total',$search_total);
        }
        $today=strtotime(date("Y-m-d 0:0:0"));
        $total=M('pi')->where("time>=$today")->sum('point');
        $this->assign('total',$total);
        $show=$page->show("admin");
        $this->assign ( "order", $data );
        $this->assign("show",$show);
        $this->display ('pi_list');
    }

    //搜索已删除排单记录
    public function pi_search_delete(){
        $mobile = I('mobile');
        $p=I("get.p");
        $total='';
        if(empty($p)){
            $p=1;
        }
        if(!empty($mobile)){
            $count= M('pi_delete')->where(array('uid'=>getId($mobile)))->count();
            $page = $this->page($count,20);
            $condition['uid'] = getId($mobile);
            $data = M('pi_delete')
                ->order("id DESC")
                ->limit($page->firstRow . ',' . $page->listRows)
                ->where($condition)
                ->select();
            if(empty($data)){
                $this->error('数据暂无！');
            }
            foreach($data as $key=>$val){
                $data[$key]['mobile']=$mobile;
                $data[$key]['user_nicename']=getUserNameById($val['uid']);
            }
            $this->assign('total',$total);
            $show=$page->show("admin");
            $this->assign ( "order", $data );
            $this->assign("show",$show);
            $this->display ('pi_list_delete');
        }
    }

    //单子币记录中心
    public function score_send(){
        if($_POST){
            $mobile = I("mobile");
            $type = I("type");
            $num = I("num");
            $is_m = is_numeric($num);
            if(empty($is_m) || $num<=0){
                $this->error("请输入正确的电子币个数");
            }
            if(empty($mobile)||empty($type)||empty($num)){
                $this->error("请填写完整资料");
            }
            $num_id = getId($mobile);
            if(empty($num_id)){
                $this->error("请输入正确的手机号");
            }
            if($type=="1"){
                $note = "后台发放";
                $result = addMoney($num_id,$num,1,$note,1);
                if($result){
                    $add['uid'] = "0";
                    $add['num'] = $num;
                    $add['activate_id'] = $num_id;
                    $add['add_time'] = time(); 
                    M('activate_pi')->add($add);
                    $this->success("该用户的".$num."个电子币增加成功！",U("Point/score_send"));
                }else{
                    $this->error("操作失败");
                }
            }elseif($type=="2"){
                $note = "后台扣除";
                $result = addMoney($num_id,$num,2,$note,1);
                if($result){
                    $add['uid'] = "0";
                    $add['num'] = $num;
                    $add['activate_id'] = $num_id;
                    $add['add_time'] = time();
                    $add['do'] = "2";
                    M('activate_pi')->add($add);
                    $this->success("该用户的".$num."个电子币扣除成功！",U("Point/score_send"));
                }else{
                    $this->error("操作失败");
                }
            }
        }
        $this->display();
    }

    //电子币发放记录

    public function score_sendlog(){
        $count = M('point_list')->where(array("type"=>1))->count();
        $page = $this->page($count,20);
        $list = M('point_list')
              ->where(array("type"=>1))
              ->order("time DESC")
              ->limit($page->firstRow . ',' . $page->listRows)
              ->select();
        foreach ($list as $k=>$v){
            $list[$k]['mobile'] = getMobile($v['uid']);
            $list[$k]['user_nicename'] = getUserNameById($v['uid']);
            $list[$k]['num'] = $v['point'];
            $list[$k]['time'] = $v['time'];
        }
        $this->assign('list', $list);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }

    //电子币发送查询
    public function score_search(){
        $mobile = I('key');
        $id = getId($mobile);
        $list = M('point_list')->where(array("uid"=>$id,"type"=>1))->select();
        foreach ($list as $k=>$v){
            $list[$k]['mobile'] = getMobile($v['uid']);
            $list[$k]['user_nicename'] = getUserNameById($v['uid']);
            $list[$k]['num'] = $v['point'];
            $list[$k]['time'] = $v['time'];
        }
        $this->assign('list',$list);
        $this->display("score_sendlog");
    }

    //赠送钱包的扣除
    public function score_update(){
        $this->display();
    }

    //赠送钱包的扣除的处理
    public function score_update_do(){
        $mobile = I("post.mobile");
        $num = I("post.num");
        $money_type = I("post.money_type");
        $type = I("post.type");
        $uid = getId($mobile);
        if(empty($uid)){
            $this->error("请输入正确的手机号");
        }
        if(empty($money_type)){
            $this->error("请选择积分类型");
        }
        if(empty($type)){
            $this->error("请选择操作类型");
        }
        if($num <= 0){
            $this->error("请输入正确的数字");
        }
        $info = getUserInfoById($uid);
        if($money_type == 2){
            $score = $info['coin'];//分享
        }elseif ($money_type == 3){
            $score = $info['play_score'];//漫香积分
        }elseif ($money_type == 4){
            $score = $info['temp_score'];//收益
        }
        $string = wallet_string($money_type);
        if($type == 1){
            $type_string = "增加";
        }elseif ($type == 2){
            $type_string = "扣除";
        }
        if($type == 2 && $num > $score){
            $this->error("该用户的".$string."钱包只有".$score.",请输入合理的数字");
        }
        $result = addMoney($uid,$num,$type,"后台".$type_string,$money_type);
        $adminid = session("ADMIN_ID");
        $content = $type_string."用户".$mobile."id为".$info['id']."的".$string."钱包:".$num."积分,".$type_string."前为：".$score;
        $add = addOperateLog($uid,$content,$adminid);
        if($result && $add){
            $this->success("成功".$type_string."该用户".$string."钱包的".$num."积分",U("Point/score_update"));
        }else{
            $this->error("操作失败");
        }
    }

    //数交所提现审核
    public function withdraw(){
        $key = I("get.key");
        $uid = getId($key);
        $p = I("p");
        if(empty($p)){
            $p = 1;
        }
        $w['status'] = 0;
        $w['exchange_id'] = 1;
        if($uid){
            $w['uid'] = $uid;
        }
        $model = M("out_point");
        $count = $model->where($w)->count();
        $page = $this->page($count,20);
        $list = $model
            ->where($w)
            ->order("add_time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($list as $k=>$val){
            $list[$k]['mobile']=getMobile($val['uid']);
            $list[$k]['user_nicename']=getUserNameById($val['uid']);
        }
        $show=$page->show("admin");
        $this->assign ("list", $list );
        $this->assign("show",$show);
        $this->display();
    }

    //全币网提现审核
    public function withdraw_qb(){
        $key = I("get.key");
        $uid = getId($key);
        $p = I("p");
        if(empty($p)){
            $p = 1;
        }
        $w['status'] = 0;
        $w['exchange_id'] = 2;
        if($uid){
            $w['uid'] = $uid;
        }
        $model = M("out_point");
        $count = $model->where($w)->count();
        $page = $this->page($count,20);
        $list = $model
            ->where($w)
            ->order("add_time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($list as $k=>$val){
            $list[$k]['mobile']=getMobile($val['uid']);
            $list[$k]['user_nicename']=getUserNameById($val['uid']);
        }
        $show=$page->show("admin");
        $this->assign ("list", $list );
        $this->assign("show",$show);
        $this->display();
    }


    //通过申请
    public function yes_withdraw(){
        $id = I("get.id");
        $info = M("out_point")->where(array("id"=>$id))->find();
        if(empty($info)){
           $this->error("系统错误");
        }
        $data_jf['address'] = $info['address'];
        $data_jf['num'] = $info['money'];
        $data_jf['token']=$this->get_token($data_jf);
        $url="http://www.quanbijs.com/money/jf_add";
        $re=request_post($url,$data_jf);
        if($re != "9"){
            $data['status'] = 2;
            $data['reply_time'] = time();
            $result = M("out_point")->where(array("id"=>$id))->save($data);
            $tui = addMoney($info['uid'],$info['point'],1,"兑换申请不通过退回",$info['type']);
            if($result && $tui){
                $this->success("积分已退回原钱包");
            }else{
                $this->error("操作失败");
            }
        }else{
            $data['status'] = 1;
            $data['reply_time'] = time();
            $result = M("out_point")->where(array("id"=>$id))->save($data);
            addSysPoint($info['uid'],$info['poundage'],"用户提现手续费");
            if($result){
                $this->success("操作成功");
            }else{
                $this->error("操作失败");
            }
        }
    }

    //审核通过-多选
    public function yes_withdraw_s(){
        $ids = I("ids");
        $id_arr = explode(',',$ids);
//        print_r($id_arr);die;
        $i = 0;
        $a = 0;
        foreach($id_arr as $oid){
            $oi = M("out_point")
                ->where(array("id"=>$oid))
                ->find();
            if(!$oi){
                continue;
            }

            $data_jf['address'] = $oi['address'];
            $data_jf['num'] = $oi['money'];
            $data_jf['token']=$this->get_token($data_jf);
            $url="http://192.168.0.106/money/jf_add";
            $re=request_post($url,$data_jf);
            if($re != "9"){
                $data['status'] = 2;
                $data['reply_time'] = time();
                $result = M("out_point")->where(array("id"=>$oi['id']))->save($data);
                $tui = addMoney($oi['uid'],$oi['point'],1,"兑换申请不通过退回",$oi['type']);
                if($result && $tui){
                    $a++;
                }
            }else{
                if(M("out_point")->where(array("id"=>$oid))->save(array("status"=>1,"reply_time"=>time()))){
                    addSysPoint($oi['uid'],$oi['poundage'],"用户提现手续费");
                    $i++;
                }
            }
        }
        echo "操作完成，成功".$i.'条,退回'.$a."个";
    }

    //数交所时间区间通过
    public function withdraw_time(){
        $st_time = I("post.st_time");
        $ed_time = I("post.ed_time");
        $exchange_id = I("exchange_id");
        $type = I("post.type");
        if(empty($ed_time)){
            $this->error("请选择时间");
        }
        if(empty($st_time)){
            $st_time = 0;
        }
        $str_time = strtotime($st_time);
        $end_time = strtotime($ed_time)+86399;
        if($str_time > $end_time){
            $this->error("请选择正确的时间");
        }
        $result = M("out_point")->where(array("add_time"=>array("between","$str_time,$end_time"),"exchange_id"=>$exchange_id,"status"=>0,"type"=>$type))->select();
//        echo "<pre/>";
//        print_r($result);die;
        if(empty($result)){
            $this->error("暂无数据需要处理");
        }else{
            $i = 0;
            $a = 0;
            foreach($result as $k => $v){

                $data_jf['address'] = $v['address'];
                $data_jf['num'] = $v['money'];
                $data_jf['token']=$this->get_token($data_jf);
                $url="http://www.quanbijs.com/money/jf_add";
                $re=request_post($url,$data_jf);
                if($re != "9"){
                    $data['status'] = 2;
                    $data['reply_time'] = time();
                    $result = M("out_point")->where(array("id"=>$v['id']))->save($data);
                    $tui = addMoney($v['uid'],$v['point'],1,"兑换申请不通过退回",$v['type']);
                    if($result && $tui){
                        $a++;
                    }
                }else{
                    if(M("out_point")
                        ->where(array("id"=>$v['id']))
                        ->save(array("status"=>1,"reply_time"=>time()))){
                        addSysPoint($v['uid'],$v['poundage'],"用户提现手续费");
                        $i++;
                    }
                }
            }
            $this->success("操作完成，成功".$i.'条,退回'.$a."个",U('Point/withdraw'));
        }
    }

    //驳回申请
    public function no_withdraw(){
        $id = I("get.id");
        $info = M("out_point")->where(array("id"=>$id))->find();
        if(empty($info)){
            $this->error("系统错误");
        }
        $data['status'] = 2;
        $data['reply_time'] = time();
        $result = M("out_point")->where(array("id"=>$id))->save($data);
        $tui = addMoney($info['uid'],$info['point'],1,"兑换申请不通过退回",$info['type']);
        if($result && $tui){
            $this->success("操作成功");
        }else{
            $this->error("操作失败");
        }
    }

    //已通过申请列表
    public function withdraw_yes(){
        $key = I("get.key");
        $uid = getId($key);
        $p = I("p");
        if(empty($p)){
            $p = 1;
        }
        $w['status'] = 1;
        if($uid){
            $w['uid'] = $uid;
        }
        $model = M("out_point");
        $count = $model->where($w)->count();
        $page = $this->page($count,20);
        $list = $model
            ->where($w)
            ->order("add_time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($list as $k=>$val){
            $list[$k]['mobile']=getMobile($val['uid']);
            $list[$k]['user_nicename']=getUserNameById($val['uid']);
        }
        $show=$page->show("admin");
        $this->assign ("list", $list );
        $this->assign("show",$show);
        $this->display();
    }


    //订单导出
    public function order_push(){
        if(IS_POST){
            $type = I("type");
            $bt1=I("bt")." 0:0:0";
            $et1=I("et")." 23:59:59";
            $bt = strtotime($bt1);
            $et = strtotime($et1);
            if(empty($bt) || empty($et)){
                $this->error("请检查所输入日期");
            }
            if($et<$bt){
                $this->error("请检查所输入日期");
            }
            $orders= M("out_point")
                ->where(array("status"=>1,"add_time"=>array("between","$bt,$et"),"type"=>$type))
                ->field("uid,money,address,exchange_id")
                ->select();
            foreach($orders as $k=>$v){
                $orders2[$k]['mobile']=getMobile($v['uid']);
                $orders2[$k]['address']=$v['address'];
                $orders2[$k]['money']=$v['money'];
                $orders2[$k]['bz']="fdc";
            }
            $order_header=array(
                "手机号","钱包地址","兑换数量","币种"
            );
            array_unshift($orders2,$order_header);
            if($type==2){
                $por = "推广钱包";
            }elseif($type==3){
                $por = "本金钱包";
            }else{
                $por = "收益钱包";
            }
            $file_name=$bt1."到".$et1."已完成".$por."提现的列表";
          //  $file_name="申请成功的提现列表";
            push_excel($orders2,$file_name);
        }
    }
    //删除订单。并返回到赠送钱包
    public function delete(){
        $id = I('id');
        if(empty($id)){
            $this->error('系统异常');
        }
        $find = M("pi")->where(array("id"=>$id))->find();
        $save['uid'] = $find['uid'];
        $save['time'] = $find['time'];
        $save['point'] = $find['point'];
        $save['status'] = $find['status'];
        $save['out_time'] = $find['out_time'];
        $save['out_point'] = $find['out_point'];
        $save['delete_time'] = time();
        //删除订单，并返回积分到赠送钱包
        $users=M('pi')->where(array('id'=>$id))->field('uid,point,status')->find();
        if($users['status']==0){
            $result = M('pi')->where(array('id'=>$id))->delete();
        }else{
            $this->error('订单已经出场了。无法删除！');
        }

        if($result){
            M("pi_delete")->add($save);
            $userInfo =getUserInfoById($users['uid']);
            if(!empty($userInfo)){
                addMoney($users['uid'],$users['point'],1,'删除排单',3);
                $this->success('订单删除成功',U("Point/pi_list"));
            }
        }
    }

    //审核数交所列表订单导出
    public function s_order_push(){
        if(IS_POST){
            $type = I("type");
            $bt1=I("bt")." 0:0:0";
            $et1=I("et")." 23:59:59";
            $bt = strtotime($bt1);
            $et = strtotime($et1);
            if(empty($bt) || empty($et)){
                $this->error("请检查所输入日期");
            }
            if($et<$bt){
                $this->error("请检查所输入日期");
            }
            $orders= M("out_point")
                ->where(array("status"=>0,"exchange_id"=>1,"add_time"=>array("between","$bt,$et"),"type"=>$type))
                ->field("uid,money,address")
                ->select();
            foreach($orders as $k=>$v){
                $orders2[$k]['mobile']=getMobile($v['uid']);
                $orders2[$k]['address']=$v['address'];
                $orders2[$k]['money']=$v['money'];
                $orders2[$k]['bz']="fdc";
            }
//               echo "<pre>";
//            print_r($orders);die();
            $order_header=array(
                "手机号","钱包地址","兑换数量","币种"
            );
            array_unshift($orders2,$order_header);
            //  print_r($orders2);die();
            if($type==2){
                $por = "推广钱包";
            }elseif($type==3){
                $por = "本金钱包";
            }else{
                $por = "收益钱包";
            }
            $file_name=$bt1."到".$et1."华中数字交易平台申请".$por."提现的列表";
            //  $file_name="申请成功的提现列表";
            push_excel($orders2,$file_name);
        }
    }

    //审核全币网列表订单导出
    public function s_order_push_qb(){
        if(IS_POST){
            $type = I("type");
            $bt1=I("bt")." 0:0:0";
            $et1=I("et")." 23:59:59";
            $bt = strtotime($bt1);
            $et = strtotime($et1);
            if(empty($bt) || empty($et)){
                $this->error("请检查所输入日期");
            }
            if($et<$bt){
                $this->error("请检查所输入日期");
            }
            $orders= M("out_point")
                ->where(array("status"=>0,"exchange_id"=>2,"add_time"=>array("between","$bt,$et"),"type"=>$type))
                ->field("uid,money,address,exchange_id")
                ->select();
            foreach($orders as $k=>$v){
                $orders2[$k]['mobile']=getMobile($v['uid']);
                $orders2[$k]['address']=$v['address'];
                $orders2[$k]['money']=$v['money'];
                $orders2[$k]['bz']="fdc";
            }
//               echo "<pre>";
//            print_r($orders2);die();
            $order_header=array(
                "手机号","钱包地址","兑换数量","币种"
            );
            array_unshift($orders2,$order_header);
            //  print_r($orders2);die();
            if($type==2){
                $por = "推广钱包";
            }elseif($type==3){
                $por = "本金钱包";
            }else{
                $por = "收益钱包";
            }
            $file_name=$bt1."到".$et1."全币网申请".$por."提现的列表";
            //  $file_name="申请成功的提现列表";
            push_excel($orders2,$file_name);
        }
    }

    //查看管理费
    public function management(){
        $result = M("user_message")->where("content like '排单出场，在收益钱包扣除管理费%'")->select();
        $num = 0;
        foreach($result as $k=>$v){
            preg_match('/\d+/',$v['content'],$matchs);
            $num += $matchs[0];
        }
//        $result1=M("sys_point")->where("note like '%用户提现手续费%'")->sum("point");
        $result1 = M('out_point')->where(array("type"=>3,"status"=>1))->sum('poundage');
        $result2 = M('out_point')->sum('poundage');
//        $data['sy'] = "所获得收益钱包的管理费为：".$num;
//        $data['bj'] = "所获得本金钱包的管理费为：".$result1;
        $data['sy'] = $num;
        $data['tx'] = $result1;
        $data['dh'] = $result2;
        $this->assign("data",$data);
        $this->display();
    }

    //收益钱包管理费
    public function sy_list(){
        $count = M('user_message')->where("content like '排单出场，在收益钱包扣除管理费%'")->count();
        $page = $this->page($count,20);
        $list = M('user_message')
            ->where("content like '排单出场，在收益钱包扣除管理费%'")
            ->order('send_time DESC')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach ($list as $k=>$v){
            $list[$k]['mobile'] = getMobile($v['uid']);
            $list[$k]['name'] = getUserNameById($v['uid']);
        }
        $show = $page->show("admin");
        $this->assign("list",$list);
        $this->assign("show",$show);
        $this->display();
    }

    //提现手续费明细
    public function tx_list(){
        $count = M('out_point')->where(array("type"=>3,"status"=>1))->count();
        $page = $this->page($count,20);
        $list =
//            M('sys_point')
//            ->where("note like '%用户提现手续费%' && point>0")
            M('out_point')->where(array("type"=>3,"status"=>1))
            ->order('add_time DESC')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach ($list as $k=>$v){
            $list[$k]['mobile'] = getMobile($v['uid']);
            $list[$k]['name'] = getUserNameById($v['uid']);
            $list[$k]['note'] = "用户提现手续费";
        }
        $show = $page->show("admin");
        $this->assign("list",$list);
        $this->assign("show",$show);
        $this->display();
    }


    //兑币手续费
//    public function dh_list(){
//        $count = M('out_point')->where("poundage>0")->count();
//        $page = $this->page($count,20);
//        $list = M('out_point')
//            ->where("poundage>0")
//            ->order('add_time DESC')
//            ->limit($page->firstRow . ',' . $page->listRows)
//            ->select();
//        foreach ($list as $k=>$v){
//            $list[$k]['name'] = getUserNameById($v['uid']);
//            $list[$k]['mobile'] = getMobile($v['uid']);
//        }
//        $show = $page->show("admin");
//        $this->assign("list",$list);
//        $this->assign("show",$show);
//        $this->display();
//    }

    public function sy_list_search(){
        $time_start = I('time_start');
        $time_end = I('time_end');
        $time_start1 = $time_start." 0:0:0";
        $time_end1 = $time_end." 23:59:59";
//        print_r($time_start1);die;
        $w['send_time'] = array('between',array($time_start1,$time_end1));
        $count = M('user_message')->where("content like '排单出场，在收益钱包扣除管理费%'")->where($w)->count();
        $page = $this->page($count,20);
        $list = M('user_message')
            ->where("content like '排单出场，在收益钱包扣除管理费%'")
            ->where($w)
            ->order('send_time DESC')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        foreach ($list as $k=>$v){
            $list[$k]['mobile'] = getMobile($v['uid']);
            $list[$k]['name'] = getUserNameById($v['uid']);
        }
        $show = $page->show("admin");
        $this->assign("list",$list);
        $this->assign("show",$show);
        $this->display('sy_list');
    }

    //查询用户收益钱包手续费
    public function sy_list_search1(){
        $mobile = I('mobile');
        $uid = getId($mobile);
        $count = M('user_message')->where(array("uid"=>$uid))->where("content like '排单出场，在收益钱包扣除管理费%'")->count();
        $page = $this->page($count,20);
        $list = M('user_message')
            ->where(array("uid"=>$uid))
            ->where("content like '排单出场，在收益钱包扣除管理费%'")
            ->order('send_time DESC')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach ($list as $k=>$v){
            $list[$k]['mobile']=getMobile($v['uid']);
            $list[$k]['name']=getUserNameById($v['uid']);
        }
        $show = $page->show("admin");
        $this->assign("list",$list);
        $this->assign("show",$show);
        $this->display('sy_list');
    }

    //系统收入
    public function sys_point(){
        $p = I("get.p");
        $mobile = I("mobile");
        $uid = getId($mobile);
        if(empty($p)){
            $p = 1;
        }
        if(!empty($mobile)){
            $w["uid"] = $uid;
        }else{
            $w = array();
        }
        $w['point'] = array('gt',0);
        $count = M("sys_point")->where($w)->count();
        $page = $this->page($count,20);
        $list = M("sys_point")
            ->where($w)
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($list as $k=>$v){
            $list[$k]["mobile"] = getMobile($v['uid']);
            $list[$k]["name"] = getUserNameById($v['uid']);
        }
        $show=$page->show("admin");
        $this->assign("list",$list);
        $this->assign("show",$show);
        $this->display();
    }

    //按时间查询系统收入
    public function sys_time(){
        $start = I("start");
        $end = I("end");
//        print_r($start.$end);die;
        if($start>$end){
            $this->error("时间选择有误");
        }
        $list =
        $this->display();
    }

    //扣除排单金额
    public function pi_edit(){
        $id = I("get.id");
        $info = M("pi")->where(array("id"=>$id))->find();
        $data['name'] = getUserNameById($info['uid']);
        $data['mobile'] = getMobile($info['uid']);
        $data['money'] = $info['point'];
        $data['id'] = $info['id'];
        $this->assign("data",$data);
        $this->display();
    }

    //处理扣除排单金额
    public function pi_edit_do(){
        $id = I("post.id");
        $money = I("post.update_money");
        $info = M("pi")->where(array("id"=>$id))->find();
        if($money > $info['point']){
            $this->error("请输入正确的金额");
        }
        if(empty($money)){
            $money = 0;
        }
        $save['point'] = $money;
        $result = M("pi")->where(array("id"=>$id))->save($save);
        $mobile = getMobile($info['uid']);
        $content = "变更用户".$mobile."id为".$info['uid']."的排单金额为:".$money.",变更前为：".$info['point'];
        $admin_id = session("ADMIN_ID");
        if($result){
            addOperateLog($info['uid'],$content,$admin_id);
            $this->success("扣除成功，该用户目前的排单金额为：".$money,U("point/pi_list"));
        }
    }

    //操作记录
    public function operate_log(){
        $p=I("get.p");
        if(empty($p)){
            $p=1;
        }
        $key = I("get.key");
        $uid = getId($key);
        if($key){
            if(empty($uid)){
                $this->error("请输入正确的手机号");
            }
            $w['uid'] = $uid;
        }else{
            $w = 1;
        }
        $count= M("operate_log")->where($w)->count();
        $page = $this->page($count,20);
        $data = M("operate_log")
            ->where($w)
            ->order("id DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        if(empty($data)){
            $this->error("暂无对该用户的操作记录");
        }
        foreach($data as $key=>$val){
            $data[$key]['mobile']=getMobile($val['uid']);
            $data[$key]['user_nicename']=getUserNameById($val['uid']);
            $admin_user = M("users")->where(array("id"=>$val['admin_id']))->getField("user_login");
            $data[$key]['admin_user']=$admin_user;
            unset($admin_user);
        }
        $show=$page->show("admin");
        $this->assign("order",$data);
        $this->assign("show",$show);
        $this->display();
    }
}