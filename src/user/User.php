<?php

namespace mapp\user\controller;


use think\exception\ValidateException;

use Cgf\Framework\Thinkphp\BaseController;

class User extends BaseController
{
    function initialize(){
        $this->m = new \mapp\model\User();
    }
    function register2()
    {
        if (request()->isPost()) {
            $mUser = new \mapp\model\User();
            $r     = $mUser->msave($_POST);
            echo '保存成功';
            redirect('/');
        } else {
            $dir = dirname(__DIR__) . '/view';
            $dir = "../../vendor/rrbrr/user/src/user/view";
            $dir = config("user.template_root_dir");
            return view($dir . '/user/register');
        }
    }

    /**
     * 手机登录验证码登录
     */
    function register($params)
    {

        try {
            $data = input();
            validate('mapp\validate\User')->check($data);
            $this->m->save($data);
            $id = $this->m->id;
            $this->assign('id', $id);
            return $this->toview();
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

    }

    /**
     * 登录
     * @return \app\member
     */
    function login($params)
    {
        $mobile   = input('mobile');
        $password = input('password');
        $rUser    = $this->m->where(['mobile' => $mobile])->find();
        $hash     = $rUser['password'];
        if (!password_verify($password, $hash)) return $this->error("用户名或密码错误");

        $this->assign('userinfo', $rUser);
        return $this->toview();

    }

    function saveInfo()
    {
        $data = [];
        $data['nickname'] = I('nickname');

        $id = $this->uid;

        //验证编辑保存权限
        if(!empty($id)){
            $rModel = $this->m->where([$this->m->getPk()=>$id])->find();
            if(empty($rModel)) return $this->error('没有所有者权限');
        }

        $r = $rModel->save($data);

        if($r === false){
            return $this->error();
        }

        $id = $this->m->id;
        if(!empty($id)) $this->assign('id',$id);
        return $this->toview();

    }

    public function switchOutlet()
    {
        $outlet_id = I('outlet_id');
        /*$hasOutlet = Db::name('outletUserRel')->where(['uid'=>$this->uid,outlet_id=>$outlet_id])->find();
        if(empty($hasOutlet)) return $this->error();*/
        $r = $this->m->where(['id'=>$this->uid])->update(['current_outlet_id'=>$outlet_id]);
        if(empty($r)) return $this->error('切换失败');
        return $this->toview();
    }


    /**
     * 检测手机验证码
     * @return mixed
     */
    protected function checkCodeForMobile(){
        $mobile = I('mobile');
        $code   = I('code');

        $codeKeys = config('app.code_keys');
        $key      = $mobile . $codeKeys['login'];

        if (CONF_ENV == 'dev' || $code == date('ymd') || ($mobile == '12000000000' && $code = '123456')) {
            //不验证
        } else {
            $cacheCode = S($key);
            if ($code != $cacheCode) return $this->error('验证码错误或过期');
        }

        return true;

    }


    /**
     * 绑定手机
     * @return \app\member|array|\think\response\Json|\think\response\Jsonp
     */
    function bindMobile(){
        $mobile = I('mobile');

        $rUser = $this->checkCodeForMobile();
        if (empty($rUser)) return $this->error('用户不存在');


        if(!empty($rUser['mobile'])){
            $this->error('已经绑定过手机了');
        }

        $rMobile=$this->m->where(['mobile'=>$mobile])->find();
        if(!empty($rMobile)){
            $this->error('手机已经被使用了,请更换手机');
        }

        $this->m->where(['id'=>$rUser['uid']])->update(['mobile'=>$mobile]);
        return $this->toview();
    }


    /**
     * 获取注册登录的验证码
     */
    function getCode()
    {
        $mobile   = input('mobile');
        $this->validate(input(),'\mapp\validate\code.mobile');

        $type     = input('type','register');
        $codeKeys = config('app.code_keys');
        $key      = $mobile . $codeKeys[$type];
        $code     = cache($key);
        if (empty($code)) {
            $code = mt_rand(1000, 9999);
            cache($key, $code, config('sms_code_expire'));
        }

        $d            = [];
        $d['mobile']  = $mobile;
        $d['content'] = $code;
        $d['ip']      = get_client_ip();
        $sms_id       = \think\facade\Db::name('SmsQueue')->insertGetId($d);
        $r            = send_sms($mobile, $code, null, $sms_id);

        if ($r === true) {
            return $this->toview('', '', "短信发送成功，请注意查收！ ");
        } else {
            return $this->error($r);
        }
    }



}
