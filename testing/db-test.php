<?php

require_once 'app/pip.app.main.php';

$db = new DB2();
if($db){
  $db->table("test")->select();
}

?>
