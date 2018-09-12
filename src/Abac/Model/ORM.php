<?php

namespace Abac\Model;

use Illuminate\Database\Eloquent\Model;

class ORM extends Model {
    public $connection;
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    public $table;

    public $primaryKey;

    protected $dateFormat = 'U';

    public function __construct(){
        $this->connection = Statics::$connection;
        $this->table = Statics::$table;
        $this->primaryKey = Statics::$primaryKey;
    }

    public static function init($table,$primaryKey = 'id',$connection = ''){
        if($connection) Statics::$connection = $connection;
        Statics::$table = $table;
        Statics::$primaryKey = $primaryKey;
        return new ORM;
    }

    public function __call($a,$b){
        Statics::$connection = $this->connection;
        Statics::$table = $this->table;
        Statics::$primaryKey = $this->primaryKey;
        return parent::__call($a,$b);
    }
}

class Statics {
    public static $connection;
    public static $table;
    public static $primaryKey;
}
