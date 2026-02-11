<?php

require_once __DIR__ . '/../vendor/autoload.php'; 

use Illuminate\Database\Capsule\Manager as DB;

// lecture du conf.ini
$config = parse_ini_file(__DIR__ . '/conf.ini');

// initialisation
$db = new DB();

$db->addConnection([
    'driver'    => $config['driver'],
    'host'      => $config['host'],
    'database'  => $config['database'],
    'username'  => $config['username'],
    'password'  => $config['password'],
    'charset'   => $config['charset'],
    'collation' => $config['collation'],
    'prefix'    => '',
]);

$db->setAsGlobal();
$db->bootEloquent();