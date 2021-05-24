<?php

namespace muser\model;

use think\Model;
use think\contract\Arrayable;

/**
 * Rule Model
 */
class User extends Model implements Arrayable
{

    public function setPasswordAttr($value)
    {
        $pwd = password_hash($value, PASSWORD_DEFAULT);
        return $pwd;
    }

    /*public function setCompanyNoAttr($value)
    {
        return substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    }*/

    public static function onBeforeInsert(&$user){
        $user->company_no = substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    }


    /*
        function msave($data = ''){
            //$this->_auto[] = array('password','pwd',3,'callback'); //是编辑用户其它字段也更新密码，还是只更新密码字段才触发生成密码？
            //if(false === $this->create($data))  return false;
            if(!empty($data[$this->getPk()])){
                return $this->save();
            }else{
                return $this->insert($data);
            }
        }


        public function setPasswordAttr($value)
        {
            return $this->pwd($value);
        }

        protected function pwd($password){
            //debug($password);
            $pwd = password_hash($password, PASSWORD_DEFAULT);
            //debug(password_verify($password, $pwd));
            return $pwd;
        }

        public function autoSalt(){
            return substr(uniqid(mt_rand()), 0, 4);
        }

        function getHeadimgAttr($value){
            return img($value);
        }
    */





}