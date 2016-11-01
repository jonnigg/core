<?php

class DB
{
//
//@var handle is our connection to the database. This will be accessed in the database class only
//
protected $handle;

//
//@var table passes in the table to run the command to. Without this, only RAW commands are alloweed
//
protected $table;

//
//@var statement is used to collect the RAW SQL command.
//
protected $statement;

//
//@var order is used to collect the order type for a selection call
//
protected $order;

//
//@var limit is used to specify the limit type for a selection call
//
protected $limit;

//
//@var request_type is used to determain the type of SQL command.
//
protected $request_type = "controlled";

//
//@var request_type is used to determain the acual SQL command.
//
protected $action;

function __construct(){

$config_path = realpath(dirname(dirname(__DIR__))."/config/config-properties.php");

include($config_path);

//The path for this is found in the pip_root/config/configuration.class.php
$path_to_db_access = Configuration::DatabaseAccess($db_creds);

require_once($path_to_db_access);

//Start the connection!
$dbh = new PDO($dsn, $username, $password, $opt);
if($dbh){$this->handle = $dbh;}

}

//Function: table
//Purpose: The (1) check if the table exists and (2) assign the table to the object variable.
//Result: The object making this request is assigned the table so that it can be passed into the request
//Additional: It is imoortant to note that this is NOT a class method. This is a object method. Class method is being looked into.
public function table($t){
//perform table check to make sure this table exists.
$this->table = $t;
return $this;
}

//Function: order
//Purpose: To force an order
//Result: The data is ordered by the column defined
public function order($column, $direction = null){
if($direction == null){$this->order = "order by ".$column;}
else{$this->order = "order by ".$column." ".$direction;}
return $this;
}

public function limit($limit_count){
//perform table check to make sure this table exists.
$this->limit = $limit_count;
return $this;
}

public function raw(){
  $this->request_type = "raw";
  return $this;
}

//Function: Select
//Purpose: Perform selection calls from the database
//Result: Returns an array of the results. If there are no results, 0 is reeturned.
//Additional: Since function overloading is not properly defined in PHP this is a hack attempt at overloading
//The select function is called and the args are evaluted. The proper select function is performed based on the args
//If sent with no args, a full select is performed on the table defined. If no table is defined, an error is returned.

public function select(){

$this->action = "select";

$numargs = func_num_args();

if($numargs == 0){
  if($this->table){return self::controlledSelect();}
  else{
    die("Error: Not enough information to perform select\n");
  }}

if($numargs == 1 && is_array(func_get_arg(0))){

  return self::controlledSelect(false, func_get_arg(0));


}else if($numargs == 2 && is_array(func_get_arg(0))){

  return self::controlledSelect(true, func_get_arg(0),func_get_arg(1));

}else {

  if($numargs == 2 && $this->request_type == "raw"){return self::rawRequest(func_get_arg(0), func_get_arg(1));}
  elseif($this->request_type == "raw"){return self::rawRequest(func_get_arg(0));}
  else{return $this->results("failed", "Raw querry requested without explicitly calling a raw request. Please run raw()");}
}

}

private function controlledSelect($isConjunction, $args = null, $conjunction = null){

if($this->order){$orderData = true;}

if($args == null){

  if($orderData){$request = "select * from $this->table $this->order;";}
  else{$request = "select * from $this->table;";}

  $limit_string = " limit $this->limit;";
  if($this->limit && is_numeric($this->limit) && $this->limit > 0){$request = str_replace(";", $limit_string, $request);}

  $statement = $this->handle->prepare($request);

  $this->statement = "select * from $this->table";

  if($statement->execute()){

  if($statement->rowCount() > 0){
        $results = $statement->fetchAll();
        return $this->results("success", null, $results);
    }else{
      return $this->results("failed", "No data returned", $results);
    }
  }else{return $this->results("failed", $statement->errorCode());}

}elseif($isConjunction == false){

  $has_key;
  $key_val_array = $args;
  $bindValString = "";

  if(count(array_keys($args)) > 0){
    $search_key = array_keys($args)[0];
    $search_value = array_values($args)[0];
    $has_key = true;
  }else{
    return $this->results("failed", "Passing array of select constraints without keys. I need to know what column belongs to which parameter");

  }

foreach ($key_val_array as $key => $value) {
  $bindValString .= "(".$key."=:".$key.")";
}

if($orderData){$request = "select * from $this->table where $bindValString $this->order;";}
else{$request = "select * from $this->table where $bindValString;";}

$limit_string = " limit $this->limit;";
if($this->limit && is_numeric($this->limit) && $this->limit > 0){$request = str_replace(";", $limit_string, $request);}

$statement = $this->handle->prepare($request);

foreach ($key_val_array as $key => $value) {
  $statement->bindValue(":".$key, $value);
}

if($statement->execute()){
if($statement->rowCount() > 0){
      $results = $statement->fetchAll();
      return $this->results("success", null, $results);
  }else{return $this->results("success", "No Results Found");}

}else{return $this->results("failed", $statement->errorCode());}


}else{
  $has_key;
  $key_val_array = $args;
  $counter = 1;
  $bindValString = "";

  if(count(array_keys($args)) > 0){
    $search_key = array_keys($args)[0];
    $search_value = array_values($args)[0];
    $has_key = true;
  }else{
    return $this->results("failed", "Passing array of select constraints without keys. I need to know what column belongs to which parameter");
    $search_var = $args[0];
    $has_key = false;
  }
foreach ($key_val_array as $key => $value) {
  $bindValString .= " (".$key."=:".$key.") ".$conjunction;
}

$bindValStringUnCleaned = explode(' ', $bindValString);
$bindValStringUnCleaned[count($bindValStringUnCleaned)-1]='';
$bindValStringCleaned=implode(' ',$bindValStringUnCleaned);

if($orderData){$request = "select * from $this->table where".$bindValStringCleaned." $this->order;";}
else{$request = "select * from $this->table where".$bindValStringCleaned.";";}

$limit_string = " limit $this->limit;";
if($this->limit && is_numeric($this->limit) && $this->limit > 0){$request = str_replace(";", $limit_string, $request);}

$statement = $this->handle->prepare($request);

foreach ($key_val_array as $key => $value) {
  $statement->bindValue(":".$key, $value);
}

if($statement->execute()){
if($statement->rowCount() > 0){
      $results = $statement->fetchAll();
      return $this->results("success", null, $results);

  }else{return $this->results("success", "No Results Found");}

}else{return $this->results("failed", $statement->errorCode());}

}

}

public function insert(){

$this->action = "insert";

$numargs = func_num_args();

if($numargs == 0){throw new Exception("Error: Not enough information to perform insertion\n");}

if($numargs == 1 && $this->request_type == "raw"){
return self::rawRequest(func_get_arg(0));

}elseif($this->request_type == "raw"){
  //Prep the insertion with a arg(array) => arg(array) relationship
  return self::rawRequest(func_get_arg(0), func_get_arg(1));
}else{
  if($numargs == 1){
    $counter = 1;
    $columns = array_keys(func_get_arg(0));
    $values = array_values(func_get_arg(0));

    $col_string="";
    $val_string="";

    foreach($columns as $col){$col_string .= $col.",";}
    $col_string = substr($col_string, 0, -1);

    foreach($values as $val){$val_string .= "?,";}
    $val_string = substr($val_string, 0, -1);

    $request = "insert into $this->table($col_string) values($val_string);";
    $statement = $this->handle->prepare($request);
    if($values){

      foreach($values as $val){$statement->bindValue($counter, $val); $counter++;}
    }

    if($statement->execute()){return $this->results("success");}
    else{return $this->results("failed", $statement->errorCode());}

  }

  if($numargs == 2){

  }
}


}

private function controlledInsert($args = null){

}

private function rawRequest($request, $args = null){

/*if(strpos($request, ":") && $args == null){
throw new Exception("Error: Raw query requested with bindings with no arguments provided\n");
}*/

if($args == null){
  //Prepare the RAW request
  $statement = $this->handle->prepare($request);

  //Run the raw request. Return the results according the the action type.
  if($statement->execute()){

    switch ($this->action){
      case("select"):

      if($statement->rowCount() > 0){
        $results = $statement->fetchAll();
        return $this->results("success", null, $results);

      }else{return $this->results("success", "No Results Found");}

      break;

      case("insert"):
      return "success";

      break;

      default:
      break;
}

}else{return $this->results("failed", $statement->errorCode());}

}else{
    $has_key;
    if(is_array($args)){

    if(count(array_keys($args)) > 0){
      $search_key = array_keys($args)[0];
      $search_value = array_values($args)[0];
      $has_key = true;
    }else{
      $search_value = $args[0];
      $has_key = false;
    }

  }else{
  $search_value = $args;
  $has_key = false;
}

    $statement = $this->handle->prepare($request);
    if($has_key){$statement->bindValue(1, $search_value);}
    else{$statement->bindValue(1, $search_value);}


    if($statement->execute()){
      switch ($this->action){

        case("select"):
        if($statement->rowCount() > 0){
          $results = $statement->fetchAll();
          return $this->results("success", null, $results);
        }else{
          return $this->results("success", "No Results Found");
        }

        break;

        case("insert"):
        return "success";
        break;

        default:
        break;
      }

    }else{
      $er = 'Error occurred:'.implode(":",$this->handle->errorInfo());
      $this->results("failed", $er, $results);
    }

  }

}

protected function results($result, $error = null, $data = null){
if($data){$count = count($data);}else{$count = 0;}
$result_array = array("result"=>$result, "response"=>$error, "count"=>$count, "data"=>$data);
return $result_array;
}

}



?>
