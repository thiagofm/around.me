<?php
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
  $mychannel1 = $myconnection1->channels->add('global');
  $mychannel1->onMessage = 'onMessage';
  $mychannel1->permission = null;

  $mychannel2 = $myconnection1->channels->add('people');
  $mychannel2->onMessage = 'onMessage';
  $mychannel2->permission = null;

  // auth
  $xrtml->authenticate();

  echo $xrtml->toXRTML();
?>
