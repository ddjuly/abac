<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 18/9/12
 * Time: 上午11:08
 */

namespace Abac;

use Illuminate\Support\Facades\Config;

class Abac {

    /**
     * Laravel application
     */
    public $app;

    /**
     * current user id
     */
    public $userId;

    private $isQuery = false;
    private $roles = [];
    private $rolePermissions = [];
    private $userPermissions = [];

    /**
     * @return void
     */
    public function __construct($app) {
        $this->app = $app;
        $this->userId = $this->app->auth->user()->id;
    }


    /**
     * query all roles and permissions of this user
     * @permission
     */
    private function queryInfos() {
        if (!$this->isQuery) {
            $this->isQuery = true;
            return;
        }

        $sql = "SELECT b.role_id, c.role_name, d.pid, e.pname, f.pid AS upid, g.pname AS upname FROM ".Config::get('abac.users', 'users')." a
                LEFT JOIN ".Config::get('abac.abac_user_role', 'abac_user_role')." b ON a.id=b.user_id
                LEFT JOIN ".Config::get('abac.abac_role', 'abac_role')." c ON b.role_id=c.role_id
                LEFT JOIN ".Config::get('abac.abac_role_permission', 'abac_role_permission')." d ON c.role_id=d.role_id
                LEFT JOIN ".Config::get('abac.abac_permission', 'abac_permission')." e ON d.pid=e.pid
                LEFT JOIN ".Config::get('abac.abac_user_permission', 'abac_user_permission')." f ON a.id=f.user_id
                LEFT JOIN ".Config::get('abac.abac_permission', 'abac_permission')." g ON f.pid=g.pid
                WHERE a.id=:user_id
                ";
        $info = Helper::select_all($sql, ['user_id'=>$this->userId]);

        foreach ($info as $val) {
            $this->roles[$val['role_id']] = $val['role_name'];
            $this->rolePermissions[$val['pid']] = $val['pname'];
            $this->userPermissions[$val['upid']] = $val['upname'];
        }
    }


    /**
     * @param $role int role_id or string role_name
     * @return bool
     */
    public function hasRole($role)
    {
        $this->queryInfos();

        if (is_string($role)) {
            return in_array($role, $this->roles);
        } else if (is_int($role)) {
            return array_key_exists($role, $this->roles);
        }

        return false;
    }


    /**
     * @param $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        $this->queryInfos();

        if (is_string($permission)) {
            $b = in_array($permission, $this->userPermissions);
            if ($b) {
                return true;
            } else {
                $b = in_array($permission, $this->rolePermissions);
                return $b;
            }
        } else if (is_int($permission)) {
            $b = array_key_exists($permission, $this->roles);
            if ($b) {
                return true;
            } else {
                $b = array_key_exists($permission, $this->rolePermissions);
                return $b;
            }
        }

        return false;
    }


    /**
     * @permission
     * @param $permission
     */
    public function ability($permissions) {
        $this->queryInfos();

        $arr = explode('|', $permissions);

        foreach ($arr as $val) {
            $b = in_array($val, $this->userPermissions);
            if ($b) {
                return true;
            } else {
                $b = in_array($val, $this->rolePermissions);
                if ($b) {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * create role
     */
    public function addRole($roleName) {
        $m = Helper::model(Config::get('abac.abac_role', 'abac_role'), 'role_id')->where('role_name', $roleName)->first();
        if ($m) {
            return false;
        }

        $m = Helper::model(Config::get('abac.abac_role', 'abac_role'), 'role_id');
        $m->role_name = $roleName;
        $b = $m->save();

        if ($b) {
            return $m->role_id;
        }
        return false;
    }


    /**
     * create permission
     */
    public function addPermission($pname) {
        $m = Helper::model(Config::get('abac.abac_permission', 'abac_permission'), 'pid')->where('pname', $pname)->first();
        if ($m) {
            return false;
        }
        $m = Helper::model(Config::get('abac.abac_permission', 'abac_permission'), 'pid');
        $m->pname = $pname;
        $b = $m->save();
        if ($b) {
            return $m->pid;
        }
        return false;
    }


    /**
     * @permission
     * @param $permission
     * @param $role
     * @return bool
     * @throws \Exception
     */
    public function addPermission2Role($permission, $role) {
        $modelP = Helper::model(Config::get('abac.abac_permission', 'abac_permission'), 'pid');
        if (is_int($permission)) {
            $modelP->where('pid', $permission);
        } else if (is_string($permission)) {
            $modelP->where('pname', $permission);
        }
        $modelP = $modelP->first();
        if (!$modelP) {
            throw new \Exception('permission id is not found!');
        }

        $modelRole = Helper::model(Config::get('abac.abac_user_role', 'abac_user_role'), 'role_id');
        if (is_int($role)) {
            $modelRole->where('role_id', $role);
        } else if (is_string($role)) {
            $modelRole->where('role_name', $role);
        }
        $modelRole = $modelRole->first();
        if (!$modelRole) {
            throw new \Exception('role id is not found!');
        }

        $m = Helper::model(Config::get('abac.abac_role_permission', 'abac_role_permission'))->where('role_id', $modelRole->role_id)->where('pid', $modelP->pid)->first();
        if ($m) {
            return false;
        }

        $m = Helper::model(Config::get('abac.abac_role_permission', 'abac_role_permission'));
        $m->role_id = $modelRole->role_id;
        $m->pid = $modelP->pid;
        $b = $m->save();
        if ($b) {
            return true;
        }

        return false;
    }


    /**
     * @permission
     * @param $user_id
     * @param $role
     * @return bool
     * @throws \Exception
     */
    public function addUser2Role($user_id, $role) {
        $modelRole = Helper::model(Config::get('abac.abac_user_role', 'abac_user_role'), 'role_id');
        if (is_int($role)) {
            $modelRole->where('role_id', $role);
        } else if (is_string($role)) {
            $modelRole->where('role_name', $role);
        }
        $modelRole = $modelRole->first();
        if (!$modelRole) {
            throw new \Exception('role is not found!');
        }

        $m = Helper::model(Config::get('abac.abac_user_role', 'abac_user_role'))->where('role_id', $modelRole->role_id)->where('user_id', $user_id)->first();
        if ($m) {
            return false;
        }

        $m = Helper::model(Config::get('abac.abac_user_role', 'abac_user_role'));
        $m->user_id = $user_id;
        $m->role_id = $modelRole->role_id;
        $b = $m->save();
        if ($b) {
            return true;
        }

        return false;
    }


    /**
     * add user perission
     * @param $user_id
     * @param $permission int is permission id or string is permission name
     * @return bool
     * @throws \Exception
     */
    public function addUser2Permission($user_id, $permission) {
        $modelP = Helper::model(Config::get('abac.abac_permission', 'abac_permission'), 'pid');
        if (is_int($permission)) {
            $modelP->where('pid', $permission);
        } else if (is_string($permission)) {
            $modelP->where('pname', $permission);
        }
        $modelP = $modelP->first();
        if (!$modelP) {
            throw new \Exception('permission is not found!');
        }

        $m = Helper::model(Config::get('abac.abac_user_permission', 'abac_user_permission'))->where('pid', $modelP->pid)->where('user_id', $user_id)->first();
        if ($m) {
            return false;
        }

        $m = Helper::model(Config::get('abac.abac_user_permission', 'abac_user_permission'));
        $m->user_id = $user_id;
        $m->pid = $modelP->pid;
        $b = $m->save();
        if ($b) {
            return true;
        }

        return false;
    }


}