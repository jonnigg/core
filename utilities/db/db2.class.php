<?php

class DB2
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
//@var limit is used to specify the constrains placed type for a selection call
//
protected $constraints;

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

public function join($join_type, $join_table, $join_constrains, $conjunction = null, $args = null){

  //Making sure none are  null
  if($join_type && $join_table && $join_constrains && is_array($join_constrains)){
    $constraint_one = $join_constrains[0];
    $constraint_two = $join_constrains[1];
    $count = 1;


    switch  ($this->action){
      case 'select':
      if($this->statement != null){
        $this->statement .= " $join_type $join_table on $constraint_one=$constraint_two";
        if($conjunction && $args && is_array($args)){
          if(count($this->constraints) > 0){$count = count($this->constraints);}
          foreach ($args as $key => $value) {
          $this->statement .= " $conjunction $key=:value$count";
          $this->constraints["value$count"] = $value;
        }}
        return $this;
      }
      break;

      default:
      throw new Exception("Joins are only available on select statements");

      break;
    }
  }
}

public function where($args, $conjunction = null){
  if(is_array($args) && count($args) > 0){
    $counter = 1;
    $this->constraints = $args;

    $this->statement .= " where";
    foreach ($args as $key => $value) {
      $this->statement .= " $key=:$key";
      if($conjunction && count($args) != $counter){$this->statement .= " $conjunction";}
    }
  }else{throw new Exception("Error: Where constrains applied without proper array. Please make sure an array is password with keys/values", 1);}

  return $this;
}

public function raw(){
  $this->request_type = "raw";
  return $this;
}

#mark PERFORM ACTION
public function execute(){
  if($this->order){$this->statement .= " $this->order";}
  if($this->limit && is_numeric($this->limit) && $this->limit > 0){$this->statement .= " limit $this->limit";}
  if(substr($this->statement, -1) != ';') {$this->statement .= ";";}

  $statement = $this->handle->prepare($this->statement);
  if(count($this->constraints) > 0 && is_array($this->constraints)){

    foreach ($this->constraints as $key => $value) {
      $statement->bindValue(":$key", $value);
    }
  }

  if($statement->execute()){

  switch ($this->action) {
    case 'select':

      $results = $statement->fetchAll();
      return $this->results("success", null, $results);

      break;

    case 'insert':
    return $this->results("success");

    default:

      break;
    }
  }else{return $this->results("failed", $statement->errorCode());}
}

//Function: Select
//Purpose: Perform selection calls from the database

public function select($args = null){
$this->action = "select";
$this->statement = "";

if(is_array($args) && count($args) > 0){
  $this->statement .= "select";

  foreach($args as $col){
    $this->statement .= " $col,";
  }

  $this->statement = rtrim($this->statement, ',');
  $this->statement .= " from $this->table";

}else{$this->statement .= "select * from $this->table";}

return $this;

}

//Function: Select
//Purpose: Perform selection calls from the database

public function insert($args){
$this->action = "insert";
$this->statement = "";
$this->constraints = $args;

if(is_array($args) && count($args) > 0){
  $this->statement .= "insert into $this->table (";


  foreach($args as $col => $value){
    $this->statement .= " $col,";
  }

  $this->statement = rtrim($this->statement, ',');
  $this->statement .= ") values(";

  foreach($args as $col => $value){
    $this->statement .= ":$col,";
  }
  $this->statement = rtrim($this->statement, ',');
  $this->statement .= ");";

}else{throw new Exception("Error: Trying to insert with no values. Please provide a key/value array for insertion", 1);}

return $this;

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

protected function prep_contraints($args){

}

function array_push_assoc($array, $key, $value){
$array[$key] = $value;
}

protected function clean_obj(){
  $this->table = "";
  $this->statement = "";
  $this->order = "";
  $this->limit = NULL;

}

protected function results($result, $error = null, $data = null){
if($data){$count = count($data);}else{$count = 0;}
$result_array = array("result"=>$result, "response"=>$error, "count"=>$count, "data"=>$data);
return $result_array;
}

}



?>
