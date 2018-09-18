<?php

namespace Abac\command;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Zizaco\Entrust
 */

use Abac\Helper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class CreateTableCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'abac:create-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';
   
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->handle();
    }

    /**
     * Execute the console command for Laravel 5.5+.
     *
     * @return void
     */
    public function handle()
    {
        $tbPrefix = $this->ask('Please input table prefix, default is abac!', 'abac');

        echo "The table prefix is {$tbPrefix}_\n";
        echo "Starting create tables!\n";

        $sql = "CREATE TABLE if not exists `{$tbPrefix}_permission` (
                 `pid` int(11) NOT NULL AUTO_INCREMENT,
                 `pname` varchar(255) NOT NULL,
                 `created_at` bigint(20) NOT NULL DEFAULT '0',
                 `updated_at` bigint(20) NOT NULL DEFAULT '0',
                 PRIMARY KEY (`pid`),
                 UNIQUE KEY `pname` (`pname`(16))
                ) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4";
        Helper::select_row($sql);

        $sql = "CREATE TABLE if not exists `{$tbPrefix}_role` (
                 `role_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'role id',
                 `role_name` varchar(255) NOT NULL COMMENT 'role name',
                 `created_at` bigint(20) NOT NULL DEFAULT '0',
                 `updated_at` bigint(20) NOT NULL DEFAULT '0',
                 PRIMARY KEY (`role_id`),
                 UNIQUE KEY `role_name` (`role_name`(16))
                ) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COMMENT='role table'";
        Helper::select_row($sql);

        $sql = "CREATE TABLE if not exists `{$tbPrefix}_role_permission` (
                 `id` int(11) NOT NULL AUTO_INCREMENT,
                 `role_id` int(11) NOT NULL,
                 `pid` int(11) NOT NULL,
                 `created_at` bigint(20) NOT NULL DEFAULT '0',
                 `updated_at` bigint(20) NOT NULL DEFAULT '0',
                 PRIMARY KEY (`id`),
                 KEY `role_id` (`role_id`),
                 KEY `pid` (`pid`)
                ) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4";
        Helper::select_row($sql);

        $sql = "CREATE TABLE if not exists `{$tbPrefix}_user_permission` (
                 `id` int(11) NOT NULL AUTO_INCREMENT,
                 `user_id` int(11) NOT NULL,
                 `pid` int(11) NOT NULL,
                 `created_at` bigint(20) NOT NULL DEFAULT '0',
                 `updated_at` bigint(20) NOT NULL DEFAULT '0',
                 PRIMARY KEY (`id`),
                 KEY `user_id` (`user_id`),
                 KEY `pid` (`pid`)
                ) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4";
        Helper::select_row($sql);

        $sql = "CREATE TABLE if not exists `{$tbPrefix}_user_role` (
                 `id` int(11) NOT NULL AUTO_INCREMENT,
                 `user_id` int(11) NOT NULL,
                 `role_id` int(11) NOT NULL,
                 `created_at` bigint(20) NOT NULL DEFAULT '0',
                 `updated_at` bigint(20) NOT NULL DEFAULT '0',
                 PRIMARY KEY (`id`),
                 KEY `user_id` (`user_id`),
                 KEY `role_id` (`role_id`)
                ) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4";
        Helper::select_row($sql);

        echo "create complete!\n";
        echo "\n";

        echo "Starting copy config file.\n";
        $configContent = file_get_contents(__DIR__ . '/../../config/config.php');
        $configContent = str_replace('abacprefix_', "{$tbPrefix}_", $configContent);
        $b = file_put_contents(base_path('config/abac.php'), $configContent);
        if ($b) {
            echo "copy config file complete!\n";
        } else {
            echo "copy config file fail! please add create it.\n";
        }

        echo "\n";
        echo "by the way, please edit 'abac_users' key of the config file if your users table name is not 'users'!\n";
    }

}
