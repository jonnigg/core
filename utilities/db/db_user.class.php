<?php


class DB_USER extends DB2{

public function __construct(){
parent::__construct();

}

public function register($username, $email, $password){

try{

  $date = date_create();
  $unix_date = date_timestamp_get($date);

  $unique_id = $this->generate_random_string_with_string_count(32);
  $hash = $this->generate_hash_from_password($password);

  $create_user_request = $this->handle->prepare("insert into users(unique_id, username, email, hash, registration_date) values(:unique_id, :username, :email, :hash, :registration_date);");

  $create_user_request->bindParam(':unique_id', $unique_id);
  $create_user_request->bindParam(':username',$username);
  $create_user_request->bindParam(':email',strtolower($email));
  $create_user_request->bindParam(':hash', $hash);
  $create_user_request->bindParam(':registration_date',$unix_date);

  if($create_user_request->execute()){return $this->results("success");}else{ return $this->results("failed", $create_user_request->errorCode()); }

  }catch(PDOException $e){
  return $this->results("failed", $e);
  }
}

public function authorize($username, $password){

try{

$users = $this->table('users')->select()->where(["username"=>$username])->execute();

if(intval($users['count']) > 0){
$user_data = $users['data'][0];

$user = $user_data['username'];
$hash = $user_data['hash'];

//These need to be converted to include the encryption after we insert change password

if($this->validate_hash($password, $hash)){
  $profile_results = $this->table('profile')->select()->where(["user_id"=>$user_data['id']])->execute();
  $profile_data = $profile_results['data'][0];
  $profile = ["first_name"=>$profile_data['first_name'], "last_name"=>$profile_data['last_name']];
  return $this->results("success", "none", $profile);
}
else{return $this->results("failed", "Invalid username or password"); }
}else{return $this->results("failed", "User does not exist");}


}catch(PDOException $e){
return $this->results("failed", $e);
}

}

/*******************************
**
**UTILITY FUNCTIONS
**
*******************************/
private function generate_hash_from_password($password){

/*$cost = 10;
$salt = strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');
$salt = sprintf("$2a$%02d$", $cost) . $salt;

$hash = crypt($password, $salt);*/

$hash = password_hash($password, PASSWORD_DEFAULT);

return $hash;

}

private function validate_hash($user_password, $hash){

/*if (hash_equals($password, crypt($user_password, $password))){return true;}
else{return false;}*/

if (password_verify($user_password, $hash)){return true;}
else{return false;}

}

private function convert_string_to_lowercase($string){


}

private function generate_random_string_with_string_count($count){
$characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
$string = '';
for ($i = 0; $i < $count; $i++) {
$string .= $characters[rand(0, strlen($characters) - 1)];
}

return $string;

}


}

?>
