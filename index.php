<?php
require_once "./vendor/autoload.php";

global $DBA;
$DBA = new \Tina4\DataSQLite3("tina4_jobs.db");

$config = new \Tina4\Config(static function (\Tina4\Config $config){
  //Your own config initializations 
});

echo new \Tina4\Tina4Php($config);