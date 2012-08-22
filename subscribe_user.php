<?php
  $dataObject["a"]="aaa";
  $message = $xrtml->createMessage('myTrigger', 'exampleAction', $dataObject);
  $message = $xrtml->sendMessage('global', $message);
?>
