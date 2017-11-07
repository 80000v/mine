<?php
namespace Common\Model;
use Common\Model\CommonModel;
class PassportModel extends CommonModel{
	protected $trueTableName="huayu_users";
	protected $autoCheckFields = false;
    protected $error = null; //如果存在错误的时候返回一下错误
    protected $token;//手机APP 需要的 access_token
	//自动验证
	protected $_validate = array(

	);
    public function getError() {
        return $this->error;
    }
	protected function _before_write(&$data) {
		parent::_before_write($data);
	}

	//判断网站是否关闭
	public function is_open(){
		$status=M('core')->where(array("id"=>1))->getField('status');
		if($status=="1"){
			return true;
		}else{
			return false;
		}
	}

	public function getToken(){
		return $this->token;
	}

	public function register($data = array()) {
		$this->token=md5(uniqid());
		if (empty($data))
			return false;
		$obj = M('users');

		$data['user_pass'] = sp_password($data['user_pass']);
		$user =getUserInfoByMobile($data['mobile']);
		if ($user) {
			$this->error = '该账户已经存在';
			return false;
		}
		$parent_info=getUserInfoByMobile($data['parent_mobile']);
		$data['parent_id']=$parent_info["id"];
		$data['user_type']='2';
		$data['user_status']='1'; //未激活状态
		$id = $obj->add($data);
		if(!$id){
			$this->error=$id;
			return false;
		}
		//计算邀请人的等级
//		$rst=$data['uplevel'];
//		if($rst=='0'){
//			count_level($data['parent_mobile']);
//		}
		return true;
	}

	//登录
	public function login($account, $password) {

		$this->token =md5(uniqid());
		$user = getUserInfoByMobile($account);
        if (empty($user)) {
            $this->error = '无此用户';
            return false;
        }

        /* if ($user['user_status'] == 0) {
             $this->error = '用户被封号！';
             return false;
         }*/
		// MD5.md5(MD5.md5("###"+MD5.md5(MD5.md5("mjfYj78weLYMlIznwU"+password))+"Vby1Y*4Pi_J1Po0W_E0o7YC"))
        if (md5(md5($user['user_pass']."Vby1Y*4Pi_J1Po0W_E0o7YC")) != $password) {
            $this->error = '账号或密码不正确';
            return false;
        }

		//登录时间间隔  XXX
		/* $last_login_time=M("login_log")->where(array("mobile"=>$account))->order("id DESC")->getField("login_time");
         if(!empty($last_login_time) && (strtotime($last_login_time)+60)>time() ){
             $this->error="登录过于频繁，请2分钟后重试！";
             return false;
         }*/

        $ip = get_client_ip(0);
		//记录登陆日志
		if (date('Y-m-d', time()) < TODAY) {
			D('Users')->save_log($user['mobile']);
		}
		$login_time = date('Y-m-d H:i:s',time());

		$data = array(
			'token' => $this->token,
			'last_login_time' => $login_time,
			'last_login_ip' => $ip
		);
		D('Users')->where(array("id"=>$user['id']))->save($data);
//		$rst=$user['uplevel'];
//		if($rst=='0'){
//			count_level($account);
//		}


		return true;
	}

	//修改密码
	public function uppwd($mobile,$newpwd) {
		$user = getUserInfoByMobile($mobile);
		if(!$user){
			return "-1";
		}
		if(sp_password($newpwd)==$user["user_pass"]){
			return "-2";
		}
		return M('users')->where(array("id"=>$user['id']))->save(array("user_pass"=>sp_password($newpwd)));
	}


}