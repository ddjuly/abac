<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 18/9/12
 * Time: 上午11:08
 */

namespace Abac;

use Abac\Model\ORM;
use Illuminate\Support\Facades\DB;

class Helper {

    /**
     * object to array
     * @synopsis toArray
     * @param $arr
     * @return
     */
    public static function toArray($arr){
        if(!$arr) return $arr;
        return json_decode(json_encode($arr),true);
    }


    /**
     * 查询sql一条
     * @param $sql
     * @param array $params
     * @return mixed|null
     */
    public static function select_row($sql, $params = []){
        $arr = DB::select($sql, $params);
        if (count($arr) > 0) {
            return self::toArray($arr[0]);
        }
        return null;
    }


    /**
     * 查询sql列表
     * @param $sql
     * @param $params 参数
     * @return mixed|null
     */
    public static function select_all($sql, $params = []){
        $arr = DB::select($sql, $params);
        if (count($arr) > 0) {
            return self::toArray($arr);
        }
        return [];
    }


    /**
     * 获取单个字段
     * @param $sql
     * @param array $params
     * @return null
     */
    public static function select_one($sql, $params = []){
        $arr = DB::select($sql, $params);
        if (count($arr) > 0) {
            return current(self::toArray($arr[0]));
        }
        return null;
    }


    /**
     * 获取一列数组
     * @param $sql
     * @param array $params
     * @return mixed|null
     */
    public static function select_cols($sql, $params = []){
        $arr = DB::select($sql, $params);
        if (count($arr) > 0) {
            $arr = self::toArray($arr);
            $result = [];
            foreach ($arr as $val) {
                $result[] = current($val);
            }
            return $result;
        }
        return null;
    }



    /**
     *
     * @synopsis model
     * @param $table
     * @param $primaryKey
     * @return
     */
    public static function model($table, $primaryKey = 'id', $connection = ''){
        return ORM::init($table,$primaryKey,$connection);
    }

}