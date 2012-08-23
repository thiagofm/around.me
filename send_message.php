<?php
  require 'conn.php';

  if (!$_REQUEST){
    die();
  }

  $longitude = $_REQUEST['longitude'];
  $latitude = $_REQUEST['latitude'];
  $user_id = $_REQUEST['user_id'];
  $username = $_REQUEST['username'];
  $message = strip_tags($_REQUEST['message']);
  $date = date("Y-m-d H:i:s");

  $conn = new Mysql();

  $query = "INSERT INTO message (date, lat, lng, message, user_id) VALUES ('".$date."', ".$latitude.", ".$longitude.", '".$message."', ".$user_id." );";
  $resultado = $conn->executar_query($query);

  // includes
  include 'xrtml/xrtml.php';
  $xrtml->jsPath = 'http://code.xrtml.org/xrtml-2.0.2.js';

  // connection
  $xrtml->config->debug = false;
  $myconnection1 = $xrtml->config->connections->add('myConnection');
  $myconnection1->url = 'http://developers2.realtime.livehtml.net/server/2.1/';
  $myconnection1->appKey = '8vVZJN';
  $myconnection1->authToken = '5aeXQtPISzb2';
  $mychannel1 = $myconnection1->channels->add('global');

  // auth
  $xrtml->authenticate();

  $obj= array();
  $obj['lng'] = $longitude + 0.0;
  $obj['lat'] = $latitude + 0.0;
  $obj['message'] = $message;
  $obj['user_id'] = $user_id;
  $obj['username'] = $username;
  $obj['date'] = $date;

  $message = $xrtml->createMessage('myTrigger1', 'exampleAction', $obj);
  $message = $xrtml->sendMessage('global', $message);

  echo var_dump($obj);
?>
