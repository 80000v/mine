<?php
namespace User\Controller;

use Common\Controller\AdminbaseController;

class IndexadminController extends AdminbaseController {
    
    // 后台本站用户列表
    public function index(){
        $where=array();
        $request=I('request.');
        $time0 = date("Y-m-d 0:0:0");
        $time_end = date("Y-m-d H:i:s");

        $map['create_time'] = array('between',array($time0,$time_end));
        $data['people_num'] = M('users')->where($map)->count();
        
        if(!empty($request['uid'])){
            $where['id']=intval($request['uid']);
        }
        
        if(!empty($request['keyword'])){
            $keyword=$request['keyword'];
            $keyword_complex=array();
            $keyword_complex['user_login']  = array('like', "%$keyword%");
            $keyword_complex['user_nicename']  = array('like',"%$keyword%");
            $keyword_complex['user_email']  = array('like',"%$keyword%");
            $keyword_complex['_logic'] = 'or';
            $where['_complex'] = $keyword_complex;
        }
        
    	$users_model=M("Users");
    	
    	$count=$users_model->where($where)->count();
    	$page = $this->page($count, 20);
    	
    	$list = $users_model
    	->where($where)
    	->order("create_time DESC")
    	->limit($page->firstRow . ',' . $page->listRows)
    	->select();
    	//获取领导人的信息
        foreach ($list as $k=>$v){
            $list[$k]['parent_mobile']=getMobile($v['parent_id']);
            $list[$k]['parent_name']=getUserNameById($v['parent_id']);
            $list[$k]['pd']=M('pi')->where(array("uid"=>$v['id'],"status"=>0))->sum('point');
        }
        $this->assign('data',$data);
    	$this->assign('list', $list);
    	$this->assign("page", $page->show('Admin'));
    	
    	$this->display(":index");
    }

    //搜索用户
    public function search(){
        $mobile = I("mobile");
        $name = I("name");
        $type = I("type");
        if(!empty($mobile)){
            $data = M('users')->where(array("mobile"=>$mobile))->select();
            foreach ($data as $k=>$v){
                $data[$k]['parent_mobile']=getMobile($v['parent_id']);
                $data[$k]['parent_name']=getUserNameById($v['parent_id']);
                $data[$k]['pd'] = M('pi')->where(array("uid"=>$v['id'],"status"=>0))->sum('point');
            }
        }elseif (!empty($name)){
            $data = M("users")->where(array("user_nicename"=>$name))->select();
            foreach ($data as $k=>$v){
                $data[$k]['parent_mobile']=getMobile($v['parent_id']);
                $data[$k]['parent_name']=getUserNameById($v['parent_id']);
                $data[$k]['pd'] = M('pi')->where(array("uid"=>$v['id'],"status"=>0))->sum('point');
            }

        }elseif(!empty($type)){
            if($type=="6"){
                $type=0;
            }
            $p = I("p");
            if(empty($p)){
                $p = 1;
            }
            $count = M("users")->where(array("level"=>$type))->count();
            $page = $this->page($count,20);
            $data = M("users")
                ->where(array("level"=>$type))
                ->limit($page->firstRow . ',' . $page->listRows)
                ->select();
            foreach ($data as $k=>$v){
                $data[$k]['parent_mobile']=getMobile($v['parent_id']);
                $data[$k]['parent_name']=getUserNameById($v['parent_id']);
                $data[$k]['pd'] = M('pi')->where(array("uid"=>$v['id'],"status"=>0))->sum('point');
            }
            $this->assign("page", $page->show('Admin'));
            $note = "一共".$count."人";
            $this->assign("note", $note);
        }

//        $this->assign('list',$list);
        $this->assign('list',$data);
        $this->display(":search");
    }
    
    // 后台本站用户禁用
    public function ban(){
    	$id= I('get.id',0,'intval');
    	if ($id) {
    		$result = M("Users")->where(array("id"=>$id,"user_type"=>2))->setField('user_status',0);
    		if ($result) {
    			$this->success("会员拉黑成功！", U("indexadmin/index"));
    		} else {
    			$this->error('会员拉黑失败,会员不存在,或者是管理员！');
    		}
    	} else {
    		$this->error('数据传入失败！');
    	}
    }
    
    // 后台本站用户启用
    public function cancelban(){
    	$id= I('get.id',0,'intval');
    	if ($id) {
    		$result = M("Users")->where(array("id"=>$id,"user_type"=>2))->setField('user_status',1);
    		if ($result) {
    			$this->success("会员启用成功！", U("indexadmin/index"));
    		} else {
    			$this->error('会员启用失败！');
    		}
    	} else {
    		$this->error('数据传入失败！');
    	}
    }

    //发放排单币
    public function send_pdb(){
        $this->display(":send_pdb");
    }

    public function do_pdb(){
        $mobile = trim(I("post.mobile"));
        $money = trim(I("post.money"));
        if(empty($mobile)){
            $this->error("手机号不能为空");
        }
        if(empty($money)){
            $this->error("金额不能为空");
        }
        $uid = getId($mobile);
        if(empty($uid)){
            $this->error("请输入正确的手机号");
        }
        $is_m = is_numeric($money);
        if(empty($is_m) || $money<=0){
            $this->error("请输入正确的金额");
        }
        $result = M("users")->where(array("id"=>$uid))->setInc("pi_key",$money);
        if($result){
            $add['uid'] = 0;
            $add['activate_id'] = $uid;
            $add['num'] = $money;
            $add['add_time'] = time();
            M('activate_play')->add($add);
            $this->success($money."排单币发放成功！");
        }else{
            $this->error("发放失败");
        }
    }

    //扣除排单币
    public function down_pdb(){
        $this->display(":down_pdb");
    }

    public function do_down_pdb(){
        $mobile = trim(I("post.mobile"));
        $money = trim(I("post.money"));
        if(empty($mobile)){
            $this->error("手机号不能为空");
        }
        if(empty($money)){
            $this->error("金额不能为空");
        }
        $uid = getId($mobile);
        if(empty($uid)){
            $this->error("请输入正确的手机号");
        }
        $is_m = is_numeric($money);
        if(empty($is_m) || $money<=0){
            $this->error("请输入正确的金额");
        }
        $uid = getId($mobile);
//        $info = getUserInfoById($uid);
//        $num = $info['pi_key'];
        $result = M("users")->where(array("id"=>$uid))->setDec("pi_key",$money);
        if($result){
            $add['uid'] = 0;
            $add['activate_id'] = $uid;
            $add['num'] = $money;
            $add['add_time'] = time();
            $add['type'] = 2;
            M('activate_play')->add($add);
            $this->success($money."排单币扣除成功！");
        }else{
            $this->error("扣除失败");
        }
    }

    //更换推荐关系页面
    public function replace(){
        $this->display(":replace");
    }

    //处理转换推荐关系
    public function do_replace(){
        $leader_name = I("post.leader_name");
        $leader_mobile = I("post.leader_mobile");
        $mobile = I("post.mobile");
        if(empty($leader_name) ||empty($leader_mobile) ||empty($mobile)){
            $this->error("所填信息不能为空");
        }
        $pid = getId($leader_mobile);
        if(empty($pid)){
            $this->error("请输入正确的领导人手机号");
        }
        $l_name = getUserNameById($pid);
        if($leader_name != $l_name){
            $this->error("请输入正确的领导人信息");
        }
        $uid = getId($mobile);
        if(empty($uid)){
            $this->error("请输入正确的被转换人的手机号");
        }
        $data["parent_id"] = $pid;
        $resuult = M("users")->where(array("id"=>$uid))->save($data);
        if($resuult){
            $this->success("关系转换成功");
        }else{
            $this->error("关系转换失败");
        }
    }

    //排单记录
    public function pi_index(){
        $p = I("p");
        $key = I("key");
        $uid = getId($key);
        if($uid){
            $w["uid"] = $uid;
        }else{
            $w = 1;
        }
        if(empty($p)){
            $p = 1;
        }
        $count = M("pi")->where($w)->count();
        $page = $this->page($count,20);
        $list = M("pi")
            ->where($w)
            ->order("time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($list as $k=>$v){
            $info = getUserInfoById($v["uid"]);
            $list[$k]["mobile"] = $info["mobile"];
            $list[$k]["user_nicename"] = $info["user_nicename"];
        }
        $this->assign('list', $list);
        $this->assign("page", $page->show('Admin'));
        $this->display(":pi_index");
    }


    //排单币发放记录
    public function pdb_log(){
        $p = I("p");
        $key = I("key");
        $uid = getId($key);
        if($uid){
            $w["activate_id"] = $uid;
        }
        if(empty($p)){
            $p = 1;
        }
        $w['uid'] = 0;
        $count = M("activate_play")->where($w)->count();
        $page = $this->page($count,20);
        $list = M("activate_play")
            ->where($w)
            ->order("add_time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($list as $k=>$v){
            $info = getUserInfoById($v["activate_id"]);
            $list[$k]["mobile"] = $info["mobile"];
            $list[$k]["user_nicename"] = $info["user_nicename"];
        }
        $this->assign('list', $list);
        $this->assign("page", $page->show('Admin'));
        $this->display(":pdb_log");
    }

    // 会员删除
    public function delete(){
        $id = I('get.id');
        if($id==1){
            $this->error("最高管理员不能删除！");
        }

        if ( M('users')->delete($id)!==false) {
            $this->success("删除成功！");
        } else {
            $this->error("删除失败！");
        }
    }


    //查询团队
    public function team_one(){
        $this->display(":team_one");
    }

    //查询一级团队
    public function team_one_search(){
        $key = I("key");
        $time_start1 = I("time_start");
        $time_end1 = I("time_end");
        $time_start = $time_start1." 00:00:00";
        $time_end = $time_end1." 23:59:59";
        $uid = getId($key);
        $name = getUserNameById($uid);
//        print_r($key);die;
        if(empty($time_start1)||empty($time_end1)||$time_start1>$time_end1){
            $this->error("请检查时间");
        }
        if(empty($uid)){
            $this->error("请输入正确的手机号");
        }
        $p = I("get.p");
        if(empty($p)){
            $p = 1;
        }
        $pp = '';
        $parent_id = M("users")->where(array("parent_id"=>$uid))->field("id")->select();

        foreach($parent_id as $k=>$v){

            $pp .= $v["id"].',';
        }
        $aa = $pp."0";
//        $money1 = M("users")->where("id in ($aa) && user_type=2")->sum("score");
//        $money2 = M("pi")->where("uid in ($aa) && status=0 ")->sum("point");
        $money = M("order")->where("uid in ($aa) && coin=1 ")->sum("goods_price");
        $count = M("users")->where(array("parent_id"=>$uid))->count();
        if($count==0){
            $this->error("该用户暂无一级团队");
        }
        $page = $this->page($count,20);
        $list = M("users")->where(array("parent_id"=>$uid))->select();
        foreach ($list as $k=>$v){
            $list[$k]['parent_mobile']=getMobile($v['parent_id']);
            $list[$k]['parent_name']=getUserNameById($v['parent_id']);
            $list[$k]['pd'] = M('pi')->where(array("uid"=>$v['id'],"status"=>0))->sum('point');

        }
        $count2 = $this->count1($uid);
        $count1 = count($count2);
        $money3=0;
        for ($i=0;$i<=$count1;$i++){
//            $count2['m'] = M('users')->where(array("id"=>$count2[$i],"user_type"=>2))->sum("score");
//            $count2['a'] = M('pi')->where(array("uid"=>$count2[$i],"status"=>0))->sum("point");
            $count3 = M("order")->where(array("uid"=>$count2[$i],"add_time"=>array("between","$time_start,$time_end"),"coin"=>1))->sum("goods_price");
            $money3 += $count3;
        }

        $data = $name."的一级团队，一共".$count."人,一级团队总业绩为：".$money."；团队总人数：".$count1.",所选时间段的总团队业绩：".$money3;
        $this->assign('list', $list);
        $this->assign('data', $data);
        $this->assign("page", $page->show('Admin'));
        $this->display(":team_one");
    }

    //查询团队总人数
    public function count1($uid,$time_start1,$time_end1,$result=array()){
        global $result;
        $list = M("users")->where(array("parent_id"=>$uid))->select();
        if(!empty($list)){
            foreach ($list as $k=>$v){
                $result[] = $v['id'];
                $this->count1($v['id'],$result);
            }
        }
        return $result;
    }


    //查询二级团队
    public function team_two_search(){
        $key = I("key");
        $uid = getId($key);
        $name = getUserNameById($uid);
//        print_r($key);die;
        if(empty($uid)){
            $this->error("请输入正确的手机号");
        }
        $p = I("get.p");
        if(empty($p)){
            $p = 1;
        }
        $pp = '';
        $parent_id = M("users")->where(array("parent_id"=>$uid))->field("id")->select();
        foreach($parent_id as $k=>$v){

            $pp .= $v["id"].',';
        }
        $aa = $pp."0";
        $count = M("users")->where("parent_id in ($aa) &&user_type=2")->count();
        if($count==0){
            $this->error("该用户暂无二级团队");
        }
        $page = $this->page($count,20);
        $list = M("users")->where("parent_id in ($aa) &&user_type=2")->select();
        $team_id = M("users")->where("parent_id in ($aa) &&user_type=2")->field("id")->select();
        $bb = '';
        foreach($team_id as $k=>$v){

            $bb .= $v["id"].',';
        }
        $cc = $bb."0";
        $money1 = M("users")->where("id in ($cc) && user_type=2")->sum("score");
        $money2 = M("pi")->where("uid in ($cc) && status=0 ")->sum("point");
        $money = $money1 + $money2;
        foreach ($list as $k=>$v){
            $list[$k]['parent_mobile']=getMobile($v['parent_id']);
            $list[$k]['parent_name']=getUserNameById($v['parent_id']);
            $list[$k]['pd'] = M('pi')->where(array("uid"=>$v['id'],"status"=>0))->sum('point');
        }
        $data = $name."的二级团队，一共".$count."人,二级团队总业绩为：".$money;
        $this->assign('list', $list);
        $this->assign('data', $data);
        $this->assign("page", $page->show('Admin'));
        $this->display(":team_one");
    }


    public function user_block_point(){
        $do = I("do");
        $do_id = I("do_id");
        if($do=='del' && !empty($do_id)){
            if(M('block_point')->where(array('id'=>$do_id))->delete()){
                $this->success('删除成功！');exit;
            }else{
                $this->error('删除失败！');exit;
            }
        }elseif($do=='add'){
            $mobile=I("mobile");
            $point=I("point");
            $ui=getUserInfoByMobile($mobile);
            if(!$ui){
                $this->error('手机号码不正确！');exit;
            }
            if($point<=0 || empty($point)){
                $this->error('请输入合适的冻结积分数！');exit;
            }
            if(M('block_point')->where(array('uid'=>$ui['id']))->find()){
                $this->error('该用户已添加冻结记录，请删除后再次添加！');exit;
            }
            if(M('block_point')->add(array("uid"=>$ui['id'],'point'=>$point))){
                $this->success('添加成功！');exit;
            }else{
                $this->error('操作失败！');exit;
            }
        }
        $w='';
        $select_mobile=I('select_mobile');
        if($select_mobile){
            $this->assign('select_mobile', $select_mobile);
            $ui=getUserInfoByMobile($select_mobile);
            if(!$ui){
                $this->error('手机号码不正确！');exit;
            }
            $w=array('uid'=>$ui['id']);
        }
        $p = I("p");
        if(empty($p)){
            $p = 1;
        }
        $count = M('block_point')->where($w)->count();
        $page = $this->page($count,20);
        $list=M('block_point')
            ->where($w)
            ->order('id desc')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach ($list as $k=>$v){
            $ui=getUserInfoById($v['uid']);
            $list[$k]['user_nicename']=$ui['user_nicename'];
            $list[$k]['mobile']=$ui['mobile'];
        }

        $this->assign('list', $list);
        $this->assign("page", $page->show('Admin'));
        $this->display(":user_block_point");
    }

    private function level_string($level){
        if($level == 0){
            return "经销商";
        }elseif($level == 1){
            return "初级经销商";
        }elseif($level == 2){
            return "中级经销商";
        }elseif($level == 3){
            return "高级经销商";
        }elseif($level == 4){
            return "经理";
        }elseif($level == 5){
            return "总监";
        }else{
            return "未知等级";
        }
    }

    //等级变更导表
    public function level_order_push(){
        $type = I("post.type");
        if($type == 7){
            $this->error("请选择等级");
        }
        if($type == 6){
            $type = 0;
        }
        $title = I("post.title");
        $et = I("post.et");
        $date = strtotime($et);
        if(empty($date)){
            $this->error("请选择时间");
        }
        if($date>time()){
            $this->error("请选择正确的时间");
        }
        $orders_header=array(
            "姓名","手机号","之前的等级","目前的等级","等级变更时间");
        $orders = M("level_log")->where(array("new_level"=>$type,"time"=>array("lt",$date)))->select();
        if(empty($orders)){
            $this->error("暂无符合要求的用户");
        }
        foreach($orders as $k=>$v){
            $uid = $v['uid'];
            $name = getUserNameById($uid);
            $mobile = getMobile($uid);
            $orders_list[$k]['name'] = $name;
            $orders_list[$k]['mobile'] = $mobile;
            $orders_list[$k]['old_level'] = $this->level_string($v['old_level']);
            $orders_list[$k]['new_level'] = $this->level_string($v['new_level']);
            $orders_list[$k]['time'] = date("Y-m-d H:i:s",$v['time']);
            unset($uid);
            unset($name);
            unset($mobile);
        }
        array_unshift($orders_list,$orders_header);
        $file_name=$et."之前等级变更到".$this->level_string($type)."的名单";
        push_excel($orders_list,$file_name);
    }
}
