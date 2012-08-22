<?php
	class xRTML {
		public $config;
		public $jsPath;
		public $authenticated;
		public $tags;
		public function __construct(){
			$this->authenticated = false;
			$this->tags = new xRTMLArray('tag');
			xRTMLTagFactory::createTagsArray();
			$this->config = xRTMLTagFactory::createTag('config');
		}
		public function addTag($tagName){
			if(!array_key_exists(strtolower($tagName), xRTMLTagFactory::$tagsArray) || !$this->isTag($tagName)){
				throw new Exception("There is no tag named " . $tagName);
			}
			$newTag = xRTMLTagFactory::createTag($tagName);
			array_push($this->tags->array, $newTag);
			return $newTag;
		}
		public function isTag($tagName){
			if(!isset(xRTMLTagFactory::$tagsArray[$tagName]->extends) || xRTMLTagFactory::$tagsArray[$tagName]->extends == ''){
				return false;
			}			
			if(xRTMLTagFactory::$tagsArray[$tagName]->extends == 'tag'){
				return true;
			} else{
				return $this->isTag(xRTMLTagFactory::$tagsArray[$tagName]->extends);
			}
		}
		public function toXRTML(){			
			if(!isset($this->jsPath)){
				throw new Exception('$xrtml->jsPath (the path to the xRTML javascript file) must be set.');
			}
			$html = "<!-- ******************************* xRTML ******************************* -->\r\n";
			$html .= '<script type="text/javascript" src="' . $this->jsPath . '"></script>' . "\r\n";
			$html .= $this->config->toXRTML();
			if(isset($this->tags) && sizeof($this->tags) > 0){
				foreach($this->tags as $tag){
					$html .= $tag->toXRTML();					
				}
			}
			$html .= "<!-- **************************** end xRTML ****************************** -->\r\n\r\n";
			return $html;
		}
		public function authenticate(){
			foreach($this->config->connections as $connection){	
				$connection->authTokenIsPrivate = isset($connection->authTokenIsPrivate)? $connection->authTokenIsPrivate: true;
				$fields = array(
					'AT' => $connection->authToken,
					'AP' => $connection->appKey,
					'PK' => $connection->privateKey,
					'PVT' => $connection->authTokenIsPrivate,
					'TTL' => 1800
				);
				$fields['TP'] = sizeof($connection->channels);
				foreach($connection->channels as $channel){
					$fields[$channel->name] = ($channel->permission == 'write')? 'w':'r';
				}
				$ch = curl_init();
				if(preg_match('/^https:/', $connection->url)){// if ssl?
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				}				
				curl_setopt($ch, CURLOPT_URL, $connection->url . '/auth');
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$result = curl_exec($ch);
				if(@curl_getinfo($ch, CURLINFO_HTTP_CODE) != 201 && @curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200 ){
					error_log("There was an error trying to establish the Connection " . $connection->id, 0);
				} else {
					$this->authenticated = true;
					return true;					
				}
				curl_close($ch);
			}			
		}
		public function sendMessage($channel, $message){
			foreach($this->config->connections as $connection){
				$fields = array(
					'AT' => $connection->authToken,
					'AK' => $connection->appKey, 
					'PK' => $connection->privateKey,
					'C' => $channel,
					'M' => $message
				);
				$ch = curl_init();
				if(preg_match('/^https:/', $connection->url)){// if ssl?
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				}
				$url_balancer = curl_init();
				curl_setopt ($url_balancer, CURLOPT_URL,$connection->url);
				curl_setopt ($url_balancer, CURLOPT_RETURNTRANSFER, 1);
				$ret = curl_exec($url_balancer);
				curl_close($url_balancer);
				//return $ret;
				$arrRet=explode('"',$ret );
				$url_send=$arrRet[1];
				//return $dataToStuff .'/sendMessage';
				curl_setopt($ch, CURLOPT_URL, $url_send . '/send');
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$result = curl_exec($ch);
				if(@curl_getinfo($ch, CURLINFO_HTTP_CODE) != 201 && @curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200 ){
					error_log("There was an error trying to send the message " . $message, 0);
				}
				curl_close($ch);
			}
			return true;
		}
		public function createMessage($trigger, $action, $data){
			$message['xrtml'] = array();
			$message['xrtml']['t'] = $trigger;
			$message['xrtml']['a'] = $action;
			$message['xrtml']['d'] = $data;
			return json_encode($message);
		}
		public function extendArray($arrayName, $tag){
			xRTMLTagFactory::extendArray($arrayName, $tag);
		}
	}		
	class xRTMLBaseTag{
		public function __construct($tagSettings){
			if(isset($tagSettings->collections)){
				foreach($tagSettings->collections as $collectionName => $collectionValue){
					$collection = strtolower($collectionName);
					$collectionClass = substr($collection, 0, strlen($collection) - 1);
					$this->$collection = new xRTMLArray($collectionClass);
				}
			}
			if(array_key_exists('children', $tagSettings)){
				foreach($tagSettings->children as $childName => $childValue ){
					$childSettings = xRTMLTagFactory::getTagSettings($childName);
					$childName = strtolower($childName);
					$this->$childName = xRTMLTagFactory::createTag($childName);
					if(isset($childSettings->attributes->content)){
						$this->$childName->content = '';
					}
				}
			}
		}
		public function toXRTML($deep = 0){
			$html = '';
			$tabs = '';
			$className = get_class($this);
			$tagName = str_replace('xRTML', '', $className);
			$tagSettings = xRTMLTagFactory::getTagSettings($tagName);
			$this->validateTag();
			if($deep == 0){
				$html .= "\r\n";
			}
			for($cont = 0; $cont < $deep; $cont++){
				$tabs .= "\t";
			}
			$html .= $tabs . "<xrtml:$tagName";
			if($tagName == 'connection'){
				$html .= ' authenticate="false"';
			}
			if(isset($tagSettings->attributes) && sizeof($tagSettings->attributes) > 0){
				foreach($tagSettings->attributes as $attrName => $attrVal){
					if($attrName == 'content') break;
					if(isset($this->$attrName)){
						if(is_bool($this->$attrName)){
							$attrVal = $this->$attrName? "true" : "false";
						} else {
							$attrVal = $this->$attrName;
						}
						$attrName = $attrName;
						$html .= " $attrName=\"" . $attrVal . "\"";
					}
				}
			}
			if(isset($tagSettings->events) && sizeof($tagSettings->events) > 0){
				foreach($tagSettings->events as $eventName => $eventValue){
					if(isset($this->$eventName)){
						$html .= " $eventName=\"" . $this->$eventName . "\"";
					}
				}
			}
			$html .= ">\r\n";
			if(isset($tagSettings->collections)){
				foreach($tagSettings->collections as $collectionName => $collectionValue){
					$collectionName = strtolower($collectionName);
					if(sizeof($this->$collectionName->array) > 0){
						$html .= $tabs . "\t" . '<xrtml:' . $collectionName . '>' . "\r\n";					
						foreach($this->$collectionName as $collectionItem){
							$html .= $collectionItem->toXRTML($deep + 2);
						}					
						$html .= $tabs . "\t" . '</xrtml:' . $collectionName . '>' . "\r\n";
					}
				}
			}
			if(isset($tagSettings->children)){
				foreach($tagSettings->children as $childName => $childValue){
					$childName = strtolower($childName);
					$html .= $tabs . $this->$childName->toXRTML($deep + 1);
				}
			}
			if(isset($tagSettings->attributes->content)){
				$html .= $tabs . "\t<!-- \r\n";
				$html .=$tabs ."\t\t" . $this->content . "\r\n";
				$html .= $tabs . "\t-->\r\n"; 
			}	
			$html .= $tabs . "</xrtml:$tagName>\n";
			return $html;
		}
		private function validateTag(){
			$className = get_class($this);
			$tagName = str_replace('xRTML', '', $className);
			$tagSettings = xRTMLTagFactory::getTagSettings($tagName);
			if(isset($tagSettings->attributes)){				
				foreach($tagSettings->attributes as $attrName => $attrVal){
					$mandatory = (string)$attrVal->mandatory;
					if($mandatory == '1'){
						if(!isset($this->$attrName)){
							throw new Exception("Attribute $attrName is mandatory for the $tagName tag.");
						}
					}
					if(strtolower($attrVal->type) == 'boolean'){
						if(isset($this->attrName) && !is_bool($this->attrName)){
							if($this->attrName != 'true' && strtolower($this->attrName) != 'false'){
								throw new Exception("Attribute $attrName must be set to true or false.");	
							}
						}
					} else if(isset($attrVal->possibleValues)){
						if(isset($this->$attrName)){							
							if(!in_array($this->$attrName, $attrVal->possibleValues)){
								throw new Exception("Attribute $attrName must be set to one of the following values: [" . implode(',', $attrVal->possibleValues) . ']');
							}
						}
					}
				}
			}			
		}
	}
	abstract class xRTMLTagFactory{
		private static $jsonConfigString = '{"Tags":{"Tag":{"abstract":true,"attributes":{"id":{"mandatory":false,"type":"String","defaultValue":""},"receiveOwnMessages":{"mandatory":false,"type":"Boolean","defaultValue":true},"active":{"mandatory":false,"type":"Boolean","defaultValue":true},"preProcess":{"mandatory":false,"type":"String","defaultValue":""},"postProcess":{"mandatory":false,"type":"String","defaultValue":""}}},"Config":{"attributes":{"logLevels":{"mandatory":false,"type":"Number","defaultValue":2},"debug":{"mandatory":false,"type":"Boolean","defaultValue":false},"logLevel":{"mandatory":false,"type":"Number","defaultValue":"info","possibleValues":["info","warn","error"]},"xrtmlActive":{"mandatory":false,"type":"Boolean","defaultValue":true},"throwErrors":{"mandatory":false,"type":"Boolean","defaultValue":false},"connectionAttempts":{"mandatory":false,"type":"Number","defaultValue":"5"},"connectionTimeout":{"mandatory":false,"type":"Number","defaultValue":""},"loadORTCScript":{"mandatory":false,"type":"Boolean","defaultValue":true},"ortcLibrary":{"mandatory":false,"type":"String","defaultValue":""},"remoteTrace":{"mandatory":false,"type":"Boolean","defaultValue":false}},"collections":{"Connections":{}},"children":{}},"BroadCast":{"extends":"Tag","attributes":{"channelId":{"mandatory":true,"type":"String","defaultValue":""}},"collections":{"Triggers":{},"Dispatchers":{}},"children":{}},"BrowserControl":{"extends":"Tag","attributes":{"channelId":{"mandatory":true,"type":"String","defaultValue":""},"target":{"mandatory":false,"type":"String","defaultValue":""},"generateControls":{"mandatory":false,"type":"Boolean","defaultValue":"false"},"role":{"mandatory":true,"type":"String","possibleValues":["sender","receiver"],"defaultValue":""}},"collections":{"Triggers":{},"Elements":{}},"children":{}},"DrawingBoard":{"extends":"Tag","attributes":{"channelId":{"mandatory":true,"type":"String","defaultValue":""},"target":{"mandatory":true,"type":"String","defaultValue":""},"role":{"mandatory":true,"type":"String","possibleValues":["sender","receiver","both"],"defaultValue":""},"width":{"mandatory":true,"type":"Number","defaultValue":320},"height":{"mandatory":true,"type":"Number","defaultValue":470},"targetmenu":{"mandatory":false,"type":"String","defaultValue":""}},"collections":{"Triggers":{},"Elements":{}},"children":{},"AdditionalDetails":{"ElementsMapping":{"details":{"DOM Elements":[{"title":"Key","data":["pencil","line","rect","circle","righttriangle","isoscelestriangle","paintbucket","quadraticcurve","beziercurve","eraser","eyedropper","undo","redo","opacity","color","morezoom","lesszoom","originalzoom","colorpickercanvas","selectedtool","positionx","positiony","selector","linewidth","red","green","blue","newdraw","save"]},{"title":"Value","data":["CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector","CSS Selector"]},{"title":"Description","data":["CSS Selector for the pencil menu item. It will be attached a click event handler for activation purpose.","CSS Selector for the line menu item. It will be attached a click event handler for activation purpose.","CSS Selector for the rect menu item. It will be attached a click event handler for activation purpose.","CSS Selector for the rect menu item. It will be attached a click event handler for activation purpose.","CSS Selector for the righttriangle menu item. It will be attached a click event handler for activation purpose.","CSS Selector for the isoscelestriangle menu item. It will be attached a click event handler for activation purpose.","CSS Selector for the paintbucket menu item. It will be attached a click event handler for activation purpose.","CSS Selector for the quadraticcurve menu item. It will be attached a click event handler for activation purpose.","CSS Selector for the beziercurve menu item. It will be attached a click event handler for activation purpose.","CSS Selector for the eraser menu item. It will be attached a click event handler for activation purpose.","CSS Selector for the eyedropper menu item. It will be attached a click event handler for activation purpose.","CSS Selector for the undo menu item. It will be attached a click event handler for activation purpose.","CSS Selector for the redo menu item. It will be attached a click event handler for activation purpose.","CSS Selector for the opacity menu item. It will be attached a click event handler for activation purpose.","CSS Selector for the color menu item. Input box for set color.","CSS Selector for the morezoom menu item. It will be attached a click event handler for activation purpose.","CSS Selector for the lesszoom menu item. It will be attached a click event handler for activation purpose.","CSS Selector for the originalzoom menu item. It will be attached a click event handler for activation purpose.","CSS Selector for the colorpickercanvas menu item. The container for the color picker canvas.","CSS Selector for the selectedtool menu item. The element to keep track of the current tool.","CSS Selector for the positionx menu item. The container of the mouse coordinates.","CSS Selector for the positiony menu item. The container of the mouse coordinates.","CSS Selector for the selector menu item. The Select element for the fill or line color and opacity.","CSS Selector for the linewidth menu item. The input box to set the line width.","CSS Selector for the red menu item. The input box to set the red component of the RGB color.","CSS Selector for the green menu item. The input box to set the green component of the RGB color.","CSS Selector for the blue menu item. The input box to set the blue component of the RGB color.","CSS Selector for the newdraw menu item. It will be attached a click event handler for activation purpose.","CSS Selector for the save menu item. It will be attached a click event handler for activation purpose."]}]}}}},"Execute":{"extends":"Tag","attributes":{"channelId":{"mandatory":false,"type":"String","defaultValue":""},"callback":{"mandatory":true,"type":"String","defaultValue":""}},"collections":{"Triggers":{}},"children":{}},"KeyTracker":{"extends":"Tag","attributes":{"channelId":{"mandatory":true,"type":"String","defaultValue":""},"target":{"mandatory":true,"type":"String","defaultValue":""},"sendAll":{"mandatory":false,"type":"Boolean","defaultValue":false}},"collections":{"Triggers":{}},"children":{}},"AbstractMouseTag":{"extends":"Tag","abstract":true,"attributes":{"channelId":{"mandatory":true,"type":"String","defaultValue":""},"target":{"mandatory":false,"type":"String","defaultValue":""},"periodicity":{"mandatory":false,"type":"Number","defaultValue":10},"centerWidth":{"mandatory":false,"type":"Boolean","defaultValue":false},"centerHeight":{"mandatory":false,"type":"Boolean","defaultValue":false},"offsetX":{"mandatory":false,"type":"Number","defaultValue":0},"offsetY":{"mandatory":false,"type":"Number","defaultValue":0}},"collections":{"Triggers":{}},"children":{}},"Media":{"extends":"Tag","abstract":true,"attributes":{"channelId":{"mandatory":false,"type":"String","defaultValue":""},"width":{"mandatory":false,"type":"Number","defaultValue":""},"height":{"mandatory":false,"type":"Number","defaultValue":""},"autoplay":{"mandatory":false,"type":"Boolean","defaultValue":false},"loop":{"mandatory":false,"type":"Boolean","defaultValue":false},"controlsBar":{"mandatory":false,"type":"Boolean","defaultValue":false},"mute":{"mandatory":false,"type":"Boolean","defaultValue":false},"loadStatus":{"mandatory":false,"type":"Number","defaultValue":""}},"collections":{"Triggers":{}},"children":{}},"Video":{"extends":"Media","attributes":{"keepRatio":{"mandatory":false,"type":"Boolean","defaultValue":""},"poster":{"mandatory":false,"type":"URL","defaultValue":""}},"collections":{},"children":{}},"Audio":{"extends":"Media","collections":{},"children":{}},"Motion":{"extends":"Tag","attributes":{"channelId":{"mandatory":true,"type":"String","defaultValue":""},"useAccelerometer":{"mandatory":false,"type":"Boolean","defaultValue":"true"},"useGyroscope":{"mandatory":false,"type":"Boolean","defaultValue":"true"},"sensitivity":{"mandatory":false,"type":"Number","defaultValue":"5"}},"collections":{"Triggers":{}},"children":{}},"MouseLive":{"extends":"AbstractMouseTag","attributes":{"role":{"mandatory":true,"type":"String","possibleValues":["sender","receiver"],"defaultValue":""}},"children":{},"collections":{},"AdditionalDetails":{"CSS":{"details":{"Data Items Style":[{"title":"CSS Rules","data":["#xRTMLReceiverPointer"]},{"title":"HTML Element","data":["<div>"]},{"title":"Description","data":["Applied to the element that will replicate the mouse movement on the receiver side."]}]}}}},"MouseTracker":{"extends":"AbstractMouseTag","attributes":{},"children":{},"collections":{}},"DragDrop":{"extends":"AbstractMouseTag","attributes":{"role":{"mandatory":true,"type":"String","possibleValues":["sender","receiver"],"defaultValue":"sender"},"syncMove":{"mandatory":true,"type":"Boolean","defaultValue":"false"},"targetSelector":{"mandatory":true,"type":"String","defaultValue":""}},"children":{},"collections":{}},"Placeholder":{"extends":"Tag","attributes":{"channelId":{"mandatory":true,"type":"String","defaultValue":""},"target":{"mandatory":false,"type":"String","defaultValue":""}},"collections":{"Triggers":{}},"children":{"Template":{}}},"Poll":{"extends":"Tag","attributes":{"channelId":{"mandatory":true,"type":"String","defaultValue":""},"target":{"mandatory":false,"type":"String","defaultValue":""},"getDataUrl":{"mandatory":false,"type":"String","defaultValue":""},"voteUrl":{"mandatory":false,"type":"String","defaultValue":""},"useChartTag":{"mandatory":false,"type":"Boolean","defaultValue":"false"},"chartTagId":{"mandatory":false,"type":"Boolean","defaultValue":"false"},"votesAllowed":{"mandatory":false,"type":"Number","defaultValue":"1"},"userId":{"mandatory":false,"type":"String","defaultValue":""},"saveVoteFunction":{"mandatory":false,"type":"String","defaultValue":""},"getDataFunction":{"mandatory":false,"type":"String","defaultValue":""},"onVote":{"mandatory":false,"type":"String","defaultValue":""},"onData":{"mandatory":false,"type":"String","defaultValue":""},"onVotesLimitReached":{"mandatory":false,"type":"String","defaultValue":""}},"collections":{"Triggers":{},"JQueryEffects":{},"Elements":{},"Buttons":{},"Mediaurls":{}},"children":{}},"Chart":{"extends":"Tag","attributes":{"channelId":{"mandatory":false,"type":"String","defaultValue":""},"chartingPlatform":{"mandatory":true,"type":"String","possibleValues":["htmlchart","highcharts"],"defaultValue":""},"configFunction":{"mandatory":false,"type":"String","defaultValue":"Default Option"},"getDataUrl":{"mandatory":false,"type":"String","defaultValue":""},"target":{"mandatory":false,"type":"String","defaultValue":""}},"children":{},"collections":{"DataItems":{},"Triggers":{},"JQueryEffects":{}},"AdditionalDetails":{"CSS":{"details":{"Data Items Style":[{"title":"CSS Class Name","data":[".items",".xRTMLDataItem",".xRTMLDataItemPercentage",".values",".graph",".xRTMLDataItemValue",".legend",".xRTMLScaleNumber"]},{"title":"HTML Element","data":["<div>","<span>","<em>","<div>","<div>","<span>","<ul>","<li>"]},{"title":"Description","data":["Applied to the container of the x-axis legends and percentages.","Applied to the data items names","Applied to the percentage values of the data items","Applied to the container of the y-axis and bars","Applied to the container of the bars that represent the data item values in the chart","Applied to the value and bar of a dataitem in the chart","Applied to the container of the y-axis","Applied to the values of the y-axis"]}]}}}},"Repeater":{"extends":"Tag","attributes":{"channelId":{"mandatory":false,"type":"String","defaultValue":""},"target":{"mandatory":false,"type":"String","defaultValue":""},"index":{"mandatory":false,"type":"String","defaultValue":"end"},"removeIndex":{"mandatory":false,"type":"String","defaultValue":"end"},"maxItens":{"mandatory":false,"type":"Number","defaultValue":""},"dataKeyName":{"mandatory":false,"type":"String","defaultValue":""}},"collections":{"Triggers":{},"JQueryEffects":{}},"children":{"Template":{}}},"TextToSpeech":{"extends":"Tag","attributes":{"channelId":{"mandatory":true,"type":"String","defaultValue":""},"message":{"mandatory":false,"type":"String","defaultValue":""},"amplitude":{"mandatory":false,"type":"Number","defaultValue":100},"wordGap":{"mandatory":false,"type":"Number","defaultValue":"0"},"pitch":{"mandatory":false,"type":"Number","defaultValue":"50"},"speed":{"mandatory":false,"type":"Number","defaultValue":"175"}},"collections":{"Triggers":{}},"children":{}},"VideoChat":{"extends":"Tag","attributes":{"channelId":{"mandatory":true,"type":"String","defaultValue":""},"target":{"mandatory":false,"type":"String","defaultValue":""},"generateHtml":{"mandatory":false,"type":"Boolean","defaultValue":"false"},"apiKey":{"mandatory":true,"type":"String","defaultValue":""},"sessionId":{"mandatory":false,"type":"String","defaultValue":""},"token":{"mandatory":false,"type":"String","defaultValue":""},"persist":{"mandatory":false,"type":"Boolean","defaultValue":"true"},"publisherWidth":{"mandatory":false,"type":"Number","defaultValue":160},"publisherHeight":{"mandatory":false,"type":"Number","defaultValue":120},"subscriberWidth":{"mandatory":false,"type":"Number","defaultValue":160},"subscriberHeight":{"mandatory":false,"type":"Number","defaultValue":120},"role":{"mandatory":false,"type":"String","defaultValue":"publisher","possibleValues":["moderator","publisher","subscriber"]},"maxUsers":{"mandatory":false,"type":"Number","defaultValue":10},"onReady":{"mandatory":false,"type":"String","defaultValue":100},"handlerUrl":{"mandatory":false,"type":"String","defaultValue":".\/handler\/VideoChatSession.ashx"},"tokenOnDemand":{"mandatory":false,"type":"Boolean","defaultValue":false},"allowWatchers":{"mandatory":false,"type":"Boolean","defaultValue":false}},"collections":{"Triggers":{},"Elements":{}},"children":{},"AdditionalDetails":{"CSS":{"details":{"Camera and Controls":[{"title":"CSS Rule","data":["#xrtml_vc_myCamera","#xrtml_vc_main",".videochat","#localview",".rightbox",".controls","#status","#action","#endChat","#count-header","#participants","#watchers"]},{"title":"HTML Element","data":["<div>","<div>","<div>","<div>","<div>","<div>","<div>","<div>","<div>","<div>","<div>","<div>"]},{"title":"Description","data":["Applies to the camera element container.","Applies to the streams container element.","Applies to the container holding all the elements generated by videochat.","Applies to the camera element.","Applies to the menu.","Applies to the controls elements.","Applies to the element containing the status.","Applies to the element containing the action to take.","Applies to the end chat control element.","Applies to the element containing the number of users connected to the session.","Applies to the element containing the number of participants in the active session.","Applies to the element containing the number of subscribers monitoring the active session."]}]}}}},"VisitorCounter":{"extends":"Tag","attributes":{"channelId":{"mandatory":true,"type":"String","defaultValue":""},"target":{"mandatory":false,"type":"String","defaultValue":""}},"collections":{"Triggers":{}},"children":{}},"ShoutBox":{"extends":"Tag","attributes":{"channelId":{"mandatory":true,"type":"String","defaultValue":""},"target":{"mandatory":false,"type":"String","defaultValue":""},"historyRepository":{"mandatory":false,"type":"String","defaultValue":".\/handler\/"},"onMessage":{"mandatory":false,"type":"String","defaultValue":""},"onMessagePost":{"mandatory":false,"type":"String","defaultValue":""},"onMessageHistory":{"mandatory":false,"type":"String","defaultValue":""}},"collections":{"Triggers":{},"Elements":{"key":{"mandatoryValues":[]}}},"children":{},"events":{"onMessage":{},"onMessagePost":{},"onMessageHistory":{}}},"GeoLocation":{"extends":"Tag","attributes":{"channelId":{"mandatory":true,"type":"String","defaultValue":""},"target":{"mandatory":false,"type":"String","defaultValue":""},"errorHandler":{"mandatory":false,"type":"String","defaultValue":""},"enableHighAccuracy":{"mandatory":false,"type":"Boolean","defaultValue":false},"timeout":{"mandatory":false,"type":"Number","defaultValue":""},"maximumAge":{"mandatory":false,"type":"Number","defaultValue":0}},"collections":{"Triggers":{}},"children":{}},"Toast":{"extends":"Tag","attributes":{"channelId":{"mandatory":false,"type":"String","defaultValue":""},"title":{"mandatory":false,"defaultValue":""},"text":{"mandatory":false,"defaultValue":""},"destinationUrl":{"mandatory":false,"defaultValue":""},"positionAt":{"mandatory":false,"defaultValue":""},"positionAtX":{"mandatory":false,"defaultValue":""},"positionAtY":{"mandatory":false,"defaultValue":""},"bannerUrl":{"mandatory":false,"defaultValue":""},"bannerContent":{"mandatory":false,"defaultValue":""},"bannerType":{"mandatory":false,"defaultValue":"image"},"changePageTitle":{"mandatory":false,"defaultValue":"true"},"displayBanner":{"mandatory":false,"defaultValue":"false"},"timeToLive":{"mandatory":false,"defaultValue":"10000"},"onToastDisplayed":{"mandatory":false,"defaultValue":""},"onToastClosed":{"mandatory":false,"defaultValue":""},"onBannerDisplayed":{"mandatory":false,"defaultValue":""},"onBannerClosed":{"mandatory":false,"defaultValue":""},"onRedirect":{"mandatory":false,"defaultValue":""}},"collections":{"Triggers":{},"JQueryEffects":{},"MediaUrls":{}},"children":{},"AdditionalDetails":{"CSS":{"details":{"Toast and Banner Style":[{"title":"CSS Rules","data":["#trayMessage",".close",".content","#xRTMLBannerContainer"]},{"title":"HTML Element","data":["<div>","<div>","<div>","<div>"]},{"title":"Description","data":["Applied to the toast container (overriden by the attribute containerid).","Applied to the element that closes the toast.","Applied to the element that contains the toast title and text.","Applied to a container generated for holding the banner if jQuery or jQuery fancybox are not available in the page (otherwise fancybox will be used)"]}]}}}},"Notify":{"extends":"Tag","attributes":{"fontcolor":{"mandatory":false,"type":"String","defaultValue":"#FFFFFF"},"backgroundcolor":{"mandatory":false,"type":"String","defaultValue":"#FF0000"}},"collections":{"Triggers":{}},"children":{}},"Cloud":{"extends":"Tag","attributes":{"channelId":{"mandatory":false,"type":"String","defaultValue":""},"target":{"mandatory":false,"type":"String","defaultValue":""},"fontUnit":{"mandatory":false,"type":"String","defaultValue":"px"},"fontSizeMin":{"mandatory":false,"type":"Number","defaultValue":8},"fontSizeMax":{"mandatory":false,"type":"Number","defaultValue":40},"numberTags":{"mandatory":false,"type":"Number","defaultValue":20},"getInitialObject":{"mandatory":false,"type":"String","defaultValue":""}},"collections":{"Triggers":{}},"children":{}},"Booking":{"extends":"Tag","attributes":{"channelId":{"mandatory":true,"type":"String","defaultValue":""},"target":{"mandatory":false,"type":"String","defaultValue":""},"bookingItem":{"mandatory":true,"type":"String","defaultValue":""},"userId":{"mandatory":false,"type":"String","defaultValue":""},"userIdCallback":{"mandatory":false,"type":"String","defaultValue":""},"bookingLimit":{"mandatory":false,"type":"Number","defaultValue":""},"handlerUrl":{"mandatory":true,"type":"String","defaultValue":""},"onDataPreload":{"mandatory":false,"type":"String","defaultValue":""},"onDataLoad":{"mandatory":false,"type":"String","defaultValue":""},"onBookingSuccess":{"mandatory":false,"type":"String","defaultValue":""},"onBookingOverLimit":{"mandatory":false,"type":"String","defaultValue":""},"onBookingTaken":{"mandatory":false,"type":"String","defaultValue":""},"onBookingCancel":{"mandatory":false,"type":"String","defaultValue":""}},"collections":{"Triggers":{},"Slots":{}},"children":{},"events":{"onDataPreload":{},"onDataLoad":{},"onBookingSuccess":{},"onBookingOverLimit":{},"onBookingTaken":{},"onBookingCancel":{}},"AdditionalDetails":{"CSS":{"details":{"Booking Items Style":[{"title":"CSS Class Name","data":[".xrtmlBook_booked",".xrtmlBook_own",".xrtmlBook_waiting"]},{"title":"HTML Element","data":["Any","Any","Any"]},{"title":"Description","data":["Applied to the reservation taken by other users","Applied to the own reservations","Applied to the slots that are waiting for the server response"]}]}}}},"Calendar":{"extends":"Tag","attributes":{"channelId":{"mandatory":true,"type":"String","defaultValue":""},"target":{"mandatory":true,"type":"String","defaultValue":""},"userId":{"mandatory":false,"type":"String","defaultValue":""},"userIdCallback":{"mandatory":false,"type":"String","defaultValue":""},"handlerUrl":{"mandatory":true,"type":"String","defaultValue":""},"startDate":{"mandatory":false,"type":"Date (yyyy-mm-dd)","defaultValue":""},"endDate":{"mandatory":false,"type":"Date (yyyy-mm-dd)","defaultValue":""},"showDate":{"mandatory":false,"type":"Date (yyyy-mm)","defaultValue":""},"dayOnly":{"mandatory":false,"type":"Boolean","defaultValue":"false"},"langsCallback":{"mandatory":false,"type":"String","defaultValue":""},"lang":{"mandatory":false,"type":"String","defaultValue":"en"}},"children":{},"collections":{"Triggers":{},"Slots":{}},"AdditionalDetails":{"CSS":{"details":{"Calendar Panel":[{"title":"CSS Class Name","data":[".xrtmlCal_calendar",".xrtmlCal_default",".xrtmlCal_disabled",".xrtmlCal_emptyDate",".xrtmlCal_partialDay",".xrtmlCal_fullDay",".xrtmlCal_today",".xrtmlCal_titleRow",".xrtmlCal_previousMonth",".xrtmlCal_nextMonth",".xrtmlCal_titleDate",".xrtmlCal_weekDays",".xrtmlCal_dateRow_odd",".xrtmlCal_dateRow_even"]},{"title":"HTML Element","data":["<table>","<td>","<td>","<td>","<td>","<td>","<td>","<tr>","<td>","<td>","<td>","<tr>","<tr>","<tr>"]},{"title":"Description","data":["Applied to the table containing the whole calendar","Applied to the table-datacells containing an empty but clickable date","Applied to the table-datacells that have no slots configured for it\'s day","Applied to the vacant table-datacells that would contain the previous and next month\'s dates","Applied to a table-datacell for which the date has some of it\'s slots filled","Applied to a table-datacell for which the date has all of it\'s slots filled","Applied to a table-datacell for which the date corresponds to the current date","The first table-row in the table-head containing the next and previous links, and the selected month\/year","Applied to the table-datacell where the link to navigate to the previous month will be placed","Applied to the table-datacell where the link to navigate to the next month will be placed","Applied to the table-datacell containing the selected month and year","Applied to the table-row where the weekday short names will be placed","Applied to the odd table-rows in table-body","Applied to the even table-rows in table-body"]}],"Daily Slot Panel":[{"title":"CSS Class Name","data":[".xrtmlCal_dailyPanel",".xrtmlCal_schedule",".xrtmlCal_schedule_slotInfo_booked",".xrtmlCal_schedule_slotInfo_free",".xrtmlCal_own",".xrtmlCal_schedule_timeslot",".xrtmlCal_schedule_slotInfo",".xrtmlCal_dailyPanel_backLink"]},{"title":"HTML Element","data":["<div>","<table>","<tr>","<tr>","<tr>","<td>","<td>","<div>"]},{"title":"Description","data":["Applied to the wrapper div containing the daily panel","Applied to the table containing the daily panel schedule","Applied to the table-row containing a booked timeslot","Applied to the table-row containing a free timeslot","Applied to the slots that are booked by the same user viewing the calendar","Applied to the table-data cell containing the timeslot designation","Applied to the table-datacell containing the timeslot\'s info (free, booked,...)","Applied to the div containing the anchor linking back to the calendar from the dailypanel"]}]}}}},"Map":{"extends":"Tag","attributes":{"channelId":{"mandatory":true,"type":"String","defaultValue":""},"target":{"mandatory":true,"type":"String","defaultValue":""},"type":{"mandatory":true,"type":"String","possibleValues":["googlemap","bingmap"],"defaultValue":""},"role":{"mandatory":true,"type":"String","possibleValues":["sender","receiver"],"defaultValue":""},"zoom":{"mandatory":false,"type":"Number","defaultValue":"3"},"latitude":{"mandatory":false,"type":"Number","defaultValue":"40"},"longitude":{"mandatory":false,"type":"Number","defaultValue":"-5"},"drawingMode":{"mandatory":false,"type":"String","defaultValue":"route","possibleValues":["polyline","route"]},"markerType":{"mandatory":false,"type":"String","defaultValue":"image","possibleValues":["polyline","image","polygon"]},"imageUrl":{"mandatory":false,"type":"String","defaultValue":""},"imageWidth":{"mandatory":false,"type":"Number","defaultValue":""},"imageHeight":{"mandatory":false,"type":"Number","defaultValue":""},"offsetImageWidth":{"mandatory":false,"type":"Number","defaultValue":"0"},"offsetImageHeight":{"mandatory":false,"type":"Number","defaultValue":"0"},"shadowImage":{"mandatory":false,"type":"String","defaultValue":""},"offsetShadowImageWidth":{"mandatory":false,"type":"Number","defaultValue":"0"},"offsetShadowImageHeight":{"mandatory":false,"type":"Number","defaultValue":"0"},"strokeColor":{"mandatory":false,"type":"String","defaultValue":""},"strokeOpacity":{"mandatory":false,"type":"Number","defaultValue":""},"strokeWeight":{"mandatory":false,"type":"Number","defaultValue":""},"fillColor":{"mandatory":false,"type":"String","defaultValue":""},"fillOpacity":{"mandatory":false,"type":"Number","defaultValue":""},"strokeColorRoute":{"mandatory":false,"type":"String","defaultValue":""},"strokeOpacityRoute":{"mandatory":false,"type":"Number","defaultValue":""},"strokeWeightRoute":{"mandatory":false,"type":"Number","defaultValue":""},"infoWindow":{"mandatory":false,"type":"String","defaultValue":""},"viewMode":{"mandatory":false,"type":"String","possibleValues":["HYBRID","ROADMAP","SATELLITE","TERRAIN","birdseye","aerial","road"],"defaultValue":""},"routeMode":{"mandatory":false,"type":"String","possibleValues":["DRIVING","WALKING"],"defaultValue":""},"targetDirections":{"mandatory":false,"type":"String","defaultValue":""}},"collections":{"Triggers":{},"Markers":{}},"children":{}},"TaskList":{"extends":"Tag","attributes":{"channelId":{"mandatory":false,"type":"String","defaultValue":""},"target":{"mandatory":false,"type":"String","defaultValue":""},"spellCheck":{"mandatory":false,"type":"Boolean","defaultValue":"true"},"handlerUrl":{"mandatory":false,"type":"String","defaultValue":""},"getDataFunction":{"mandatory":false,"type":"String","defaultValue":""},"multipleLists":{"mandatory":false,"type":"Boolean","defaultValue":true}},"collections":{"Triggers":{},"Elements":{}},"children":{},"AdditionalDetails":{"CSS":{"details":{"Task List Elements":[{"title":"CSS Class Name","data":[".xRTMLTaskListItemContainer",".xRTMLTaskListItem",".xRTMLTaskListItemCompletedCheck",".xRTMLTaskListItemDelete",".xRTMLTaskList",".xRTMLTaskListContainer",".xRTMLTaskListMenu",".xRTMLTaskListDetailSection",".xRTMLTaskListListMenu"]},{"title":"HTML Element","data":["<div>","<div>","<input>","<a>","<div>","<input>","<div>","<div>","<div>"]},{"title":"Description","data":["Applied to containers for the item details.","Applied to the editable area of the item.","Applied to the checkbox identifying if the item is completed.","Applied to the anchor containing the action to delete an item.","Applied to list containers.","Applied to the text input for a new list s title.","Applied to the container for all the lists.","Applied to the task list menu container.","Applied to the section containing the details for the selected item.","Applied to the menu section container specific for lists related menu actions."]}]}}}}},"Collections":{"Connections":{"Connection":{"attributes":{"id":{"mandatory":false,"type":"String","defaultValue":""},"active":{"mandatory":false,"type":"Boolean","defaultValue":true},"appKey":{"mandatory":true,"type":"String","defaultValue":""},"authToken":{"mandatory":true,"type":"String","defaultValue":""},"sendRetries":{"mandatory":false,"type":"Number","defaultValue":"5"},"sendInterval":{"mandatory":false,"type":"Number","defaultValue":"1000"},"timeout":{"mandatory":false,"type":"Number","defaultValue":"10000"},"connectAttemps":{"mandatory":false,"type":"Number","defaultValue":"5"},"autoConnect":{"mandatory":false,"type":"Boolean","defaultValue":true},"metadata":{"mandatory":false,"type":"String","defaultValue":""},"serverType":{"mandatory":false,"type":"String","defaultValue":"IbtRealTimeSJ"},"isCluster":{"mandatory":false,"type":"Boolean","defaultValue":"true"},"url":{"mandatory":false,"type":"String","defaultValue":"http:\/\/stag-balancer.realtime.livehtml.net\/server\/2.0"},"messageAdapter":{"mandatory":false,"type":"Function","defaultValue":""},"onCreated":{"mandatory":false,"type":"String","defaultValue":""},"onConnected":{"mandatory":false,"type":"String","defaultValue":""},"onDisconnected":{"mandatory":false,"type":"String","defaultValue":""},"onSubscribed":{"mandatory":false,"type":"String","defaultValue":""},"onUnsubscribed":{"mandatory":false,"type":"String","defaultValue":""},"onException":{"mandatory":false,"type":"String","defaultValue":""},"onReconnected":{"mandatory":false,"type":"String","defaultValue":""},"onReconnecting":{"mandatory":false,"type":"String","defaultValue":""},"onMessage":{"mandatory":false,"type":"String","defaultValue":""}},"collections":{"Channels":{}},"mandatory":"true"}},"Channels":{"Channel":{"attributes":{"name":{"mandatory":true,"type":"String","minSize":1,"defaultValue":""},"subscribeOnReconnect":{"mandatory":false,"type":"Boolean","defaultValue":true},"subscribe":{"mandatory":false,"type":"Boolean","defaultValue":true},"onMessage":{"mandatory":false,"type":"String","defaultValue":""},"messageAdapter":{"mandatory":false,"type":"String","defaultValue":""}},"mandatory":"true"}},"Triggers":{"Trigger":{"attributes":{"name":{"mandatory":true,"type":"String","minSize":1,"defaultValue":""}},"collections":{"Mappings":{}},"mandatory":"true"}},"Mappings":{"Mapping":{"attributes":{"action":{"mandatory":true,"type":"String","minSize":1,"defaultValue":""},"to":{"mandatory":true,"type":"String","minSize":1,"defaultValue":""}},"mandatory":"false"}},"Dispatchers":{"Dispatcher":{"attributes":{"id":{"mandatory":false,"type":"String","minSize":1,"defaultValue":""},"event":{"mandatory":false,"type":"String","minSize":15,"defaultValue":""},"callback":{"mandatory":false,"type":"String","minSize":100,"defaultValue":""},"message":{"mandatory":false,"type":"String","minSize":500,"defaultValue":""},"target":{"mandatory":false,"type":"String","minSize":100,"defaultValue":""},"timeout":{"mandatory":false,"type":"Number","minSize":10,"defaultValue":""},"interval":{"mandatory":false,"type":"Number","minSize":10,"defaultValue":""},"limit":{"mandatory":false,"type":"Number","minSize":100,"defaultValue":""},"messageSource":{"mandatory":false,"type":"String"},"messageAttribute":{"mandatory":false,"type":"String"}},"mandatory":"false"}},"JQueryEffects":{"JQueryEffect":{"attributes":{"id":{"mandatory":true,"type":"String","maxOccurs":50,"minSize":1,"defaultValue":""},"name":{"mandatory":true,"type":"String","defaultValue":""},"properties":{"mandatory":false,"type":"String","defaultValue":""},"options":{"mandatory":false,"type":"String","defaultValue":""},"revert":{"mandatory":false,"type":"Boolean","defaultValue":"true"},"target":{"mandatory":true,"type":"String","defaultValue":""}},"mandatory":"false"}},"Elements":{"Element":{"attributes":{"property":{"mandatory":true,"type":"String","minSize":1,"defaultValue":""},"target":{"mandatory":false,"type":"String","defaultValue":""}},"mandatory":"false"}},"DataItems":{"DataItem":{"attributes":{"name":{"mandatory":false,"type":"String","maxOccurs":50,"minSize":1,"defaultValue":""},"value":{"mandatory":false,"type":"Number","defaultValue":""}}},"mandatory":"false"},"Slots":{"Slot":{"attributes":{"year":{"mandatory":false,"type":"String","minSize":1,"defaultValue":""},"month":{"mandatory":false,"type":"String","minSize":1,"defaultValue":""},"day":{"mandatory":false,"type":"String","minSize":1,"defaultValue":""},"weekday":{"mandatory":false,"type":"String","minSize":1,"possibleValues":["Mon","Tue","Wed","Thu","Fri","Sat","Sun"],"defaultValue":""},"value":{"mandatory":false,"type":"String","minSize":1,"defaultValue":""}},"mandatory":"false"}},"Buttons":{"Button":{"attributes":{"item":{"mandatory":false,"type":"String","minSize":1,"defaultValue":""},"text":{"mandatory":true,"type":"String","minSize":1,"defaultValue":""},"position":{"mandatory":false,"type":"Number","defaultValue":""}},"mandatory":"false"}},"MediaUrls":{"MediaUrl":{"attributes":{"file":{"mandatory":false,"type":"String","minSize":1,"defaultValue":""},"ext":{"mandatory":true,"type":"String","minSize":1,"defaultValue":""}},"mandatory":"false"}},"Markers":{"Marker":{"attributes":{"id":{"mandatory":true,"type":"String","minSize":1,"defaultValue":""},"latitude":{"mandatory":true,"type":"Number","minSize":1,"defaultValue":""},"longitude":{"mandatory":true,"type":"Number","minSize":1,"defaultValue":""},"imagesource":{"mandatory":false,"type":"String","minSize":1,"defaultValue":""},"width":{"mandatory":false,"type":"Number","minSize":1,"defaultValue":""},"height":{"mandatory":false,"type":"Number","minSize":1,"defaultValue":""},"offsetimagex":{"mandatory":false,"type":"Number","minSize":1,"defaultValue":0},"offsetimagey":{"mandatory":false,"type":"Number","minSize":1,"defaultValue":0},"shaodowimagesource":{"mandatory":false,"type":"Number","minSize":1,"defaultValue":""},"shadowwidth":{"mandatory":false,"type":"Number","minSize":1,"defaultValue":""},"shadowheight":{"mandatory":false,"type":"Number","minSize":1,"defaultValue":""},"offsetshadowimagex":{"mandatory":false,"type":"Number","minSize":1,"defaultValue":0},"offsetshadowimagey":{"mandatory":false,"type":"Number","minSize":1,"defaultValue":0},"moveable":{"mandatory":false,"type":"Boolean","minSize":1,"defaultValue":"false"},"infowindow":{"mandatory":false,"type":"String","minSize":1,"defaultValue":""}},"latitude":{"mandatory":true,"type":"Number","minSize":1,"defaultValue":""},"longitude":{"mandatory":true,"type":"Number","minSize":1,"defaultValue":""},"imagesource":{"mandatory":false,"type":"String","minSize":1,"defaultValue":""},"width":{"mandatory":false,"type":"Number","minSize":1,"defaultValue":""},"height":{"mandatory":false,"type":"Number","minSize":1,"defaultValue":""},"offsetimagex":{"mandatory":false,"type":"Number","minSize":1,"defaultValue":0},"offsetimagey":{"mandatory":false,"type":"Number","minSize":1,"defaultValue":0},"shadowimagesource":{"mandatory":false,"type":"Number","minSize":1,"defaultValue":""},"shadowwidth":{"mandatory":false,"type":"Number","minSize":1,"defaultValue":""},"shadowheight":{"mandatory":false,"type":"Number","minSize":1,"defaultValue":""},"offsetshadowimagex":{"mandatory":false,"type":"Number","minSize":1,"defaultValue":0},"offsetshadowimagey":{"mandatory":false,"type":"Number","minSize":1,"defaultValue":0},"moveable":{"mandatory":false,"type":"Boolean","minSize":1,"defaultValue":"false"},"infowindow":{"mandatory":false,"type":"String","minSize":1,"defaultValue":""}}}},"Children":{"Template":{"attributes":{"content":{"mandatory":true,"type":"String","maxOccurs":1,"maxSize":10000,"defaultValue":""}},"mandatory":"true"}}}';
		public static $tagsArray;
		public static $collectionsArray;
		public static $childrenArray;
		public static function createTagsArray(){
			$jsonObject = json_decode(self::$jsonConfigString);
			self::$tagsArray = array();
			self::$collectionsArray = array();
			self::$childrenArray = array();
			foreach($jsonObject->Tags as $tagName => $tagObject){
				self::$tagsArray[strtolower($tagName)] = $tagObject;
				if(isset(self::$tagsArray[strtolower($tagName)]->extends)){
					self::$tagsArray[strtolower($tagName)]->extends = strtolower(self::$tagsArray[strtolower($tagName)]->extends);
				}
			}
			foreach($jsonObject->Collections as $collectionName => $collectionObject){
				foreach($collectionObject as $name => $object){
					$attrName = strtolower($name);
					self::$collectionsArray[$attrName] = $collectionObject->$name;
				}
			}
			foreach($jsonObject->Children as $childName => $childObject){
				self::$childrenArray[strtolower($childName)] = $childObject;
			}					
		}
		public static function createTag($tagName){
			$tagSettings = self::getTagSettings($tagName);				
			if(isset($tagSettings->abstract) && $tagSettings->abstract){
				throw new Exception($tagName . " is an abstract tag, cannot be instantiated");
			}
			$className = 'xRTML' . $tagName;			
			if(!class_exists($className)){
				eval('class '. $className .' extends xRTMLBaseTag { public function __construct($tagSettings){parent::__construct($tagSettings); } }');
			}
			return new $className($tagSettings);
		}
		public static function getTagSettings($tagName){
			$name = strtolower($tagName);
			if(array_key_exists($name, self::$tagsArray)){				
				$tagSettings = self::$tagsArray[$name];				
				if(isset($tagSettings->extends)){
					$parentSettings = self::getTagSettings($tagSettings->extends);
					if(isset($parentSettings->attributes)){
						if(!isset($tagSettings->attributes)){
							$tagSettings->attributes = new stdClass();
						}
						foreach($parentSettings->attributes as $attributeName => $attributeValue){
							$tagSettings->attributes->$attributeName = $attributeValue;
						}
					}
					if(isset($parentSettings->collections)){
						if(!isset($tagSettings->collections)){
							$tagSettings->collections = new stdClass();
						}
						foreach($parentSettings->collections as $collectionName => $collectionValue){
							$tagSettings->collections->$collectionName = $collectionValue;
						}
					}
					if(isset($parentSettings->children)){
						if(!isset($tagSettings->children)){
							$tagSettings->children = new stdClass();
						}
						foreach($parentSettings->children as $childName => $childValue){
							$tagSettings->children->$childName = $childValue;
						}
					}
					if(isset($parentSettings->events)){
						if(!isset($tagSettings->events)){
							$tagSettings->events = new stdClass();
						}
						foreach($parentSettings->events as $eventName => $eventValue){
							$tagSettings->events->$eventName = $eventValue;
						}
					}
				}
				return $tagSettings;
			}
			if(array_key_exists($name, self::$collectionsArray)){
				return self::$collectionsArray[$name];
			}
			if(array_key_exists($name, self::$childrenArray)){
				return self::$childrenArray[$name];
			}
			return false;
		}		
		public static function extendArray($arrayName, $tag){
			switch (strtolower($arrayName)){
				case 'tag':
					array_push(self::$tagsArray, $tag);
					break;
				case 'collection':
					array_push(self::$collectionsArray, $tag);
					break;
				case 'children':
					$array = array_push(self::$childrenArray, $tag);
					break;
				default:
					throw new Exception('$arrayName must be "tag", "collection" or "children".', 1);
				    break;				
			}
		}
	}
	class xRTMLArray implements IteratorAggregate{
		public $className;
		public $array;
		public static $identifiers = array('id', 'name', 'key', 'action');
		public function __construct($className){
			$this->className = $className;
			$this->array = array();
		}
		public function add($name = null){
			if($this->className == 'tag'){
				throw new Exception('you have to use $xrtml->addTag() to add a Tag.');
			}			
			$object = xRTMLTagFactory::createTag($this->className);
			if(isset($name)){
				$tagSettings = xRTMLTagFactory::getTagSettings($this->className);
				foreach(self::$identifiers as $identifier){					
					if(isset($tagSettings->attributes->$identifier)){
						$object->$identifier = $name;
						break;
					}
				}				
			}
			array_push($this->array, $object);
			return $object;
		}
		public function remove($item){
			if(is_object($item)){
				foreach($this->array as $arrayItem){
					if($item === $arrayItem){						
						return sizeof(array_splice($this->array, array_search($item, $this->array, true), 1)) > 0;
					}
				}
			} else{
				$cont = 0;
				foreach($this->array as $arrayItem){
					foreach(self::$identifiers as $identifier){
						if(isset($arrayItem->$identifier) && $arrayItem->$identifier == $item){
							array_splice($this->array, $cont, 1);
							return true;
						}
					}
					$cont++;
				}
				return false;
			}			
		}
		public function find($itemName){
			foreach($this->array as $obj){
				foreach(self::$identifiers as $identifier){
					if(isset($arrayItem->$identifier) && $arrayItem->$identifier == $item){						
						return $obj;
					}
				}
			}
			return false;
		}
		public function getIterator(){
			return new ArrayIterator($this->array);
		}
	}
	$xrtml = new xRTML();
	