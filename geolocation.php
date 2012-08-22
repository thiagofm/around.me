<?php
  // includes
  include 'xrtml/xrtml.php';
  $xrtml->jsPath = 'http://code.xrtml.org/xrtml-2.0.2.js';

  // connection
  $xrtml->config->debug = false;
  $myconnection1 = $xrtml->config->connections->add('myConnection');
  $myconnection1->url = 'http://developers2.realtime.livehtml.net/server/2.1/';
  $myconnection1->appKey = '8vVZJN';
  $myconnection1->authToken = 'key5aeXQtPISzb2';
  $mychannel1 = $myconnection1->channels->add('global');
  $mychannel1->onMessage = 'onMessage';

  // auth
  $xrtml->authenticate();

  // geolocation tag
  $mygeolocation = $xrtml->addTag('geolocation');
  $mygeolocation->enableHighAccuracy = true;
  $mygeolocation->channelId = 'global';
  $mytrigger1 = $mygeolocation->triggers->add('getGeolocation');
  
  // message handler
  $myexecute = $xrtml->addTag('execute');
  $myexecute->callback = 'geolocationCallBack';
  $mytrigger1 = $myexecute->triggers->add('getGeolocation');

<<<<<<< HEAD
  // test
  $myexecute1 = $xrtml->addTag('execute');
  $myexecute1->callback = 'geolocationCallBack';
  $mytrigger2 = $myexecute1->triggers->add('myTrigger1');
  $dataObject["a"]="aaa";
  $message = $xrtml->createMessage('myTrigger', 'exampleAction', $dataObject);
  $message = $xrtml->sendMessage('global', $message);

  //echo $xrtml->toXRTML();
?>










=======
  echo $xrtml->toXRTML();
?>
>>>>>>> 15f14b51ef17425fb75511ca0794bcb961629aa3
