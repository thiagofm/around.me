<?php
  require 'conn.php';

  if (!$_REQUEST){
    die();
  }

  $longitude = $_REQUEST['longitude'];
  $latitude = $_REQUEST['latitude'];
  $user_id = $_REQUEST['user_id'];
  $username = $_REQUEST['username'];
  $message = $_REQUEST['message'];
  $date = date("Y-m-d H:i:s");

  $conn = new Mysql();

  $query = "INSERT INTO message (date, lat, lng, message, user_id) VALUES ('".$date."', ".$latitude.", ".$longitude.", '".$message."', ".$user_id." );";
  $resultado = $conn->executar_query($query);
?>
