<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class UserController extends AdminbaseController{

	protected $users_model,$role_model;

	public function _initialize() {
		parent::_initialize();
		$this->users_model = D("Common/Users");
		$this->role_model = D("Common/Role");
	}

	// 管理员列表
	public function index(){
		$where = array("user_type"=>1);
		/**搜索条件**/
		$user_login = I('request.user_login');
		$user_email = trim(I('request.user_email'));
		if($user_login){
			$where['user_login'] = array('like',"%$user_login%");
		}

		if($user_email){
			$where['user_email'] = array('like',"%$user_email%");;
		}

		$count=$this->users_model->where($where)->count();
		$page = $this->page($count, 20);
        $users = $this->users_model
            ->where($where)
            ->order("create_time DESC")
            ->limit($page->firstRow, $page->listRows)
            ->select();
		$roles_src=$this->role_model->select();
		$roles=array();
		foreach ($roles_src as $r){
			$roleid=$r['id'];
			$roles["$roleid"]=$r;
		}
		$this->assign("page", $page->show('Admin'));
		$this->assign("roles",$roles);
		$this->assign("users",$users);
		$this->display();
	}

	// 管理员添加
	public function add(){
		$roles=$this->role_model->where(array('status' => 1))->order("id DESC")->select();
		$this->assign("roles",$roles);
		$this->display();
	}

	// 管理员添加提交
    function add_post()
    {
        if(IS_POST){
            if(!empty($_POST['role_id']) && is_array($_POST['role_id'])){
                if (empty($_POST["mobile"])) {
                    $this->error("手机号不能为空！");
                }
                $role_ids=$_POST['role_id'];
                unset($_POST['role_id']);
                if ($this->users_model->create()) {
                    $result=$this->users_model->add();
                    if ($result!==false) {
                        $role_user_model=M("RoleUser");
                        foreach ($role_ids as $role_id){
                            $role_user_model->add(array("role_id"=>$role_id,"user_id"=>$result));
                        }
                        $this->success("添加成功！", U("user/index"));
                    } else {
                        $this->error("添加失败！");
                    }
                } else {
                    $this->error($this->users_model->getError());
                    //$this->error("添加失败！");
                }
            }else{
                $this->error("请为此用户指定角色！");
            }

        }
    }

	// 管理员编辑
	public function edit(){
	    $id = I('get.id',0,'intval');
		$roles=$this->role_model->where(array('status' => 1))->order("id DESC")->select();
		$this->assign("roles",$roles);
		$role_user_model=M("RoleUser");
		$role_ids=$role_user_model->where(array("user_id"=>$id))->getField("role_id",true);
		$this->assign("role_ids",$role_ids);

		$user=$this->users_model->where(array("id"=>$id))->find();
		$this->assign($user);
		$this->display();
	}

	// 管理员编辑提交
	public function edit_post(){
		if (IS_POST) {
			if(!empty($_POST['role_id']) && is_array($_POST['role_id'])){
				if(empty($_POST['user_pass'])){
					unset($_POST['user_pass']);
				}
				$role_ids = I('post.role_id/a');
				unset($_POST['role_id']);
				if ($this->users_model->create()!==false) {
					$result=$this->users_model->save();
					if ($result!==false) {
						$uid = I('post.id',0,'intval');
						$role_user_model=M("RoleUser");
						$role_user_model->where(array("user_id"=>$uid))->delete();
						foreach ($role_ids as $role_id){
							if(sp_get_current_admin_id() != 1 && $role_id == 1){
								$this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
							}
							$role_user_model->add(array("role_id"=>$role_id,"user_id"=>$uid));
						}
						$this->success("保存成功！");
					} else {
						$this->error("保存失败！");
					}
				} else {
					$this->error($this->users_model->getError());
				}
			}else{
				$this->error("请为此用户指定角色！");
			}

		}
	}

	// 管理员删除
	public function delete(){
	    $id = I('get.id',0,'intval');
		if($id==1){
			$this->error("最高管理员不能删除！");
		}

		if ($this->users_model->delete($id)!==false) {
			M("RoleUser")->where(array("user_id"=>$id))->delete();
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}

	// 管理员个人信息修改
	public function userinfo(){
		$id=sp_get_current_admin_id();
		$user=$this->users_model->where(array("id"=>$id))->find();
		$this->assign($user);
		$this->display();
	}

	// 管理员个人信息修改提交
	public function userinfo_post(){
		if (IS_POST) {
			$_POST['id']=sp_get_current_admin_id();
			$create_result=$this->users_model
			->field("id,user_nicename,sex,birthday,user_url,signature")
			->create();
			if ($create_result!==false) {
				if ($this->users_model->save()!==false) {
					$this->success("保存成功！");
				} else {
					$this->error("保存失败！");
				}
			} else {
				$this->error($this->users_model->getError());
			}
		}
	}

	// 停用管理员
    public function ban(){
        $id = I('get.id',0,'intval');
    	if (!empty($id)) {
    		$result = $this->users_model->where(array("id"=>$id,"user_type"=>1))->setField('user_status','0');
    		if ($result!==false) {
    			$this->success("管理员停用成功！", U("user/index"));
    		} else {
    			$this->error('管理员停用失败！');
    		}
    	} else {
    		$this->error('数据传入失败！');
    	}
    }

    // 启用管理员
    public function cancelban(){
    	$id = I('get.id',0,'intval');
    	if (!empty($id)) {
    		$result = $this->users_model->where(array("id"=>$id,"user_type"=>1))->setField('user_status','1');
    		if ($result!==false) {
    			$this->success("管理员启用成功！", U("user/index"));
    		} else {
    			$this->error('管理员启用失败！');
    		}
    	} else {
    		$this->error('数据传入失败！');
    	}
    }

    //实名认证
    public function realname(){
        $server = $_SERVER;
//        print_r($server);die;

        $count = M('realname')->count();
        $page = $this->page($count,20);
        $realname_list = M('realname')
            ->order("status DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($realname_list as $key=>$val){
            $realname_list[$key]['mobile']=getMobile($val['uid']);
        }
        $p=$page->firstRow/$page->listRows+1;
        $this->assign("p",$p);
        $this->assign('page',$page->show('Admin'));
        $show=$page->show("admin");
        $this->assign('realname_list',$realname_list);
        $this->assign('server',$server);
        $this->assign("show",$show);
        $this->display();
    }

    //实名认证不通过
    public function no_realname(){

        $p=I("get.p");
        if(empty($p)){
            $p=1;
        }
        $server = $_SERVER;
        $count = M('realname')->where("status=0")->count();
        $page = $this->page($count,20);
        $realname_list = M('realname')
            ->order("apply_time")
            ->where("status=0")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($realname_list as $key=>$val){
            $realname_list[$key]['mobile']=getMobile($val['uid']);
        }
        //$this->assign('page',$page->show('Admin'));
        $show=$page->show("admin");
        $this->assign('realname_list',$realname_list);
        $this->assign('server',$server);
        $this->assign("show",$show);
        $this->display();
    }

    //实名认证通过
    public function yes_realname(){
        $p=I("get.p");
        if(empty($p)){
            $p=1;
        }
        $server = $_SERVER;
        $count = M('realname')->count();
        $page = $this->page($count,20);
        $realname_list = M('realname')
            ->order("status DESC")
            ->where("status=1")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach($realname_list as $key=>$val){
            $realname_list[$key]['mobile']=getMobile($val['uid']);
        }
        //$this->assign('page',$page->show('Admin'));
        $show=$page->show("admin");
        $this->assign('realname_list',$realname_list);
        $this->assign('server',$server);
        $this->assign("show",$show);
        $this->display();
    }

    //处理实名
    public function deal_realname(){
        $id=I('id');
        $mobile = I("mobile");
        $note = "恭喜您，审核已通过！";
        $uid = getId($mobile);
        $status = intval($_GET['status']);
        $p=I('p');
        if($id){
            $data['id']=$id;
            $data['status']=$status;
            $rst = M('realname')->save($data);
            if($rst){
                if($status==1){
                    //把真实姓名写入到users表中
                    $uid = M('realname')->where("id=".$id)->getField('uid');
                    $realname = M('realname')->where("id=".$id)->getField('realname');
                    M('users')->where('id='."'$uid'")->setField('user_nicename',$realname);
                    M('realname')->where('uid='."'$uid'")->setField('deal_time',time());
                    sendMessage($uid,$note);
                    $this->success('审核成功',U('User/no_realname',array('p'=>$p)));
                }else{
                    $this->success("审核不通过！");
                }
            }else{
                $this->error("审核失败");
            }
        }
    }

    public function deal(){
        $id = I("id");
        $reason=I('reason');
        $mobile = I("mobile");
        $uid = getId($mobile);
        if(empty($reason)){
            $this->error('请选择不通过的理由');
        }
        if($reason=="a"){
            $note = " 未通过原因:"."照片清晰度不够";
            $rst =dealrealname($id,$note);
            if ($rst) {
                sendMessage($uid,$note);
                $this->success("操作成功！", U("user/no_realname"));
            } else {
                $this->error('操作失败！');
            }
        }elseif($reason=="b"){
            $note = " 未通过原因:"."身份证号码或者姓名填写错误";
            $rst =dealrealname($id,$note);
            if ($rst) {
                sendMessage($uid,$note);
                $this->success("操作成功！", U("user/no_realname"));
            } else {
                $this->error('操作失败！');
            }
        }elseif($reason=="c"){
            $note = " 未通过原因:"."年龄不符";
            $rst =dealrealname($id,$note);
            if ($rst) {
                sendMessage($uid,$note);
                $this->success("操作成功！", U("user/no_realname"));
            } else {
                $this->error('操作失败！');
            }
        }elseif($reason=="d"){
            $note = " 未通过原因:"."证件不符";
            $rst =dealrealname($id,$note);
            if ($rst) {
                sendMessage($uid,$note);
                $this->success("操作成功！", U("user/no_realname"));
            } else {
                $this->error('操作失败！');
            }
        } else {
            $this->error('数据传入失败！');
        }

    }

    //实名搜索
    public function search(){
        $type=I('value');
        $key=I('key');
        $User=M('users');
        $server = $_SERVER;
        $this->assign('server',$server);
        $data=$User->where("user_nicename like '%$key%' || mobile like '%$key%'")->find();
        $id=$data['id'];
        $user=M('realname')->where(array("uid"=>$id))->find();
        if(empty($user)){
            $this->error("请输入正确的用户名或手机号！");
        }
        if($type=="realname"){
            $data = M('realname')->where(array("uid"=>$id))
                ->select();
            //print_r($data);exit;
            foreach($data as $key=>$val){
                $data[$key]['mobile']=getMobile($id);
            }
            $this->assign ( "realname_list", $data );
            $this->display ("realname");
        }
        if($type=="no_realname"){
            $data = M('realname')->where(array("uid"=>$id,"status"=>2))
                ->select();
            //print_r($data);exit;
            foreach($data as $key=>$val){
                $data[$key]['mobile']=getMobile($id);
            }
            $this->assign ( "realname_list", $data );
            $this->display ("no_realname");
        }
        if($type=="yes_realname"){
            $data = M('realname')->where(array("uid"=>$id,"status"=>1))
                ->select();
            //print_r($data);exit;
            foreach($data as $key=>$val){
                $data[$key]['mobile']=getMobile($id);
            }
            $this->assign ( "realname_list", $data );
            $this->display ("yes_realname");
        }
    }

    public function del_realname(){
        $rid=I('rid');
        if(empty($rid)){
            $this->error('删除失败');
        }
        if(M("realname")->where(array('id'=>$rid))->delete()){
            $this->success('删除成功！');
        }else{
            $this->error('删除失败');
        }
    }

    //财务总览
    public function financial(){
        $list['score'] = M('users')->sum('score');
        $list['coin'] = M('users')->sum('coin');
        $list['cc'] = M('users')->sum('cc');
        $list['temp_score'] = M('users')->sum('temp_score');
        $list['play_score'] = M('users')->sum('play_score');
        $list['pi_all'] = M('pi')->sum('point');
        $list['cc_buy'] = M('order')->where(array("coin"=>0))->sum('goods_price');
        $list['score_buy'] = M('order')->where(array("coin"=>1))->sum('goods_price');
        $list['tuiguang_tx'] = M('out_point')->where(array("type"=>2,"status"=>1))->sum('point');
        $list['gains_all'] = M('point_list')->where(array("do"=>1,"type"=>4))->sum('point');
        $list['gains_all_tx'] = M('point_list')->where(array("do"=>2,"type"=>4))->sum('point');
        $this->assign('list',$list);
        $this->display();
    }

    //财务每日记录
    public function financial_more(){
        header("Content-type:application/json");
        $re['x']=array();
        $re['y']=array();
        $re['t']='';
        $i=I('i');
        $b_date=I('b_date');
        $e_date=I('e_date');
        $re['error']=0;
        if(empty($b_date) || $b_date>date('Y/m/d') || empty($e_date) || $e_date<$b_date || $e_date>date('Y/m/d')){
            $re['error']=1;
        }
        if($i=='bdzxgml'){
            $the_date=$b_date;
            $re['t']='单日报单中心购买量';
            while(strtotime($the_date) <=  strtotime($e_date)){
                $nt=date('Y-m-d',(strtotime($the_date.' 0:0:0')+86400));
              //  echo $nt;
                $re['x'][]=$the_date;
                $re['y'][]=M('order')
                    ->where(array('coin'=>1,'order_status'=>array('GT',0)))
                    ->where('add_time>="'.$the_date.' 0:0:0"'.' && add_time<"'.$nt.' 0:0:0"')
                    ->sum('goods_price');
                $the_date=$nt;
            }
        }elseif($i=='pdl'){
            $the_date=$b_date;
            $re['t']='单日排单量';
            while(strtotime($the_date) <=  strtotime($e_date)){
                $nt=date('Y-m-d',(strtotime($the_date.' 0:0:0')+86400));
                //  echo $nt;
                $re['x'][]=$the_date;
                $re['y'][]=M('pi')
                    ->where('time>='.strtotime($the_date.' 0:0:0').' && time<'.strtotime($nt.' 0:0:0'))
                    ->sum('point');
              //  ->select(false);
                $the_date=$nt;
            }
        } elseif($i=='tgtx'){
            $the_date=$b_date;
            $re['t']='单日推广钱包提现量';
            while(strtotime($the_date) <=  strtotime($e_date)){
                $nt=date('Y-m-d',(strtotime($the_date.' 0:0:0')+86400));
                //  echo $nt;
                $re['x'][]=$the_date;
                $re['y'][]=M('out_point')
                    ->where(array('type'=>2,'status'=>array('ELT',1)))
                    ->where('add_time>='.strtotime($the_date.' 0:0:0').' && add_time<'.strtotime($nt.' 0:0:0'))
                    ->sum('point');
                //  ->select(false);
                $the_date=$nt;
            }

        } elseif($i=='pdsy'){
            $the_date=$b_date;
            $re['t']='单日排单收益量';
            while(strtotime($the_date) <=  strtotime($e_date)){
                $nt=date('Y-m-d',(strtotime($the_date.' 0:0:0')+86400));
                //  echo $nt;
                $re['x'][]=$the_date;
                $re['y'][]=M('point_list')
                    ->where(array("do"=>1,"type"=>4))
                    ->where('time>='.strtotime($the_date.' 0:0:0').' && time<'.strtotime($nt.' 0:0:0'))
                    ->sum('point');
                //  ->select(false);
                $the_date=$nt;
            }
        } elseif($i=='pdsytx'){
            $the_date=$b_date;
            $re['t']='单日排单收益提现量';
            while(strtotime($the_date) <=  strtotime($e_date)){
                $nt=date('Y-m-d',(strtotime($the_date.' 0:0:0')+86400));
                //  echo $nt;
                $re['x'][]=$the_date;
                $re['y'][]=M('point_list')
                    ->where(array("do"=>2,"type"=>4))
                    ->where('time>='.strtotime($the_date.' 0:0:0').' && time<'.strtotime($nt.' 0:0:0'))
                    ->sum('point');
                //  ->select(false);
                $the_date=$nt;
            }
        }

        echo json_encode($re);
        exit;

    }

    //个人财务查询
    public function user_financial(){
        $mobile = I("mobile");
        $uid = getId($mobile);
        $list['pi_all'] = M('pi')->where(array("uid"=>$uid))->sum('point');
        $list['tc_all'] = M('point_list')->where(array("uid"=>$uid,"type"=>2,"do"=>1))->sum("point");
        $list['sys_sy_all'] = getUserMaxPi($uid);
        $list['tx_all'] = M('out_point')->where(array("uid"=>$uid))->sum('point');
        $list['xf_all'] = M('point_list')->where(array("uid"=>$uid,"do"=>1,"type"=>5))->sum('point');
        $list['sy_all'] = M('point_list')->where(array("uid"=>$uid,"do"=>1,"type"=>4))->sum('point');
        $count = M("point_list")->where(array("uid"=>$uid))->count();
        $page = $this->page($count,20);
        $user_financial_list = M('point_list')
            ->where(array("uid"=>$uid))
            ->order("time DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        foreach ($user_financial_list as $k=>$v){
            $user_financial_list[$k]['name'] = getUserNameById($v['uid']);
            $user_financial_list[$k]['mobile'] = getMobile($v['uid']);
        }
        $show=$page->show("admin");
        $this->assign("show",$show);
        $this->assign("user_financial_list",$user_financial_list);
        $this->assign("list",$list);
        $this->display();
    }

    public function user_send(){
        $this->display();
    }

    public function user_send_do(){
        $mobile = I("mobile");
        $note = I("note");
        $result = $this->sendSmsToUser($mobile,$note);
        if($result == 1){
            $this->success("发送成功");
        }else{
            $this->error("发送失败");
        }
    }

    public function user_send_all(){
        $this->display();
    }

    public function user_send_all_do(){
        $pwd = I("post.pwd");
        $note = I("note");
        $admin_id = session("ADMIN_ID");
        $pass = sp_password($pwd);
        $admin_pass = M("users")->where(array("id"=>$admin_id))->getField("user_pass");
        if($pass != $admin_pass){
            $this->error("请输入正确的登录密码");
        }
        $user = M("users")->where(array("user_type"=>2))->select();
        $i = 0;
        foreach($user as $k=>$v){
            $r = $this->sendSmsToUser($v['mobile'],$note);
            if($r){
                $i += 1;
            }
        }
        if($i > 0){
            $this->success("发送成功，一共发出".$i."条");
        }else{
            $this->error("发送失败");
        }

    }
    //后台给用户发短信
    public function sendSmsToUser($mobile,$note){
        $post_data = array();

        $url='http://sms.253.com/msg/send';
        $post_data['un'] ="N7729183";//账号
        $post_data['pw'] = "wA6muWd1DJe6f2";//密码
        $post_data['phone'] =$mobile;//手机
        $post_data['rd']=1;

        $msg=$note;
        if(!$msg){
            return false;
        }
        $post_data['msg']=$msg;

        $res=http_request($url,http_build_query($post_data));
        if(!$res){
            return false;
        }else{
            return true;
        }
    }

}