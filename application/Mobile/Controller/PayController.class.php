<?php
namespace Mobile\Controller;
use Common\Controller\HomebaseController;
class PayController extends HomebaseController{
    




    private $cert;
    private $crypt;

    public $request;

    public function __construct() {
        parent::__construct();
       // $PUB_KEY_PATH = $_SERVER['DOCUMENT_ROOT']."/data/ecabfb8041fa675b8/merchant_cert.pfx";


     }

    public function prePost($url) {
        $request = json_encode($this->request);

        //签名
        $sign = $this->crypt->sign($request);

        //post数据
        $params = array(
            'charset' => 'utf-8',
            'signType' => '01',
            'data' => $request,
            'sign' => $sign
        );
        $query = http_build_query($params);
        $response = httpClient::request_post($url, $query);
        parse_str(urldecode($response), $arr);

        //base64编码中的+经过urldecode会转成空格 需要替换成+
        $arr['sign'] = preg_replace('/(\s+)/', '+', $arr['sign']);

        //验签
        $this->crypt->verify($arr['data'], $arr['sign']);

        return $arr['data'];
    }

    public function thePay(){
        $order_id=I("get.id");
        $status=I('status');
        if($status=='2'){ //支付完成
            $this->assign("error","2");
            $this->display(":mpay");
            exit;
        }
        $oi=M("offline_pay")->where(array("id"=>$order_id))->find();
       // var_dump($oi);exit;
        if(!$oi || $oi['statas']=='2'){
            $this->assign("error","1");
            $this->display(":mpay");
            exit;
        }
        if($oi['statas']=='1'){
            $this->assign("error","2");
            $this->display(":mpay");
            exit;
        }


        $pay_img=U('Pay/thePayImg',array('id'=>$order_id));
        $this->assign("pay_img",$pay_img);
        $this->assign("money",$oi['money']);
        $this->assign("order_id",$order_id);
        $this->assign("pay_title",'微信充值');

        $this->display(":mpay");
        exit;
    }

    public function thePayImg(){
        $order_id=I("get.id");
        $status=I('s');
        if($status=='2'){ //支付完成
            exit;
        }
        $oi=M("offline_pay")->where(array("id"=>$order_id,"status"=>0))->find();
        if(!$oi){
            exit;
        }
        $this->qt_pay($oi);

        exit;
    }

    public function is_ok(){
        $order_id=I("get.id");
        if(M("offline_pay")->where(array("id"=>$order_id,"status"=>1))->find()){
            die('1');
        }else{
            die('0');
        }
    }


    private function qt_pay($thePayInfo){
        header("Content-type: text/html; charset=utf-8");

        error_reporting(E_ERROR | E_WARNING | E_PARSE);

        //改v
        $API_HOST = 'https://cashier.sandpay.com.cn/qr/api';
//        $API_HOST = 'http://61.129.71.103:8003/qr/api';


        $WXPAY_PRODUCTID = "00000005";//	微信扫码
        $ALIPAY_PRODUCTID = "00000006";//	支付宝扫码

        $QR_ORDERPAY = "sandpay.trade.barpay";//	统一下单并支付
        $QR_ORDERCREATE = "sandpay.trade.precreate";//		预下单
        $QR_ORDERQUERY = "sandpay.trade.query";//		订单查询
        $QR_ORDERCANCEL = "sandpay.trade.cancel";//		订单撤销
        $QR_ORDERREFUND = "sandpay.trade.refund";//		退货
        $QR_CLEARFILEDOWNLOAD = "sandpay.trade.download";//

//        spl_autoload_register('autoload');
//
//        function autoload($class) {
//            $class = __DIR__ . '/' . $class;
//            $file = str_replace('\\', '/', $class);
//            $file .= '.php';
//            if (file_exists($file)) {
//                require_once $file;
//            }
//        }


        $service = new service();
        $request = &$service->request;
        //改v
        $request->setDefaultHead($QR_ORDERCREATE, $WXPAY_PRODUCTID, 13066538);  //100211702080001  13066538 18059877 19959949
        $request->body = new orderCreate();
        $request->body->payTool = '0402';//支付工具
        $request->body->orderCode = date('YmdHis',$thePayInfo['add_time']).$thePayInfo['id'].date('si');//商户订单号
//        $request->body->orderCode = date('YmdHis',$thePayInfo['add_time']).$thePayInfo['id'];//商户订单号
//        $request->body->limitPay = '1';//限定支付方式
        if($thePayInfo['money']<10){
            $mq='000000000';
        }elseif($thePayInfo['money']<100){
            $mq='00000000';
        }elseif($thePayInfo['money']<1000&&$thePayInfo['money']>=100){
            $mq='0000000';
        }elseif($thePayInfo['money']<10000&&$thePayInfo['money']>=1000){
            $mq='000000';
        }elseif($thePayInfo['money']=10000){
            $mq='00000';
        }else{
            $mq='';
        }
        $request->body->totalAmount = $mq.(string)($thePayInfo['money']*100);//订单金额0000000001
        //$request->body->totalAmount = '000001';//订单金额0000000001
        $request->body->subject = '在线充值';//订单标题
        $request->body->body = '在线充值';//订单描述
        $request->body->txnTimeOut = date('YmdHis',($thePayInfo['add_time']+7200));//订单超时时间
        $request->body->storeId = '';//商户门店编号
        $request->body->terminalId = '';//商户终端编号
        $request->body->operatorId = '';//操作员编号
        $request->body->notifyUrl = 'http://ios.whyuejia.cc/mobile/pay/rpay';//异步通知地址
        $request->body->bizExtendParams = '';//业务扩展参数
        $request->body->merchExtendParams = '';//业务扩展参数
        $request->body->extend = '';//扩展域

        $the_r=$service->prePost($API_HOST . '/order/create');
        $arr=json_decode($the_r,1);
//        echo $arr['body']['qrCode'];die;
        if($arr['head']['respCode']=='000000'){
//            echo $arr['body']['qrCode'];
            qrcode($arr['body']['qrCode']);
        }else{

        }
//        print_r($arr);
        exit;

    }

    public function rpay(){


        $r=$_REQUEST;


//        $tmp = explode("|", $result);
//        $resp_xml = base64_decode($tmp[0]);
//        $resp_sign = $tmp[1];
//        if($this->verity(MD5($resp_xml,true),$resp_sign)){//验签
//            $d= '<br/>响应结果<br/><textarea cols="120" rows="20">'.$resp_xml.'</textarea>';
//        } else
//            $d= '验签失败';
//
//        $this->we($d);

        $r_data=json_decode($r['data'],1);
       // $this->we($r_data['body'],'data_body：');
       // $this->we($r_data['head'],'data_head：');
        $r_sign=$r['sign'];

        //验签
//        $PUB_KEY_PATH = $_SERVER['DOCUMENT_ROOT']."/data/xxx/sand-test.cer";
//        $PRI_KEY_PATH = $_SERVER['DOCUMENT_ROOT']."/data/xxx/mid-test.pfx";
//        $CERT_PWD = '123456';
//        $this->cert = new certUtil($PUB_KEY_PATH, $PRI_KEY_PATH,$CERT_PWD);
//        $this->crypt = new cryptUtil($this->cert->getPublicKey(), $this->cert->getPrivateKey());
//        $r_sign = preg_replace('/(\s+)/', '+', $r_sign);
//        $this->crypt->verify($r_data,$r_sign);
        if($r_data['head']['respCode']=='000000'){
            $orderCode=explode('_',$r_data['body']['orderCode']);
          //  $this->we($orderCode,'1：');
            $order_sn=$orderCode[0];
           // $this->we($order_sn,'2：');
            $order_sn=substr($order_sn,14,-4);
           // $this->we($order_sn,'3order_sn：');
            $oi=M("offline_pay")->where(array("id"=>$order_sn,"status"=>0))->find();
            //$this->we($oi,'oi：');
            if($oi && ($oi['money']*100)==(int)$r_data['body']['totalAmount']){
          //  if($oi){
                addMoney($oi['uid'],(int)$oi['money'],1,"在线充值",1);
                M("offline_pay")->where(array("id"=>$order_sn,"status"=>0))->save(array('status'=>1,'do_time'=>time()));
            }
        }
       // $this->we($r);


//        $post=$_POST;
//        $order_sn=I("post.orderNo");
//        $oi=M("order")->where(array("order_sn"=>$order_sn))->find();
//        // var_dump($oi);exit;
//        $error=0;
//        if($oi['order_status']==1){
//            $this->assign("error",$error);
//            $this->display(":rpay");
//            exit;
//        }
//
//        if(!$oi){
//            $error=1;
//        }
//        if($post['orderStatus']!="1"){
//            $error=2;
//        }
//        if($post['tradeAmt']!=$oi['order_price']){
//            $error=3;
//        }
//        if($post['merchNo']!=$this->mi){
//            $error=4;
//        }
//
//        if($error!=0){
//            $this->assign("error",$error);
//            $this->display(":rpay");
//            exit;
//        }else{
//            D('Shop')->order_pay_enter($order_sn,$post['accNo'],"mobao");
////            M("order")->where(array("order_sn"=>$order_sn))->save(array(
////                "order_status"=>1,
////                "pay_type"=>"mobao",
////                "pay_trade_no"=>$post['accNo'],
////                "pay_time"=>time()
////            ));
//            if($post["notifyType"]=="1"){
//                echo "SUCCESS";
//            }else{
//                $this->assign("error",$error);
//                $this->display(":rpay");
//                exit;
//            }
//        }

//        $s="";
//        foreach ($_POST as $k=>$v){
//            $s.="$".$k."='".$v."';\n";
//        }
//        M("v")->add(array("v"=>$s));

    }

    private function we($post,$b=''){
        $s="".$b."：";
        if(!is_array($post)){
           $post[0]=$post;
        }
        foreach ($post as $k=>$v){
            $s.="$".$k."='".$v."';\n";
        }
        M("v")->add(array("v"=>$s,'d'=>date('Y-m-d H:i:s')));
    }


}

class service {
    private $cert;
    private $crypt;

    public $request;

    public function __construct() {
//        $PUB_KEY_PATH = $_SERVER['DOCUMENT_ROOT']."/data/xxx/sand-test.cer";
//        $PRI_KEY_PATH = $_SERVER['DOCUMENT_ROOT']."/data/xxx/mid-test.pfx";
//        $CERT_PWD = '123456';

        //改v
//        $PUB_KEY_PATH = $_SERVER['DOCUMENT_ROOT']."/data/xxx/public_key.cer";
        $PUB_KEY_PATH = $_SERVER['DOCUMENT_ROOT']."/data/xxx/sand.cer";
//        $PRI_KEY_PATH = $_SERVER['DOCUMENT_ROOT']."/data/xxx/sy_acp_prod_sign.pfx";
        $PRI_KEY_PATH = $_SERVER['DOCUMENT_ROOT']."/data/xxx/mid-pri.pfx";
        $CERT_PWD = 'a123123';

        $this->request = new request();
        $this->cert = new certUtil($PUB_KEY_PATH, $PRI_KEY_PATH,$CERT_PWD);
        $this->crypt = new cryptUtil($this->cert->getPublicKey(), $this->cert->getPrivateKey());
    }

    public function prePost($url) {
        $request = json_encode($this->request);

        //签名
        $sign = $this->crypt->sign($request);

        //post数据
        $params = array(
            'charset' => 'utf-8',
            'signType' => '01',
            'data' => $request,
            'sign' => $sign
        );
        $query = http_build_query($params);
        $response = httpClient::request_post($url, $query);
        parse_str(urldecode($response), $arr);

        //base64编码中的+经过urldecode会转成空格 需要替换成+
        $arr['sign'] = preg_replace('/(\s+)/', '+', $arr['sign']);

        //验签
        $this->crypt->verify($arr['data'], $arr['sign']);

        return $arr['data'];
    }
}

class head {
    public $version = '';//版本号
    public $method = '';//接口名称
    public $productId = '';//产品编码
    public $accessType = '';//接入类型
    public $mid = '';//商户ID
    public $subMid = '';//二级商户ID
    public $subMidName = '';//二级商户名称
    public $subMidAddr = '';//二级商户简称
    public $channelType = '';//渠道类型
    public $reqTime = '';//请求时间
}

class request {
    public $head;
    public $body;

    public function __construct() {
        $this->head = new head();
    }

    public function setDefaultHead($method, $productId, $mid) {
        $head = &$this->head;

        $head->version = '1.0';
        $head->method = $method;
        $head->productId = $productId;
        $head->accessType = '1';
        $head->mid = $mid;
        $head->channelType = '07';
        $head->reqTime = date('YmdHis', time());
    }
}

class httpClient {
    public static function request_post($url = '', $param = '') {
        if (empty($url) || empty($param)) {
            return false;
        }

        try {
            $ch = curl_init();//初始化curl
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $data = curl_exec($ch);//运行curl
            curl_close($ch);

            if (!$data) {
                throw new \Exception('请求出错');
            }

            return $data;
        } catch (\Exception $e) {
            errorHandle::log($e);
            throw $e;
        }
    }
}

class cryptUtil {
    private $puk;
    private $prk;

    function __construct($puk, $prk) {
        $this->puk = $puk;
        $this->prk = $prk;
    }

    /**
     * 私钥签名
     * @param $plainText
     * @return string
     * @throws \Exception
     */
    public function sign($plainText) {
        try {
            $resource = openssl_pkey_get_private($this->prk);
            $result = openssl_sign($plainText, $sign, $resource);
            openssl_free_key($resource);

            if (!$result) {
                throw new \Exception('签名出错'.$plainText);
            }

            return base64_encode($sign);
        } catch (\Exception $e) {
            errorHandle::log($e);
            throw $e;
        }
    }

    /**
     * 公钥验签
     * @param $plainText
     * @param $sign
     * @return int
     * @throws \Exception
     */
    public function verify($plainText, $sign) {
        $resource = openssl_pkey_get_public($this->puk);
        $result = openssl_verify($plainText, base64_decode($sign), $resource);
        openssl_free_key($resource);

        if (!$result) {//todo cancel annotation
            errorHandle::throwException(new \Exception('签名验证未通过,plainText:' . $plainText . '。sign:' . $sign, '020002'));
        }

        return $result;
    }
}

class certUtil {
    private $puk = null;
    private $prk = null;

    public function __construct($pubPath, $priPath, $certPwd) {
        $this->puk = $this->loadX509Cert($pubPath);
        $this->prk = $this->loadPk12Cert($priPath, $certPwd);
//        $this->load($priPath, $certPwd);//todo back
    }

    public function getPublicKey() {
        return $this->puk;
    }

    public function getPrivateKey() {
        return $this->prk;
    }

    /**
     * 获取公钥
     * @param $path
     * @return mixed
     * @throws \Exception
     */
    private function loadX509Cert($path) {
        try {
            $file = file_get_contents($path);
            if (!$file) {
              //  throw new \Exception('loadX509Cert::file_get_contents ERROR');
            }

            $cert = chunk_split(base64_encode($file), 64, "\n");
            $cert = "-----BEGIN CERTIFICATE-----\n" . $cert . "-----END CERTIFICATE-----\n";

            $res = openssl_pkey_get_public($cert);
            $detail = openssl_pkey_get_details($res);
            openssl_free_key($res);

            if (!$detail) {
                throw new \Exception('loadX509Cert::openssl_pkey_get_details ERROR');
            }

            return $detail['key'];
        } catch (\Exception $e) {
            errorHandle::log($e);
            throw $e;
        }
    }

    /**
     * 获取私钥
     * @param $path
     * @param $pwd
     * @return mixed
     * @throws \Exception
     */
    private function loadPk12Cert($path, $pwd) {
        try {
            $file = file_get_contents($path);
            if (!$file) {
                throw new \Exception('loadPk12Cert::file_get_contents ERROR');
            }

            if (!openssl_pkcs12_read($file, $cert, $pwd)) {
                throw new \Exception('loadPk12Cert::openssl_pkcs12_read ERROR');
            }

            return $cert['pkey'];
        } catch (\Exception $e) {
            errorHandle::log($e);
            throw $e;
        }
    }

    private function load($path, $pwd) {
        try {
            $file = file_get_contents($path);
            if (!$file) {
                throw new \Exception('loadPk12Cert::file_get_contents ERROR');
            }

            if (!openssl_pkcs12_read($file, $cert, $pwd)) {
                throw new \Exception('loadPk12Cert::openssl_pkcs12_read ERROR');
            }

            $res = openssl_pkey_get_public($cert['cert']);
            $detail = openssl_pkey_get_details($res);
            openssl_free_key($res);

            $this->prk =  $cert['pkey'];
            $this->puk = $detail['key'];

        } catch (\Exception $e) {
            errorHandle::log($e);
            throw $e;
        }
    }
}

class orderCreate {
    public $payTool = '';//支付工具
    public $orderCode = '';//商户订单号
    public $limitPay = '';//限定支付方式
    public $totalAmount = '';//订单金额
    public $subject = '';//订单标题
    public $body = '';//订单描述
    public $txnTimeOut = '';//订单超时时间
    public $storeId = '';//商户门店编号
    public $terminalId = '';//商户终端编号
    public $operatorId = '';//操作员编号
    public $notifyUrl = '';//异步通知地址
    public $bizExtendParams = '';//业务扩展参数
    public $merchExtendParams = '';//商户扩展参数
    public $extend = '';//扩展域
}

class errorHandle {
    public static function log($msg) {
        error_log(date("[Y-m-d H:i:s]") . " -[ error : " . $msg . " \n", 3, "/tmp/sd_plug_err.log");
    }

    public static function throwException(\Exception $e) {
        echo json_encode(
            array(
                'respCode' => $e->getCode(),
                'respDesc' => $e->getMessage()
            )
        );
        exit;
    }
}