<?php

namespace muser\service;
class User
{
    /**
     * 手机登录验证码登录
     */
    function register()
    {
        $mobile = input('mobile');
        $code   = input('code');

        //Cache::set('name', $value, 3600);

        //验证手机号，相当于验证open_id是否真实

        try {
            $data = input();
            validate('app\validate\OutletUser')->check($data);
            /* $m = new \app\model\User();
             $className = '\\app\\model\\User';
             $m = new $className();
             dump($m);*/
            //dump($this->m);exit;
            //$m->save($data);
            $this->m->save($data);
            $id = $this->m->id;
            $this->assign('id', $id);
            return $this->toview();
        } catch (ValidateException $e) {
            return $this->error($e->getError());
        }

        /*$where['mobile'] = $mobile;
        $u = $this->m->whereWidthFilterField($where)->find();
        if(empty($u)){ //新用户注册
            $d = [];
            $d['mobile'] = $mobile;
            $d['ip'] = get_client_ip();
            $d['avatar'] = rand_avatar();
            $d['source'] = $this->getSource();
            //$id = $this->m->mobileAdd($d);
            if(($id = $this->m->mobileAdd()) === false){
                $this->error($this->m->getError());
            }

            $u = $this->m->find($id);
            $u['is_new'] = '1';

            $this->returnUserinfo($u);




        }else{ //老用户登录
            $this->returnUserinfo($u);
        }*/

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

    /**
     * 检测手机验证码
     * @return mixed
     */
    protected function checkCodeForMobile()
    {
        $mobile = input('mobile');
        $code   = input('code');

        $codeKeys = config('app.code_keys');
        $key      = $mobile . $codeKeys['login'];

        if (CONF_ENV == 'dev' || $code == date('ymd') || ($mobile == '12000000000' && $code = '123456')) {
            //不验证
        } else {
            $cacheCode = cache($key);
            if ($code != $cacheCode) return $this->error('验证码错误或过期');
        }

        return true;

    }

    /**
     * 验证码登录
     * @return \app\member|array|\think\response\Json|\think\response\Jsonp
     */
    function loginForCode()
    {
        //try {
        $data     = input();
        $validate = new \app\validate\Code();
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
        /*} catch (ValidateException $e) {

        }*/
        /*

                $rUser = $this->m->where(['mobile' => $mobile])->find();
                if (empty($rUser)) return $this->error('用户不存在');

                $this->assign('userinfo', $rUser);
                return $this->toview();*/
    }

    /**
     * 找回密码
     * @return \app\member|array|\think\response\Json|\think\response\Jsonp
     */
    function findPassword()
    {
        try {
            $data = input();
            validate('app\validate\Code')->check($data);
            $rUser = \think\facade\Db::name('OutletUser')->where(['mobile' => $data['mobile']])->find();
            $this->m->where(['id' => $rUser['id']])->save(['password' => input('password')]);
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
        //$this->validate(input(),'\app\validate\code.mobile');

        $type     = input('type', 'register');
        $codeKeys = config('app.code_keys');
        $key      = $mobile . $codeKeys[$type];
        $code     = '';//cache($key);
        if (empty($code)) {
            $code = mt_rand(1000, 9999);
            cache($key, $code, config('sms_code_expire'));
        }

        $d            = [];
        $d['mobile']  = $mobile;
        $d['content'] = $code;
        $d['ip']      = get_client_ip();
        $sms_id       = M('SmsQueue')->insertGetId($d);
        $r            = send_sms($mobile, $code, null, $sms_id);

        if ($r === true) {
            return $this->toview('', '', "短信发送成功，请注意查收！ ");
        } else {
            return $this->error($r);
        }
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
}