<?php

require_once 'app/pip.app.main.php';

$db = new DB2();
if($db){
  $results = $db->table("test")->select()->join("INNER JOIN", "test", ["id", "id"], "AND", ["id"=>"id"]);
  print_r($db->constrains);
  print_r($results);
  $results = $db->table("users")->select()->where(["id"=>"123"]);
  print_r($results);
}

?>
