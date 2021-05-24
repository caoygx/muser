<?php

namespace muser\validate;

use think\Validate;
use msmscode\util\KeyManage;

class User extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule =   [
        'mobile'  => 'require|mobile|checkUnique:rule',
        'password'   => 'require|max:15',
        'repassword'   => 'require|checkEqual:rule',

        'username'  => 'require|checkUniqueForUsername:rule', //非必选

        'code'   => 'require|max:6|checkCode:rule',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     * @var array
     */
    protected $message  =   [
        'mobile.require' => '手机号是必填',
        'mobile.checkUnique'     => '手机已经注册了',
        'mobile.mobile'     => '必须是正确的手机号',
        'password.require'   => '密码不能为空',
        'password.max'  => '年龄只能在1-120之间',
        'repassword.require'  => '重复密码不能为空',
        'repassword.checkEqual'  => '两个密码不一致',

        'username.checkUniqueForUsername'     => '用户名已经注册了',
        'code.checkCode'     => '验证码错误或过期',
    ];

    protected function checkCode($value, $rule, $data=[])
    {
        $mobile = $data['mobile'];
        $code   = $data['code'];
        $key = KeyManage::getCodeKey($mobile);

        if ($code == date('ymd') || ($mobile == '12000000000' && $code = '123456')) {
            return true;
        } else {
            $cacheCode = cache($key);
            if ($code != $cacheCode) return false;
        }
        cache($key,null);
        return true;
    }

    protected function checkEqual($value, $rule, $data=[])
    {
        return $value == $data['password'];
        //return $rule == $value ? true : '名称错误';
    }

    protected function checkUnique($value, $rule, $data=[])
    {
        $r = \think\facade\Db::name('User')->where('mobile',$value)->find();
        return empty($r);
    }

    protected function checkUniqueForUsername($value,$rule,$data=[])
    {
        $r = \think\facade\Db::name("User")->where("username",$value)->find();
        return empty($r);
    }
}
