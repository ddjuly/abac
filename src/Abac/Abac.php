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
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * @permission
     * @param $role int role_id or string role_name
     * @return bool
     */
    public function hasRole($role)
    {
        $user = $this->user();
        $user_id = $user->id;

        $sql = "SELECT * FROM ".Config::get('abac.abac_user_role', 'abac_user_role')." a 
                LEFT JOIN ".Config::get('abac.abac_role', 'abac_role')." b ON a.role_id=b.role_id
                WHERE a.user_id=:user_id 
                ".
                value(function () use ($role) {
                    if (is_string($role)) {
                        return " AND b.role_name=:role ";
                    } else if (is_int($role)) {
                        return " AND b.role_id=:role ";
                    }
                })
                ."
                ";
        $row = Helper::select_row($sql, ['user_id' => $user_id, 'role' => $role]);
        if ($row) {
            return true;
        }

        return false;
    }

    public function hasPermission($permission)
    {
        $user = $this->user();
        $user_id = $user->id;

        $sql = "SELECT * FROM ".Config::get('abac.abac_user_permission', 'abac_user_permission')." a 
                LEFT JOIN ".Config::get('abac.abac_permission', 'abac_permission')." b ON a.pid=b.pid 
                WHERE a.user_id=:user_id 
                ".
                value(function () use ($permission) {
                    if (is_string($permission)) {
                        return " AND b.pname=:permission ";
                    } else if (is_int($permission)) {
                        return " AND b.pid=:permission ";
                    }
                })
                ."
                ";
        $row = Helper::select_row($sql, ['user_id' => $user_id, 'permission' => $permission]);
        if ($row) {
            return true;
        }

        $sql = "SELECT * FROM ".Config::get('abac.abac_user_role', 'abac_user_role')." a 
                LEFT JOIN ".Config::get('abac.abac_role', 'abac_role')." b ON a.role_id=b.role_id 
                LEFT JOIN ".Config::get('abac.abac_role_permission', 'abac_role_permission')." c ON b.role_id=c.role_id 
                LEFT JOIN ".Config::get('abac.abac_permission', 'abac_permission')." d ON c.pid=d.pid 
                WHERE a.user_id=:user_id 
                ".
                value(function () use ($permission) {
                    if (is_string($permission)) {
                        return " AND d.pname=:permission ";
                    } else if (is_int($permission)) {
                        return " AND d.pid=:permission ";
                    }
                })
                ."
                ";
        $row = Helper::select_row($sql, ['user_id' => $user_id, 'permission' => $permission]);
        if ($row) {
            return true;
        }

        return false;
    }


    /**
     */
    public function user()
    {
        return $this->app->auth->user();
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