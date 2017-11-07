<?php
namespace Common\Model;
use Common\Model\CommonModel;
class UsersModel extends CommonModel
{
	
	protected $_validate = array(
		//array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
		array('user_login', 'require', '用户名称不能为空！', 1, 'regex', CommonModel:: MODEL_INSERT  ),
		array('user_pass', 'require', '密码不能为空！', 1, 'regex', CommonModel:: MODEL_INSERT ),
		array('user_login', 'require', '用户名称不能为空！', 0, 'regex', CommonModel:: MODEL_UPDATE  ),
		array('user_pass', 'require', '密码不能为空！', 0, 'regex', CommonModel:: MODEL_UPDATE  ),
		array('user_login','','用户名已经存在！',0,'unique',CommonModel:: MODEL_BOTH ), // 验证user_login字段是否唯一
	    array('mobile','','手机号已经存在！',0,'unique',CommonModel:: MODEL_BOTH ), // 验证mobile字段是否唯一
		array('user_email','require','邮箱不能为空！',0,'regex',CommonModel:: MODEL_BOTH ), // 验证user_email字段是否唯一
		array('user_email','','邮箱帐号已经存在！',0,'unique',CommonModel:: MODEL_BOTH ), // 验证user_email字段是否唯一
		array('user_email','email','邮箱格式不正确！',0,'',CommonModel:: MODEL_BOTH ), // 验证user_email字段格式是否正确
	);
	
	protected $_auto = array(
	    array('create_time','mGetDate',CommonModel:: MODEL_INSERT,'callback'),
	    array('birthday','',CommonModel::MODEL_UPDATE,'ignore')
	);
	
	//用于获取时间，格式为2012-02-03 12:12:12,注意,方法不能为private
	function mGetDate() {
		return date('Y-m-d H:i:s');
	}
	
	protected function _before_write(&$data) {
		parent::_before_write($data);
		
		if(!empty($data['user_pass']) && strlen($data['user_pass'])<25){
			$data['user_pass']=sp_password($data['user_pass']);
		}
	}

	//记录登陆日志
	public function save_log($mobile){
		$data['mobile']=$mobile;
		$data['content']="登陆";
		$data['login_ip']=getenv("HTTP_X_FORWARDED_FOR");
		$data['login_time']=date('Y-m-d H:i:s',time());
		$user = M('login_log');
		$user->add($data);
		return true;
	}

	/*
	 * 获取系统留言
	 * @param $uid 用户id
	 * @return  array
	 *  */
	public function get_message($uid){
//		$map['type'] = array('gt',1);
		$list =M("message")->where(array("uid"=>$uid,"parent_id"=>0))->order("add_time desc")->select();
		if($list){
			return $list;
		}else{
			return false;
		}
	}

	//财务
	public function get_message_money($uid){
		$list =M("message")->where(array("uid"=>$uid,"parent_id"=>0,"type"=>1))->order("add_time desc")->select();
		if($list){
			return $list;
		}else{
			return false;
		}
	}

	/*
	 * 增加系统反馈
	 * @param $uid 用户id
	 * @param $content  内容
	 * @return  bool
	 * */
	public function message_add($uid,$content,$type){
		$data['uid']=$uid;
		$data['content']=$content;
		$data['type']=$type;
		$data['add_time']=date('Y-m-d H:i:s',time());
		$result =M('message')->add($data);
		if($result){
			return true;
		}else{
			return false;
		}
	}


	/*
	 * 获取商城留言
	 * @param $uid 用户id
	 * @return  array
	 *  */
	public function get_message_shop($uid){
		$list = M("message_shop")->where(array("uid"=>$uid,"parent_id"=>0))->order("add_time desc")->select();
		if($list){
			return $list;
		}else{
			return false;
		}
	}

	/*
	 * 增加商城留言
	 * @param $uid 用户id
	 * @param $content  内容
	 * @return  bool
	 * */
	public function message_shop_add($uid,$content,$type)
	{
		$data['uid'] = $uid;
		$data['content'] = $content;
		$data['type'] = $type;
		$data['add_time'] = date('Y-m-d H:i:s', time());
		$result = M("message_shop")->add($data);
		if ($result) {
			return $result;
//	public function deal_realname($realname,$idcard,$uid){
//		$data['uid']=$uid;
//		$data['realname']=$realname;
//		$data['idcard']=$idcard;
//		$data['apply_time']=time();
//		$users=M('realname')->where("uid="."'$uid'")->find();
//		if($users){
//			//如果有就更新 未审核可以更新
//			if($users['status']=='0'||$users['status']=='2'){
//				//删除之前的图片
//				$file = $users['img'];
//				@unlink($_SERVER['DOCUMENT_ROOT'] .$file);
//				$data['img']=$this->saveIdImg($mobile,$xmlstr);
//				$data['status']='0';
//				$result=M('realname')->where("uid=".$uid)->save($data);
//			}else{
//				$result= false;
//			}
//		}else{
//			$result=M('realname')->add($data);
//		}
//		return $result;
//
		}
	}


	public function is_realname($uid){
		$status=M('realname')->where(array("uid"=>$uid))->find();
		if($status) {
			return "1";
		}else{
			return "0";
		}
	}

	/*
	 * @access public
     * @param string $mobile 手机号
     * @param string $xmlstr 数据流
     * @param string $realname 真实姓名
     * @param string $idcard 身份证号
     * @param string $uid 用户id
     * @return bool
	 *处理实名制
	 * */
	public function deal_realname($mobile,$xmlstr,$realname,$idcard,$uid){
		$data['uid']=$uid;
		$data['realname']=$realname;
		$data['idcard']=$idcard;
		$data['apply_time']=time();
		$users=M('realname')->where("uid="."'$uid'")->find();
		if($users){
			//如果有就更新 未审核可以更新
			if($users['status']=='0'||$users['status']=='2'){
				//删除之前的图片
				$file = $users['img'];
				@unlink($_SERVER['DOCUMENT_ROOT'] .$file);
				$data['img']=$this->saveIdImg($mobile,$xmlstr);
				$data['status']='0';
				$result=M('realname')->where("uid=".$uid)->save($data);
			}else{
				$result= false;
			}
		}else{
			$data['img']=$this->saveIdImg($mobile,$xmlstr);
			$result=M('realname')->add($data);
		}
		return $result;

	}
	/*
	 * @access public
     * @param string $mobile 手机号
     * @param string $xmlstr 数据流
     * @return bool
	 *保存身份证图片
	 * */

	public function saveIdImg($mobile,$xmlstr){
		//print_r($xmlstr);exit;
		if(is_string($xmlstr)){
			$xmlstr=$xmlstr;
		}else{
			//$filename = $mobile."_".time().".jpg";
			$upload = new \Think\Upload();// 实例化上传类
			$upload->maxSize   =     3145728 ;// 设置附件上传大小
			$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
			$upload->rootPath  =      './data/upload/idcard/'; // 设置附件上传根目录
			// 上传单个文件
			$info   =   $upload->uploadOne($xmlstr['img']);
			//print_r($info);exit;
			if(!$info) {// 上传错误提示错误信息
				$this->error($upload->getError());
			}else{// 上传成功 获取上传文件信息
				$img = 'data/upload/idcard/'.$info['savepath'].$info['savename'];
				//print_r($img);exit;
				//return $img;
				$image = new \Think\Image();
				$image->open($img);
// 按照原图的比例生成一个最大为150*150的缩略图并保存为thumb.jpg
				$image->thumb(600, 600)->save($img);
				//$img=$image;
				//print_r($image);exit;
				return $img;
			}
		}
		$time= date('Y-m', time());
		$filename = $time."/".$mobile."_".time().".jpg";
		if (!is_dir($_SERVER['DOCUMENT_ROOT']."/data/upload/idcard/".$time."/")){
			mkdir($_SERVER['DOCUMENT_ROOT']."/data/upload/idcard/".$time."/");
		}
		$jpg = base64_decode($xmlstr);//得到post过来的二进制原始数据

		file_put_contents($_SERVER['DOCUMENT_ROOT'] ."/data/upload/idcard/".$filename,$jpg);
		$img = "/data/upload/idcard/".$filename;
		return $img;
	}

	/*
	 * @access public
	 * @param string  $id 用户id
	 * @returne striing
	 * 验证实名状态
	 * */
	public function validate_name($uid){
		$users = M('realname')->where('uid='."'$uid'")->find();
		if($users){
			$status=$users['status'];
			if($status=='0'||$status=='2'){
				$data['status']=$status;
				$data['realname']=$users['realname'];
				$data['idcard']=$users['idcard'];
				$data['img']=$users['img'];
				$data['note']=$users['note'];
			}else{
				$data['status']=$status;
			}
		}else{
			$data['status']='3';
		}

		return $data;
	}


	public function message_info($id){
		$message=M('message')->where(array("id"=>$id))->select();
		$message_reply = M('message')->where(array("parent_id"=>$id))->order("add_time asc")->select();
		$result = array_merge($message,$message_reply);
		foreach ($result as $k=>$v){
			$result[$k]['year']=date('Y-m-d',strtotime($v['add_time']));
			$result[$k]['time']=date('H:i:s',strtotime($v['add_time']));
//			return "1";

		}

		//更新查看状态
		M('message')->where(array("id"=>$id))->save(array("isread"=>"0"));
		return $result;

	}



	public function message_shop_info($id){
		$message=M('message_shop')->where('id='."$id")->select();
		$message_reply = M('message_shop')->where('parent_id='."$id")->order("add_time asc")->select();
		$result = array_merge($message,$message_reply);
		foreach ($result as $k=>$v){
			$result[$k]['year']=date('Y-m-d',strtotime($v['add_time']));
			$result[$k]['time']=date('H:i:s',strtotime($v['add_time']));
		}
		//更新查看状态
		M('message_shop')->where('id='."$id")->save(array("isread"=>"0"));
		return $result;
	}

	//出场操作
    function out_pi($pi_id,$uid=0){
	    $w['id']=$pi_id;
	    $w['status']=0;
	    if($uid!=0){
            $w['uid']=$uid;
        }
        $pi=M("pi")->where($w)->find();
        if(!$pi){
            return "1";
        }
        if(($pi['time']+config_get_vv("block_days")*86400)>time()) {
        //if(($pi['time']+300)>time()) {  //30分钟
            return "2";
        }

        if($pi['point']<=1000){
            //小于三千排单算体验 收益15%
            $pr=15;
        }else{
            //收益钱包记录-----------
            $pr=(int)config_get_vv('pi_rate');  //收益比例
            //排单轮数
            $cc=get_pi_num($pi['uid']);

            if($cc>5) {  //轮数大于5论收益多2%  变成12%
                $pr+=2;
            }
        }


//        //收益钱包记录-----------
//        $pr=(int)config_get_vv('pi_rate');  //收益比例
//        //排单轮数
//        $cc=get_pi_num($pi['uid']);
//
//        if($cc>=5) {  //轮数大于5论收益多2%  变成12%
//            $pr+=2;
//        }


        M("pi")->where(array("id"=>$pi_id))->save(array('out_point'=>($pr/100*$pi['point']),"status"=>1,"out_time"=>time()));

        //本金返还
        addMoney($pi['uid'],$pi['point'],1,"排单出场",3);
        addMoney($pi['uid'],($pi['point']*$pr/100*(1-(int)config_get_vv('rate')/100-(int)config_get_vv('management_rate')/100)),1,"排单出场分红",4);
        sendMessage($pi['uid'],"排单出场，在收益钱包扣除管理费".($pi['point']*$pr/100*(int)config_get_vv('management_rate')/100)."积分");
        addSysPoint($pi['uid'],$pi['point']*$pr/100*(int)config_get_vv('management_rate')/100,'用户出场手续费');
        //消费钱包记录
        addMoney($pi['uid'],($pi['point']*$pr/100*(int)config_get_vv('rate')/100),1,"排单出场分红",5);

        return "0";
    }

    //直推购买额
    private function next_put($uid){
        $next_users=M("users")->where(array("parent_id"=>$uid,"block"=>0))->select();
        $na=0;
        foreach ($next_users as $v){
            $na+= M("pi")->where(array("uid"=>$v['id']))->sum('point');
        }
        return $na;
    }

    //团队分红
    private function team_get($point,$uid){
        if($point<=0){
            return false;
        }
        $up=getParentInfoById($uid);
        if(!$up){
            return false;
        }
        if($up['level']>=5){
            $rate=(float)(config_get_vv('level_three_rate')/100);
        }elseif ($up['level']>=4){
            $rate=(float)(config_get_vv('level_two_rate')/100);
        }elseif ($up['level']>=3){
            $rate=(float)(config_get_vv('level_one_rate')/100);
        }else{
            $rate=0;
        }
        $the_point=$point;
        if($rate>0){
            $up_point=getUserMaxPi($up['id']);
            if($up_point<$point){
                $the_point=$up_point;
            }else{
                $the_point=$point;
            }

            addMoney($up['id'],$the_point*$rate*(1-(int)config_get_vv('tg_rate')/100),1,"团队分红",2);
            addMoney($up['id'],$the_point*$rate*(int)config_get_vv('tg_rate')/100,1,"团队分红",5);
        }
        $this->team_get($the_point,$up['id']);
    }

    public function tg($uid,$point){
        //上级用户推广奖励
        $up_u=getParentInfoById($uid);
        if($up_u){
            count_level($up_u['id']);
            if(getUserLevelById($up_u['id'])>0){
                $up_point=getUserMaxPi($up_u['id']);
                if($up_point<$point){
                    $the_point=$up_point;
                }else{
                    $the_point=$point;
                }
                if($the_point>0){
                    $um1=$the_point*(int)config_get_vv("one_rate")/100;
                    addMoney($up_u['id'],$um1*(1-(int)config_get_vv('tg_rate')/100),1,"一级团队提成",2);
                    addMoney($up_u['id'],$um1*(int)config_get_vv('tg_rate')/100,1,"一级团队提成",5);
                }
            }
        }

        //上上级用户推广奖励
        $up_u2=getParentInfoById($up_u['id']);
        if($up_u2){
            count_level($up_u['id']);
            if(getUserLevelById($up_u2['id'])>1){
                $up_point2=getUserMaxPi($up_u['id']);
                if($up_point2<$point){
                    $the_point2=$up_point2;
                }else{
                    $the_point2=$point;
                }
                if($the_point2>0){
                    $um2=$the_point2*(int)config_get_vv("two_rate")/100;
                    addMoney($up_u2['id'],$um2*(1-(int)config_get_vv('tg_rate')/100),1,"二级团队提成",2);
                    addMoney($up_u2['id'],$um2*(int)config_get_vv('tg_rate')/100,1,"二级团队提成",5);
                }
            }
        }
        //团队分红
        $this->team_get($point,$uid);
    }

}

