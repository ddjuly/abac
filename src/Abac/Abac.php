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

    const DELIMITER = '|';

    /**
     * Laravel application
     */
    public $app;

    /**
     * current user id
     */
    public $userId = 0;

    private $isQuery = false;
    private $roles = [];
    private $rolePermissions = [];
    private $userPermissions = [];

    /**
     * @return void
     */
    public function __construct($app) {
        $this->app = $app;
        $user = $this->app->auth->user();
        if ($user && $user->id) {
            $this->userId = $user->id;
        }
    }


    /**
     * query all roles and permissions of this user
     * @permission
     */
    private function queryInfos() {
        if (!$this->isQuery && !$this->userId) {
            $this->isQuery = true;
            return;
        }

        $sql = "SELECT b.role_id, c.role_name, d.pid, e.pname, f.pid AS upid, g.pname AS upname FROM ".Config::get('abac.abac_users', 'users')." a
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
    public function hasRole($role, $validateAll = false) {
        $this->queryInfos();

        if (is_string($role)) {
            return in_array($role, $this->roles);
        } else if (is_int($role)) {
            return array_key_exists($role, $this->roles);
        } else if (is_array($role)) {
            $same = array_intersect_assoc($role, $this->roles);
            if ($validateAll && count($same) == count($role)) {
                return true;
            }
            if (count($same) > 0) {
                return true;
            }
        }

        return false;
    }


    /**
     * @param $permission
     * @return bool
     */
    public function hasPermission($permission, $validateAll = false) {
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
        } else if (is_array($permission)) {
            $sameUser = array_intersect_assoc($permission, $this->userPermissions);
            if (!$validateAll && count($sameUser) > 0) {
                return true;
            }
            $sameRole = array_intersect_assoc($permission, $this->rolePermissions);
            if (!$validateAll && count($sameUser) > 0) {
                return true;
            }

            $merge = array_merge($sameUser, $sameRole);
            if ($validateAll && count($merge) == count($permission)) {
                return true;
            }
        }

        return false;
    }


    /**
     * @permission
     * @param $permission
     */
    public function ability($roles = null, $permissions = null, $validateAll = false) {
        $this->queryInfos();

        if ($roles) {
            if (is_array($roles)) {
                $rarr = $roles;
            } else if (is_string($roles)) {
                $rarr = explode(self::DELIMITER, $roles);
            }

            foreach ($rarr as $rval) {
                $b = in_array($rval, $this->roles);
                if ($b) {
                    if (!$validateAll) {
                        return true;
                    }
                } else {
                    if ($validateAll) {
                        return false;
                    }
                }
            }
        }

        if ($permissions) {
            if (is_array($permissions)) {
                $parr = $permissions;
            } else if (is_string($permissions)) {
                $parr = explode(self::DELIMITER, $permissions);
            }

            foreach ($parr as $pval) {
                $b = in_array($pval, $this->userPermissions);
                if ($b) {
                    if (!$validateAll) {
                        return true;
                    }
                } else {
                    $b = in_array($pval, $this->rolePermissions);
                    if ($b) {
                        if (!$validateAll) {
                            return true;
                        }
                    } else {
                        if ($validateAll) {
                            return false;
                        }
                    }
                }
            }
        }

        if ($validateAll) {
            return true;
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
            $modelP = $modelP->where('pid', $permission);
        } else if (is_string($permission)) {
            $modelP = $modelP->where('pname', $permission);
        }
        $modelP = $modelP->first();
        if (!$modelP) {
            throw new \Exception('permission id is not found!');
        }

        $modelRole = Helper::model(Config::get('abac.abac_role', 'abac_role'), 'role_id');
        if (is_int($role)) {
            $modelRole = $modelRole->where('role_id', $role);
        } else if (is_string($role)) {
            $modelRole = $modelRole->where('role_name', $role);
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
        $modelRole = Helper::model(Config::get('abac.abac_role', 'abac_role'), 'role_id');
        if (is_int($role)) {
            $modelRole = $modelRole->where('role_id', $role);
        } else if (is_string($role)) {
            $modelRole = $modelRole->where('role_name', $role);
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
            $modelP = $modelP->where('pid', $permission);
        } else if (is_string($permission)) {
            $modelP = $modelP->where('pname', $permission);
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


    /**
     * delete role, and all of relationship will be delete.
     * @permission
     */
    public function delRole($role) {
        $role_id = $role;
        if (is_string($role)) {
            $role = Helper::model(Config::get('abac.abac_role', 'abac_role'), 'role_id')->where('role_name', $role)->first();
            if (!$role) {
                return false;
            }
            $role_id = $role->role_id;
        } else {
            $role = Helper::model(Config::get('abac.abac_role', 'abac_role'), 'role_id')->where('role_id', $role)->first();
            if (!$role) {
                return false;
            }
        }

        try {
            \DB::beginTransaction();
            $b = $role->delete();
            if (!$b) {
                throw new \Exception();
            }

            Helper::model(Config::get('abac.abac_role_permission', 'abac_role_permission'))
                ->where('role_id', $role_id)
                ->delete();

            Helper::model(Config::get('abac.abac_user_role', 'abac_user_role'))
                ->where('role_id', $role_id)
                ->delete();
            \DB::commit();

            return true;
        } catch (\Exception $e) {
            \DB::rollback();
        }

        return false;
    }


    /**
     * delete permission
     * @permission
     */
    public function delPermission($permission) {
        $pid = $permission;
        if (is_string($permission)) {
            $permission = Helper::model(Config::get('abac.abac_permission', 'abac_permission'), 'pid')
                ->where('pname', $permission)
                ->first();
            if (!$permission) {
                return false;
            }
            $pid = $permission->pid;
        } else {
            $permission = Helper::model(Config::get('abac.abac_permission', 'abac_permission'), 'pid')
                ->where('pid', $permission)
                ->first();
            if (!$permission) {
                return false;
            }
        }

        try {
            \DB::beginTransaction();
            $b = $permission->delete();
            if (!$b) {
                throw new \Exception();
            }

            Helper::model(Config::get('abac.abac_role_permission', 'abac_role_permission'))
                ->where('pid', $pid)
                ->delete();

            Helper::model(Config::get('abac.abac_user_permission', 'abac_user_permission'))
                ->where('pid', $pid)
                ->delete();
            \DB::commit();

            return true;
        } catch (\Exception $e) {
            \DB::rollback();
        }

        return false;
    }


    public function removePermissionOfRole($permission, $role) {
        if (is_string($permission)) {
            $permission = Helper::model(Config::get('abac.abac_permission', 'abac_permission'))->where('pname', $permission)->first();
            if (!$permission) {
                return false;
            }
            $permission = $permission->pid;
        }
        if (is_string($role)) {
            $role = Helper::model(Config::get('abac.abac_role', 'abac_role'))->where('role_name', $role)->first();
            if (!$role) {
                return false;
            }
            $role = $role->role_id;
        }
        return Helper::model(Config::get('abac.abac_role_permission', 'abac_role_permission'))
            ->where('role_id', $role)
            ->where('pid', $permission)
            ->delete();
    }


    public function removePermissionOfUser($user_id, $permission) {
        if (is_string($permission)) {
            $permission = Helper::model(Config::get('abac.abac_permission', 'abac_permission'))->where('pname', $permission)->first();
            if (!$permission) {
                return false;
            }
            $permission = $permission->pid;
        }
        return Helper::model(Config::get('abac.abac_user_permission', 'abac_user_permission'))
            ->where('user_id', $user_id)
            ->where('pid', $permission)
            ->delete();
    }


    public function removeRoleOfUser($user_id, $role) {
        if (is_string($role)) {
            $role = Helper::model(Config::get('abac.abac_role', 'abac_role'))->where('role_name', $role)->first();
            if (!$role) {
                return false;
            }
            $role = $role->role_id;
        }
        return Helper::model(Config::get('abac.abac_user_role', 'abac_user_role'))
            ->where('user_id', $user_id)
            ->where('role_id', $role)
            ->delete();
    }


}