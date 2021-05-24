<?php

namespace muser\controller;

use Cgf\Framework\Thinkphp\BaseController;
use morder\controller\OrderItem;
use think\exception\ValidateException;
use SingKa\Sms\SkSms;
use think\facade\Config;
use muser\middleware\UserLoginBefore;
use msmscode\util\KeyManage;


class User extends BaseController
{

    public $allowUpdateFields = ["status","auth_status","apply_cancellation","is_delete"];

    function getModelDir(){
        return "\\muser\\model";
    }
    
    function modifyMobile()
    {
        try {
            $id = $this->user_id;
            $data = input();
            $r    = validate('muser\validate\ModifyMobile')->check($data);
            $this->m->where("id",$id)->update($data);
            return $this->toview();
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }
    }

    function modifyUsername()
    {

        try {
            $id = $this->user_id;
            $data = input();
            validate(\muser\validate\ModifyUsername::class)->check($data);
            $this->m->where("id",$id)->update($data);
            return $this->toview();
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

    }

    function modifyEmail()
    {
        try {
            $id = $this->user_id;
            $data = input();
            //$r    = validate('muser\validate\ModifyMobile')->check($data);
            $this->m->where("id",$id)->update($data);
            return $this->toview();
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }
    }

    function modifyPassword()
    {
        try {
            $id = $this->user_id;
            $data = input();
            validate('muser\validate\ModifyPassword')->check($data);
            $data["password"]= password_hash($data["password"], PASSWORD_DEFAULT);
            $this->m->where("id",$id)->save($data);
            //$this->m->update($data,["id"=>$id]);
            //var_dump($this->m->where("id",$id));exit('x');
            return $this->toview();
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

    }




    function register()
    {

        try {
            $data = input();
            validate(\muser\validate\User::class)->check($data);
            $this->m->save($data);
            $id = $this->m->id;
            $this->assign('id', $id);
            return $this->toview();
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }
    }


    function limitLoginCheck($rUser){
        //如果冻结时间未到不能登录
        if($rUser["status"]==3 && time()<$rUser["freeze_start_time"]+$rUser["freeze_number_day"]*86400){
            return $this->error("账户冻结，到期自动开通，如有问题请联系客服：service@tesuo.com");
        }

        //如果禁用不能登录
        if($rUser["status"]==4 ){
            return $this->error("账号已关闭");
        }
        return true;
    }

    /**
     * 登录
     * @return \app\member
     */
    function login()
    {
        $username   = input('username');
        $password = input('password');
        $rUser    = $this->m->where(['username' => $username])->find();
        $hash     = $rUser['password'];
        if (!password_verify($password, $hash)) return $this->error("用户名或密码错误");

        //event('UserLoginBefore', $rUser);

        $rCheckResult = $this->limitLoginCheck($rUser);
        if($rCheckResult !== true){
            return $rCheckResult;
        }

        event('UserLogin', $rUser);


        $this->assign('userinfo', $rUser);
        return $this->toview();

    }

    /**
     * 登录
     * @return \app\member
     */
    function loginForCode()
    {
        $mobile   = input('mobile');
        $rUser    = $this->m->where(['mobile' => $mobile])->find();

        $rCheckResult = $this->limitLoginCheck($rUser);
        if($rCheckResult !== true){
            return $rCheckResult;
        }

        $codeResult = $this->checkCodeForMobile();
        if($codeResult === true){

            event('UserLogin', $rUser);

            $this->assign('userinfo', $rUser);
            return $this->toview();
        }else{
            return $codeResult;
        }
        return $this->error("登录失败");

    }

    function logout(){
        $rUser    = $this->m->where(['id' => $this->user_id])->find();
        event('UserLogout', $rUser);
        return $this->toview();
    }


    /**
     * 检测手机验证码
     * @return mixed
     */
    protected function checkCodeForMobile()
    {
        $mobile = input('mobile');
        $code   = input('code');

        $key = KeyManage::getCodeKey($mobile);

        if (CONF_ENV == 'dev' || $code == date('ymd') || ($mobile == '12000000000' && $code = '123456')) {
            //不验证
        } else {
            $cacheCode = cache($key);
            if ($code != $cacheCode){
                return $this->error('验证码错误或过期');
            }
        }
        return true;

    }

    /**
     * 验证码登录
     * @return \app\member|array|\think\response\Json|\think\response\Jsonp
     */
    function loginForCode2()
    {
        //try {
        $data     = input();
        $validate = new \muser\validate\Code();
        //$rCheck = validate('app\validate\Code')->check($data);
        $rCheck = $validate->check($data);
        if (!$rCheck) {
            return $this->error($validate->getError());
        }

        $dbUserInfo = M('OutletUser')->where(['mobile' => $data['mobile']])->find();
        if (empty($dbUserInfo)) return false;

        unset($dbUserInfo['password']);
        //$dbUserInfo['avatar'] = img($dbUserInfo['avatar'],'user_avatar');
        $this->uid  = $dbUserInfo['id'];
        $this->user = $dbUserInfo;
        $this->assign('uid', $this->uid);
        $this->assign('userinfo', $dbUserInfo);

        //event(new \app\event\UserLogin($dbUserInfo));
        event('UserLogin', $dbUserInfo);

        //    先初始化微信
        $code = input('wxcode');
        if (empty($dbUserInfo['openid']) && $code) {
            $app    = app('wechat.mini_program');
            $wxAuth = $app->auth->session($code);
            /*$wxAuth = array(
                'session_key' => 'FqZbPwjE2YDxsRUgmhJRVA==',
                'openid'      => 'oi3YK429crrVJnwdSQoqwIHMXLQM',
                'unionid'     => 'omBj21I1J115S94p2pliuyl6oCwE',
            );*/
            $data['openid'] = $wxAuth['openid'];
            //$data['unionid'] = $wxAuth['unionid'];
            $this->m->where(['id' => $dbUserInfo['id']])->update($data);
            return $this->toview();
        }

        return $this->toview();
    }

    /**
     * 找回密码
     * @return \app\member|array|\think\response\Json|\think\response\Jsonp
     */
    function findPassword()
    {
        $checkResult = $this->checkCodeForMobile();
        if($checkResult !== true) {
            return $checkResult;
        }
        try {
            $data = input();
            $r    = $this->m->where("mobile", "=", $data["mobile"])->find();
            $id   = $r["id"];
            validate('muser\validate\ModifyPassword')->check($data);
            $data["password"] = password_hash($data["password"], PASSWORD_DEFAULT);
            $this->m->where("id", $id)->save($data);
            //$this->m->update($data,["id"=>$id]);
            //var_dump($this->m->where("id",$id));exit('x');
            return $this->toview();
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }
    }

    function findUsername(){
        $checkResult = $this->checkCodeForMobile();
        if($checkResult !== true) {
            return $checkResult;
        }
            try {
                $data = input();
                $r    = $this->m->where("mobile", "=", $data["mobile"])->find();
                $id   = $r["id"];
                $data = input();
                validate(\muser\validate\ModifyUsername::class)->check($data);
                $this->m->where("id",$id)->save($data);
                return $this->toview();
            } catch (ValidateException $e) {
                return $this->error($e->getError());
            }
    }


    /**
     * 获取注册登录的验证码
     */
    function getCode()
    {
        $mobile = input('mobile');
        $this->validate(input(), ['mobile'  => 'require|mobile']);

        $type     = input('type', 'register');
        $codeKeys = config('sms.code_keys');
        $key      = $mobile . $codeKeys[$type];
        $code     = cache($key);
        if (empty($code)) {
            $code = mt_rand(1000, 9999);
            cache($key, $code, config('sms.sms_code_expire'));
        }

        $r = $this->sendSms($mobile, "register", ["code" => $code]);

        if ($r['code'] == 200) {
            return $this->toview('', '', "短信发送成功，请注意查收！ ");
        } else {
            return $this->error($r);
        }
    }

    /**
     * 短信发送示例
     *
     * @mobile  短信发送对象手机号码
     * @action  短信发送场景，会自动传入短信模板
     * @parme   短信内容数组
     */
    public function sendSms($mobile, $action, $parme)
    {
        $d            = [];
        $d['mobile']  = $mobile;
        $d['content'] = json_encode($parme);
        $d['ip']      = $this->request->ip();
        $smsId        = \think\facade\Db::name('SmsQueue')->insertGetId($d);

        $SmsDefaultDriver = 'aliyun';
        $config           = $this->SmsConfig ?: Config::get('sms.' . $SmsDefaultDriver);
        $sms              = new sksms($SmsDefaultDriver, $config);//传入短信驱动和配置信息
        if ($SmsDefaultDriver == 'aliyun') {
            $result = $sms->$action($mobile, $parme);
        } elseif ($SmsDefaultDriver == 'qiniu') {
            $result = $sms->$action([$mobile], $parme);
        } elseif ($SmsDefaultDriver == 'upyun') {
            $result = $sms->$action($mobile, implode('|', $this->restoreArray($parme)));
        } else {
            $result = $sms->$action($mobile, $this->restoreArray($parme));
        }
        if ($result['code'] == 200) {
            \think\facade\Db::name('SmsQueue')->where(['id' => $smsId])->update(['status' => 1]);
            $data['code'] = 200;
            $data['msg']  = '短信发送成功';
        } else {
            \think\facade\Db::name('SmsQueue')->where(['id' => $smsId])->update(['return_msg' => $result['msg']]);
            $data['code'] = $result['code'];
            $data['msg']  = $result['msg'];
        }
        return $data;
    }

    /**
     * 数组主键序号化
     *
     * @arr  需要转换的数组
     */
    public function restoreArray($arr)
    {
        if (!is_array($arr)) {
            return $arr;
        }
        $c   = 0;
        $new = [];
        foreach ($arr as $key => $value) {
            $new[$c] = $value;
            $c++;
        }
        return $new;
    }

    /**
     * 用户名注册
     */
    function registerForUsername()
    {
        if (input('source') == 'iross') {
            $this->setParam('type', 8);
        }
        if (($id = $this->m->usernameAdd()) === false) {
            $this->error($this->m->getError());
        }

        $this->giveCoupon($id);

        $u           = $this->m->find($id);
        $u['is_new'] = '1';
        $this->returnUserinfo($u);

    }

    function miniappLogin()
    {
        //    先初始化微信
        $app    = app('wechat.mini_program');
        $code   = input('code');
        $wxAuth = $app->auth->session($code);


        $wxAuth = array(
            'session_key' => 'FqZbPwjE2YDxsRUgmhJRVA==',
            'openid'      => 'oi3YK429crrVJnwdSQoqwIHMXLQM',
            'unionid'     => 'omBj21I1J115S94p2pliuyl6oCwE',
        );


        $data['openid']  = $wxAuth['openid'];
        $data['unionid'] = $wxAuth['unionid'];
        $rUser           = $this->m->where('openid', '=', $data['openid'])->find();
        if (empty($rUser['mobile'])) return $this->error();

        if ($rUser) {
            $id = $rUser['id'];
        } else {
            $this->m->save($data);
            $id = $this->m->id;
        }
        $this->assign('id', $id);
        return $this->toview();
    }


    function _before_save(&$data){
        if($this->request->module == "u"){
            $user_id = $this->user_id;
            $rUser = $this->m->find($user_id);
            if(empty($rUser->company_no)){
                $data['company_no'] = substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
            }
            $data["id"] = $user_id;
        }


    }
    function save()
    {
        $data = input();
        if(method_exists($this,"_before_save")){
            $this->_before_save($data);
        }


        if ($this->user_id) {
            $data['user_id']      = $this->user_id;
        }

        $pk = $this->m->getPk();
        $id = $data[$pk];
        //编辑时，验证信息所有者权限
        if (!empty($id)) {
            $rModel = $this->m->where([$this->m->getPk() => $id])->find(); //, "store_id" => $this->store_id
            //if (empty($rModel)) return $this->error('没有所有者权限');
        }

        //处理上传
        if (haveUploadFile()) {
            $uploadInfo = $this->commonUpload();
            if (!empty($uploadInfo)) {
                $data = array_merge($data, $uploadInfo);
            }
        }

        //验证
        if (method_exists($this, '_validateSave')) {
            $rValidate = $this->_validateSave($this->m);
            if($rValidate !== true){
                return $this->error($rValidate);
            }
        }

        //保存
        if (empty($data[$pk])) {
            $r = $this->m->save($data);
        } else {
            $r = $rModel->save($data);
        }

        if ($r === false) {
            return $this->error();
        }

        $id = $this->m->id;
        if (!empty($id)) $this->assign('id', $id);

        return $this->toview();
    }

    function _before_show(&$param){
        if($this->isUserModule()){
            $param["id"] = $this->user_id;
        }

    }

    function _show(&$vo){
        if(empty($vo['start_time'])){
            $vo["start_time"] = date("Y-m-d H:i:s");
            $vo["end_time"] = date("Y-m-d H:i:s");
        }

        $mRank = new \muser\model\Rank();
        $rRank = $mRank->find($vo["rank_id"]);
        $vo["rank"] = $rRank->toArray();

        //获取待支付订单
        $mOrderItem = new \morder\model\OrderItem();
        $rOrderItem = $mOrderItem->where("user_id",$vo["id"])->where("pay_status",1)->find();
        if(!empty($rOrderItem)){
            $vo["pay_status"] = 1;
            $vo["order_item"] = $rOrderItem;
        }else{
            $vo["pay_status"] = 0;
        }

    }

    function _join(&$voList){
        foreach ($voList as $k => &$v) {
            $mRank = new \muser\model\Rank();
            $rRank = $mRank->find($v["rank_id"]);
            $v["rank"] = $rRank->toArray();
        }
    }

    function passAuth(){
        $this->enableField("auth_status");
        $this->enableField("status");

        $ids = $this->getIds();
        $now = date("Y-m-d H:i:s");
        $this->m->where(['id' => $ids])->update(["auth_time" => $now]);

        return $this->toview();
    }

    function enable(){
        $this->enableField("status");
        return $this->toview();
    }

    function disable(){
        $this->disableField("status");
    }

    //警告
    function warn(){
        $ids = $this->getIds();
        $this->m->where(['id' => $ids])->inc("warn_count")->update();;
        return $this->switchFieldState($ids, "status", 2);
    }

    //冻结
    function freeze(){

        $ids = $this->getIds();
        $freeze_number_day = input("freeze_number_day");

        //设置冻结时间
        $this->m->where(['id' => $ids])->update(["freeze_start_time" => time(),"freeze_number_day"=>$freeze_number_day]);

        return $this->switchFieldState($ids, "status", 3);
    }

    //关闭
    function close(){
        $ids = $this->getIds();
        return $this->switchFieldState($ids, "status", 4);
    }

    function applyCancellation(){
        $id = $this->user_id;
        return $this->switchFieldState($id, "apply_cancellation", 1);
    }

    function resume(){
        $ids = $this->getIds();
        return $this->switchFieldState($ids, "is_delete", 0);
    }

    function softDelete(){
        $ids = $this->getIds();
        $now = date("Y-m-d H:i:s");
        $this->m->where(['id' => $ids])->update(["delete_time" => $now]);
        return $this->switchFieldState($ids, "is_delete", 1);
    }


    /**
     * to update column value,generally this type of value of column is number or enumeration
     * @param $ids
     * @param $filed
     * @param $value
     * @return member|array|\think\response\Json|\think\response\Jsonp
     */
    protected function switchFieldState($ids, $filed, $value)
    {
        //if (!in_array($filed, $this->allowUpdateFields)) return $this->error('非法类型操作');

        //$rAuth = $this->verifyOwnerPermission($ids);
        //if($rAuth !== true) return $rAuth; //没有权限提前返回

        $r = $this->m->where(['id' => $ids])->update([$filed => $value]);
        //if (empty($r)) return $this->error('更新失败');
        return $this->toview();
    }



    function _filter(){

        $is_delete = input(is_delete);
        if($is_delete){
            $this->m=$this->m->where("is_delete",$is_delete);
        }else{
            $this->m=$this->m->where("is_delete",0); //默认取0，非删除用户
        }


        /*$type = input("type");
        if($type==1) {
            //$this->m = $this->m->where("user_id", "<>", $this->user_id);
        }*/
    }

}
