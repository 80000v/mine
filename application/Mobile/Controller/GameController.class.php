<?php
namespace Mobile\Controller;
use Common\Controller\AppController;
use Org\Util\String;

class GameController extends AppController{

    private $hy_rate;
    private $do;
    private $cost;
    public function _initialize() {
        $this->hy_rate=config_get_vv("hy_rate");
        parent::_initialize();
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
    //游戏信息主页
    public function game_info(){
        $b_time=strtotime(date("Y-m-d 0:0:0",time()));
        $attendance=M("attendance")->where(array("uid"=>$this->uid,"add_time"=>array('EGT',$b_time)))->find();
        if($attendance){
            $re["attendance"]="1";
        }else{
            $re["attendance"]="0";
        }
        $re["nickname"]=$this->userinfo["nickname"];
        $re["level"]=level_string($this->userinfo["level"]);
        $re["string_0"]="积极签到才能免费参与游戏抽奖哟";
        $re["string_1"]="1.每天签到送积分；";
        $re["string_2"]="2.连续签到三天即可免费参与一次欢乐砸金蛋游戏；";
        $re["string_3"]="3.连续签到七天即可免费参与一次幸运大转盘游戏；";
        $re["string_4"]="4.暂无";
        $this->exitJson(0,$re);
    }

    //用户签到
    public function user_attendance(){
        //检查订单增加奖金池
        $sc_orders = M("order")->where(array("uid"=>$this->uid))->where("pay_time + 604800<".time())->where("order_status>=1 && refundable_status=0 && sc_status=0")->select();
        foreach ($sc_orders as $v){
            $ie=M("goods")->where(array("id"=>$v['goods_id']))->getField("is_exchange");
            if($ie=="0"){
                D("Shop")->sc_list_add($v['id']);
            }
            M("order")->where(array("id"=>$v['id']))->save(array("sc_status"=>1));
        }


        //返利计算
        $b_time=strtotime(date("Y-m-d 0:0:0",time()));
        $attendance=M("attendance")->where(array("uid"=>$this->uid,"add_time"=>array('EGT',$b_time)))->find();
        if($attendance){
            $this->exitJson(13001);
        }
        $add["uid"]=$this->uid;
        $add["add_time"]=time();
        if(M("attendance")->add($add)){
            $today_time=strtotime(date("Y-m-d 0:0:0"));
            $sl=M("sc_list")
                ->where("uid=".$this->uid." && ar_t<all_t && last_time<".$today_time)
                ->select();
            $all=0;
            foreach ($sl as $v){
                $all+=$v["point"];
            }
            $all=(int)($all*0.01);
            $add_uid=$this->uid;

            if($all>0){
                $coin_point=(int)($all*$this->hy_rate/100);
                $score_point=$all-$coin_point;
                if($score_point>0){
                    pointAlteration($add_uid,1,1,$score_point,"签到获得积分");
                }
                if($coin_point>0){
                    pointAlteration($add_uid,1,2,$coin_point,"签到获得消费积分");
                }
                foreach ($sl as $v){
                    M("sc_list")->where(array("id"=>$v['id']))
                        ->save(array(
                            "last_time"=>time(),
                            "ar_t"=>($v['ar_t']+1)
                            ));
                }
                //上级奖励
                $parent_info=getParentInfoById($add_uid);
                while($parent_info && $parent_info["id"]!=$add_uid ){
                    $add_uid=$parent_info['id'];
                    if($parent_info["up_level"]!=0){
                        if($parent_info["level"]==1){
                            $parent_get_point=(int)($all*0.1);
                            $today_max_income=10000;
                        }elseif ($parent_info["level"]==2){
                            $parent_get_point=(int)($all*0.2);
                            $today_max_income=20000;
                        }elseif ($parent_info["level"]==3){
                            $parent_get_point=(int)($all*0.3);
                            $today_max_income=30000;
                        }
                        $parent_today_income=getTodayIncome($parent_info['id']);
                        if($parent_get_point>0 && $parent_today_income<$today_max_income){
                            if(($parent_get_point+$parent_today_income)>$today_max_income){
                                $parent_get_point=$today_max_income-$parent_today_income;
                            }
                            $p_coin_point=(int)($parent_get_point*$this->hy_rate/100);
                            $p_score_point=$parent_get_point-$p_coin_point;
                            if($p_score_point>0){
                                pointAlteration($add_uid,1,1,$p_score_point,"奖励积分");
                            }
                            if($p_coin_point>0){
                                pointAlteration($add_uid,1,2,$p_coin_point,"获得消费积分");
                            }
                        }
                    }
                    if($parent_get_point<=0){
                        break;
                    }
                    $parent_info=getParentInfoById($add_uid);
                }

            }

            //游戏次数增加
            countGameTime($this->uid,1);
            countGameTime($this->uid,2);

            $this->exitJson(0,"签到成功");
        }
    }


    //转盘游戏
    public function turntable_game(){

        if(getGameSurplusTime($this->uid,2)<=0){
            $this->cost=M("game_list")->where(array("id"=>2))->getField("cost");
            if($this->userinfo['score']<$this->cost){
                $this->exitJson(13003);
            }else{
                $this->do=0;
            }
        }else{
            $this->do=1;
        }

        $rand=rand(0,10);
        if($rand>=0 && $rand<30){
            $re["item"]="1";
            $r="未中奖";
        }elseif($rand>=30 && $rand<40){
            $re["item"]="2";
            $prize_id=2;
            $r="二等奖";
        }elseif($rand>=40 && $rand<70){
            $re["item"]="3";
            $r="未中奖";
        }elseif($rand>=70 && $rand<80){
            $re["item"]="4";
            $prize_id=4;
            $r="优秀奖";
        }elseif($rand>=80 && $rand<90){
            $re["item"]="5";
            $prize_id=3;
            $r="三等奖";
        }elseif($rand>=90 && $rand<100){
            $re["item"]="6";
            $prize_id=1;
            $r="一等奖";
        }else{
            $re["item"]="1";
            $r="未中奖";
        }
        //$re["surplus_time"]=(string)(getGameSurplusTime($this->uid,2)-1);
        $re["surplus_time"]=(String)((getGameSurplusTime($this->uid,2)-1)<0?0:(getGameSurplusTime($this->uid,2)-1));
        $this->addGameRecord("幸运转盘抽奖",2,$r);
        $re['score']=(string)M('users')->where(array("id"=>$this->uid))->getField("score");
        if($prize_id && M("prize_list")->where(array("id"=>$prize_id))->find()){
            $this->addPrizeRecord($prize_id,2);
        }
        $this->exitJson(0,$re);
    }
    //转盘奖励说明
    public function turntable_game_info(){
        $prize_list=M("prize_list")->select();
        foreach($prize_list as $v){
            $p["".$v['id']]=$v["name"];
        }
        $re["prize_1"]=$p["1"];  //一等奖
        $re["prize_2"]=$p["2"];
        $re["prize_3"]=$p["3"];
        $re["prize_4"]=$p["4"];  //特等奖
        $re["content"]="每天可免费参加幸运大转盘游戏一次(最多5次)或使用100积分参加幸运大转盘一次";
        $re["surplus_time"]=(string)getGameSurplusTime($this->uid,2);
        $re['score']=(string)$this->userinfo['score'];
        $re['cost']=M("game_list")->where(array("id"=>2))->getField("cost");
        $this->exitJson(0,$re);
    }
    //砸金蛋游戏
    public function smashEggs_game(){

        if(getGameSurplusTime($this->uid,1)<=0){
            $this->cost=M("game_list")->where(array("id"=>1))->getField("cost");
            if($this->userinfo['score']<$this->cost){
                $this->exitJson(13003);
            }else{
                $this->do=0;
            }
        }else{
            $this->do=1;
        }
        $rand=rand(0,9);
        if($rand>=0 && $rand<30){
            $re["item"]="未中奖";
        }elseif($rand>=30 && $rand<40){
            $re["item"]="二等奖";
            $prize_id=2;
        }elseif($rand>=40 && $rand<70){
            $re["item"]="未中奖";
        }elseif($rand>=70 && $rand<80){
            $re["item"]="特等奖";
            $prize_id=4;
        }elseif($rand>=80 && $rand<90){
            $re["item"]="三等奖";
            $prize_id=3;
        }elseif($rand>=90 && $rand<100){
            $re["item"]="一等奖";
            $prize_id=1;
        }else{
            $re["item"]="未中奖";
        }
        $re["surplus_time"]=(String)((getGameSurplusTime($this->uid,1)-1)<0?0:(getGameSurplusTime($this->uid,1)-1));
       // $re['score']=(string)$this->userinfo['score'];

        $this->addGameRecord("砸金蛋游戏",1,$re['item']);
        $re['score']=(string)M('users')->where(array("id"=>$this->uid))->getField("score");
        if($prize_id && M("prize_list")->where(array("id"=>$prize_id))->find()){
            $this->addPrizeRecord($prize_id,1);
        }
        $this->exitJson(0,$re);
    }
    //砸金蛋奖励说明
    public function smashEggs_game_info(){
        $prize_list=M("prize_list")->select();
        foreach($prize_list as $v){
            $p["".$v['id']]=$v["name"];
        }
        $re["prize_1"]=$p["1"];  //一等奖
        $re["prize_2"]=$p["2"];
        $re["prize_3"]=$p["3"];
        $re["prize_4"]=$p["4"];  //特等奖
        $re["content"]="欢迎参加砸金蛋游戏";
        $re["surplus_time"]=(String)getGameSurplusTime($this->uid,1);
        $re['score']=(string)$this->userinfo['score'];
        $re['cost']=M("game_list")->where(array("id"=>1))->getField("cost");
        $this->exitJson(0,$re);
    }


    //添加抽奖记录
    private function addGameRecord($note,$gid,$re="再接再厉"){
        $add["uid"]=$this->uid;
        $add["note"]=$note;
        $add["time"]=time();
        $add["gid"]=$gid;
        $add["result"]=$re;
        if($this->do==1){
            M("game_time")->where(array("uid"=>$this->uid,"gid"=>$gid))->setDec("surplus_time",1);
        }else{
            addMoney($this->uid,$this->cost,2,$note,"score");
            sendMessage($this->uid,"您使用".$this->cost."积分进行了一次".$note);
        }
        M("game_record")->add($add);
    }
    //添加中奖记录
    private function addPrizeRecord($prize_id,$type){
        $add["uid"]=$this->uid;
        $add["prize_id"]=$prize_id;
        $add["time"]=time();
        $add["type"]=$type;
        M("prize_record")->add($add);
    }

    //查看中奖记录
    public function prize_lists(){
        $type = I("post.type");
        $p = I("p");
        if(empty($p)){
            $p = 1;
        }
        $result = M("prize_record")->where(array("uid"=>$this->uid,"type"=>$type))->order('time DESC')->page($p.',20')->select();
        $count = M("prize_record")->where(array("uid"=>$this->uid,"type"=>$type))->count();
        foreach($result as $k=>$v){
            $result[$k]["time"] = date("Y-m-d H:i:s",$result[$k]["time"]);
            $a = $result[$k]["prize_id"];
            $result[$k]["name"] = M("prize_list")->where(array("id"=>$a))->getField("name");
            unset($a);
        }
        $data["list"] = $result;
        $data["count"] = $count;
        if($data){
            $this->exitJson(0,$data);
        }
    }

    //查看抽奖记录
    public function game_record(){
        $type = I("post.type");
        $p = I("p");
        if(empty($p)){
            $p = 1;
        }
        $result=array();
        $result0 = M("game_record")->where(array("uid"=>$this->uid,"gid"=>$type))->order('time DESC')->page($p.',20')->select();
        $count = M("game_record")->where(array("uid"=>$this->uid,"gid"=>$type))->count();
        foreach($result0 as $k=>$v){
            $result[$k]["time"] = date("Y-m-d H:i:s",$result0[$k]["time"]);
            $result[$k]["result"] = $result0[$k]["result"];
        }
        $data["list"] = $result;
        $data["count"] = $count;
        if($data){
            $this->exitJson(0,$data);
        }
    }
}