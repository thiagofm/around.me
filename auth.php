<?php
  require 'conn.php';

  if (!$_REQUEST){
    die();
  }

  $longitude = $_REQUEST['longitude'];
  $latitude = $_REQUEST['latitude'];
  session_start();

  $conn = new Mysql();

  if(isset($_SESSION['user_id'])){
  } else {

    //gerar nome do usuario
    $username = uniqid('anon');
    $found_name = false;

    while(!$found_name){
      $resultado = $conn->executar_query("SELECT id FROM user WHERE username = '".$username."';");
      if(mysql_num_rows($resultado)){
        $username = uniqid('anon');
      } else {
        $found_name = true;
      }
    }

    //cadastra user no banco
    $date = date("Y-m-d H:i:s");
    $resultado = $conn->executar_query("INSERT INTO user (username, last_seen, lat, lng) VALUES ('".$username."', '".$date."', ".$latitude.", ".$longitude.");");

    $_SESSION['user_id'] = mysql_insert_id();
    $_SESSION['username'] = $username;
    $_SESSION['longitude'] = $longitude;
    $_SESSION['latitude'] = $latitude;
    $_SESSION['twitter'] = false;
    $_SESSION['facebook'] = false;
  }

  //carregar ultimas mensagens do banco
  $resultado = $conn->executar_query("SELECT * FROM message WHERE lat < ".$_SESSION['latitude']." + 5 AND lat > ".$_SESSION['latitude']." - 5 AND lng < ".$_SESSION['longitude']." + 5 AND lng > ".$_SESSION['longitude']." - 5 ORDER BY id desc LIMIT 10;");

  $result_user = $conn->executar_query('SELECT id, username FROM user;');

  $users = array();

  while ($row = mysql_fetch_assoc($result_user)) {
    $users[$row["id"]] = $row["username"];
  }

  $mensagens = array();

  while ($row = mysql_fetch_assoc($resultado)) {
    
    if (array_key_exists($row["user_id"], $users)) {
      $row['username'] = $users[$row['user_id']];
    } else {
      $row['username'] = "Anon";
    }

    $mensagens[] = $row;
  }

  $mensagens = array_reverse($mensagens);

  // cospe json
  $data = array(
    'user_id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'lat' => $_SESSION['latitude'],
    'lng' => $_SESSION['longitude'],
    'mensagens' => $mensagens
  );

  // includes
  include 'xrtml/xrtml.php';
  $xrtml->jsPath = 'http://code.xrtml.org/xrtml-2.0.2.js';

  // connection
  $xrtml->config->debug = false;
  $myconnection1 = $xrtml->config->connections->add('myConnection');
  $myconnection1->url = 'http://developers2.realtime.livehtml.net/server/2.1/';
  $myconnection1->appKey = '8vVZJN';
  $myconnection1->authToken = '5aeXQtPISzb2';
  $myconnection1->privateKey = '5aeXQtPISzb2';

  $mychannel1 = $myconnection1->channels->add('people');
  $mychannel1->onMessage = "onMessage";
  $mychannel1->permission = null;

  // auth
  $xrtml->authenticate();

  $message = $xrtml->createMessage('myTrigger1', 'peopleAction', $data);
  $message = $xrtml->sendMessage('global', $message);

  echo json_encode($data); 
?>
