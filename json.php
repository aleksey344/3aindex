<?php
    $code='{sgXdYvgL';
    if(empty($_GET['code']) || $_GET['code'] != $code) {
      header("Content-type: application/json; charset=utf-8");
		  die('{"status":"Access denied!!"}');
      exit;
	   }

	include_once 'admin/includes.php';

  $session_key = '';
  $email='';
  $name='';

	if (isset($_GET['action'])) $action=$_GET['action'];
	if (isset($_GET['subaction'])) $subaction=$_GET['subaction'];
  if (isset($_GET['session_key'])) $session_key=$_GET['session_key'];
  if (isset($_GET['mail'])) $email=$_GET['mail'];
  if (isset($_GET['name'])) $name=$_GET['name'];


	$donor = new Donor();
	$config = new Config();
	$db_main = $config::db_main;
	$connect = $config::get_connect();
//	echo ($config::db_main.'<br>');
//	$donor->GetListCiberCurrenciesPoloniex($config::get_connect(), $config::db_main);
//	exit;
if(!isset($action)){
  header("Content-type: application/json; charset=utf-8");
  die('{"status":"Action not set!!"}');
	exit;
}
elseif($action=='getcalcportfel') { //action=
  header("Content-type: application/json; charset=utf-8");
  $email = $donor->GetUserEMAIL($config::get_connect(), $config::db_main, $session_key);
  $calc = $donor->GetCalcPortfel($config::get_connect(), $config::db_main, $email);
  $contents = json_encode($calc);
  echo($contents);
}
elseif($action=='linkseskeyemail') { //action=
  $contents = $donor->CreateNewListUsers($config::get_connect(), $config::db_main, $name, ' ', $email, $session_key, ' ', ' ');
  if ($contents===false) {
    header("Content-type: application/json; charset=utf-8");
    die('{"status":"Action not set!!"}');
  	exit;
  }
//  email=".$code_email."&session_key=
  header("Content-type: application/json; charset=utf-8");
  echo('{"status":"ok"}');
  exit;
}
elseif($action=='createportfel') { //action=
  $rez_arr=array();
  foreach ($_GET['namecurid'] as $key => $value) {
    $rez_arr[$value] =  $_GET['count'][$key];
  }
  $contents = $donor->CreateNewPortfel($config::get_connect(), $config::db_main, $email, $rez_arr); // Создание нового портфеля с валютами. $listCurrentANDCount = array(IdCurrenciesPair => countCurrencies)
  //$contents = $donor->CreateNewListUsers($config::get_connect(), $config::db_main, ' ', ' ', $email, $session_key, ' ', ' ');
  if ($contents===false) {
    header("Content-type: application/json; charset=utf-8");
    die('{"status":"Action not set!!"}');
  	exit;
  }
//  email=".$code_email."&session_key=
  header("Content-type: application/json; charset=utf-8");
  echo('{"status":"ok"}');
  exit;
}
// http://russian-hackers.pro/json.php?code={sgXdYvgL&action=createportfel&mail=YW5pa2l0ZW5rb0BrYXRhbWFyYW5vdi5ydQ==&session_key=MzZjYWQzNmRiMmVlZWE4ODY2YWMyMzU5NjMyYjk1Yzc=&namecurid[4]=17&count[4]=10&namecurid[3]=6&count[3]=5
///json.php?code={sgXdYvgL&action=linkseskeyemail&email=".$code_email."&session_key=

?>
