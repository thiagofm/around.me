<?php
  // includes
  include 'xrtml/xrtml.php';
  $xrtml->jsPath = 'http://code.xrtml.org/xrtml-2.0.2.js';

  // connection
  $xrtml->config->debug = false;
  $myconnection1 = $xrtml->config->connections->add('myConnection');
  $myconnection1->url = 'http://developers2.realtime.livehtml.net/server/2.1/';
  $myconnection1->appKey = 'key8vVZJN';
  $myconnection1->authToken = 'key5aeXQtPISzb2';
  $mychannel1 = $myconnection1->channels->add('global');

  // auth
  $xrtml->authenticate();

  // geolocation tag
  $mygeolocation = $xrtml->addTag('geolocation');
  $mygeolocation->enableHighAccuracy = true;
  $mygeolocation->channelId = 'global';
  $mytrigger1 = $mygeolocation->triggers->add('getGeolocation');
  
  // markup writer
  echo $xrtml->toXRTML();

  // message handler
  $myexecute = $xrtml->addTag('execute');
  $myexecute->callback = 'geolocationCallBack';
  $mytrigger1 = $myexecute->triggers->add('getGeolocation');
?>
