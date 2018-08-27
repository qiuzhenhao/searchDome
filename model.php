<?php
/**
 * Created by PhpStorm.
 * User: xiaoqiu
 * Date: 2018/8/27
 * Time: 16:25
 */

    class dbLink{
        public function mysqlDb(){
            //连接数据库
            $user = "root";
            //$pass = "";
            $pass = "123456";
            $dbh = new PDO('mysql:host=localhost;dbname=testdb', $user, $pass,array(PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8"));
            return $dbh;
        }
        public function memcache(){
            // 连接本地memcached
            $memcache = new Memcache();
            $memcache->connect('127.0.0.1',11211) or die('shit');
            return $memcache;
        }
    }