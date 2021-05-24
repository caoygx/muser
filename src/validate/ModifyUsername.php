<?php

namespace muser\validate;

class ModifyUsername extends Code
{

    protected $customizedRule = [
        'username'   => 'require|max:15|checkUnique:rule',
        'reusername'   => 'require|checkEqual:rule',
    ];

    protected $customizedMessage  =   [
        'username.require'   => '用户名不能为空',
        'username.checkEqual'  => '确认用户名不一致',
        'username.checkUnique'  => '用户名已被使用',
    ];

    function __construct()
    {
        parent::__construct();
        $this->rule($this->customizedRule);
        $this->message($this->customizedMessage);
    }

    protected function checkEqual($value, $rule, $data=[])
    {
        return $value == $data['username'];
    }

    protected function checkUnique($value, $rule, $data=[])
    {
        $r = \think\facade\Db::name('User')->where(['username'=>$value])->find();
        return empty($r);
    }


}
